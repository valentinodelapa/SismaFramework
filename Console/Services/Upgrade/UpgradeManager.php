<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SismaFramework\Console\Services\Upgrade;

use SismaFramework\Console\Exceptions\UpgradeException;
use SismaFramework\Console\Exceptions\VersionMismatchException;
use SismaFramework\Console\Services\Upgrade\DTO\UpgradeReport;
use SismaFramework\Console\Services\Upgrade\Strategies\Upgrade10to11Strategy;
use SismaFramework\Console\Services\Upgrade\Strategies\UpgradeStrategyInterface;
use SismaFramework\Console\Services\Upgrade\Utils\BackupManager;
use SismaFramework\Console\Services\Upgrade\Utils\FileScanner;
use SismaFramework\Console\Services\Upgrade\Utils\VersionDetector;
use SismaFramework\Core\HelperClasses\Config;

/**
 * Main orchestrator for module upgrades
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class UpgradeManager
{

    private Config $config;
    private bool $dryRun = false;
    private bool $skipCritical = false;
    private bool $skipBackup = false;

    public function __construct(?Config $config = null,
            private VersionDetector $versionDetector = new VersionDetector(),
            private FileScanner $fileScanner = new FileScanner(),
            private BackupManager $backupManager = new BackupManager())
    {
        $this->config = $config ?? Config::getInstance();
    }
    
    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }
    
    public function setSkipCritical(bool $skipCritical): self
    {
        $this->skipCritical = $skipCritical;
        return $this;
    }
    
    public function setSkipBackup(bool $skipBackup): self
    {
        $this->skipBackup = $skipBackup;
        return $this;
    }
    
    public function upgrade(string $moduleName, string $targetVersion, ?string $sourceVersion = null): UpgradeReport
    {
        $modulePath = $this->getModulePath($moduleName);
        if ($sourceVersion === null) {
            $sourceVersion = $this->versionDetector->detectVersion($modulePath);
        }
        if (!$this->versionDetector->isValidVersion($targetVersion)) {
            throw new VersionMismatchException("Invalid target version: {$targetVersion}");
        }
        $strategy = $this->selectStrategy($sourceVersion, $targetVersion);
        $backupPath = null;
        if (!$this->dryRun && !$this->skipBackup) {
            $backupPath = $this->backupManager->createBackup($modulePath);
        }
        try {
            $files = $this->fileScanner->scanModuleFiles($modulePath, !$this->skipCritical);
            $fileResults = [];
            $filesModified = 0;
            $filesSkipped = 0;
            $warningsCount = 0;
            $manualActions = [];
            foreach ($files as $filePath) {
                $category = $this->fileScanner->categorizeFile($filePath);
                if (!$this->fileScanner->shouldProcessFile($category, $this->skipCritical)) {
                    $filesSkipped++;
                    continue;
                }
                $content = file_get_contents($filePath);
                $transformedContent = $content;
                $fileChangesCount = 0;
                $fileWarnings = [];
                $fileTransformations = [];
                foreach ($strategy->getTransformers() as $transformer) {
                    if (!$transformer->canTransform($filePath, $transformedContent)) {
                        continue;
                    }
                    $result = $transformer->transform($transformedContent, $filePath);
                    if ($result->changesCount > 0) {
                        $transformedContent = $result->transformedContent;
                        $fileChangesCount += $result->changesCount;
                        $fileTransformations[] = $transformer->getDescription();
                    }
                    if (!empty($result->warnings)) {
                        $fileWarnings = array_merge($fileWarnings, $result->warnings);
                    }
                    if ($result->requiresManualReview) {
                        $manualActions[] = "Review {$this->getRelativePath($filePath)}: {$transformer->getDescription()}";
                    }
                }
                if ($fileChangesCount > 0) {
                    if (!$this->dryRun) {
                        file_put_contents($filePath, $transformedContent);
                    }
                    $filesModified++;
                    $warningsCount += count($fileWarnings);
                    $fileResults[] = (object) [
                                'filePath' => $filePath,
                                'changesCount' => $fileChangesCount,
                                'confidence' => $this->calculateAverageConfidence($strategy->getTransformers()),
                                'warnings' => $fileWarnings,
                                'transformations' => $fileTransformations
                    ];
                } else {
                    $filesSkipped++;
                }
            }
            if (!$this->dryRun) {
                $this->versionDetector->updateVersion($modulePath, $targetVersion);
            }
            if ($strategy->requiresManualIntervention()) {
                foreach ($strategy->getBreakingChanges() as $breakingChange) {
                    $manualActions[] = $breakingChange;
                }
            }
            $status = $this->dryRun ? 'DRY-RUN' : 'SUCCESS';
            return new UpgradeReport(
                    moduleName: $moduleName,
                    fromVersion: $sourceVersion,
                    toVersion: $targetVersion,
                    status: $status,
                    filesModified: $filesModified,
                    filesSkipped: $filesSkipped,
                    warningsCount: $warningsCount,
                    fileResults: $fileResults,
                    manualActions: array_unique($manualActions),
                    backupPath: $backupPath
            );
        } catch (\Exception $e) {
            if ($backupPath && !$this->dryRun) {
                $this->backupManager->rollback($modulePath, $backupPath);
            }
            throw new UpgradeException("Upgrade failed: " . $e->getMessage(), 0, $e);
        }
    }
    
    private function getModulePath(string $moduleName): string
    {
        $systemPath = $this->config->systemPath;
        $modulePath = $systemPath . DIRECTORY_SEPARATOR . $moduleName;
        if (!is_dir($modulePath)) {
            throw new UpgradeException("Module not found: {$moduleName} at {$modulePath}");
        }
        return $modulePath;
    }
    
    private function selectStrategy(string $sourceVersion, string $targetVersion): UpgradeStrategyInterface
    {
        $strategies = [
            new Upgrade10to11Strategy()
        ];
        foreach ($strategies as $strategy) {
            $sourceMajor = (int) explode('.', $sourceVersion)[0];
            $targetMajor = (int) explode('.', $targetVersion)[0];
            $strategySourceMajor = (int) explode('.', $strategy->getSourceVersion())[0];
            $strategyTargetMajor = (int) explode('.', $strategy->getTargetVersion())[0];
            if ($sourceMajor === $strategySourceMajor && $targetMajor === $strategyTargetMajor) {
                return $strategy;
            }
        }
        throw new VersionMismatchException(
                        "No upgrade strategy found for {$sourceVersion} → {$targetVersion}"
                );
    }
    
    private function calculateAverageConfidence(array $transformers): int
    {
        if (empty($transformers)) {
            return 0;
        }
        $total = 0;
        foreach ($transformers as $transformer) {
            $total += $transformer->getConfidence();
        }
        return (int) round($total / count($transformers));
    }
    
    private function getRelativePath(string $filePath): string
    {
        $normalized = str_replace('\\', '/', $filePath);
        $parts = explode('/', $normalized);
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            if (in_array($parts[$i], ['Application', 'Public', 'Config'])) {
                return implode('/', array_slice($parts, $i));
            }
        }
        return basename($filePath);
    }
}

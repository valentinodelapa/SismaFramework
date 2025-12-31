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

namespace SismaFramework\Console\Services\Upgrade\Utils;

use SismaFramework\Console\Services\Upgrade\DTO\UpgradeReport;

/**
 * Utility class for generating upgrade reports
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ReportGenerator
{
    /**
     * Generate a formatted report from UpgradeReport data
     *
     * @param UpgradeReport $report Report data
     * @param bool $quiet Minimal output mode
     * @return string Formatted report
     */
    public function generate(UpgradeReport $report, bool $quiet = false): string
    {
        if ($quiet) {
            return $this->generateQuietReport($report);
        }
        return $this->generateDetailedReport($report);
    }

    /**
     * Generate a minimal report
     *
     * @param UpgradeReport $report Report data
     * @return string Formatted report
     */
    private function generateQuietReport(UpgradeReport $report): string
    {
        $statusSymbol = $report->status === 'SUCCESS' ? '✓' : ($report->status === 'DRY-RUN' ? '◯' : '✗');
        return <<<REPORT

{$statusSymbol} {$report->status}: {$report->moduleName} {$report->fromVersion} → {$report->toVersion}
Files modified: {$report->filesModified}, Warnings: {$report->warningsCount}

REPORT;
    }

    /**
     * Generate a detailed report
     *
     * @param UpgradeReport $report Report data
     * @return string Formatted report
     */
    private function generateDetailedReport(UpgradeReport $report): string
    {
        $isDryRun = $report->status === 'DRY-RUN';
        $title = $isDryRun ? 'UPGRADE REPORT (DRY-RUN)' : 'UPGRADE REPORT';
        $output = <<<HEADER

╔══════════════════════════════════════════════════════════════╗
║            SISMA FRAMEWORK - {$title}                  ║
╚══════════════════════════════════════════════════════════════╝

Module: {$report->moduleName}
Version: {$report->fromVersion} → {$report->toVersion}
Status: {$report->status}


HEADER;
        if ($isDryRun) {
            $output .= "FILES TO MODIFY: {$report->filesModified}\n";
            $output .= "FILES TO SKIP: {$report->filesSkipped}\n";
        } else {
            $output .= "FILES MODIFIED: {$report->filesModified}\n";
            $output .= "FILES SKIPPED: {$report->filesSkipped}\n";
        }
        $output .= "WARNINGS: {$report->warningsCount}\n";
        if ($report->backupPath) {
            $output .= "BACKUP CREATED: " . basename($report->backupPath) . "\n";
        }
        if (!empty($report->fileResults)) {
            $output .= "\n";
            foreach ($report->fileResults as $fileResult) {
                $output .= $this->formatFileResult($fileResult);
            }
        }
        if (!empty($report->manualActions)) {
            $output .= "\n🔧 MANUAL ACTIONS REQUIRED:\n";
            foreach ($report->manualActions as $action) {
                $output .= "   - {$action}\n";
            }
        }
        if ($isDryRun) {
            $output .= "\n" . str_repeat('─', 62) . "\n";
            $output .= "This was a dry-run. No files were modified.\n";
            $output .= "Run without --dry-run to apply changes.\n";
        } elseif ($report->status === 'SUCCESS') {
            $output .= "\n" . str_repeat('─', 62) . "\n";
            $output .= "✓ Upgrade completed successfully!\n\n";
            $output .= "Next steps:\n";
            $output .= "1. Review manual actions listed above (if any)\n";
            $output .= "2. Run your test suite\n";
            $output .= "3. Check for runtime errors\n";
            if ($report->backupPath) {
                $output .= "\nBackup location: {$report->backupPath}\n";
            }
        }
        return $output;
    }

    /**
     * Format a file result for display
     *
     * @param object $fileResult File result object
     * @return string Formatted file result
     */
    private function formatFileResult(object $fileResult): string
    {
        $relativePath = $this->getRelativePath($fileResult->filePath);
        $confidenceSymbol = $fileResult->confidence >= 80 ? '✓' : ($fileResult->confidence >= 65 ? '◯' : '⚠');
        $output = "\n{$confidenceSymbol} {$relativePath}\n";
        $output .= "   Changes: {$fileResult->changesCount}\n";
        $output .= "   Confidence: {$fileResult->confidence}%\n";
        if (!empty($fileResult->transformations)) {
            $output .= "   Transformations:\n";
            foreach ($fileResult->transformations as $transformation) {
                $output .= "     • {$transformation}\n";
            }
        }
        if (!empty($fileResult->warnings)) {
            $output .= "   ⚠ Warnings:\n";
            foreach ($fileResult->warnings as $warning) {
                $output .= "      - {$warning}\n";
            }
        }
        return $output;
    }

    /**
     * Get relative path from absolute path
     *
     * @param string $filePath Absolute file path
     * @return string Relative path
     */
    private function getRelativePath(string $filePath): string
    {
        $normalized = str_replace('\\', '/', $filePath);
        $parts = explode('/', $normalized);
        $relevantParts = [];
        $foundModuleOrPublic = false;
        foreach ($parts as $part) {
            if ($foundModuleOrPublic) {
                $relevantParts[] = $part;
            } elseif ($part === 'Application' || $part === 'Public' || $part === 'Config') {
                $foundModuleOrPublic = true;
                $relevantParts[] = $part;
            }
        }
        return !empty($relevantParts) ? implode('/', $relevantParts) : basename($filePath);
    }
}

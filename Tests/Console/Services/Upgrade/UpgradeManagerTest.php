<?php

namespace SismaFramework\Tests\Console\Services\Upgrade;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Exceptions\UpgradeException;
use SismaFramework\Console\Exceptions\VersionMismatchException;
use SismaFramework\Console\Services\Upgrade\DTO\UpgradeReport;
use SismaFramework\Console\Services\Upgrade\UpgradeManager;
use SismaFramework\Console\Services\Upgrade\Utils\BackupManager;
use SismaFramework\Console\Services\Upgrade\Utils\FileScanner;
use SismaFramework\Console\Services\Upgrade\Utils\VersionDetector;
use SismaFramework\Core\HelperClasses\Config;

class UpgradeManagerTest extends TestCase
{
    private VersionDetector $versionDetectorStub;
    private FileScanner $fileScannerStub;
    private BackupManager $backupManagerStub;
    private UpgradeManager $manager;
    private string $tempDir;

    #[\Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'upgrade_manager_test_' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir($this->tempDir, 0777, true);

        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['systemPath', $this->tempDir],
                ]);

        $this->versionDetectorStub = $this->createStub(VersionDetector::class);
        $this->fileScannerStub = $this->createStub(FileScanner::class);
        $this->backupManagerStub = $this->createStub(BackupManager::class);

        $this->manager = new UpgradeManager(
            $configStub,
            $this->versionDetectorStub,
            $this->fileScannerStub,
            $this->backupManagerStub
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $modulePath = $this->tempDir . 'TestUpgradeModule';
        if (is_dir($modulePath)) {
            $this->removeDirectory($modulePath);
        }
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testSetDryRunReturnsSelf(): void
    {
        $result = $this->manager->setDryRun(true);

        $this->assertInstanceOf(UpgradeManager::class, $result);
    }

    public function testSetSkipCriticalReturnsSelf(): void
    {
        $result = $this->manager->setSkipCritical(true);

        $this->assertInstanceOf(UpgradeManager::class, $result);
    }

    public function testSetSkipBackupReturnsSelf(): void
    {
        $result = $this->manager->setSkipBackup(true);

        $this->assertInstanceOf(UpgradeManager::class, $result);
    }

    public function testUpgradeThrowsExceptionForModuleNotFound(): void
    {
        $this->expectException(UpgradeException::class);
        $this->expectExceptionMessage('Module not found');

        $this->manager->upgrade('NonExistentModuleXyz', '11.0.0', '10.1.7');
    }

    public function testUpgradeThrowsExceptionForInvalidTargetVersion(): void
    {
        mkdir($this->tempDir . 'TestUpgradeModule', 0755, true);

        $this->versionDetectorStub->method('detectVersion')->willReturn('10.1.7');
        $this->versionDetectorStub->method('isValidVersion')->willReturn(false);

        $this->expectException(VersionMismatchException::class);
        $this->expectExceptionMessage('Invalid target version');

        $this->manager->upgrade('TestUpgradeModule', 'invalid', '10.1.7');
    }

    public function testUpgradeDryRunReturnsReportWithDryRunStatus(): void
    {
        mkdir($this->tempDir . 'TestUpgradeModule', 0755, true);

        $this->versionDetectorStub->method('detectVersion')->willReturn('10.1.7');
        $this->versionDetectorStub->method('isValidVersion')->willReturn(true);
        $this->fileScannerStub->method('scanModuleFiles')->willReturn([]);

        $this->manager->setDryRun(true);

        $report = $this->manager->upgrade('TestUpgradeModule', '11.0.0', '10.1.7');

        $this->assertEquals('DRY-RUN', $report->status);
        $this->assertEquals('TestUpgradeModule', $report->moduleName);
        $this->assertEquals('10.1.7', $report->fromVersion);
        $this->assertEquals('11.0.0', $report->toVersion);
    }

    public function testUpgradeThrowsExceptionForNoStrategy(): void
    {
        mkdir($this->tempDir . 'TestUpgradeModule', 0755, true);

        $this->versionDetectorStub->method('detectVersion')->willReturn('8.0.0');
        $this->versionDetectorStub->method('isValidVersion')->willReturn(true);

        $this->expectException(UpgradeException::class);

        $this->manager->upgrade('TestUpgradeModule', '11.0.0', '8.0.0');
    }

    public function testUpgradeProcessesFilesWithTransformers(): void
    {
        $modulePath = $this->tempDir . 'TestUpgradeModule';
        mkdir($modulePath, 0755, true);

        $filePath = $modulePath . DIRECTORY_SEPARATOR . 'Application' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'SampleController.php';
        mkdir(dirname($filePath), 0755, true);
        file_put_contents($filePath, '<?php ErrorHandler::disableErrorDisplay();');

        $this->versionDetectorStub->method('detectVersion')->willReturn('10.1.7');
        $this->versionDetectorStub->method('isValidVersion')->willReturn(true);
        $this->fileScannerStub->method('scanModuleFiles')->willReturn([$filePath]);
        $this->fileScannerStub->method('categorizeFile')->willReturn('controller');
        $this->fileScannerStub->method('shouldProcessFile')->willReturn(true);

        $this->manager->setDryRun(true)->setSkipBackup(true);

        $report = $this->manager->upgrade('TestUpgradeModule', '11.0.0', '10.1.7');

        $this->assertInstanceOf(UpgradeReport::class, $report);
    }

    public function testUpgradeSkipsFilesBasedOnCategory(): void
    {
        $modulePath = $this->tempDir . 'TestUpgradeModule';
        mkdir($modulePath, 0755, true);

        $filePath = $modulePath . DIRECTORY_SEPARATOR . 'Application' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'Helper.php';
        mkdir(dirname($filePath), 0755, true);
        file_put_contents($filePath, '<?php // helper');

        $this->versionDetectorStub->method('detectVersion')->willReturn('10.1.7');
        $this->versionDetectorStub->method('isValidVersion')->willReturn(true);
        $this->fileScannerStub->method('scanModuleFiles')->willReturn([$filePath]);
        $this->fileScannerStub->method('categorizeFile')->willReturn('other');
        $this->fileScannerStub->method('shouldProcessFile')->willReturn(false);

        $this->manager->setDryRun(true)->setSkipBackup(true);

        $report = $this->manager->upgrade('TestUpgradeModule', '11.0.0', '10.1.7');

        $this->assertEquals(0, $report->filesModified);
        $this->assertEquals(1, $report->filesSkipped);
    }

    public function testUpgradeAutoDetectsSourceVersion(): void
    {
        mkdir($this->tempDir . 'TestUpgradeModule', 0755, true);

        $this->versionDetectorStub->method('detectVersion')->willReturn('10.1.7');
        $this->versionDetectorStub->method('isValidVersion')->willReturn(true);
        $this->fileScannerStub->method('scanModuleFiles')->willReturn([]);

        $this->manager->setDryRun(true);

        $report = $this->manager->upgrade('TestUpgradeModule', '11.0.0');

        $this->assertEquals('10.1.7', $report->fromVersion);
    }

    public function testUpgradeSuccessUpdatesVersion(): void
    {
        mkdir($this->tempDir . 'TestUpgradeModule', 0755, true);

        $this->versionDetectorStub->method('detectVersion')->willReturn('10.1.7');
        $this->versionDetectorStub->method('isValidVersion')->willReturn(true);
        $this->fileScannerStub->method('scanModuleFiles')->willReturn([]);

        $this->manager->setSkipBackup(true);

        $report = $this->manager->upgrade('TestUpgradeModule', '11.0.0', '10.1.7');

        $this->assertEquals('SUCCESS', $report->status);
    }

    public function testUpgradeIncludesManualActionsForBreakingChanges(): void
    {
        mkdir($this->tempDir . 'TestUpgradeModule', 0755, true);

        $this->versionDetectorStub->method('detectVersion')->willReturn('10.1.7');
        $this->versionDetectorStub->method('isValidVersion')->willReturn(true);
        $this->fileScannerStub->method('scanModuleFiles')->willReturn([]);

        $this->manager->setDryRun(true);

        $report = $this->manager->upgrade('TestUpgradeModule', '11.0.0', '10.1.7');

        $this->assertNotEmpty($report->manualActions);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Utils;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\DTO\UpgradeReport;
use SismaFramework\Console\Services\Upgrade\Utils\ReportGenerator;

class ReportGeneratorTest extends TestCase
{
    private ReportGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ReportGenerator();
    }

    public function testGenerateQuietReportSuccess(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 5,
            filesSkipped: 2,
            warningsCount: 1
        );

        $output = $this->generator->generate($report, true);

        $this->assertStringContainsString('SUCCESS', $output);
        $this->assertStringContainsString('Blog', $output);
        $this->assertStringContainsString('10.1.7', $output);
        $this->assertStringContainsString('11.0.0', $output);
        $this->assertStringContainsString('Files modified: 5', $output);
        $this->assertStringContainsString('Warnings: 1', $output);
    }

    public function testGenerateQuietReportDryRun(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'DRY-RUN',
            filesModified: 3,
            filesSkipped: 0,
            warningsCount: 0
        );

        $output = $this->generator->generate($report, true);

        $this->assertStringContainsString('DRY-RUN', $output);
    }

    public function testGenerateQuietReportFailed(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'FAILED',
            filesModified: 0,
            filesSkipped: 0,
            warningsCount: 0
        );

        $output = $this->generator->generate($report, true);

        $this->assertStringContainsString('FAILED', $output);
    }

    public function testGenerateDetailedReportContainsHeader(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 2,
            filesSkipped: 1,
            warningsCount: 0
        );

        $output = $this->generator->generate($report, false);

        $this->assertStringContainsString('SISMA FRAMEWORK', $output);
        $this->assertStringContainsString('UPGRADE REPORT', $output);
        $this->assertStringContainsString('Module: Blog', $output);
        $this->assertStringContainsString('10.1.7', $output);
        $this->assertStringContainsString('11.0.0', $output);
        $this->assertStringContainsString('FILES MODIFIED: 2', $output);
        $this->assertStringContainsString('FILES SKIPPED: 1', $output);
    }

    public function testGenerateDetailedReportDryRunShowsPreviewLabels(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'DRY-RUN',
            filesModified: 3,
            filesSkipped: 0,
            warningsCount: 0
        );

        $output = $this->generator->generate($report, false);

        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('FILES TO MODIFY: 3', $output);
        $this->assertStringContainsString('No files were modified', $output);
    }

    public function testGenerateDetailedReportWithFileResults(): void
    {
        $fileResult = (object) [
            'filePath' => '/path/to/Application/Controllers/SampleController.php',
            'changesCount' => 3,
            'confidence' => 85,
            'warnings' => [],
            'transformations' => ['Converts static calls to instance calls']
        ];

        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 1,
            filesSkipped: 0,
            warningsCount: 0,
            fileResults: [$fileResult]
        );

        $output = $this->generator->generate($report, false);

        $this->assertStringContainsString('Application/Controllers/SampleController.php', $output);
        $this->assertStringContainsString('Changes: 3', $output);
        $this->assertStringContainsString('Confidence: 85%', $output);
        $this->assertStringContainsString('Converts static calls to instance calls', $output);
    }

    public function testGenerateDetailedReportWithWarnings(): void
    {
        $fileResult = (object) [
            'filePath' => '/path/to/Application/Controllers/SampleController.php',
            'changesCount' => 1,
            'confidence' => 60,
            'warnings' => ['Static calls require manual review'],
            'transformations' => []
        ];

        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 1,
            filesSkipped: 0,
            warningsCount: 1,
            fileResults: [$fileResult]
        );

        $output = $this->generator->generate($report, false);

        $this->assertStringContainsString('Static calls require manual review', $output);
    }

    public function testGenerateDetailedReportWithManualActions(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 1,
            filesSkipped: 0,
            warningsCount: 0,
            manualActions: ['Review index.php changes']
        );

        $output = $this->generator->generate($report, false);

        $this->assertStringContainsString('MANUAL ACTIONS REQUIRED', $output);
        $this->assertStringContainsString('Review index.php changes', $output);
    }

    public function testGenerateDetailedReportWithBackupPath(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 1,
            filesSkipped: 0,
            warningsCount: 0,
            backupPath: '/path/to/Blog_backup_20260207120000.zip'
        );

        $output = $this->generator->generate($report, false);

        $this->assertStringContainsString('BACKUP CREATED', $output);
        $this->assertStringContainsString('Blog_backup_20260207120000.zip', $output);
    }

    public function testGenerateDetailedReportSuccessShowsNextSteps(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 1,
            filesSkipped: 0,
            warningsCount: 0
        );

        $output = $this->generator->generate($report, false);

        $this->assertStringContainsString('Upgrade completed successfully', $output);
        $this->assertStringContainsString('Next steps', $output);
        $this->assertStringContainsString('Run your test suite', $output);
    }
}

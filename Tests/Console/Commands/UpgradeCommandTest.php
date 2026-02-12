<?php

namespace SismaFramework\Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Commands\UpgradeCommand;
use SismaFramework\Console\Exceptions\UpgradeException;
use SismaFramework\Console\Services\Upgrade\DTO\UpgradeReport;
use SismaFramework\Console\Services\Upgrade\UpgradeManager;
use SismaFramework\Console\Services\Upgrade\Utils\ReportGenerator;

class UpgradeCommandTest extends TestCase
{
    private UpgradeCommand $command;
    private UpgradeManager $upgradeManagerStub;
    private ReportGenerator $reportGeneratorStub;

    protected function setUp(): void
    {
        $this->upgradeManagerStub = $this->createStub(UpgradeManager::class);
        $this->reportGeneratorStub = $this->createStub(ReportGenerator::class);
        $this->command = new UpgradeCommand($this->upgradeManagerStub, $this->reportGeneratorStub);
    }

    public function testCheckCompatibility(): void
    {
        $this->assertTrue($this->command->checkCompatibility('upgrade'));
        $this->assertFalse($this->command->checkCompatibility('install'));
        $this->assertFalse($this->command->checkCompatibility('other'));
    }

    public function testConfigureShowsHelpMessage(): void
    {
        ob_start();
        $this->command->run();
        $output = ob_get_clean();

        $expectedStrings = [
            'Usage: php SismaFramework/Console/sisma upgrade',
            'Arguments:',
            'module',
            'Options:',
            '--to=VERSION',
            '--from=VERSION',
            '--dry-run',
            '--skip-critical',
            '--skip-backup',
            '--quiet',
            'Examples:',
        ];

        foreach ($expectedStrings as $string) {
            $this->assertStringContainsString($string, $output);
        }
    }

    public function testExecuteWithMissingModuleName(): void
    {
        $this->command->setArguments([]);
        $this->command->setOptions(['to' => '11.0.0']);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Module name is required', $output);
    }

    public function testExecuteWithMissingTargetVersion(): void
    {
        $this->command->setArguments(['0' => 'Blog']);
        $this->command->setOptions([]);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Target version is required', $output);
    }

    public function testSuccessfulUpgrade(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 3,
            filesSkipped: 1,
            warningsCount: 0
        );

        $this->upgradeManagerStub->method('setDryRun')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipCritical')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipBackup')->willReturnSelf();
        $this->upgradeManagerStub->method('upgrade')->willReturn($report);
        $this->reportGeneratorStub->method('generate')->willReturn('Report output');

        $this->command->setArguments(['0' => 'Blog']);
        $this->command->setOptions(['to' => '11.0.0']);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('Report output', $output);
    }

    public function testDryRunShowsPreviewMessage(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'DRY-RUN',
            filesModified: 3,
            filesSkipped: 1,
            warningsCount: 0
        );

        $this->upgradeManagerStub->method('setDryRun')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipCritical')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipBackup')->willReturnSelf();
        $this->upgradeManagerStub->method('upgrade')->willReturn($report);
        $this->reportGeneratorStub->method('generate')->willReturn('Report');

        $this->command->setArguments(['0' => 'Blog']);
        $this->command->setOptions(['to' => '11.0.0', 'dry-run' => '']);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('DRY-RUN MODE', $output);
        $this->assertStringContainsString('No files will be modified', $output);
        $this->assertStringContainsString('without --dry-run', $output);
    }

    public function testDryRunQuietDoesNotShowPreviewMessage(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'DRY-RUN',
            filesModified: 0,
            filesSkipped: 0,
            warningsCount: 0
        );

        $this->upgradeManagerStub->method('setDryRun')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipCritical')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipBackup')->willReturnSelf();
        $this->upgradeManagerStub->method('upgrade')->willReturn($report);
        $this->reportGeneratorStub->method('generate')->willReturn('Quiet report');

        $this->command->setArguments(['0' => 'Blog']);
        $this->command->setOptions(['to' => '11.0.0', 'dry-run' => '', 'quiet' => '']);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringNotContainsString('DRY-RUN MODE', $output);
    }

    public function testUpgradeFailureShowsErrorMessage(): void
    {
        $this->upgradeManagerStub->method('setDryRun')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipCritical')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipBackup')->willReturnSelf();
        $this->upgradeManagerStub->method('upgrade')->willThrowException(
            new UpgradeException('Module not found')
        );

        $this->command->setArguments(['0' => 'Blog']);
        $this->command->setOptions(['to' => '11.0.0']);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('UPGRADE FAILED', $output);
        $this->assertStringContainsString('Module not found', $output);
    }

    public function testUpgradeWithFromVersionOption(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.0.0',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 0,
            filesSkipped: 0,
            warningsCount: 0
        );

        $this->upgradeManagerStub->method('setDryRun')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipCritical')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipBackup')->willReturnSelf();
        $this->upgradeManagerStub->method('upgrade')
            ->with('Blog', '11.0.0', '10.0.0')
            ->willReturn($report);
        $this->reportGeneratorStub->method('generate')->willReturn('Report');

        $this->command->setArguments(['0' => 'Blog']);
        $this->command->setOptions(['to' => '11.0.0', 'from' => '10.0.0']);

        ob_start();
        $result = $this->command->run();
        ob_get_clean();

        $this->assertTrue($result);
    }

    public function testUpgradeFailureWithPreviousException(): void
    {
        $previous = new \RuntimeException('Detailed cause');
        $exception = new UpgradeException('Upgrade failed', 0, $previous);

        $this->upgradeManagerStub->method('setDryRun')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipCritical')->willReturnSelf();
        $this->upgradeManagerStub->method('setSkipBackup')->willReturnSelf();
        $this->upgradeManagerStub->method('upgrade')->willThrowException($exception);

        $this->command->setArguments(['0' => 'Blog']);
        $this->command->setOptions(['to' => '11.0.0']);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Caused by: Detailed cause', $output);
    }
}

<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\DTO;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\DTO\UpgradeReport;

class UpgradeReportTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $fileResults = [(object) ['filePath' => '/path/to/file.php', 'changesCount' => 3]];
        $manualActions = ['Review file.php'];

        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 5,
            filesSkipped: 2,
            warningsCount: 3,
            fileResults: $fileResults,
            manualActions: $manualActions,
            backupPath: '/path/to/backup.zip'
        );

        $this->assertEquals('Blog', $report->moduleName);
        $this->assertEquals('10.1.7', $report->fromVersion);
        $this->assertEquals('11.0.0', $report->toVersion);
        $this->assertEquals('SUCCESS', $report->status);
        $this->assertEquals(5, $report->filesModified);
        $this->assertEquals(2, $report->filesSkipped);
        $this->assertEquals(3, $report->warningsCount);
        $this->assertEquals($fileResults, $report->fileResults);
        $this->assertEquals($manualActions, $report->manualActions);
        $this->assertEquals('/path/to/backup.zip', $report->backupPath);
    }

    public function testConstructorWithDefaultValues(): void
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

        $this->assertEquals([], $report->fileResults);
        $this->assertEquals([], $report->manualActions);
        $this->assertNull($report->backupPath);
    }

    public function testPropertiesAreReadonly(): void
    {
        $report = new UpgradeReport(
            moduleName: 'Blog',
            fromVersion: '10.1.7',
            toVersion: '11.0.0',
            status: 'SUCCESS',
            filesModified: 0,
            filesSkipped: 0,
            warningsCount: 0
        );

        $reflection = new \ReflectionClass($report);

        $this->assertTrue($reflection->getProperty('moduleName')->isReadOnly());
        $this->assertTrue($reflection->getProperty('fromVersion')->isReadOnly());
        $this->assertTrue($reflection->getProperty('toVersion')->isReadOnly());
        $this->assertTrue($reflection->getProperty('status')->isReadOnly());
        $this->assertTrue($reflection->getProperty('filesModified')->isReadOnly());
        $this->assertTrue($reflection->getProperty('filesSkipped')->isReadOnly());
        $this->assertTrue($reflection->getProperty('warningsCount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('fileResults')->isReadOnly());
        $this->assertTrue($reflection->getProperty('manualActions')->isReadOnly());
        $this->assertTrue($reflection->getProperty('backupPath')->isReadOnly());
    }
}

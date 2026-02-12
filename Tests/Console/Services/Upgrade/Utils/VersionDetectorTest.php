<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Utils;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Exceptions\VersionMismatchException;
use SismaFramework\Console\Services\Upgrade\Utils\VersionDetector;

class VersionDetectorTest extends TestCase
{
    private VersionDetector $detector;
    private string $testDir;

    protected function setUp(): void
    {
        $this->detector = new VersionDetector();
        $this->testDir = sys_get_temp_dir() . '/sisma_version_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }
    }

    public function testDetectVersionWithFrameworkVersion(): void
    {
        $moduleJson = [
            'name' => 'Blog',
            'version' => '1.0.0',
            'framework_version' => '10.1.7'
        ];
        file_put_contents($this->testDir . '/module.json', json_encode($moduleJson));

        $version = $this->detector->detectVersion($this->testDir);

        $this->assertEquals('10.1.7', $version);
    }

    public function testDetectVersionFallsBackToVersion(): void
    {
        $moduleJson = [
            'name' => 'Blog',
            'version' => '10.1.7'
        ];
        file_put_contents($this->testDir . '/module.json', json_encode($moduleJson));

        $version = $this->detector->detectVersion($this->testDir);

        $this->assertEquals('10.1.7', $version);
    }

    public function testDetectVersionThrowsExceptionWhenFileNotFound(): void
    {
        $this->expectException(VersionMismatchException::class);
        $this->expectExceptionMessage('module.json not found');

        $this->detector->detectVersion($this->testDir);
    }

    public function testDetectVersionThrowsExceptionOnInvalidJson(): void
    {
        file_put_contents($this->testDir . '/module.json', 'invalid json content');

        $this->expectException(VersionMismatchException::class);
        $this->expectExceptionMessage('Invalid JSON in module.json');

        $this->detector->detectVersion($this->testDir);
    }

    public function testDetectVersionThrowsExceptionWhenNoVersionField(): void
    {
        $moduleJson = ['name' => 'Blog'];
        file_put_contents($this->testDir . '/module.json', json_encode($moduleJson));

        $this->expectException(VersionMismatchException::class);
        $this->expectExceptionMessage("No 'framework_version' or 'version' field found");

        $this->detector->detectVersion($this->testDir);
    }

    public function testUpdateVersion(): void
    {
        $moduleJson = [
            'name' => 'Blog',
            'framework_version' => '10.1.7'
        ];
        file_put_contents($this->testDir . '/module.json', json_encode($moduleJson));

        $this->detector->updateVersion($this->testDir, '11.0.0');

        $content = json_decode(file_get_contents($this->testDir . '/module.json'), true);
        $this->assertEquals('11.0.0', $content['framework_version']);
    }

    public function testUpdateVersionThrowsExceptionWhenFileNotFound(): void
    {
        $this->expectException(VersionMismatchException::class);
        $this->expectExceptionMessage('module.json not found');

        $this->detector->updateVersion($this->testDir, '11.0.0');
    }

    public function testUpdateVersionThrowsExceptionOnInvalidJson(): void
    {
        file_put_contents($this->testDir . '/module.json', 'invalid json');

        $this->expectException(VersionMismatchException::class);
        $this->expectExceptionMessage('Invalid JSON in module.json');

        $this->detector->updateVersion($this->testDir, '11.0.0');
    }

    public function testIsValidVersionWithValidVersions(): void
    {
        $this->assertTrue($this->detector->isValidVersion('10.1.7'));
        $this->assertTrue($this->detector->isValidVersion('11.0.0'));
        $this->assertTrue($this->detector->isValidVersion('0.0.1'));
    }

    public function testIsValidVersionWithInvalidVersions(): void
    {
        $this->assertFalse($this->detector->isValidVersion('10.1'));
        $this->assertFalse($this->detector->isValidVersion('10'));
        $this->assertFalse($this->detector->isValidVersion('abc'));
        $this->assertFalse($this->detector->isValidVersion('10.1.7.1'));
        $this->assertFalse($this->detector->isValidVersion(''));
    }

    public function testCompareVersions(): void
    {
        $this->assertEquals(-1, $this->detector->compareVersions('10.1.7', '11.0.0'));
        $this->assertEquals(0, $this->detector->compareVersions('11.0.0', '11.0.0'));
        $this->assertEquals(1, $this->detector->compareVersions('11.0.0', '10.1.7'));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

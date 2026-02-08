<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Utils;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Utils\FileScanner;

class FileScannerTest extends TestCase
{
    private FileScanner $scanner;
    private string $testDir;

    protected function setUp(): void
    {
        $this->scanner = new FileScanner();
        $this->testDir = sys_get_temp_dir() . '/sisma_scanner_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }
    }

    public function testScanModuleFilesFindsApplicationFiles(): void
    {
        $this->createModuleStructure();

        $files = $this->scanner->scanModuleFiles($this->testDir);

        $fileNames = array_map('basename', $files);
        $this->assertContains('SampleController.php', $fileNames);
        $this->assertContains('SampleForm.php', $fileNames);
        $this->assertContains('SampleEntity.php', $fileNames);
    }

    public function testScanModuleFilesIncludesCriticalFiles(): void
    {
        $this->createModuleStructure();

        $files = $this->scanner->scanModuleFiles($this->testDir, true);

        $fileNames = array_map('basename', $files);
        $this->assertContains('index.php', $fileNames);
        $this->assertContains('configFramework.php', $fileNames);
    }

    public function testScanModuleFilesExcludesCriticalFiles(): void
    {
        $this->createModuleStructure();

        $files = $this->scanner->scanModuleFiles($this->testDir, false);

        $fileNames = array_map('basename', $files);
        $this->assertNotContains('index.php', $fileNames);
        $this->assertNotContains('configFramework.php', $fileNames);
    }

    public function testScanModuleFilesReturnsEmptyArrayForEmptyModule(): void
    {
        $emptyDir = $this->testDir . '/EmptyModule';
        mkdir($emptyDir, 0755, true);

        $files = $this->scanner->scanModuleFiles($emptyDir, false);

        $this->assertEquals([], $files);
    }

    public function testCategorizeFileForForm(): void
    {
        $this->assertEquals('form', $this->scanner->categorizeFile('/path/to/Application/Forms/SampleForm.php'));
    }

    public function testCategorizeFileForController(): void
    {
        $this->assertEquals('controller', $this->scanner->categorizeFile('/path/to/Application/Controllers/SampleController.php'));
    }

    public function testCategorizeFileForModel(): void
    {
        $this->assertEquals('model', $this->scanner->categorizeFile('/path/to/Application/Models/SampleModel.php'));
    }

    public function testCategorizeFileForEntity(): void
    {
        $this->assertEquals('entity', $this->scanner->categorizeFile('/path/to/Application/Entities/SampleEntity.php'));
    }

    public function testCategorizeFileForIndexPhp(): void
    {
        $this->assertEquals('critical', $this->scanner->categorizeFile('/path/to/Public/index.php'));
    }

    public function testCategorizeFileForConfig(): void
    {
        $this->assertEquals('critical', $this->scanner->categorizeFile('/path/to/Config/configFramework.php'));
    }

    public function testCategorizeFileForOther(): void
    {
        $this->assertEquals('other', $this->scanner->categorizeFile('/path/to/Application/Services/SampleService.php'));
    }

    public function testCategorizeFileWithBackslashPaths(): void
    {
        $this->assertEquals('form', $this->scanner->categorizeFile('C:\\path\\to\\Application\\Forms\\SampleForm.php'));
    }

    public function testShouldProcessFileForForm(): void
    {
        $this->assertTrue($this->scanner->shouldProcessFile('form'));
    }

    public function testShouldProcessFileForController(): void
    {
        $this->assertTrue($this->scanner->shouldProcessFile('controller'));
    }

    public function testShouldProcessFileForModel(): void
    {
        $this->assertTrue($this->scanner->shouldProcessFile('model'));
    }

    public function testShouldProcessFileForEntity(): void
    {
        $this->assertTrue($this->scanner->shouldProcessFile('entity'));
    }

    public function testShouldProcessFileForCritical(): void
    {
        $this->assertTrue($this->scanner->shouldProcessFile('critical'));
    }

    public function testShouldProcessFileSkipsCriticalWhenFlagSet(): void
    {
        $this->assertFalse($this->scanner->shouldProcessFile('critical', true));
    }

    public function testShouldProcessFileReturnsFalseForOther(): void
    {
        $this->assertFalse($this->scanner->shouldProcessFile('other'));
    }

    private function createModuleStructure(): void
    {
        $dirs = [
            $this->testDir . '/Application/Controllers',
            $this->testDir . '/Application/Forms',
            $this->testDir . '/Application/Entities',
            $this->testDir . '/Application/Models',
            $this->testDir . '/Config',
            dirname($this->testDir) . '/Public',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        file_put_contents($this->testDir . '/Application/Controllers/SampleController.php', '<?php // controller');
        file_put_contents($this->testDir . '/Application/Forms/SampleForm.php', '<?php // form');
        file_put_contents($this->testDir . '/Application/Entities/SampleEntity.php', '<?php // entity');
        file_put_contents($this->testDir . '/Config/configFramework.php', '<?php // config');
        file_put_contents(dirname($this->testDir) . '/Public/index.php', '<?php // index');
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

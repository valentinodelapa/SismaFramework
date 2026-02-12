<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Utils;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Exceptions\BackupFailedException;
use SismaFramework\Console\Services\Upgrade\Utils\BackupManager;

class BackupManagerTest extends TestCase
{
    private BackupManager $manager;
    private string $testDir;

    protected function setUp(): void
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('ZIP extension is not available');
        }
        $this->manager = new BackupManager();
        $this->testDir = sys_get_temp_dir() . '/sisma_backup_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }

        $parentDir = dirname($this->testDir);
        $pattern = $parentDir . '/' . basename($this->testDir) . '_backup_*.zip';
        foreach (glob($pattern) as $file) {
            unlink($file);
        }
    }

    public function testCreateBackupCreatesZipFile(): void
    {
        $this->createTestFiles();

        $backupPath = $this->manager->createBackup($this->testDir);

        $this->assertFileExists($backupPath);
        $this->assertStringEndsWith('.zip', $backupPath);

        unlink($backupPath);
    }

    public function testCreateBackupContainsModuleFiles(): void
    {
        $this->createTestFiles();

        $backupPath = $this->manager->createBackup($this->testDir);

        $zip = new \ZipArchive();
        $zip->open($backupPath);

        $fileNames = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileNames[] = str_replace('\\', '/', $zip->getNameIndex($i));
        }
        $zip->close();

        $this->assertContains('file1.php', $fileNames);
        $this->assertContains('subdir/file2.php', $fileNames);

        unlink($backupPath);
    }

    public function testCreateBackupThrowsExceptionForNonExistentPath(): void
    {
        $this->expectException(BackupFailedException::class);
        $this->expectExceptionMessage('Module path does not exist');

        $this->manager->createBackup($this->testDir . '/nonexistent');
    }

    public function testRollbackRemovesDirectoryAndExtractsBackup(): void
    {
        $this->createTestFiles();

        $backupPath = $this->manager->createBackup($this->testDir);

        $this->assertFileExists($backupPath);

        $this->manager->rollback($this->testDir, $backupPath);

        $parentDir = dirname($this->testDir);
        $this->assertFileExists($parentDir . '/file1.php');

        unlink($backupPath);
        @unlink($parentDir . '/file1.php');
        @unlink($parentDir . '/subdir/file2.php');
        @rmdir($parentDir . '/subdir');
    }

    public function testRollbackThrowsExceptionForNonExistentBackup(): void
    {
        $this->expectException(BackupFailedException::class);
        $this->expectExceptionMessage('Backup file does not exist');

        $this->manager->rollback($this->testDir, '/nonexistent/backup.zip');
    }

    private function createTestFiles(): void
    {
        file_put_contents($this->testDir . '/file1.php', '<?php // file1');
        mkdir($this->testDir . '/subdir', 0755, true);
        file_put_contents($this->testDir . '/subdir/file2.php', '<?php // file2');
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

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

use SismaFramework\Console\Exceptions\BackupFailedException;

/**
 * Utility class for creating and managing backups
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class BackupManager
{
    /**
     * Create a backup of the module
     *
     * @param string $modulePath Path to the module directory
     * @return string Path to the backup file
     * @throws BackupFailedException If backup creation fails
     */
    public function createBackup(string $modulePath): string
    {
        if (!is_dir($modulePath)) {
            throw new BackupFailedException("Module path does not exist: {$modulePath}");
        }
        $timestamp = date('YmdHis');
        $moduleName = basename($modulePath);
        $backupPath = dirname($modulePath) . DIRECTORY_SEPARATOR . "{$moduleName}_backup_{$timestamp}.zip";
        try {
            $this->createZip($modulePath, $backupPath);
            if ($this->isGitRepo($modulePath)) {
                $this->createGitCommit($modulePath, "Pre-upgrade backup - {$timestamp}");
            }
            return $backupPath;
        } catch (\Exception $e) {
            throw new BackupFailedException("Failed to create backup: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Restore from a backup
     *
     * @param string $modulePath Path to the module directory
     * @param string $backupPath Path to the backup file
     * @return void
     * @throws BackupFailedException If restore fails
     */
    public function rollback(string $modulePath, string $backupPath): void
    {
        if (!file_exists($backupPath)) {
            throw new BackupFailedException("Backup file does not exist: {$backupPath}");
        }
        try {
            if (is_dir($modulePath)) {
                $this->removeDirectory($modulePath);
            }
            $this->extractZip($backupPath, dirname($modulePath));
        } catch (\Exception $e) {
            throw new BackupFailedException("Failed to restore backup: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a ZIP archive of a directory
     *
     * @param string $source Source directory
     * @param string $destination Destination ZIP file
     * @return void
     * @throws \Exception If ZIP creation fails
     */
    private function createZip(string $source, string $destination): void
    {
        if (!extension_loaded('zip')) {
            throw new \Exception("ZIP extension is not available");
        }
        $zip = new \ZipArchive();
        if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Cannot create ZIP file: {$destination}");
        }
        $source = realpath($source);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            $file = realpath($file);
            if (is_dir($file)) {
                $zip->addEmptyDir(str_replace($source . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
            } elseif (is_file($file)) {
                $zip->addFile($file, str_replace($source . DIRECTORY_SEPARATOR, '', $file));
            }
        }
        $zip->close();
    }

    /**
     * Extract a ZIP archive
     *
     * @param string $zipPath Path to ZIP file
     * @param string $destination Destination directory
     * @return void
     * @throws \Exception If extraction fails
     */
    private function extractZip(string $zipPath, string $destination): void
    {
        if (!extension_loaded('zip')) {
            throw new \Exception("ZIP extension is not available");
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception("Cannot open ZIP file: {$zipPath}");
        }
        $zip->extractTo($destination);
        $zip->close();
    }

    /**
     * Check if a directory is a git repository
     *
     * @param string $directory Directory to check
     * @return bool True if is a git repository
     */
    private function isGitRepo(string $directory): bool
    {
        while ($directory !== dirname($directory)) {
            if (is_dir($directory . DIRECTORY_SEPARATOR . '.git')) {
                return true;
            }
            $directory = dirname($directory);
        }
        return false;
    }

    /**
     * Create a git commit
     *
     * @param string $directory Directory containing the git repository
     * @param string $message Commit message
     * @return void
     */
    private function createGitCommit(string $directory, string $message): void
    {
        $currentDir = getcwd();
        chdir($directory);
        exec('git add -A 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            exec('git commit -m ' . escapeshellarg($message) . ' 2>&1', $output, $returnCode);
        }
        chdir($currentDir);
    }

    /**
     * Remove a directory recursively
     *
     * @param string $directory Directory to remove
     * @return void
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($directory);
    }
}

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

/**
 * Utility class for scanning module files
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class FileScanner
{
    /**
     * Scan all PHP files in a module
     *
     * @param string $modulePath Path to the module directory
     * @param bool $includeCritical Include critical files (index.php, config files)
     * @return array Array of file paths
     */
    public function scanModuleFiles(string $modulePath, bool $includeCritical = true): array
    {
        $files = [];
        $applicationPath = $modulePath . DIRECTORY_SEPARATOR . 'Application';
        if (is_dir($applicationPath)) {
            $files = array_merge($files, $this->scanDirectory($applicationPath, '*.php'));
        }
        if ($includeCritical) {
            $publicIndexPath = dirname($modulePath) . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'index.php';
            if (file_exists($publicIndexPath)) {
                $files[] = $publicIndexPath;
            }
            $configPath = $modulePath . DIRECTORY_SEPARATOR . 'Config';
            if (is_dir($configPath)) {
                $files = array_merge($files, $this->scanDirectory($configPath, '*.php'));
            }
        }
        return $files;
    }

    /**
     * Scan directory recursively for files matching pattern
     *
     * @param string $directory Directory to scan
     * @param string $pattern File pattern (e.g., '*.php')
     * @return array Array of file paths
     */
    private function scanDirectory(string $directory, string $pattern): array
    {
        $files = [];
        if (!is_dir($directory)) {
            return $files;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }
        return $files;
    }

    /**
     * Categorize a file based on its path
     *
     * @param string $filePath File path
     * @return string Category (form, controller, model, entity, critical, other)
     */
    public function categorizeFile(string $filePath): string
    {
        $normalizedPath = str_replace('\\', '/', $filePath);
        if (str_contains($normalizedPath, '/Forms/')) {
            return 'form';
        }
        if (str_contains($normalizedPath, '/Controllers/')) {
            return 'controller';
        }
        if (str_contains($normalizedPath, '/Models/')) {
            return 'model';
        }
        if (str_contains($normalizedPath, '/Entities/')) {
            return 'entity';
        }
        if (basename($filePath) === 'index.php') {
            return 'critical';
        }
        if (str_contains($normalizedPath, '/Config/')) {
            return 'critical';
        }
        return 'other';
    }

    /**
     * Check if a file should be processed based on its category
     *
     * @param string $category File category
     * @param bool $skipCritical Skip critical files
     * @return bool True if should process
     */
    public function shouldProcessFile(string $category, bool $skipCritical = false): bool
    {
        if ($skipCritical && $category === 'critical') {
            return false;
        }
        return in_array($category, ['form', 'controller', 'model', 'entity', 'critical']);
    }
}

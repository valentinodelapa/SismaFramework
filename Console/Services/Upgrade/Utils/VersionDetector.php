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

use SismaFramework\Console\Exceptions\VersionMismatchException;

/**
 * Utility class for detecting and managing module versions
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class VersionDetector
{
    /**
     * Detect the framework version of a module from its module.json file
     *
     * @param string $modulePath Path to the module directory
     * @return string Framework version
     * @throws VersionMismatchException If module.json not found or invalid
     */
    public function detectVersion(string $modulePath): string
    {
        $moduleJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        if (!file_exists($moduleJsonPath)) {
            throw new VersionMismatchException(
                "module.json not found in {$modulePath}. Please create it with version information."
            );
        }
        $content = file_get_contents($moduleJsonPath);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new VersionMismatchException(
                "Invalid JSON in module.json: " . json_last_error_msg()
            );
        }
        return $data['framework_version'] ?? $data['version'] ?? throw new VersionMismatchException(
            "No 'framework_version' or 'version' field found in module.json"
        );
    }

    /**
     * Update the framework version in module.json
     *
     * @param string $modulePath Path to the module directory
     * @param string $newVersion New framework version
     * @return void
     * @throws VersionMismatchException If module.json not found or invalid
     */
    public function updateVersion(string $modulePath, string $newVersion): void
    {
        $moduleJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        if (!file_exists($moduleJsonPath)) {
            throw new VersionMismatchException(
                "module.json not found in {$modulePath}"
            );
        }
        $content = file_get_contents($moduleJsonPath);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new VersionMismatchException(
                "Invalid JSON in module.json: " . json_last_error_msg()
            );
        }
        $data['framework_version'] = $newVersion;
        $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($moduleJsonPath, $newContent);
    }

    /**
     * Validate if a version string is valid (semantic versioning)
     *
     * @param string $version Version string to validate
     * @return bool True if valid
     */
    public function isValidVersion(string $version): bool
    {
        return (bool) preg_match('/^\d+\.\d+\.\d+$/', $version);
    }

    /**
     * Compare two versions
     *
     * @param string $version1 First version
     * @param string $version2 Second version
     * @return int -1 if version1 < version2, 0 if equal, 1 if version1 > version2
     */
    public function compareVersions(string $version1, string $version2): int
    {
        return version_compare($version1, $version2);
    }
}

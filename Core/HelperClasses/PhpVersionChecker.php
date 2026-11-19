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

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Core\Exceptions\PhpVersionException;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class PhpVersionChecker
{

    public static function checkPhpVersion(?string $customComposerJsonPath = null): void
    {
        $minimumPhpVersion = self::getMinimumPhpVersion($customComposerJsonPath);
        $currentPhpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        if (($minimumPhpVersion !== null) && version_compare($currentPhpVersion, $minimumPhpVersion, '<')) {
            throw new PhpVersionException($minimumPhpVersion);
        }
    }

    public static function getMinimumPhpVersion(?string $customComposerJsonPath = null): ?string
    {
        $composerJsonPath = $customComposerJsonPath ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'composer.json';
        if (is_file($composerJsonPath) === false) {
            return null;
        }
        $composerData = json_decode(file_get_contents($composerJsonPath), true);
        $phpRequirement = $composerData['require']['php'] ?? '';
        if (preg_match('/(\d+)\.(\d+)(?:\.(\d+))?/', $phpRequirement, $matches) === 1) {
            return $matches[1] . '.' . $matches[2] . '.' . ($matches[3] ?? '0');
        }
        return null;
    }
}

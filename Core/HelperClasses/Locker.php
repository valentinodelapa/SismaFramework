<?php

/*
 * The MIT License
 *
 * Copyright 2024 Valentino de Lapa.
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

use SismaFramework\Core\Exceptions\FilesystemPermissionException;

/**
 * Description of Locker
 *
 * @author Valentino de Lapa
 */
class Locker
{

    public static function lockFolder(string $dirname): void
    {
        $file = fopen($dirname . DIRECTORY_SEPARATOR . '.lock', 'w');
        if ($file) {
            fclose($file);
        } else {
            throw new FilesystemPermissionException();
        }
    }

    public static function unlockFolder(string $dirname): void
    {
        if (file_exists($dirname . DIRECTORY_SEPARATOR . '.lock')) {
            $result = unlink($dirname . DIRECTORY_SEPARATOR . '.lock');
            if ($result === false) {
                throw new FilesystemPermissionException();
            }
        } else {
            throw new FilesystemPermissionException();
        }
    }

    public static function folderIsLocked(string $dirname): bool
    {
        return file_exists($dirname . DIRECTORY_SEPARATOR . '.lock');
    }

    public static function lockFile(string $filename): void
    {
        $file = fopen($filename . '.lock', 'w');
        if ($file) {
            fclose($file);
        } else {
            throw new FilesystemPermissionException();
        }
    }

    public static function unlockFile(string $filename): void
    {
        if (file_exists($filename . '.lock')) {
            $result = unlink($filename . '.lock');
            if ($result === false) {
                throw new FilesystemPermissionException();
            }
        } else {
            throw new FilesystemPermissionException();
        }
    }

    public static function fileIsLocked(string $filename): bool
    {
        return file_exists($filename . '.lock');
    }
}

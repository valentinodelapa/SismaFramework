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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Locker;

/**
 * Description of LockerTest
 *
 * @author Valentino de Lapa
 */
class LockerTest extends TestCase
{

    public function testLockFolder()
    {
        $testDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('lock_', true) . DIRECTORY_SEPARATOR;
        mkdir($testDirectoryPath);
        $locker = new Locker();
        $this->assertFalse($locker->folderIsLocked($testDirectoryPath));
        $locker->lockFolder($testDirectoryPath);
        $this->assertTrue($locker->folderIsLocked($testDirectoryPath));
        $locker->unlockFolder($testDirectoryPath);
        $this->assertFalse($locker->folderIsLocked($testDirectoryPath));
        rmdir($testDirectoryPath);
    }

    public function testLockFile()
    {
        $testDirectoryPath = \Config\SYSTEM_PATH . \Config\APPLICATION_PATH . 'TestDirectory';
        mkdir($testDirectoryPath);
        $testFilePath = $testDirectoryPath . DIRECTORY_SEPARATOR . 'testFile.txt';
        $file = fopen($testFilePath, 'w');
        fclose($file);
        $locker = new Locker();
        $this->assertFalse($locker->fileIsLocked($testFilePath));
        $locker->lockFile($testFilePath);
        $this->assertTrue($locker->fileIsLocked($testFilePath));
        $locker->unlockFile($testFilePath);
        $this->assertFalse($locker->fileIsLocked($testFilePath));
        unlink($testFilePath);
        rmdir($testDirectoryPath);
    }
}

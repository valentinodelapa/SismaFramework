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

    public function testLockerClass()
    {
        $testDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('lock_', true) . DIRECTORY_SEPARATOR;
        mkdir($testDirectoryPath);
        $this->assertFalse(Locker::folderIsLocked($testDirectoryPath));
        Locker::lockFolder($testDirectoryPath);
        $this->assertTrue(Locker::folderIsLocked($testDirectoryPath));
        Locker::unlockFolder($testDirectoryPath);
        $this->assertFalse(Locker::folderIsLocked($testDirectoryPath));
        rmdir($testDirectoryPath);
    }
}

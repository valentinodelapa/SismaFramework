<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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
use SismaFramework\Core\HelperClasses\Logger;

/**
 * @author Valentino de Lapa
 */
class LoggerTest extends TestCase
{
    public function testSaveLogAndGetLog()
    {
        Logger::clearLog();
        Logger::saveLog('sample message', 1, 'filePath', 0);
        $log = Logger::getLog();
        $this->assertStringContainsString("1\tsample message\tfilePath(0)\n", $log);
    }
    
    public function testTruncateLog()
    {
        Logger::setMaxRows(2);
        Logger::clearLog();
        $this->assertEquals(0, count(Logger::getLogRowByRow()));
        Logger::saveLog('sample message', 1, 'filePath', 0);
        $this->assertEquals(1, count(Logger::getLogRowByRow()));
        Logger::saveLog('sample message', 1, 'filePath', 0);
        $this->assertEquals(2, count(Logger::getLogRowByRow()));
        Logger::saveLog('sample message', 1, 'filePath', 0);
        $this->assertEquals(2, count(Logger::getLogRowByRow()));
    }
    
    public function testSaveTrace()
    {
        Logger::clearLog();
        Logger::saveTrace(debug_backtrace());
        $log = Logger::getLog();
        $this->assertStringContainsString('SismaFramework\Tests\Core\HelperClasses\LoggerTest->testSaveTrace', $log);
    }
    
    public function testAssertClearLog()
    {
        $this->assertNotEquals(0, count(Logger::getLogRowByRow()));
        Logger::clearLog();
        $this->assertEquals(0, count(Logger::getLogRowByRow()));
    }
    
    public function testGetLogRowByRow()
    {
        Logger::clearLog();
        Logger::saveLog('sample message', 1, 'filePath', 0);
        Logger::saveLog('sample message', 1, 'filePath', 0);
        $log = Logger::getLogRowByRow();
        $this->assertIsArray($log);
        $this->assertCount(2, $log);
    }
}

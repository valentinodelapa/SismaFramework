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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\SismaLogger;
use SismaFramework\Core\HelperClasses\SismaLogReader;
use SismaFramework\Core\HelperClasses\Locker;

/**
 * @author Valentino de Lapa
 */
class LoggerTest extends TestCase
{

    private Config $configStubOne;
    private Config $configStubTwo;
    private Locker $lockerMock;
    private SismaLogger $loggerOne;
    private SismaLogger $loggerTwo;
    private SismaLogReader $logReaderOne;
    private SismaLogReader $logReaderTwo;

    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->configStubOne = $this->createStub(Config::class);
        $this->configStubOne->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 100],
                    ['logVerboseActive', true],
        ]);
        $this->configStubTwo = $this->createStub(Config::class);
        $this->configStubTwo->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
        ]);
        $this->lockerMock = $this->createStub(Locker::class);
        $this->loggerOne = new SismaLogger($this->lockerMock, $this->configStubOne);
        $this->loggerTwo = new SismaLogger($this->lockerMock, $this->configStubTwo);
        $this->logReaderOne = new SismaLogReader($this->lockerMock, $this->configStubOne);
        $this->logReaderTwo = new SismaLogReader($this->lockerMock, $this->configStubTwo);
    }

    public function testSaveLogAndGetLog()
    {
        $this->loggerOne->error('sample message', ['code' => 1, 'file' => 'filePath', 'line' => 0]);
        $log = $this->logReaderOne->getLog();
        $this->assertStringContainsString("1\tsample message\tfilePath(0)\n", $log);
    }

    public function testTruncateLog()
    {
        $this->assertEquals(0, count($this->logReaderTwo->getLogRowByRow()));
        $this->loggerTwo->error('sample message', ['code' => 1, 'file' => 'filePath', 'line' => 0]);
        $this->assertEquals(1, count($this->logReaderTwo->getLogRowByRow()));
        $this->loggerTwo->error('sample message', ['code' => 1, 'file' => 'filePath', 'line' => 0]);
        $this->assertEquals(2, count($this->logReaderTwo->getLogRowByRow()));
        $this->loggerTwo->error('sample message', ['code' => 1, 'file' => 'filePath', 'line' => 0]);
        $this->assertEquals(2, count($this->logReaderTwo->getLogRowByRow()));
    }

    public function testSaveTrace()
    {
        $this->loggerOne->debug('trace', ['trace' => debug_backtrace()]);
        $log = $this->logReaderOne->getLog();
        $this->assertStringContainsString('SismaFramework\Tests\Core\HelperClasses\LoggerTest->testSaveTrace', $log);
    }

    public function testAssertClearLog()
    {
        $this->loggerOne->error('sample message', ['code' => 1, 'file' => 'filePath', 'line' => 0]);
        $this->assertNotEquals(0, count($this->logReaderOne->getLogRowByRow()));
        $this->logReaderOne->clearLog();
        $this->assertEquals(0, count($this->logReaderOne->getLogRowByRow()));
    }

    public function testGetLogRowByRow()
    {
        $this->loggerOne->error('sample message', ['code' => 1, 'file' => 'filePath', 'line' => 0]);
        $this->loggerOne->error('sample message', ['code' => 1, 'file' => 'filePath', 'line' => 0]);
        $log = $this->logReaderOne->getLogRowByRow();
        $this->assertIsArray($log);
        $this->assertCount(2, $log);
    }
}

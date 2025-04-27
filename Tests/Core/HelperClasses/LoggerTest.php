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
use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\HelperClasses\Logger;
use SismaFramework\Core\HelperClasses\Locker;

/**
 * @author Valentino de Lapa
 */
class LoggerTest extends TestCase
{

    private BaseConfig $configMockOne;
    private BaseConfig $configMockTwo;
    private Locker $lockerMock;

    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->configMockOne = $this->createMock(BaseConfig::class);
        $this->configMockOne->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 100],
                    ['logVerboseActive', true],
        ]);
        $this->configMockTwo = $this->createMock(BaseConfig::class);
        $this->configMockTwo->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
        ]);
        $this->lockerMock = $this->createMock(Locker::class);
    }

    public function testSaveLogAndGetLog()
    {
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->lockerMock, $this->configMockOne);
        $log = Logger::getLog($this->lockerMock, $this->configMockOne);
        $this->assertStringContainsString("1\tsample message\tfilePath(0)\n", $log);
    }

    public function testTruncateLog()
    {
        $this->assertEquals(0, count(Logger::getLogRowByRow($this->lockerMock, $this->configMockTwo)));
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->lockerMock, $this->configMockTwo);
        $this->assertEquals(1, count(Logger::getLogRowByRow($this->lockerMock, $this->configMockTwo)));
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->lockerMock, $this->configMockTwo);
        $this->assertEquals(2, count(Logger::getLogRowByRow($this->lockerMock, $this->configMockTwo)));
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->lockerMock, $this->configMockTwo);
        $this->assertEquals(2, count(Logger::getLogRowByRow($this->lockerMock, $this->configMockTwo)));
    }

    public function testSaveTrace()
    {
        Logger::saveTrace(debug_backtrace(), $this->lockerMock, $this->configMockOne);
        $log = Logger::getLog($this->lockerMock, $this->configMockOne);
        $this->assertStringContainsString('SismaFramework\Tests\Core\HelperClasses\LoggerTest->testSaveTrace', $log);
    }

    public function testAssertClearLog()
    {
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->lockerMock, $this->configMockOne);
        $this->assertNotEquals(0, count(Logger::getLogRowByRow($this->lockerMock, $this->configMockOne)));
        Logger::clearLog($this->lockerMock, $this->configMockOne);
        $this->assertEquals(0, count(Logger::getLogRowByRow($this->lockerMock, $this->configMockOne)));
    }

    public function testGetLogRowByRow()
    {
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->lockerMock, $this->configMockOne);
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->lockerMock, $this->configMockOne);
        $log = Logger::getLogRowByRow($this->lockerMock, $this->configMockOne);
        $this->assertIsArray($log);
        $this->assertCount(2, $log);
    }
}

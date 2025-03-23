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

/**
 * @author Valentino de Lapa
 */
class LoggerTest extends TestCase
{

    private LoggerConfigTestOne $configTestOne;
    private LoggerConfigTestTwo $configTestTwo;

    public function setUp(): void
    {
        $this->configTestOne = new LoggerConfigTestOne();
        $this->configTestTwo = new LoggerConfigTestTwo();
    }

    public function testSaveLogAndGetLog()
    {
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->configTestOne);
        $log = Logger::getLog($this->configTestOne);
        $this->assertStringContainsString("1\tsample message\tfilePath(0)\n", $log);
    }

    public function testTruncateLog()
    {
        $this->assertEquals(0, count(Logger::getLogRowByRow($this->configTestTwo)));
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->configTestTwo);
        $this->assertEquals(1, count(Logger::getLogRowByRow($this->configTestTwo)));
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->configTestTwo);
        $this->assertEquals(2, count(Logger::getLogRowByRow($this->configTestTwo)));
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->configTestTwo);
        $this->assertEquals(2, count(Logger::getLogRowByRow($this->configTestTwo)));
    }

    public function testSaveTrace()
    {
        Logger::saveTrace(debug_backtrace(), $this->configTestOne);
        $log = Logger::getLog($this->configTestOne);
        $this->assertStringContainsString('SismaFramework\Tests\Core\HelperClasses\LoggerTest->testSaveTrace', $log);
    }

    public function testAssertClearLog()
    {
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->configTestOne);
        $this->assertNotEquals(0, count(Logger::getLogRowByRow($this->configTestOne)));
        Logger::clearLog($this->configTestOne);
        $this->assertEquals(0, count(Logger::getLogRowByRow($this->configTestOne)));
    }

    public function testGetLogRowByRow()
    {
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->configTestOne);
        Logger::saveLog('sample message', 1, 'filePath', 0, $this->configTestOne);
        $log = Logger::getLogRowByRow($this->configTestOne);
        $this->assertIsArray($log);
        $this->assertCount(2, $log);
    }
}

class LoggerConfigTestOne extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        return false;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->developmentEnvironment = false;
        $this->logDevelopmentMaxRow = 100;
        $this->logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->logPath = $this->logDirectoryPath . 'log.txt';
        $this->logProductionMaxRow = 100;
        $this->logVerboseActive = true;
    }
}

class LoggerConfigTestTwo extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        return false;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->developmentEnvironment = false;
        $this->logDevelopmentMaxRow = 100;
        $this->logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->logPath = $this->logDirectoryPath . 'log.txt';
        $this->logProductionMaxRow = 2;
        $this->logVerboseActive = true;
    }
}

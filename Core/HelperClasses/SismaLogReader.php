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

use SismaFramework\Core\Interfaces\Logging\LogReaderInterface;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Locker;

/**
 * @author Valentino de Lapa
 */
class SismaLogReader implements LogReaderInterface
{
    private Locker $locker;
    private Config $config;

    public function __construct(?Locker $locker = null, ?Config $config = null)
    {
        $this->locker = $locker ?? new Locker();
        $this->config = $config ?? Config::getInstance();
    }

    public function getLog(): string|false
    {
        $this->createLogStructure();
        return file_get_contents($this->config->logPath);
    }

    public function getLogRowByRow(): array|false
    {
        $this->createLogStructure();
        return file($this->config->logPath);
    }

    public function getLogRowNumber(): int
    {
        $this->createLogStructure();
        return count(file($this->config->logPath));
    }

    public function clearLog(): void
    {
        $this->createLogStructure();
        file_put_contents($this->config->logPath, '');
    }

    private function createLogStructure(): void
    {
        $this->createLogDirectory();
        $this->createLogFile();
    }

    private function createLogDirectory(): void
    {
        if (is_dir($this->config->logDirectoryPath) === false) {
            mkdir($this->config->logDirectoryPath);
            $handle = fopen($this->config->logPath, 'a');
            fclose($handle);
        }
        $this->locker->lockFolder($this->config->logDirectoryPath);
    }

    private function createLogFile(): void
    {
        if (file_exists($this->config->logPath) === false) {
            $file = fopen($this->config->logPath, 'w');
            if ($file) {
                fclose($file);
            }
        }
    }
}

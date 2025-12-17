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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Locker;
use SismaFramework\Core\Exceptions\LoggerException;

/**
 * @author Valentino de Lapa
 */
class SismaLogger implements LoggerInterface
{
    private Locker $locker;
    private Config $config;

    public function __construct(?Locker $locker = null, ?Config $config = null)
    {
        $this->locker = $locker ?? new Locker();
        $this->config = $config ?? Config::getInstance();
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $message = $this->interpolate($message, $context);
        $code = $context['code'] ?? $level;
        $file = $context['file'] ?? '';
        $line = $context['line'] ?? '';
        
        $this->saveLog($message, $code, $file, $line);
        
        if (isset($context['trace']) && is_array($context['trace'])) {
            $this->saveTrace($context['trace']);
        }
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (in_array($key, ['code', 'file', 'line', 'trace'])) {
                continue;
            }
            
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
    }

    private function saveLog(string $message, int|string $code, string $file, string $line): void
    {
        $this->createLogStructure();
        $handle = fopen($this->config->logPath, 'a');
        if ($handle !== false) {
            $logEntry = date("Y-m-d H:i:s") . "\t" . $code . "\t" . $message;
            if ($file !== '' || $line !== '') {
                $logEntry .= "\t" . $file . "(" . $line . ")";
            }
            fwrite($handle, $logEntry . "\n");
            fclose($handle);
        }
        $this->truncateLog();
    }

    private function saveTrace(array $trace): void
    {
        $this->createLogStructure();
        $handle = fopen($this->config->logPath, 'a');
        if ($handle !== false) {
            foreach ($trace as $call) {
                $row = "\t" . ($call['class'] ?? '') . ($call['type'] ?? '') . ($call['function'] ?? '') . "\n";
                $row .= isset($call['file']) ? "\t\t" . $call['file'] . '(' . $call['line'] . ')' . "\n" : '';
                fwrite($handle, $row);
            }
            fclose($handle);
        }
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

    private function truncateLog(): void
    {
        $maxRows = $this->config->developmentEnvironment 
            ? $this->config->logDevelopmentMaxRow 
            : $this->config->logProductionMaxRow;
            
        $logRows = file($this->config->logPath);
        if (count($logRows) > $maxRows) {
            $offset = $maxRows - count($logRows) - 1;
            $logRows = array_slice($logRows, $offset);
            $file = fopen($this->config->logPath, 'w');
            if ($file) {
                foreach ($logRows as $line) {
                    fwrite($file, $line);
                }
                fclose($file);
            } else {
                throw new LoggerException();
            }
        }
    }
}

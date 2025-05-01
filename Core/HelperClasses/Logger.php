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

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\LoggerException;

/**
 *
 * @author Valentino de Lapa
 */
class Logger
{

    public static function saveLog(string $message, int|string $code, string $file, string $line, Locker $locker = new Locker(), ?Config $customConfig = null): void
    {
        $config = $customConfig ?? Config::getInstance();
        self::createLogStructure($locker, $config);
        $handle = fopen($config->logPath, 'a');
        if ($handle !== false) {
            fwrite($handle, date("Y-m-d H:i:s") . "\t" . $code . "\t" . $message . "\t" . $file . "(" . $line . ")" . "\n");
            fclose($handle);
        }
        self::truncateLog($config);
    }

    private static function createLogStructure(Locker $locker, Config $config): void
    {
        self::createLogDirectory($locker, $config);
        self::createLogFile($config);
	}

    private static function createLogDirectory(Locker $locker, Config $config): void
    {
        if (is_dir($config->logDirectoryPath) === false) {
            mkdir($config->logDirectoryPath);
            $handle = fopen($config->logPath, 'a');
            fclose($handle);
        }
        $locker->lockFolder($config->logDirectoryPath);
    }

    private static function createLogFile(Config $config): void
    {
        if (file_exists($config->logPath) === false) {
            $file = fopen($config->logPath, 'w');
            if ($file) {
                fclose($file);
            } else {
                
            }
        }
    }

    private static function truncateLog(Config $config): void
    {
        $maxRows = $config->developmentEnvironment ? $config->logDevelopmentMaxRow : $config->logProductionMaxRow;
        $logRows = file($config->logPath);
        if (count($logRows) > $maxRows) {
            $offset = $maxRows - count($logRows) - 1;
            $logRows = array_slice($logRows, $offset);
            $file = fopen($config->logPath, 'w');
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

    public static function saveTrace(array $trace, Locker $locker = new Locker(), ?Config $customConfig = null): void
    {
        $config = $customConfig ?? Config::getInstance();
        self::createLogStructure($locker, $config);
        $handle = fopen($config->logPath, 'a');
        if ($handle !== false) {
            foreach ($trace as $call) {
                $row = "\t" . ($call['class'] ?? '') . ($call['type'] ?? '') . ($call['function'] ?? '') . "\n";
                $row .= isset($call['file']) ? "\t\t" . $call['file'] . '(' . $call['line'] . ')' . "\n" : '';
                fwrite($handle, $row);
            }
            fclose($handle);
        }
    }

    public static function clearLog(Locker $locker = new Locker(), ?Config $customConfig = null): void
    {
        $config = $customConfig ?? Config::getInstance();
        self::createLogStructure($locker, $config);
        file_put_contents($config->logPath, '');
    }

    public static function getLog(Locker $locker = new Locker(), ?Config $customConfig = null): string|false
    {
        $config = $customConfig ?? Config::getInstance();
        self::createLogStructure($locker, $config);
        return file_get_contents($config->logPath);
    }

    public static function getLogRowByRow(Locker $locker = new Locker(), ?Config $customConfig = null): array|false
    {
        $config = $customConfig ?? Config::getInstance();
        self::createLogStructure($locker, $config);
        return file($config->logPath);
    }

    public static function getLogRowNumber(Locker $locker = new Locker(), ?Config $customConfig = null): int
    {
        $config = $customConfig ?? Config::getInstance();
        self::createLogStructure($locker, $config);
        return count(file($config->logPath));
    }
}

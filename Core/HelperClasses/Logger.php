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

use SismaFramework\Core\Exceptions\LoggerException;

/**
 *
 * @author Valentino de Lapa
 */
class Logger
{

    private static string $logDirectoryPath = \Config\LOG_DIRECTORY_PATH;
    private static string $logPath = \Config\LOG_PATH;
    private static int $maxRows = \Config\DEVELOPMENT_ENVIRONMENT ? \Config\LOG_DEVELOPEMENT_MAX_ROW : \Config\LOG_PRODUCTION_MAX_ROW;

    public static function setMaxRows(int $maxRows): void
    {
        self::$maxRows = $maxRows;
    }

    public static function saveLog(string $message, int|string $code, string $file, string $line, Locker $locker = new Locker()): void
    {
        self::createLogStructure($locker);
        $handle = fopen(self::$logPath, 'a');
        if ($handle !== false) {
            fwrite($handle, date("Y-m-d H:i:s") . "\t" . $code . "\t" . $message . "\t" . $file . "(" . $line . ")" . "\n");
            fclose($handle);
        }
        self::truncateLog();
    }

    private static function createLogStructure(Locker $locker): void
    {
        self::createLogDirectory($locker);
        self::createLogFile();
    }

    private static function createLogDirectory(Locker $locker): void
    {
        if (is_dir(self::$logDirectoryPath) === false) {
            mkdir(self::$logDirectoryPath);
            $handle = fopen(self::$logPath, 'a');
            fclose($handle);
        }
        $locker->lockFolder(self::$logDirectoryPath);
    }

    private static function createLogFile(): void
    {
        if (file_exists(self::$logPath) === false) {
            $file = fopen(self::$logPath, 'w');
            if ($file) {
                fclose($file);
            } else {
                
            }
        }
    }

    private static function truncateLog(): void
    {
        $logRows = file(self::$logPath);
        if (count($logRows) > self::$maxRows) {
            $offset = self::$maxRows - count($logRows) - 1;
            $logRows = array_slice($logRows, $offset);
            $file = fopen(self::$logPath, 'w');
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

    public static function saveTrace(array $trace): void
    {
        $handle = fopen(self::$logPath, 'a');
        if ($handle !== false) {
            foreach ($trace as $call) {
                $row = "\t" . ($call['class'] ?? '') . ($call['type'] ?? '') . ($call['function'] ?? '') . "\n";
                $row .= isset($call['file']) ? "\t\t" . $call['file'] . '(' . $call['line'] . ')' . "\n" : '';
                fwrite($handle, $row);
            }
            fclose($handle);
        }
    }

    public static function clearLog(Locker $locker = new Locker()): void
    {
        self::createLogStructure($locker);
        file_put_contents(self::$logPath, '');
    }

    public static function getLog(Locker $locker = new Locker()): string|false
    {
        self::createLogStructure($locker);
        return file_get_contents(self::$logPath);
    }

    public static function getLogRowByRow(Locker $locker = new Locker()): array|false
    {
        self::createLogStructure($locker);
        return file(self::$logPath);
    }

    public static function getLogRowNumber(Locker $locker = new Locker()): int
    {
        self::createLogStructure($locker);
        return count(file(self::$logPath));
    }
}

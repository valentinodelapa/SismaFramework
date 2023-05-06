<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Logger
{

    private static int $maxRows = \Config\DEVELOPMENT_ENVIRONMENT ? \Config\LOG_DEVELOPEMENT_MAX_ROW : \Config\LOG_PRODUCTION_MAX_ROW;
    
    public static function setMaxRows(int $maxRows):void
    {
        self::$maxRows = $maxRows;
    }

    public static function saveLog(string $message, int $code, string $file = ''): void
    {
        self::createLogDirectory();
        $handle = fopen(\Config\LOG_PATH, 'a');
        if ($handle !== false) {
            fwrite($handle, date("Y-m-d H:i:s") . "\t" . $code . "\t" . $message . "\t" . $file . "\n");
            fclose($handle);
        }
        self::truncateLog();
    }

    public static function createLogDirectory()
    {
        if (is_dir(\Config\LOG_DIRECTORY_PATH) === false) {
            mkdir(\Config\LOG_DIRECTORY_PATH);
            $handle = fopen(\Config\LOG_PATH, 'a');
            fclose($handle);
        }
    }

    private static function truncateLog(): void
    {
        $logRows = file(\Config\LOG_PATH);
        if (count($logRows) > self::$maxRows) {
            $offset = self::$maxRows - count($logRows) - 1;
            $logRows = array_slice($logRows, $offset);
            file_put_contents(\Config\LOG_PATH, $logRows);
        }
    }

    public static function saveTrace(array $trace): void
    {
        $handle = fopen(\Config\LOG_PATH, 'a');
        if ($handle !== false) {
            foreach ($trace as $call) {
                $row = "\t" . ($call['class'] ?? '') . ($call['type'] ?? '') . ($call['function'] ?? '') . "\n";
                $row .= isset($call['file']) ? "\t\t" . $call['file'] . '(' . $call['line'] . ')' . "\n" : ';';
                fwrite($handle, $row);
            }
            fclose($handle);
        }
    }

    public static function clearLog(): void
    {
        self::createLogDirectory();
        file_put_contents(\Config\LOG_PATH, '');
    }

    public static function getLog(): string|false
    {
        self::createLogDirectory();
        return file_get_contents(\Config\LOG_PATH);
    }

    public static function getLogRowByRow(): array|false
    {
        self::createLogDirectory();
        return file(\Config\LOG_PATH);
    }

    public static function getLogRowNumber(): int
    {
        self::createLogDirectory();
        return count(file(\Config\LOG_PATH));
    }

}

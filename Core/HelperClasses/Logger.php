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

    public static function saveLog(string $message, int $code, string $file = ''): void
    {
        $handle = fopen(\Config\LOG_PATH, 'a');
        if ($handle !== false) {
            fwrite($handle, date("Y-m-d H:i:s") . "\t" . $code . "\t" . $message . "\t" . $file . "\n");
            fclose($handle);
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
        file_put_contents(\Config\LOG_PATH, '');
    }

    public static function getLog(): string|false
    {
        $handle = fopen(\Config\LOG_PATH, 'r');
        if ($handle !== false) {
            $log = fread($handle, (filesize(\Config\LOG_PATH) > 0) ? filesize(\Config\LOG_PATH) : 1);
            fclose($handle);
            return $log;
        } else {
            return false;
        }
    }

    public static function getLogRowByRow(): array|false
    {
        $handle = fopen(\Config\LOG_PATH, 'r');
        if ($handle !== false) {
            $log = [];
            while (($line = fgets($handle)) !== false) {
                $log[] = $line;
            }
            fclose($handle);
            return $log;
        } else {
            return false;
        }
    }

}

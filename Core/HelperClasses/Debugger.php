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

use SismaFramework\Core\CustomTypes\FormFilterError;
use SismaFramework\Core\HelperClasses\Logger;
use SismaFramework\Core\HelperClasses\Templater;
use SismaFramework\Core\HelperClasses\Parser;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class Debugger
{

    private static float $startMicrotime;
    private static float $executionTime;
    private static int $queryExecutedNumber = 0;
    private static int $logRowNumber = 0;
    private static int $formFilterNumber = 0;
    private static int $varsNumber = 0;
    private static array $queryExecuted = [];
    private static array $formFilter = [];
    private static array $vars = [];

    public static function startExecutionTimeCalculation(): void
    {
        static::$startMicrotime = microtime(true);
    }

    public static function establishExecutionTimeCalculation(): void
    {
        $executionTime = (microtime(true) - self::$startMicrotime) * 1000;
        static::$executionTime = round($executionTime, 2);
    }

    public function generateDebugBar()
    {
        $debugBarQuery = $debugBarLog = $debugBarForm = $debugBarVars = '';
        foreach (self::$queryExecuted as $query) {
            $debugBarQuery .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $query]);
        }
        foreach (Logger::getLogRowByRow() as $logRow) {
            self::$logRowNumber++;
            $debugBarLog .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $logRow]);
        }
        $debugBarForm = self::generateDebugBarForm(self::$formFilter);
        $debugBarVars = self::generateDebugBarVars();
        $vars = array_merge(self::getInformations(), [
            'debugBarQuery' => $debugBarQuery,
            'debugBarLog' => $debugBarLog,
            'debugBarForm' => $debugBarForm,
            'debugBarVars' => $debugBarVars,
        ]);
        return Templater::generateStructuralTemplate('debugBar', $vars);
    }

    private static function generateDebugBarForm(array $informations, string $tabulation = '')
    {
        $debugBarForm = '';
        foreach ($informations as $field => $information) {
            if (is_array($information)) {
                $debugBarForm .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $tabulation . $field . ':']);
                $debugBarForm .= self::generateDebugBarForm($information, $tabulation . '&emsp;');
            } else {
                $debugBarForm .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $tabulation . $field . ': ' . (($information) ? 'true' : 'false')]);
            }
        }
        return $debugBarForm;
    }

    private static function generateDebugBarVars()
    {
        $vars = [];
        foreach (self::$vars as $key => $value) {
            $vars[$key] = $value;
        }
        Parser::unparseValues($vars);
        $debugBarVars = '';
        foreach ($vars as $field => $information) {
            $debugBarVars .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $field . ': ' . print_r($information, true)]);
        }
        return $debugBarVars;
    }

    public static function getInformations(): array
    {
        self::establishExecutionTimeCalculation();
        return [
            "queryExecutedNumber" => self::$queryExecutedNumber,
            "logRowNumber" => self::$logRowNumber,
            "formFilterNumber" => self::$formFilterNumber,
            "varsNumber" => self::$varsNumber,
            "memoryUsed" => self::getMemoryUsed(),
            "executionTime" => self::$executionTime,
        ];
    }

    private static function getMemoryUsed(): float
    {
        $memoryUsedInByte = memory_get_usage();
        return round($memoryUsedInByte / 1024 / 1024, 2);
    }

    public static function addQueryExecuted(string $query): void
    {
        self::$queryExecuted[] = $query;
        self::incrementQueryExecutedNumber();
    }

    private static function incrementQueryExecutedNumber(): void
    {
        self::$queryExecutedNumber = self::$queryExecutedNumber + 1;
    }

    public static function setFormFilter(FormFilterError $formFilter): void
    {
        self::$formFilter = $formFilter->getErrorsToArray();
        self::$formFilterNumber = count(self::$formFilter);
    }

    public static function setVars(array $vars): void
    {
        $parsedVars = [];
        foreach ($vars as $key => $value) {
            if (is_string($value)) {
                $parsedVars[$key] = htmlentities($value);
            } elseif (is_scalar($value)) {
                $parsedVars[$key] = $value;
            } else {
                $parsedVars[$key] = gettype($value);
            }
        }
        self::$vars = $parsedVars;
        self::$varsNumber = count(self::$vars);
    }
}

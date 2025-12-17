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
use SismaFramework\Core\HelperClasses\SismaLogReader;
use SismaFramework\Core\HelperClasses\Templater;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\Interfaces\Logging\LogReaderInterface;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class Debugger
{

    private float $startMicrotime = 0.0;
    private float $executionTime = 0.0;
    private int $queryExecutedNumber = 0;
    private int $logRowNumber = 0;
    private int $formFilterNumber = 0;
    private int $varsNumber = 0;
    private array $queryExecuted = [];
    private array $formFilter = [];
    private array $vars = [];
    private LogReaderInterface $logReader;

    public function __construct(?LogReaderInterface $logReader = null)
    {
        $this->logReader = $logReader ?? new SismaLogReader();
    }

    public function startExecutionTimeCalculation(): void
    {
        $this->startMicrotime = microtime(true);
    }

    public function establishExecutionTimeCalculation(): void
    {
        $executionTime = (microtime(true) - $this->startMicrotime) * 1000;
        $this->executionTime = round($executionTime, 2);
    }

    public function generateDebugBar(): string
    {
        $debugBarQuery = $debugBarLog = $debugBarForm = $debugBarVars = '';
        foreach ($this->queryExecuted as $query) {
            $debugBarQuery .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $query]);
        }
        foreach ($this->logReader->getLogRowByRow() as $logRow) {
            $this->logRowNumber++;
            $debugBarLog .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $logRow]);
        }
        $debugBarForm = $this->generateDebugBarForm($this->formFilter);
        $debugBarVars = $this->generateDebugBarVars();
        $vars = array_merge($this->getInformations(), [
            'debugBarQuery' => $debugBarQuery,
            'debugBarLog' => $debugBarLog,
            'debugBarForm' => $debugBarForm,
            'debugBarVars' => $debugBarVars,
        ]);
        return Templater::generateStructuralTemplate('debugBar', $vars);
    }

    private function generateDebugBarForm(array $informations, string $tabulation = ''): string
    {
        $debugBarForm = '';
        foreach ($informations as $field => $information) {
            if (is_array($information)) {
                $debugBarForm .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $tabulation . $field . ':']);
                $debugBarForm .= $this->generateDebugBarForm($information, $tabulation . '&emsp;');
            } else {
                $debugBarForm .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $tabulation . $field . ': ' . (($information) ? 'true' : 'false')]);
            }
        }
        return $debugBarForm;
    }

    private function generateDebugBarVars(): string
    {
        $vars = [];
        foreach ($this->vars as $key => $value) {
            $vars[$key] = $value;
        }
        Parser::unparseValues($vars);
        $debugBarVars = '';
        foreach ($vars as $field => $information) {
            $debugBarVars .= Templater::generateStructuralTemplate('debugBarBody', ['information' => $field . ': ' . print_r($information, true)]);
        }
        return $debugBarVars;
    }

    public function getInformations(): array
    {
        $this->establishExecutionTimeCalculation();
        return [
            "queryExecutedNumber" => $this->queryExecutedNumber,
            "logRowNumber" => $this->logRowNumber,
            "formFilterNumber" => $this->formFilterNumber,
            "varsNumber" => $this->varsNumber,
            "memoryUsed" => $this->getMemoryUsed(),
            "executionTime" => $this->executionTime,
        ];
    }

    private function getMemoryUsed(): float
    {
        $memoryUsedInByte = memory_get_usage();
        return round($memoryUsedInByte / 1024 / 1024, 2);
    }

    public function addQueryExecuted(string $query): void
    {
        $this->queryExecuted[] = $query;
        $this->incrementQueryExecutedNumber();
    }

    private function incrementQueryExecutedNumber(): void
    {
        $this->queryExecutedNumber = $this->queryExecutedNumber + 1;
    }

    public function setFormFilter(FormFilterError $formFilter): void
    {
        $this->formFilter = $formFilter->getErrorsToArray();
        $this->formFilterNumber = count($this->formFilter);
    }

    public function setVars(array $vars): void
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
        $this->vars = $parsedVars;
        $this->varsNumber = count($this->vars);
    }
}

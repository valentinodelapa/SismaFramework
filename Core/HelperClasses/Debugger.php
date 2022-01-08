<?php

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Core\HelperClasses\Logger;
use SismaFramework\Core\HelperClasses\Templater;

class Debugger
{

    private static float $microtime;
    private static float $endExecutionTime;
    private static float $executionTime;
    private static int $queryExecutedNumber = 0;
    private static int $logRowNumber = 0;
    private static int $formFilterNumber = 0;
    private static array $queryExecuted = [];
    private static array $formFilter = [];

    public static function startExecutionTimeCalculation(): void
    {
        static::$microtime = microtime(true);
    }

    public static function endExecutionTimeCalculation(): void
    {
        $executionTime = (microtime(true) - self::$microtime) * 1000;
        static::$executionTime = round($executionTime, 2);
    }

    public static function generateDebugBar()
    {
        Templater::setStructural();
        $debugBarQuery = $debugBarLog = $debugBarForm = '';
        foreach (self::$queryExecuted as $query) {
            $debugBarQuery .= Templater::generateTemplate('debugBarBody', ['information' => $query]);
        }
        foreach (Logger::getLogRowByRow() as $logRow) {
            self::$logRowNumber++;
            $debugBarLog .= Templater::generateTemplate('debugBarBody', ['information' => $logRow]);
        }
        $debugBarForm = self::generateDebugBarForm(self::$formFilter);
        $vars = array_merge(self::getInformations(), [
            'debugBarQuery' => $debugBarQuery,
            'debugBarLog' => $debugBarLog,
            'debugBarForm' => $debugBarForm,
        ]);
        return Templater::generateTemplate('debugBar', $vars);
    }

    private static function generateDebugBarForm(array $informations, string $tabulation = '')
    {
        $debugBarForm = '';
        foreach ($informations as $field => $information) {
            if (is_array($information)) {
                $debugBarForm .= Templater::generateTemplate('debugBarBody', ['information' => $tabulation . $field . ':']);
                $debugBarForm .= self::generateDebugBarForm($information, $tabulation . '&emsp;');
            } else {
                self::$formFilterNumber++;
                $debugBarForm .= Templater::generateTemplate('debugBarBody', ['information' => $tabulation . $field . ': ' . (($information) ? 'true' : 'false')]);
            }
        }
        return $debugBarForm;
    }

    public static function getInformations(): array
    {
        return [
            "queryExecutedNumber" => self::$queryExecutedNumber,
            "logRowNumber" => self::$logRowNumber,
            "formFilterNumber" => self::$formFilterNumber,
            "memoryUsed" => self::getMemoryUsed(),
            "executionTime" => self::$executionTime,
        ];
    }

    private static function getMemoryUsed()
    {
        $memoryUsedInByte = memory_get_usage();
        $memoryUsed = round($memoryUsedInByte / 1024 / 1024, 2);
        return $memoryUsed;
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

    public static function setFormFilter(array $formFilter): void
    {
        self::$formFilter = $formFilter;
    }

}

<?php

namespace Sisma\Core\HelperClasses;

use Sisma\Core\HelperClasses\Logger;
use Sisma\Core\HelperClasses\Templater;

class Debugger
{

    private static float $microtime;
    private static float $endExecutionTime;
    private static float $executionTime;
    private static int $queryExecutedNumber = 0;
    private static int $logRowNumber = 0;
    private static array $queryExecuted = [];

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
        $debugBarQuery = $debugBarLog = '';
        foreach (self::$queryExecuted as $query) {
            $debugBarQuery .= Templater::generateTemplate('debugBarBody', ['information' => $query]);
        }
        foreach (Logger::getLogRowByRow() as $logRow) {
            self::$logRowNumber++;
            $debugBarLog .= Templater::generateTemplate('debugBarBody', ['information' => $logRow]);
        }
        $vars = array_merge(self::getInformations(), [
            'debugBarQuery' => $debugBarQuery,
            'debugBarLog' => $debugBarLog,
        ]);
        return Templater::generateTemplate('debugBar', $vars);
    }

    public static function getInformations(): array
    {
        return [
            "queryExecutedNumber" => self::$queryExecutedNumber,
            "logRowNumber" => self::$logRowNumber,
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

}

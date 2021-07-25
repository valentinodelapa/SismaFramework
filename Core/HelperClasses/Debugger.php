<?php

namespace Sisma\Core\HelperClasses;

class Debugger
{

    private static float $microtime;
    private static float $endExecutionTime;
    private static float $executionTime;
    private static int $queryExecutedNumber = 0;

    public static function startExecutionTimeCalculation(): void
    {
        static::$microtime = microtime(true);
    }

    public static function endExecutionTimeCalculation(): void
    {
        $executionTime = (microtime(true) - self::$microtime) * 1000;
        static::$executionTime = round($executionTime, 4);
    }
    
    public static function getInformations():array
    {
        return [
            "execution time (ms)" => self::$executionTime,
            "query executed number" => self::$queryExecutedNumber,
        ];
    }
    
    public static function incrementQueryExecutedNumber():void
    {
        self::$queryExecutedNumber = self::$queryExecutedNumber+1;
    }

}

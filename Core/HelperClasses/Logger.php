<?php

namespace Sisma\Core\HelperClasses;

class Logger
{

    public static function saveLog(string $message, int $code): void
    {
        $handle = fopen(\Config\LOG_PATH, 'a');
        fwrite($handle, date("Y-m-d H:i:s") . "\t" . $code . "\t" . $message . "\n");
        fclose($handle);
    }

    public static function clearLog(): void
    {
        file_put_contents(\Config\LOG_PATH, '');
    }

    public static function getLog(): string
    {
        $handle = fopen(\Config\LOG_PATH, 'r');
        $log = fread($handle, filesize(\Config\LOG_PATH));
        fclose($handle);
        return $log;
    }
    
    public static function getLogRowByRow():array
    {
        $handle = fopen(\Config\LOG_PATH, 'r');
        $log = [];
        while (($buffer = fgets($handle, filesize(\Config\LOG_PATH))) !== false) {
            $log[] = $buffer;
        }
        fclose($handle);
        return $log;
    }

}

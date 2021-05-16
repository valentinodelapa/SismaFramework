<?php

namespace Sisma\Core\HelperClasses;

class LogManager
{
    private $handle;
    
    public function __construct()
    {
        $this->handle = fopen(\Sisma\Core\LOG_PATH, 'a');
    }
    
    public function saveLog(string $message, int $code)
    {
        fwrite($this->handle, date("Y-m-d H:i:s"). "\t". $code . "\t" .$message."\n");
    }
    
    public function __destruct()
    {
        unset($this->handle);
    }
}

<?php

namespace Sisma\Core\BaseClasses;

use Sisma\Core\HelperClasses\LogManager;
use Sisma\Core\HelperClasses\Router;

class BaseException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
        $log = new LogManager();
        $log->saveLog($message, $code);
        unset($log);
        $this->errorRedirect();
    }
    
    protected function errorRedirect()
    {
        //Router::redirect('error/message/Errore');
    }
}

<?php

namespace Sisma\Core\BaseClasses;

use Sisma\Core\HelperClasses\Logger;
use Sisma\Core\HelperClasses\Router;

class BaseException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
        Logger::saveLog($message, $code);
        $this->errorRedirect();
    }
    
    protected function errorRedirect()
    {
        Router::redirect('error/message/Errore');
    }
}

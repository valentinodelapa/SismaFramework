<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace SismaFramework\ExtendedClasses;

use SismaFramework\BaseClasses\BaseException;
use SismaFramework\Core\HelperClasses\Logger;

/**
 * @author Valentino de Lapa
 */
abstract class LogException extends BaseException
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        Logger::saveLog($message, $code, $this->getFile(), $this->getLine());
        if (\Config\DEVELOPMENT_ENVIRONMENT) {
            Logger::saveTrace($this->getTrace());
        }
    }
}

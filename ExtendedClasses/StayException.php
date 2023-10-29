<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace SismaFramework\ExtendedClasses;

use SismaFramework\BaseClasses\BaseException;
use SismaFramework\Core\Interfaces\Controllers\ExceptionControllerInterface;

/**
 * @author Valentino de Lapa
 */
abstract class StayException extends BaseException
{
    protected ExceptionControllerInterface $controller;
    
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $defaultExceptionController = \Config\DEFAULT_EXCEPTION_CONTROLLER;
        $this->controller = new $defaultExceptionController();
    }
    
    public static function createWithCustomController(ExceptionControllerInterface $customController, string $message = "", int $code = 0, \Throwable $previous = null){
        $exception = new self($message, $code, $previous);
        $exception->setCustomController($customController);
        return $exception;
    }
    
    public function setCustomController(ExceptionControllerInterface $customController)
    {
        $this->controller = $customController;
    }

    public function show()
    {
        $this->response->setResponseType(ResponseType::httpInternalServerError);
        $this->controller->error($this->message);
    }
}

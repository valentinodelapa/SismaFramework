<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace SismaFramework\ExtendedClasses;

use SismaFramework\BaseClasses\BaseException;
use SismaFramework\Core\Enumerations\ResponseType;

/**
 * Description of RedirectException
 *
 * @author Valentino de Lapa
 */
abstract class RedirectException extends BaseException
{
    abstract public function redirect();
    
    public function setResponseType()
    {
        $this->response->setResponseType(ResponseType::httpTemporaryRedirect);
    }
}

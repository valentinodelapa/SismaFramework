<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace SismaFramework\ExtendedClasses;

use SismaFramework\BaseClasses\BaseException;

/**
 * @author Valentino de Lapa
 */
abstract class RedirectException extends BaseException
{
    abstract public function redirect();
}

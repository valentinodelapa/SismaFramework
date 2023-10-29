<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace SismaFramework\Core\Interfaces\Controllers;

use SismaFramework\Core\HttpClasses\Response;

/**
 *
 * @author Valentino de Lapa
 */
interface ExceptionControllerInterface
{

    public function error(string $message): Response;
}

<?php

namespace SismaFramework\Core\Exceptions;

use SismaFramework\Core\BaseClasses\BaseException;

class InvalidArgumentException extends BaseException
{
    public function __construct()
    {
        parent::__construct('Siamo spiacenti ma i parametri inviati non corrispondono con quelli richiesti dalla funzione', 0);
    }
}

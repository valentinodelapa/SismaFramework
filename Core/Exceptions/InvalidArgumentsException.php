<?php

namespace Sisma\Core\Exceptions;

use Sisma\Core\BaseClasses\BaseException;

class InvalidArgumentsException extends BaseException
{
    public function __construct()
    {
        parent::__construct('Siamo spiacenti ma i parametri inviati non corrispondono con quelli richiesti dalla funzione', 0);
    }
}

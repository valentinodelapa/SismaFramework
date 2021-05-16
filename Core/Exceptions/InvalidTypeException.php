<?php

namespace Sisma\Core\Exceptions;

use Sisma\Core\BaseClasses\BaseException;

class InvalidTypeException extends BaseException
{
    public function __construct()
    {
        parent::__construct('Siamo spiacenti ma la variabile non è del tipo giusto', 0);
    }
}

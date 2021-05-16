<?php

namespace Sisma\Core\Exceptions;

use Sisma\Core\BaseClasses\BaseException;

class ModelException extends BaseException
{
    
    public function __construct()
    {
        parent::__construct("ModelException", 0);
    }
}

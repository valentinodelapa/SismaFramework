<?php

namespace SismaFramework\Core\Exceptions;

use SismaFramework\Core\BaseClasses\BaseException;

class ModelException extends BaseException
{
    
    public function __construct()
    {
        parent::__construct("ModelException", 0);
    }
}

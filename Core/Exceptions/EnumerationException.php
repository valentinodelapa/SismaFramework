<?php

namespace SismaFramework\Core\Exceptions;

use SismaFramework\Core\BaseClasses\BaseException;

class EnumerationException extends BaseException
{
    
    public function __construct()
    {
        parent::__construct("EnumerationException", 0);
    }
}

<?php

namespace SismaFramework\Core\Exceptions;

use SismaFramework\Core\BaseClasses\BaseException;

class FilterException extends BaseException
{
    
    public function __construct()
    {
        parent::__construct("Filter Exception", 0);
    }
}

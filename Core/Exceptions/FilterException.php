<?php

namespace Sisma\Core\Exceptions;

use Sisma\Core\BaseClasses\BaseException;

class FilterException extends BaseException
{
    
    public function __construct()
    {
        parent::__construct("Filter Exception", 0);
    }
}

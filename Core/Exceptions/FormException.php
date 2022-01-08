<?php

namespace SismaFramework\Core\Exceptions;

use SismaFramework\Core\BaseClasses\BaseException;

class FormException extends BaseException
{
    
    public function __construct()
    {
        parent::__construct("FormException", 0);
    }
}

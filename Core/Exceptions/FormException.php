<?php

namespace Sisma\Core\Exceptions;

use Sisma\Core\BaseClasses\BaseException;

class FormException extends BaseException
{
    
    public function __construct()
    {
        parent::__construct("FormException", 0);
    }
}

<?php

namespace SismaFramework\Core\Exceptions;

use SismaFramework\Core\BaseClasses\BaseException;

class AccessDeniedException extends BaseException
{
    public function __construct()
    {
        parent::__construct("Accesso negato", 0);
    }
}

<?php

namespace Sisma\Core\Exceptions;

use Sisma\Core\BaseClasses\BaseException;

class AccessDeniedException extends BaseException
{
    public function __construct()
    {
        parent::__construct("Accesso negato", 0);
    }
}

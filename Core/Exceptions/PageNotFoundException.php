<?php

namespace Sisma\Core\Exceptions;

use Sisma\Core\BaseClasses\BaseException;

class PageNotFoundException extends BaseException
{
    public function __construct()
    {
        parent::__construct("Pagina non trovata", 0);
    }
}

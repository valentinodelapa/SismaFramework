<?php

namespace Sisma\Core\BaseClasses;

use Sisma\Core\HelperClasses\Dispatcher;
use Sisma\Core\HelperClasses\Router;
use Sisma\Core\HelperClasses\Session;

abstract class BaseController
{

    protected array $vars;

    public function __construct()
    {
        $this->vars['metaPath'] = Router::getMetaPath();
    }

}

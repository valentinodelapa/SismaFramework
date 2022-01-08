<?php

namespace SismaFramework\Core\BaseClasses;

use SismaFramework\Core\HelperClasses\Dispatcher;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HelperClasses\Session;

abstract class BaseController
{

    protected array $vars;

    public function __construct()
    {
        $this->vars['metaPath'] = Router::getMetaPath();
    }

}

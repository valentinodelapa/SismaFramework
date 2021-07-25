<?php

namespace Sisma\Autoload;

use Sisma\Core\HelperClasses\Router;

spl_autoload_register(function(string $className) {
    $modifiedClassName = str_replace(\Config\PROJECT.'\\', '', $className);
    $partialClassPath = str_replace('\\', DIRECTORY_SEPARATOR, $modifiedClassName);
    $classPath = \Config\ROOT_PATH . $partialClassPath . '.php';
    if (file_exists($classPath)) {
        require_once($classPath);
    }
});


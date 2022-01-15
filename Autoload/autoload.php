<?php

namespace SismaFramework\Autoload;

require_once __DIR__.'/Autoloader.php';

spl_autoload_register(function (string $className) {
    $autoloader = new Autoloader($className);
    if ($autoloader->findClass()) {
        require_once($autoloader->getClassPath());
    }
});

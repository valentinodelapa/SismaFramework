<?php

namespace SismaFramework\Autoload;

require_once __DIR__.'/Autoloader.php';

spl_autoload_register(function (string $className) {
    Autoloader::injectClassName($className);
    if (Autoloader::findClass(substr(\Config\ROOT_PATH, 0, -1))) {
        require_once(Autoloader::getClassPath());
    }
});

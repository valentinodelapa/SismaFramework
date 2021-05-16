<?php

namespace Sisma;

use Sisma\Core\CoreException;
use Sisma\Core\HelperClasses\Dispatcher;

try {

    require_once(__DIR__ . '/../core/config.php');
    require_once(__DIR__ . '/../core/autoload.php');

    new Dispatcher();
    
} catch (CoreException $exception) {
    
} catch (Exception $exception) {
    
}

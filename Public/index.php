<?php

namespace Sisma;

use SismaFramework\Core\CoreException;
use SismaFramework\Core\HelperClasses\Dispatcher;

try {

    require_once(__DIR__ . '/../Config/config.php');
    require_once(__DIR__ . '/../Autoload/autoload.php');

    new Dispatcher();
    
} catch (CoreException $exception) {
    
} catch (Exception $exception) {
    
}

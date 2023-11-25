<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SismaFramework\ErrorHandling;

use SismaFramework\Core\HelperClasses\Logger;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Structural\Controllers\FrameworkController;

register_shutdown_function(function () {
    $error = error_get_last();
    $backtrace = debug_backtrace();
    if (is_array($error)) {
        \ob_end_clean();
        Logger::saveLog($error['message'], $error['type'], $error['file'], $error['line']);
        if (\Config\LOG_VERBOSE_ACTIVE) {
            Logger::saveTrace($backtrace);
        }
        if (\Config\DEVELOPMENT_ENVIRONMENT) {
            Router::setActualCleanUrl('framework', 'nonThowableError');
            $frameworkController = new FrameworkController();
            $frameworkController->nonThrowableError($error, $backtrace);
        } else {
            Router::setActualCleanUrl('framework', 'internalServerError');
            $frameworkController = new FrameworkController();
            $frameworkController->internalServerError();
        }
    }
});


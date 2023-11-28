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

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Core\HelperClasses\Logger;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Structural\Controllers\FrameworkController;
use Throwable;

/**
 * Description of ErrorHandler
 *
 * @author Valentino de Lapa
 */
class ErrorHandler
{

    public static function handleThrowableError(Throwable $throwable): Response
    {
        \ob_clean();
        Logger::saveLog($throwable->getMessage(), $throwable->getCode(), $throwable->getFile(), $throwable->getLine());
        if (\Config\LOG_VERBOSE_ACTIVE) {
            Logger::saveTrace($throwable->getTrace());
        }
        if (\Config\DEVELOPMENT_ENVIRONMENT) {
            return self::callThrowableErrorAction($throwable);
        } else {
            return self::callInternalServerErrorAction();
        }
    }

    public static function callThrowableErrorAction(Throwable $throwable): Response
    {
        Router::setActualCleanUrl('framework', 'thowableError');
        $frameworkController = new FrameworkController();
        return $frameworkController->throwableError($throwable);
    }

    private static function callInternalServerErrorAction(): Response
    {

        Router::setActualCleanUrl('framework', 'internalServerError');
        $frameworkController = new FrameworkController();
        return $frameworkController->internalServerError();
    }

    public static function handleNonThrowableError(): void
    {
        register_shutdown_function(function () {
            $error = error_get_last();
            $backtrace = debug_backtrace();
            if (is_array($error)) {
                \ob_clean();
                Logger::saveLog($error['message'], $error['type'], $error['file'], $error['line']);
                if (\Config\LOG_VERBOSE_ACTIVE) {
                    Logger::saveTrace($backtrace);
                }
                if (\Config\DEVELOPMENT_ENVIRONMENT) {
                    self::callNonThrowableErrorAction($error, $backtrace);
                } else {
                    self::callInternalServerErrorAction();
                }
            }
        });
    }

    private static function callNonThrowableErrorAction(array $error, array $backtrace): Response
    {
        Router::setActualCleanUrl('framework', 'nonThowableError');
        $frameworkController = new FrameworkController();
        return $frameworkController->nonThrowableError($error, $backtrace);
    }
}

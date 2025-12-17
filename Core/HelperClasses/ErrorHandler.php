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

use Psr\Log\LoggerInterface;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\BufferManager;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HelperClasses\SismaLogger;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\Interfaces\Controllers\DefaultControllerInterface;
use SismaFramework\Core\Interfaces\Controllers\StructuralControllerInterface;
use SismaFramework\Sample\Controllers\SampleController;
use SismaFramework\Security\BaseClasses\BaseException;
use SismaFramework\Security\Interfaces\Exceptions\ShouldBeLoggedException;
use SismaFramework\Structural\Controllers\FrameworkController;
use Throwable;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class ErrorHandler
{

    private LoggerInterface $logger;
    private Config $config;

    public function __construct(?LoggerInterface $logger = null, ?Config $config = null)
    {
        $this->logger = $logger ?? new SismaLogger();
        $this->config = $config ?? Config::getInstance();
    }

    public function disableErrorDisplay(): void
    {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(0);
    }

    public function registerNonThrowableErrorHandler(StructuralControllerInterface $controller = new FrameworkController()): void
    {
        register_shutdown_function(function () use ($controller) {
            $error = error_get_last();
            $backtrace = debug_backtrace();
            if (is_array($error)) {
                BufferManager::clear();
                $this->logger->error($error['message'], [
                    'code' => $error['type'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'trace' => $this->config->logVerboseActive ? $backtrace : null
                ]);
                if ($this->config->developmentEnvironment) {
                    $this->callNonThrowableErrorAction($controller, $error, $backtrace);
                } else {
                    $this->callInternalServerErrorAction($controller);
                }
            }
        });
    }

    private function callNonThrowableErrorAction(StructuralControllerInterface $controller, array $error, array $backtrace): Response
    {
        Router::setActualCleanUrl('framework', 'nonThowableError');
        return $controller->nonThrowableError($error, $backtrace);
    }

    private function callInternalServerErrorAction(StructuralControllerInterface $structuralController): Response
    {
        Router::setActualCleanUrl('framework', 'internalServerError');
        return $structuralController->internalServerError();
    }

    public function handleBaseException(BaseException $exception,
            DefaultControllerInterface $defaultController = new SampleController(),
            StructuralControllerInterface $structuralController = new FrameworkController()): Response
    {
        if ($exception instanceof ShouldBeLoggedException) {
            $this->logException($exception);
        }
        
        if ($this->config->developmentEnvironment) {
            return $this->callThrowableErrorAction($structuralController, $exception);
        } else {
            return $this->callDefaultControllerError($defaultController, $exception);
        }
    }

    private function callThrowableErrorAction(StructuralControllerInterface $controller, Throwable $throwable): Response
    {
        Router::setActualCleanUrl('framework', 'thowableError');
        return $controller->throwableError($throwable);
    }

    private function callDefaultControllerError(DefaultControllerInterface $controller, BaseException $exception): Response
    {
        $fullControllerName = get_class($controller);
        $controllerName = basename(str_replace('\\', DIRECTORY_SEPARATOR, $fullControllerName));
        Router::setActualCleanUrl(str_replace('Controller', '', $controllerName), 'error');
        return $controller->error($exception->getMessage(), $exception->getResponseType());
    }

    public function handleThrowableError(Throwable $throwable,
            StructuralControllerInterface $structuralController = new FrameworkController()): Response
    {
        BufferManager::clear();
        
        $this->logException($throwable);
        
        if ($this->config->developmentEnvironment) {
            return $this->callThrowableErrorAction($structuralController, $throwable);
        } else {
            return $this->callInternalServerErrorAction($structuralController);
        }
    }

    private function logException(Throwable $exception): void
    {
        $context = [
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        
        if ($this->config->logVerboseActive) {
            $context['trace'] = $exception->getTrace();
        }
        
        $this->logger->error($exception->getMessage(), $context);
    }
		
    public function showErrorInDevelopmentEnvironment(): void
    {
        if ($this->config->developmentEnvironment) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL & ~E_DEPRECATED);
        }
    }
	
    public static function handleCommandLineInterfaceNonThrowableError(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            echo "Error ($errno): $errstr in $errfile on line $errline" . PHP_EOL;
            exit(1);
        });
	}
}

<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\Exceptions\PageNotFoundException;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Authentication;
use SismaFramework\Core\HttpClasses\Request;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Dispatcher
{

    const CONTENT_TYPE_DECLARATION = 'Content-type: ';

    public static string $selectedModule = '';
    private static int $reloadAttempts = 0;
    private Request $request;
    private string $path;
    private $streamContex = null;
    private array $pathParts;
    private bool $defaultControllerInjected = false;
    private bool $defaultActionInjected = false;
    private ?BaseController $controllerInstance = null;
    private bool $defaultControllerChecked = false;
    private bool $defaultActionChecked = false;
    private string $controllerName;
    private array $constructorArguments;
    private string $action;
    private array $actionArguments;
    private \ReflectionClass $reflectionController;
    private \ReflectionMethod $reflectionConstructor;
    private array $reflectionConstructorArguments;
    private \ReflectionMethod $reflectionAction;
    private array $reflectionActionArguments;

    public function __construct()
    {
        Debugger::startExecutionTimeCalculation();
        Session::start();
        $this->request = new Request;
        $this->path = strtok($this->request->server['REQUEST_URI'], '?');
        if (strlen($this->request->server['QUERY_STRING']) > 0) {
            if (Resource::tryFrom($this->getExtension())) {
                $this->streamContex = $this->request->getStreamContentResource();
            } else {
                Router::reloadWithParseQuery($this->request->server['REQUEST_URI']);
                exit();
            }
        }
        $this->parsePath();
        $this->handle();
    }

    private function parsePath(): void
    {
        $this->pathParts = [];
        if ($this->path == '/') {
            $this->pathParts[] = \Config\DEFAULT_PATH;
            $this->pathParts[] = \Config\DEFAULT_ACTION;
        } else {
            $this->pathParts = array_values(array_filter(explode('/', $this->path), function ($var) {
                        return $var !== "";
                    }));
        }
        $this->parsePathParts();
    }

    private function parsePathParts(): void
    {
        $this->selectModule();
        if (isset($this->pathParts[1])) {
            $this->action = NotationManager::convertToCamelCase($this->pathParts[1]);
        } else {
            $this->defaultActionInjected = true;
            $this->action = $this->pathParts[1] = \Config\DEFAULT_ACTION;
        }
        $this->actionArguments = array_slice($this->pathParts, 2);
    }

    private function selectModule(): void
    {
        foreach (\Config\MODULE_FOLDERS as $module) {
            $this->controllerName = $module . '\\' . \Config\CONTROLLER_NAMESPACE . NotationManager::convertToStudlyCaps($this->pathParts[0] . 'Controller');
            if (($this->checkControllerPresence()) || ((count($this->pathParts) === 2) && (file_exists(\Config\ROOT_PATH . $module . DIRECTORY_SEPARATOR . \Config\APPLICATION_ASSETS_PATH . $this->pathParts[0] . DIRECTORY_SEPARATOR . $this->pathParts[1])))) {
                self::$selectedModule = $module;
                break;
            }
        }
    }

    private function checkControllerPresence(): bool
    {
        return class_exists($this->controllerName);
    }

    private function handle(): void
    {
        if (Resource::tryFrom($this->getExtension())) {
            $this->handleFile();
        } elseif ($this->checkControllerPresence() === true) {
            $this->resolveRouteCall();
        } elseif (($this->pathParts[0] === strtolower(\Config\FIXTURES)) && (\Config\DEVELOPMENT_ENVIRONMENT === true)) {
            $fixtureManager = new FixturesManager();
        } else {
            $this->switchNotFoundActions();
        }
    }

    private function handleFile(): void
    {
        if (file_exists(\Config\ROOT_PATH . implode(DIRECTORY_SEPARATOR, $this->pathParts))) {
            $this->makeResource(\Config\ROOT_PATH . implode(DIRECTORY_SEPARATOR, $this->pathParts));
        } elseif ((count($this->pathParts) === 2) && file_exists(\Config\STRUCTURAL_ASSETS_PATH . $this->path)) {
            $this->makeResource(\Config\STRUCTURAL_ASSETS_PATH . $this->path);
        } elseif ((count($this->pathParts) === 2) && file_exists(\Config\ROOT_PATH . self::$selectedModule . DIRECTORY_SEPARATOR . \Config\APPLICATION_ASSETS_PATH . $this->path)) {
            $this->makeResource(\Config\ROOT_PATH . self::$selectedModule . DIRECTORY_SEPARATOR . \Config\APPLICATION_ASSETS_PATH . $this->path);
        } else {
            $this->switchNotFoundActions();
        }
    }

    private function makeResource(string $filename): void
    {
        header("Expires: " . gmdate('D, d-M-Y H:i:s \G\M\T', time() + 60));
        header(self::CONTENT_TYPE_DECLARATION . Resource::from($this->getExtension())->getMime());
        //echo file_get_contents($filename, false, $this->streamContex);
        readfile($filename, false, $this->streamContex);
        /*$stream = fopen($filename, 'rb', false, $this->streamContex);
        fpassthru($stream);*/
    }

    private function switchNotFoundActions(): void
    {
        if (self::$reloadAttempts < \Config\MAX_RELOAD_ATTEMPTS) {
            $this->reloadDispatcher();
        } else {
            throw new PageNotFoundException($this->path);
        }
    }

    private function reloadDispatcher(): void
    {
        $this->switchPath();
        $this->parsePath();
        $this->handle();
    }

    private function switchPath(): void
    {
        if ($this->defaultControllerChecked && $this->defaultActionChecked) {
            Router::concatenateMetaUrl('/' . $this->pathParts[0]);
            $this->path = '/' . implode('/', array_slice($this->pathParts, 2));
            $this->defaultControllerChecked = $this->defaultActionChecked = false;
            self::$reloadAttempts++;
        } elseif ($this->defaultControllerChecked === false) {
            array_unshift($this->pathParts, \Config\DEFAULT_PATH);
            $this->defaultControllerInjected = true;
            if ($this->defaultActionInjected) {
                $this->defaultActionInjected = false;
                array_pop($this->pathParts);
            }
            $this->path = '/' . implode('/', $this->pathParts);
            $this->defaultControllerChecked = true;
        } elseif ($this->defaultActionChecked === false) {
            $this->defaultControllerInjected = false;
            $this->pathParts = [$this->pathParts[1], \Config\DEFAULT_ACTION, ...array_slice($this->pathParts, 2)];
            $this->path = '/' . implode('/', $this->pathParts);
            $this->defaultActionChecked = true;
        }
    }

    public function getExtension(): string
    {
        $splittedPath = explode('.', $this->path);
        return strtolower(end($splittedPath));
    }

    private function resolveRouteCall(): void
    {
        if ($this->checkActionPresenceInController()) {
            $this->instanceControllerClass();
            $this->callControllerMethod();
        } else {
            $this->switchNotFoundActions();
        }
    }

    private function checkActionPresenceInController(): bool
    {
        Router::setActualCleanUrl($this->pathParts[0], $this->pathParts[1]);
        if ($this->defaultControllerInjected && (count($this->pathParts) === 2) && (str_contains(\Config\ROOT_PATH, $this->pathParts[1]) === false)) {
            return is_callable([new $this->controllerName, $this->action]);
        } else {
            return method_exists($this->controllerName, $this->action);
        }
    }

    private function instanceControllerClass(): void
    {
        $this->getControllerConstructorArguments();
        if (count($this->reflectionConstructorArguments) == 0) {
            $this->controllerInstance = new $this->controllerName;
        } else {
            $this->constructorArguments[] = $this->prevAccessToken;
            $this->constructorArguments[] = $this->nextAccessToken;
            $this->getAutoDependecyInjectionClass();
            $this->controllerInstance = $this->reflectionController->newInstanceArgs($this->constructorArguments);
        }
    }

    private function getControllerConstructorArguments(): void
    {
        $this->reflectionController = new \ReflectionClass($this->controllerName);
        $this->reflectionConstructor = $this->reflectionController->getConstructor();
        $this->reflectionConstructorArguments = $this->reflectionConstructor->getParameters();
    }

    private function getAutoDependecyInjectionClass(): void
    {
        foreach ($this->reflectionConstructorArguments as $argument) {
            $argumentType = $argument->getType();
            if ($argumentType->isBuiltin() === false) {
                $className = $argumentType->getName();
                $this->constructorArguments[] = new $className();
            } else {
                throw new InvalidArgumentException();
            }
        }
    }

    private function callControllerMethod(): void
    {
        $this->getActionArguments();
        if (count($this->reflectionActionArguments) > 0) {
            $this->callControllerMethodWithArguments();
        } else {
            $currentAction = $this->action;
            $this->controllerInstance->$currentAction();
        }
    }

    private function getActionArguments(): void
    {
        if (method_exists($this->controllerInstance, $this->action)) {
            $this->reflectionAction = $this->reflectionController->getMethod($this->action);
            $this->reflectionActionArguments = $this->reflectionAction->getParameters();
        } else {
            $this->reflectionActionArguments = [];
        }
    }

    private function callControllerMethodWithArguments(): void
    {
        $this->setArrayOddToKeyEvenToValue();
        $this->parseArgsAssociativeArray();
        call_user_func_array([$this->controllerInstance, $this->action], $this->actionArguments);
    }

    private function setArrayOddToKeyEvenToValue(): void
    {
        $odd = $even = [];
        while (count($this->actionArguments) > 1) {
            $odd[] = NotationManager::convertToCamelCase(array_shift($this->actionArguments));
            $even[] = array_shift($this->actionArguments);
        }
        $this->actionArguments = array_combine($odd, $even);
    }

    private function parseArgsAssociativeArray(): void
    {
        $currentActionArguments = [];
        foreach ($this->reflectionActionArguments as $argument) {
            if ($argument->getType()->getName() === Request::class) {
                $currentActionArguments[$argument->name] = $this->request;
            } elseif ($argument->getType()->getName() === Authentication::class) {
                $currentActionArguments[$argument->name] = new Authentication($this->request);
            } elseif (array_key_exists($argument->name, $this->actionArguments)) {
                $reflectionType = $argument->getType();
                $currentActionArguments[$argument->name] = Parser::parseValue($reflectionType, $this->actionArguments[$argument->name]);
            } elseif ($argument->isDefaultValueAvailable()) {
                $currentActionArguments[$argument->name] = $argument->getDefaultValue();
            } else {
                throw new InvalidArgumentException();
            }
        }
        $this->actionArguments = $currentActionArguments;
    }

}

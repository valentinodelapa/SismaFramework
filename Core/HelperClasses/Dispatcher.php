<?php

namespace Sisma\Core\HelperClasses;

use Sisma\Core\BaseClasses\BaseController;
use Sisma\Core\Exceptions\InvalidArgumentsException;
use Sisma\Core\Exceptions\PageNotFoundException;
use Sisma\Core\HelperClasses\Router;
use Sisma\Core\HttpClasses\Authentication;
use Sisma\Core\HttpClasses\Request;

class Dispatcher
{

    use \Sisma\Core\Traits\ParseValue;

    private static int $reloadAttempts = 0;
    private Request $request;
    private string $path;
    private array $pathParts;
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
        Session::start();
        $this->request = new Request;
        $this->path = $this->request->server['REQUEST_URI'];
        if (empty($this->request->server['QUERY_STRING'])) {
            $this->parsePath();
            $this->handle();
        } else {
            Router::reloadWithParseQuery($this->path);
        }
    }

    private function parsePath(): void
    {
        if ($this->path == '/') {
            $this->controllerName = \Sisma\Core\DEFAULT_CONTROLLER_NAMESPACE;
            $this->action = \Sisma\Core\DEFAULT_ACTION;
            $this->actionArguments = [];
        } else {
            $this->pathParts = array_values(array_filter(explode('/', $this->path), function($var)
            {
                return $var !== "";
            }));
            $this->parsePathParts();
        }
    }

    private function parsePathParts(): void
    {
        $this->controllerName = \Sisma\Core\CONTROLLER_NAMESPACE . self::convertToStudlyCaps($this->pathParts[0] . 'Controller');
        $this->action = (isset($this->pathParts[1])) ? self::convertToCamelCase($this->pathParts[1]) : \Sisma\Core\DEFAULT_ACTION;
        $this->actionArguments = array_slice($this->pathParts, 2);
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    private static function convertToCamelCase(string $string): string
    {
        return lcfirst(self::convertToStudlyCaps($string));
    }

    private function handle(): void
    {
        $this->instanceControllerClass();
        if ($this->controllerInstance instanceof $this->controllerName) {
            $this->executeControllerAction();
        } elseif (($this->pathParts[0] === 'fixture') && (\Sisma\Core\DEVELOPMENT_ENVIRONMENT === true)) {
            new FixturesManager();
        } elseif (file_exists(\Sisma\Core\CORE_ASSETS_PATH . $this->pathParts[0] . '\\' . $this->action)) {
            header('Content-type: ' . array_search($this->pathParts[0], \Sisma\Core\ASSET_FOLDERS));
            echo file_get_contents(\Sisma\Core\CORE_ASSETS_PATH . $this->pathParts[0] . '/' . $this->action);
        } elseif (file_exists(\Sisma\Core\APPLICATION_ASSETS_PATH . $this->pathParts[0] . '\\' . $this->action)) {
            header('Content-type: ' . array_search($this->pathParts[0], \Sisma\Core\ASSET_FOLDERS));
            echo file_get_contents(\Sisma\Core\APPLICATION_ASSETS_PATH . $this->pathParts[0] . '/' . $this->action);
        } elseif (self::$reloadAttempts < \Sisma\Core\MAX_RELOAD_ATTEMPTS) {
            $this->reloadDispatcher();
        } else {
            throw new PageNotFoundException();
        }
    }

    private function instanceControllerClass(): void
    {
        if ($this->checkControllerPresence() === true) {
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
    }

    private function checkControllerPresence(): bool
    {
        return class_exists($this->controllerName);
    }

    private function getControllerConstructorArguments()
    {
        $this->reflectionController = new \ReflectionClass($this->controllerName);
        $this->reflectionConstructor = $this->reflectionController->getConstructor();
        $this->reflectionConstructorArguments = $this->reflectionConstructor->getParameters();
    }

    private function getAutoDependecyInjectionClass()
    {
        foreach ($this->reflectionConstructorArguments as $argument) {
            $argumentType = $argument->getType();
            if ($argumentType->isBuiltin() === false) {
                $className = $argumentType->getName();
                $this->constructorArguments[] = new $className();
            } else {
                throw new InvalidArgumentsException();
            }
        }
    }

    private function reloadDispatcher()
    {
        $this->switchPath();
        $this->parsePath();
        $this->handle();
    }

    private function switchPath()
    {
        if ($this->defaultControllerChecked && $this->defaultActionChecked) {
            Router::concatenateMetaPath('/' . $this->pathParts[0]);
            $this->path = '/' . implode('/', array_slice($this->pathParts, 2));
            $this->defaultControllerChecked = $this->defaultActionChecked = false;
            self::$reloadAttempts++;
        } elseif ($this->defaultControllerChecked === false) {
            array_unshift($this->pathParts, \Sisma\Core\DEFAULT_PATH);
            $this->path = '/' . implode('/', $this->pathParts);
            $this->defaultControllerChecked = true;
        } elseif ($this->defaultActionChecked === false) {
            $this->pathParts = [$this->pathParts[1], \Sisma\Core\DEFAULT_ACTION, ...array_slice($this->pathParts, 2)];
            $this->path = '/' . implode('/', $this->pathParts);
            $this->defaultActionChecked = true;
        }
    }

    private function executeControllerAction(): void
    {
        if ($this->checkActionPresenceInController()) {
            $this->callControllerMethod();
        } elseif (self::$reloadAttempts < \Sisma\Core\MAX_RELOAD_ATTEMPTS) {
            $this->reloadDispatcher();
        } else {
            throw new PageNotFoundException();
        }
    }

    private function checkActionPresenceInController(): bool
    {
        return method_exists($this->controllerName, $this->action);
    }

    private function callControllerMethod(): void
    {
        $this->getActionArguments();
        if (count($this->reflectionActionArguments) > 0) {
            $this->callControllerMethodWithArguments();
        } else {
            $action = $this->action;
            $this->controllerInstance->$action();
        }
    }

    private function getActionArguments(): void
    {
        $this->reflectionAction = $this->reflectionController->getMethod($this->action);
        $this->reflectionActionArguments = $this->reflectionAction->getParameters();
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
            $odd[] = array_shift($this->actionArguments);
            $even[] = array_shift($this->actionArguments);
        }
        $this->actionArguments = array_combine($odd, $even);
    }

    private function parseArgsAssociativeArray(): void
    {
        $actionArguments = [];
        foreach ($this->reflectionActionArguments as $argument) {
            if ($argument->getType()->getName() === Request::class) {
                $actionArguments[$argument->name] = $this->request;
            } elseif ($argument->getType()->getName() === Authentication::class) {
                $actionArguments[$argument->name] = new Authentication($this->request);
            } elseif (array_key_exists($argument->name, $this->actionArguments)) {
                $reflectionType = $argument->getType();
                $actionArguments[$argument->name] = $this->parseValue($reflectionType, $this->actionArguments[$argument->name]);
            } elseif ($argument->isOptional()) {
                $actionArguments[$argument->name] = $argument->getDefaultValue();
            } else {
                throw new InvalidArgumentsException();
            }
        }
        $this->actionArguments = $actionArguments;
    }

}

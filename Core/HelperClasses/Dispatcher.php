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

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Exceptions\BadRequestException;
use SismaFramework\Core\Exceptions\PageNotFoundException;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\ResourceMaker;
use SismaFramework\Core\Interfaces\Services\SitemapMakerInterface;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Security\HttpClasses\Authentication;

/**
 *
 * @author Valentino de Lapa
 */
class Dispatcher
{

    private ResourceMaker $resourceMaker;
    private FixturesManager $fixturesManager;
    private DataMapper $dataMapper;
    private SitemapMakerInterface $sitemapBuider;
    private static int $reloadAttempts = 0;
    private Request $request;
    private string $originalPath;
    private string $path;
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
    private static string $applicationAssetsPath = \Config\APPLICATION_ASSETS_PATH;
    private static string $controllerNamespace = \Config\CONTROLLER_NAMESPACE;
    private static string $defaultAction = \Config\DEFAULT_ACTION;
    private static string $defaultPath = \Config\DEFAULT_PATH;
    private static int $maxReoladAttempts = \Config\MAX_RELOAD_ATTEMPTS;
    private static string $rootPath = \Config\ROOT_PATH;
    private static string $structuralAssetsPath = \Config\STRUCTURAL_ASSETS_PATH;

    public function __construct(Request $request = new Request,
            ResourceMaker $resourceMaker = new ResourceMaker,
            FixturesManager $fixtureManager = new FixturesManager(),
            DataMapper $dataMapper = new DataMapper())
    {
        $this->request = $request;
        $this->resourceMaker = $resourceMaker;
        $this->fixturesManager = $fixtureManager;
        $this->dataMapper = $dataMapper;
    }

    public static function setCustomRootPath(string $customRootPath): void
    {
        self::$rootPath = $customRootPath;
    }

    public function setSitemapMaker(SitemapMakerInterface $sitemapBuilder): void
    {
        $this->sitemapBuider = $sitemapBuilder;
    }

    public function run($router = new Router()): Response
    {
        $this->originalPath = $this->path = strtok($this->request->server['REQUEST_URI'], '?');
        if (strlen($this->request->server['QUERY_STRING']) > 0) {
            if ($this->resourceMaker->isAcceptedResourceFile($this->path)) {
                $this->resourceMaker->setStreamContex();
            } else {
                return $router->reloadWithParsedQueryString();
            }
        }
        $this->parsePath();
        return $this->handle();
    }

    private function parsePath(): void
    {
        $this->pathParts = [];
        if ($this->path == '/') {
            $this->pathParts[] = self::$defaultPath;
            $this->pathParts[] = self::$defaultAction;
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
        } elseif ($this->fixturesManager->isFixtures($this->pathParts) === false) {
            $this->defaultActionInjected = true;
            $this->action = $this->pathParts[1] = self::$defaultAction;
        }
        $this->actionArguments = array_slice($this->pathParts, 2);
    }

    private function selectModule(): void
    {
        ModuleManager::initializeApplicationModule();
        foreach (ModuleManager::getModuleList() as $module) {
            $this->controllerName = $module . '\\' . self::$controllerNamespace . NotationManager::convertToStudlyCaps($this->pathParts[0] . 'Controller');
            if ($this->checkControllerPresence() || $this->checkFilePresence($module)) {
                ModuleManager::setApplicationModule($module);
                break;
            }
        }
    }

    private function checkControllerPresence(): bool
    {
        return class_exists($this->controllerName);
    }

    private function checkFilePresence(string $module): bool
    {
        return (count($this->pathParts) === 2) && (file_exists(self::$rootPath . $module . DIRECTORY_SEPARATOR . self::$applicationAssetsPath . $this->pathParts[0] . DIRECTORY_SEPARATOR . $this->pathParts[1]));
    }

    private function handle(): Response
    {
        if ($this->resourceMaker->isRobotsFile($this->pathParts)) {
            return $this->resourceMaker->makeRobotsFile();
        } elseif (isset($this->sitemapBuider) && ($this->sitemapBuider->isSitemap($this->pathParts))) {
            return $this->sitemapBuider->generate();
        } elseif ($this->resourceMaker->isAcceptedResourceFile($this->path)) {
            return $this->handleFile();
        } elseif ($this->checkControllerPresence() === true) {
            return $this->resolveRouteCall();
        } elseif ($this->fixturesManager->isFixtures($this->pathParts)) {
            return $this->fixturesManager->run();
        } else {
            return $this->switchNotFoundActions();
        }
    }

    private function handleFile(): Response
    {
        if (file_exists(self::$rootPath . implode(DIRECTORY_SEPARATOR, $this->pathParts))) {
            return $this->resourceMaker->makeResource(self::$rootPath . implode(DIRECTORY_SEPARATOR, $this->pathParts));
        } elseif (file_exists(self::$structuralAssetsPath . implode(DIRECTORY_SEPARATOR, $this->pathParts))) {
            return $this->resourceMaker->makeResource(self::$structuralAssetsPath . implode(DIRECTORY_SEPARATOR, $this->pathParts));
        } elseif (file_exists(self::$rootPath . ModuleManager::getApplicationModule() . DIRECTORY_SEPARATOR . self::$applicationAssetsPath . implode(DIRECTORY_SEPARATOR, $this->pathParts))) {
            return $this->resourceMaker->makeResource(self::$rootPath . ModuleManager::getApplicationModule() . DIRECTORY_SEPARATOR . self::$applicationAssetsPath . implode(DIRECTORY_SEPARATOR, $this->pathParts));
        } else {
            return $this->switchNotFoundActions();
        }
    }

    private function switchNotFoundActions(): Response
    {
        if (self::$reloadAttempts < self::$maxReoladAttempts) {
            return $this->reloadDispatcher();
        } else {
            throw new PageNotFoundException($this->originalPath);
        }
    }

    private function reloadDispatcher(): Response
    {
        $this->switchPath();
        $this->parsePath();
        return $this->handle();
    }

    private function switchPath(): void
    {
        if ($this->defaultControllerChecked && $this->defaultActionChecked) {
            Router::concatenateMetaUrl('/' . $this->pathParts[0]);
            $this->path = '/' . implode('/', array_slice($this->pathParts, 2));
            $this->defaultControllerChecked = $this->defaultActionChecked = false;
            self::$reloadAttempts++;
        } elseif ($this->defaultControllerChecked === false) {
            array_unshift($this->pathParts, self::$defaultPath);
            $this->defaultControllerInjected = true;
            if ($this->defaultActionInjected) {
                $this->defaultActionInjected = false;
                array_pop($this->pathParts);
            }
            $this->path = '/' . implode('/', $this->pathParts);
            $this->defaultControllerChecked = true;
        } elseif ($this->defaultActionChecked === false) {
            $this->defaultControllerInjected = false;
            $this->pathParts = [$this->pathParts[1], self::$defaultAction, ...array_slice($this->pathParts, 2)];
            $this->path = '/' . implode('/', $this->pathParts);
            $this->defaultActionChecked = true;
        }
    }

    private function resolveRouteCall(): Response
    {
        $this->getControllerConstructorArguments();
        if ($this->checkActionPresenceInController()) {
            $this->controllerInstance = $this->instanceControllerClass();
            return $this->callControllerMethod();
        } else {
            return $this->switchNotFoundActions();
        }
    }

    private function getControllerConstructorArguments(): void
    {
        $this->reflectionController = new \ReflectionClass($this->controllerName);
        $this->reflectionConstructor = $this->reflectionController->getConstructor();
        $this->reflectionConstructorArguments = $this->reflectionConstructor->getParameters();
    }

    private function checkActionPresenceInController(): bool
    {
        Router::setActualCleanUrl($this->pathParts[0], $this->pathParts[1]);
        $methodExsist = false;
        if (method_exists($this->controllerName, $this->action)) {
            $this->reflectionAction = $this->reflectionController->getMethod($this->action);
            ModuleManager::setApplicationModuleByClassName($this->reflectionAction->getDeclaringClass()->getName());
            $methodExsist = true;
        }
        if ($this->defaultControllerInjected && (count($this->pathParts) === 2) && (str_contains(self::$rootPath, $this->pathParts[1]) === false)) {
            return is_callable([$this->instanceControllerClass(), $this->action]);
        } else {
            return $methodExsist;
        }
    }

    private function instanceControllerClass(): BaseController
    {
        if ((count($this->reflectionConstructorArguments) == 0) || (is_a($this->reflectionConstructorArguments[0]->getType()->getName(), DataMapper::class, true))) {
            return new $this->controllerName($this->dataMapper);
        } else {
            $this->getAutoDependecyInjectionClass();
            return $this->reflectionController->newInstanceArgs($this->constructorArguments);
        }
    }

    private function getAutoDependecyInjectionClass(): void
    {
        foreach ($this->reflectionConstructorArguments as $argument) {
            $argumentType = $argument->getType();
            if (is_a($argumentType->getName(), DataMapper::class, true)) {
                $this->constructorArguments[] = $this->dataMapper;
            } elseif ($argumentType->isBuiltin() === false) {
                $className = $argumentType->getName();
                $this->constructorArguments[] = new $className();
            } else {
                throw new BadRequestException($argumentType->getName());
            }
        }
    }

    private function callControllerMethod(): Response
    {
        $this->getActionArguments();
        if (count($this->reflectionActionArguments) > 0) {
            return $this->callControllerMethodWithArguments();
        } else {
            $currentAction = $this->action;
            return $this->controllerInstance->$currentAction();
        }
    }

    private function getActionArguments(): void
    {
        if (method_exists($this->controllerInstance, $this->action)) {
            $this->reflectionActionArguments = $this->reflectionAction->getParameters();
        } else {
            $this->reflectionActionArguments = [];
        }
    }

    private function callControllerMethodWithArguments(): Response
    {
        $this->setArrayOddToKeyEvenToValue();
        $this->parseArgsAssociativeArray();
        return call_user_func_array([$this->controllerInstance, $this->action], $this->actionArguments);
    }

    private function setArrayOddToKeyEvenToValue(): void
    {
        $odd = $even = [];
        while (count($this->actionArguments) > 1) {
            $odd[] = NotationManager::convertToCamelCase(array_shift($this->actionArguments));
            $even[] = array_shift($this->actionArguments);
        }
        $this->actionArguments = $this->combineArrayToAssociativeArray($odd, $even);
        $this->request->query = $this->actionArguments;
    }

    private function combineArrayToAssociativeArray(array $keys, array $values): array
    {
        $result = [];
        foreach ($keys as $index => $key) {
            if (array_key_exists($key, $result)) {
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $values[$index];
            } else {
                $result[$key] = $values[$index];
            }
        }
        return $result;
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
                $currentActionArguments[$argument->name] = Parser::parseValue($reflectionType, $this->actionArguments[$argument->name], true, $this->dataMapper);
            } elseif ($argument->isDefaultValueAvailable()) {
                $currentActionArguments[$argument->name] = $argument->getDefaultValue();
            } else {
                throw new BadRequestException($argument->name);
            }
        }
        $this->actionArguments = $currentActionArguments;
    }
}

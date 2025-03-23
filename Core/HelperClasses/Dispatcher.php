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

use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Exceptions\BadRequestException;
use SismaFramework\Core\Exceptions\PageNotFoundException;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\ResourceMaker;
use SismaFramework\Core\Interfaces\Services\CrawlComponentMakerInterface;
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
    private array $crawlComponentMakerList = [];
    private CrawlComponentMakerInterface $currentCrawlComponentMaker;
    private int $reloadAttempts = 0;
    private Request $request;
    private string $originalPath;
    private string $path;
    private array $pathParts;
    private array $cleanPathParts;
    private bool $defaultControllerInjected = false;
    private bool $defaultActionInjected = false;
    private ?BaseController $controllerInstance = null;
    private bool $defaultControllerChecked = false;
    private bool $defaultActionChecked = false;
    private string $pathController;
    private string $controllerClassName;
    private array $constructorArguments;
    private string $pathAction;
    private string $parsedAction;
    private array $actionArguments = [];
    private \ReflectionClass $reflectionController;
    private \ReflectionMethod $reflectionConstructor;
    private array $reflectionConstructorArguments;
    private \ReflectionMethod $reflectionAction;
    private array $reflectionActionArguments;
    private BaseConfig $config;

    public function __construct(Request $request = new Request,
            ResourceMaker $resourceMaker = new ResourceMaker,
            FixturesManager $fixtureManager = new FixturesManager(),
            DataMapper $dataMapper = new DataMapper(),
            ?BaseConfig $config = null)
    {
        $this->request = $request;
        $this->resourceMaker = $resourceMaker;
        $this->fixturesManager = $fixtureManager;
        $this->dataMapper = $dataMapper;
        $this->config = $config ?? BaseConfig::getInstance();
    }

    public function addCrawlComponentMaker(CrawlComponentMakerInterface $crawlComponentMaker): void
    {
        $this->crawlComponentMakerList[] = $crawlComponentMaker;
    }

    public function run($router = new Router()): Response
    {
        $requestUriParts = explode('?', $this->request->server['REQUEST_URI'], 2);
        $this->originalPath = $this->path = $requestUriParts[0];
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
        $this->pathParts = explode('/', rtrim($this->path, '/'));
        $this->cleanPathParts = array_values(array_filter($this->pathParts, function ($var) {
                    return $var !== "";
                }));
        $cleanPathPartsNumber = count($this->cleanPathParts);
        if ($cleanPathPartsNumber === 0) {
            $this->pathController = $this->config->defaultPath;
            $this->parsedAction = $this->pathAction = $this->config->defaultAction;
        } else {
            $this->parsePathWithMultipleCleanParts($cleanPathPartsNumber);
        }
        $this->selectModule();
    }

    private function parsePathWithMultipleCleanParts(int $cleanPathPartsNumber)
    {
        $this->pathController = $this->cleanArrayShift($this->pathParts);
        if ($cleanPathPartsNumber === 1) {
            $this->pathAction = $this->config->defaultAction;
            if (($this->resourceMaker->isAcceptedResourceFile($this->path) === false) &&
                    ($this->fixturesManager->isFixtures($this->pathParts) === false)) {
                $this->defaultActionInjected = true;
                $this->parsedAction = NotationManager::convertToCamelCase($this->config->defaultAction);
            }
        } else {
            $this->pathAction = $this->cleanArrayShift($this->pathParts);
            $this->parsedAction = NotationManager::convertToCamelCase($this->pathAction);
            $this->actionArguments = $this->pathParts;
        }
    }

    private function cleanArrayShift(array &$array): ?string
    {
        $element = '';
        while ($element === '') {
            $element = array_shift($array);
        }
        return $element;
    }

    private function selectModule(): void
    {
        ModuleManager::initializeApplicationModule();
        foreach (ModuleManager::getModuleList() as $module) {
            $this->controllerClassName = $module . '\\' . $this->config->controllerNamespace . NotationManager::convertToStudlyCaps($this->pathController . 'Controller');
            if ($this->checkControllerPresence() || $this->checkFilePresence($module)) {
                ModuleManager::setApplicationModule($module);
                break;
            }
        }
    }

    private function checkControllerPresence(): bool
    {
        return class_exists($this->controllerClassName);
    }

    private function checkFilePresence(string $module): bool
    {
        return ($this->resourceMaker->isAcceptedResourceFile($this->path) && (file_exists($this->config->rootPath . $module . DIRECTORY_SEPARATOR . $this->config->applicationAssetsPath . implode(DIRECTORY_SEPARATOR, $this->cleanPathParts))));
    }

    private function handle(): Response
    {
        if ($this->isCrawlComponent()) {
            return $this->currentCrawlComponentMaker->generate();
        } elseif ($this->resourceMaker->isAcceptedResourceFile($this->path)) {
            return $this->handleFile();
        } elseif ($this->checkControllerPresence() === true) {
            return $this->resolveRouteCall();
        } elseif ($this->fixturesManager->isFixtures($this->cleanPathParts)) {
            return $this->fixturesManager->run();
        } else {
            return $this->switchNotFoundActions();
        }
    }

    private function isCrawlComponent(): bool
    {
        foreach ($this->crawlComponentMakerList as $crawlComponentMaker) {
            if ($crawlComponentMaker->isCrawlComponent($this->cleanPathParts)) {
                $this->currentCrawlComponentMaker = $crawlComponentMaker;
                return true;
            }
        }
        return false;
    }

    private function handleFile(): Response
    {
        if (file_exists($this->config->rootPath . implode(DIRECTORY_SEPARATOR, $this->cleanPathParts))) {
            return $this->resourceMaker->makeResource($this->config->rootPath . implode(DIRECTORY_SEPARATOR, $this->cleanPathParts));
        } elseif (file_exists($this->config->structuralAssetsPath . implode(DIRECTORY_SEPARATOR, $this->cleanPathParts))) {
            return $this->resourceMaker->makeResource($this->config->structuralAssetsPath . implode(DIRECTORY_SEPARATOR, $this->cleanPathParts));
        } elseif (file_exists($this->config->rootPath . ModuleManager::getApplicationModule() . DIRECTORY_SEPARATOR . $this->config->applicationAssetsPath . implode(DIRECTORY_SEPARATOR, $this->cleanPathParts))) {
            return $this->resourceMaker->makeResource($this->config->rootPath . ModuleManager::getApplicationModule() . DIRECTORY_SEPARATOR . $this->config->applicationAssetsPath . implode(DIRECTORY_SEPARATOR, $this->cleanPathParts));
        } else {
            return $this->switchNotFoundActions();
        }
    }

    private function switchNotFoundActions(): Response
    {
        if ($this->reloadAttempts < $this->config->maxReloadAttempts) {
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
            $this->slicePathElement();
        } elseif ($this->defaultControllerChecked === false) {
            $this->injectDefaultController();
        } elseif ($this->defaultActionChecked === false) {
            $this->injectDefaultAction();
        }
    }

    public function slicePathElement(): void
    {
        if ($this->resourceMaker->isAcceptedResourceFile($this->pathController)) {
            throw new PageNotFoundException($this->originalPath);
        } else {
            Router::concatenateMetaUrl('/' . $this->pathController);
            $this->path = '/' . implode('/', $this->pathParts);
            $this->defaultControllerChecked = $this->defaultActionChecked = false;
            $this->reloadAttempts++;
        }
    }

    private function injectDefaultController(): void
    {
        array_unshift($this->pathParts, $this->config->defaultPath, $this->pathController, $this->pathAction);
        $this->defaultControllerInjected = true;
        if ($this->defaultActionInjected) {
            $this->defaultActionInjected = false;
            array_pop($this->pathParts);
        }
        $this->path = '/' . implode('/', $this->pathParts);
        $this->defaultControllerChecked = true;
    }

    private function injectDefaultAction(): void
    {
        $this->defaultControllerInjected = false;
        $this->pathParts = [$this->pathAction, $this->config->defaultAction, ...$this->pathParts];
        $this->path = '/' . implode('/', $this->pathParts);
        $this->defaultActionChecked = true;
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
        $this->reflectionController = new \ReflectionClass($this->controllerClassName);
        $this->reflectionConstructor = $this->reflectionController->getConstructor();
        $this->reflectionConstructorArguments = $this->reflectionConstructor->getParameters();
    }

    private function checkActionPresenceInController(): bool
    {
        Router::setActualCleanUrl($this->pathController, $this->parsedAction);
        $methodExsist = false;
        if (method_exists($this->controllerClassName, $this->parsedAction)) {
            $this->reflectionAction = $this->reflectionController->getMethod($this->parsedAction);
            ModuleManager::setApplicationModuleByClassName($this->reflectionAction->getDeclaringClass()->getName());
            $methodExsist = true;
        }
        if ($this->defaultControllerInjected && (count($this->pathParts) === 0) && (str_contains($this->config->rootPath, $this->pathAction) === false)) {
            return is_callable([$this->instanceControllerClass(), $this->parsedAction]);
        } else {
            return $methodExsist;
        }
    }

    private function instanceControllerClass(): BaseController
    {
        if ((count($this->reflectionConstructorArguments) == 0) || (is_a($this->reflectionConstructorArguments[0]->getType()->getName(), DataMapper::class, true))) {
            return new $this->controllerClassName($this->dataMapper);
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
            $currentAction = $this->parsedAction;
            return $this->controllerInstance->$currentAction();
        }
    }

    private function getActionArguments(): void
    {
        if (method_exists($this->controllerInstance, $this->parsedAction)) {
            $this->reflectionActionArguments = $this->reflectionAction->getParameters();
        } else {
            $this->reflectionActionArguments = [];
        }
    }

    private function callControllerMethodWithArguments(): Response
    {
        $this->setArrayOddToKeyEvenToValue();
        $this->parseArgsAssociativeArray();
        return call_user_func_array([$this->controllerInstance, $this->parsedAction], $this->actionArguments);
    }

    private function setArrayOddToKeyEvenToValue(): void
    {
        $odd = $even = [];
        while (count($this->actionArguments) > 1) {
            $odd[] = NotationManager::convertToCamelCase($this->cleanArrayShift($this->actionArguments));
            $even[] = urldecode(array_shift($this->actionArguments));
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

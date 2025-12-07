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

use SismaFramework\Core\Exceptions\PageNotFoundException;
use SismaFramework\Core\HelperClasses\Dispatcher\ActionArgumentsParser;
use SismaFramework\Core\HelperClasses\Dispatcher\ControllerFactory;
use SismaFramework\Core\HelperClasses\Dispatcher\ResourceHandler;
use SismaFramework\Core\HelperClasses\Dispatcher\RouteResolver;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\Interfaces\Controllers\CallableController;
use SismaFramework\Core\Interfaces\Services\CrawlComponentMakerInterface;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class Dispatcher
{

    private Request $request;
    private RouteResolver $routeResolver;
    private ControllerFactory $controllerFactory;
    private ActionArgumentsParser $actionArgumentsParser;
    private ResourceHandler $resourceHandler;
    private DataMapper $dataMapper;
    private array $crawlComponentMakerList = [];
    private CrawlComponentMakerInterface $currentCrawlComponentMaker;

    public function __construct(Request $request = new Request(),
            DataMapper $dataMapper = new DataMapper(),
            RouteResolver $routeResolver = new RouteResolver(),
            ResourceHandler $resourceHandler = new ResourceHandler(),
            ?ControllerFactory $controllerFactory = null,
            ?ActionArgumentsParser $actionArgumentsParser = null)
    {
        $this->request = $request;
        $this->dataMapper = $dataMapper;
        $this->routeResolver = $routeResolver;
        $this->controllerFactory = $controllerFactory ?? new ControllerFactory($this->dataMapper);
        $this->actionArgumentsParser = $actionArgumentsParser ?? new ActionArgumentsParser($this->request, $this->dataMapper);
        $this->resourceHandler = $resourceHandler;
    }

    public function addCrawlComponentMaker(CrawlComponentMakerInterface $crawlComponentMaker): void
    {
        $this->crawlComponentMakerList[] = $crawlComponentMaker;
    }

    public function run($router = new Router()): Response
    {
        $requestUri = $this->request->server['REQUEST_URI'];
        if ((strlen($this->request->server['QUERY_STRING']) > 0) &&
                ($this->resourceHandler->isResourceFile(explode('?', $requestUri, 2)[0]) === false)) {
            return $router->reloadWithParsedQueryString();
        }
        $this->routeResolver->initialize($requestUri);
        return $this->handle();
    }

    private function handle(): Response
    {
        if ($this->isCrawlComponent($this->routeResolver->getRouteInfo()->cleanPathParts)) {
            return $this->currentCrawlComponentMaker->generate();
        } elseif ($this->resourceHandler->isResourceFile($this->routeResolver->getOriginalPath())) {
            return $this->resourceHandler->handleResourceFile($this->routeResolver->getRouteInfo()->cleanPathParts) ?? $this->handleNotFound();
        } elseif (class_exists($this->routeResolver->getRouteInfo()->controllerClassName)) {
            return $this->dispatchToController();
        } elseif ($this->routeResolver->isFixturesRequest($this->routeResolver->getRouteInfo()->cleanPathParts)) {
            return $this->routeResolver->runFixtures();
        } else {
            return $this->handleNotFound();
        }
    }

    private function isCrawlComponent(array $cleanPathParts): bool
    {
        foreach ($this->crawlComponentMakerList as $crawlComponentMaker) {
            if ($crawlComponentMaker->isCrawlComponent($cleanPathParts)) {
                $this->currentCrawlComponentMaker = $crawlComponentMaker;
                return true;
            }
        }
        return false;
    }

    private function dispatchToController(): Response
    {
        $reflectionController = new \ReflectionClass($this->routeResolver->getRouteInfo()->controllerClassName);
        if ($this->hasValidAction($reflectionController, $this->routeResolver->getRouteInfo()->parsedAction)) {
            return $this->callControllerAction($reflectionController);
        } elseif ($this->isCallableController($reflectionController)) {
            return $this->executeCallableController();
        } else {
            return $this->handleNotFound();
        }
    }

    private function hasValidAction(\ReflectionClass $reflectionController, string $actionName): bool
    {
        if ($reflectionController->hasMethod($actionName)) {
            $method = $reflectionController->getMethod($actionName);
            if ($method->getReturnType()?->getName() === Response::class) {
                Router::setActualCleanUrl($this->routeResolver->getRouteInfo()->pathController, $this->routeResolver->getRouteInfo()->pathAction);
                ModuleManager::setApplicationModuleByClassName($method->getDeclaringClass()->getName());
                return true;
            }
        }

        return false;
    }

    private function callControllerAction(\ReflectionClass $reflectionController): Response
    {
        $controller = $this->controllerFactory->createController($this->routeResolver->getRouteInfo()->controllerClassName);
        $reflectionAction = $reflectionController->getMethod($this->routeResolver->getRouteInfo()->parsedAction);
        $reflectionParameters = $reflectionAction->getParameters();
        if (count($reflectionParameters) === 0) {
            return $controller->{$this->routeResolver->getRouteInfo()->parsedAction}();
        } else {
            $arguments = $this->actionArgumentsParser->parseActionArguments($this->routeResolver->getRouteInfo()->actionArguments, $reflectionParameters);
            return call_user_func_array([$controller, $this->routeResolver->getRouteInfo()->parsedAction], $arguments);
        }
    }

    private function isCallableController(\ReflectionClass $reflectionController): bool
    {
        if ($reflectionController->isSubclassOf(CallableController::class) === false) {
            return false;
        } else {
            Router::setActualCleanUrl(
                    $this->routeResolver->getRouteInfo()->pathController,
                    $this->routeResolver->getRouteInfo()->pathAction
            );
            $fullCallableParts = [$this->routeResolver->getRouteInfo()->pathAction, ...$this->routeResolver->getRouteInfo()->actionArguments];
            return $this->routeResolver->getRouteInfo()->controllerClassName::checkCompatibility($fullCallableParts);
        }
    }

    private function executeCallableController(): Response
    {
        $controller = $this->controllerFactory->createController($this->routeResolver->getRouteInfo()->controllerClassName);
        return $controller->{$this->routeResolver->getRouteInfo()->parsedAction}(...$this->routeResolver->getRouteInfo()->actionArguments);
    }

    private function handleNotFound(): Response
    {
        if ($this->routeResolver->canRetry()) {
            $this->routeResolver->retryWithNextStrategy();
            return $this->handle();
        } else {
            throw new PageNotFoundException($this->routeResolver->getOriginalPath());
        }
    }
}

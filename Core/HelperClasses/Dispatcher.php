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
use SismaFramework\Core\HelperClasses\Dispatcher\RouteInfo;
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
            ?ControllerFactory $controllerFactory = null,
            ?ActionArgumentsParser $actionArgumentsParser = null,
            ResourceHandler $resourceHandler = new ResourceHandler())
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

    public function slicePathElement(): void
    {
        $this->routeResolver->slicePathElement();
    }

    private function handle(): Response
    {
        $routeInfo = $this->routeResolver->getRouteInfo();

        if ($this->isCrawlComponent($routeInfo->cleanPathParts)) {
            return $this->currentCrawlComponentMaker->generate();
        }

        if ($this->resourceHandler->isResourceFile($this->routeResolver->getOriginalPath())) {
            $response = $this->resourceHandler->handleResourceFile($routeInfo->cleanPathParts);
            if ($response !== null) {
                return $response;
            }
            return $this->handleNotFound();
        }

        if (class_exists($routeInfo->controllerClassName)) {
            return $this->dispatchToController($routeInfo);
        }

        if ($this->routeResolver->isFixturesRequest($routeInfo->cleanPathParts)) {
            return $this->routeResolver->runFixtures();
        }

        return $this->handleNotFound();
    }

    private function dispatchToController(RouteInfo $routeInfo): Response
    {
        $reflectionController = new \ReflectionClass($routeInfo->controllerClassName);

        if ($this->hasValidAction($reflectionController, $routeInfo->parsedAction)) {
            return $this->callControllerAction($reflectionController, $routeInfo);
        }

        if ($this->isCallableController($reflectionController, $routeInfo)) {
            return $this->executeCallableController($routeInfo);
        }

        return $this->handleNotFound();
    }

    private function hasValidAction(\ReflectionClass $reflectionController, string $actionName): bool
    {
        Router::setActualCleanUrl(
                $this->routeResolver->getRouteInfo()->pathController,
                $actionName
        );

        if ($reflectionController->hasMethod($actionName)) {
            $method = $reflectionController->getMethod($actionName);
            if ($method->getReturnType()?->getName() === Response::class) {
                ModuleManager::setApplicationModuleByClassName($method->getDeclaringClass()->getName());
                return true;
            }
        }

        return false;
    }

    private function callControllerAction(\ReflectionClass $reflectionController, RouteInfo $routeInfo): Response
    {
        $controller = $this->controllerFactory->createController($routeInfo->controllerClassName);
        $reflectionAction = $reflectionController->getMethod($routeInfo->parsedAction);
        $reflectionParameters = $reflectionAction->getParameters();

        if (count($reflectionParameters) === 0) {
            return $controller->{$routeInfo->parsedAction}();
        }

        $arguments = $this->actionArgumentsParser->parseActionArguments(
                $routeInfo->actionArguments,
                $reflectionParameters
        );

        return call_user_func_array([$controller, $routeInfo->parsedAction], $arguments);
    }

    private function isCallableController(\ReflectionClass $reflectionController, RouteInfo $routeInfo): bool
    {
        if (!$reflectionController->isSubclassOf(CallableController::class)) {
            return false;
        }

        $fullCallableParts = [$routeInfo->pathAction, ...$routeInfo->actionArguments];
        return $routeInfo->controllerClassName::checkCompatibility($fullCallableParts);
    }

    private function executeCallableController(RouteInfo $routeInfo): Response
    {
        $controller = $this->controllerFactory->createController($routeInfo->controllerClassName);
        return $controller->{$routeInfo->parsedAction}(...$routeInfo->actionArguments);
    }

    private function handleNotFound(): Response
    {
        if ($this->routeResolver->canRetry()) {
            $this->routeResolver->retryWithNextStrategy();
            return $this->handle();
        }

        throw new PageNotFoundException($this->routeResolver->getOriginalPath());
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
}

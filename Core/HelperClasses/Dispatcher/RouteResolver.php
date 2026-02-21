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

namespace SismaFramework\Core\HelperClasses\Dispatcher;

use SismaFramework\Core\Exceptions\PageNotFoundException;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Router;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class RouteResolver
{

    private Config $config;
    private ResourceMaker $resourceMaker;
    private string $originalPath;
    private string $path;
    private array $pathParts;
    private array $cleanPathParts;
    private bool $defaultControllerInjected = false;
    private bool $defaultActionInjected = false;
    private bool $defaultControllerChecked = false;
    private bool $defaultActionChecked = false;
    private int $reloadAttempts = 0;
    private string $pathController = '';
    private string $controllerClassName = '';
    private string $pathAction = '';
    private string $parsedAction = '';
    private array $actionArguments = [];

    public function __construct(ResourceMaker $resourceMaker = new ResourceMaker(), ?Config $config = null)
    {
        $this->resourceMaker = $resourceMaker;
        $this->config = $config ?? Config::getInstance();
    }

    public function initialize(string $requestUri): void
    {
        $requestUriParts = explode('?', $requestUri, 2);
        $this->originalPath = $this->path = $requestUriParts[0];
        $this->parsePath();
    }

    public function getRouteInfo(): RouteInfo
    {
        return new RouteInfo(
                $this->pathController,
                $this->controllerClassName,
                $this->pathAction,
                $this->parsedAction,
                $this->actionArguments,
                $this->cleanPathParts
        );
    }

    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    public function canRetry(): bool
    {
        return $this->reloadAttempts < $this->config->maxReloadAttempts;
    }

    public function retryWithNextStrategy(): void
    {
        $this->switchPath();
        $this->parsePath();
    }

    public function slicePathElement(): void
    {
        if ($this->resourceMaker->isAcceptedResourceFile($this->pathController)) {
            throw new PageNotFoundException($this->originalPath);
        } else {
            Router::concatenateMetaUrl($this->pathController);
            $this->path = '/' . implode('/', $this->pathParts);
            $this->defaultControllerChecked = $this->defaultActionChecked = false;
            $this->reloadAttempts++;
        }
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

    private function parsePathWithMultipleCleanParts(int $cleanPathPartsNumber): void
    {
        $this->pathController = $this->cleanArrayShift($this->pathParts);
        if ($cleanPathPartsNumber === 1) {
            $this->pathAction = $this->config->defaultAction;
            $this->parsedAction = NotationManager::convertToCamelCase($this->config->defaultAction);
            if ($this->resourceMaker->isAcceptedResourceFile($this->path) === false) {
                $this->defaultActionInjected = true;
            }
        } else {
            $this->pathAction = $this->cleanArrayShift($this->pathParts);
            $this->parsedAction = NotationManager::convertToCamelCase($this->pathAction);
            $this->actionArguments = $this->pathParts;
        }
    }

    private function cleanArrayShift(array &$array): string
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
        return $this->resourceMaker->isAcceptedResourceFile($this->path) && file_exists($this->config->rootPath . $module . DIRECTORY_SEPARATOR . $this->config->applicationAssetsPath . implode(DIRECTORY_SEPARATOR, $this->cleanPathParts));
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
}

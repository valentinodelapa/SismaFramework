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

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Core\HttpClasses\Response;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class ResourceHandler
{

    private ResourceMaker $resourceMaker;
    private Config $config;

    public function __construct(ResourceMaker $resourceMaker = new ResourceMaker(), ?Config $config = null)
    {
        $this->resourceMaker = $resourceMaker;
        $this->config = $config ?? Config::getInstance();
    }

    public function isResourceFile(string $path): bool
    {
        return $this->resourceMaker->isAcceptedResourceFile($path);
    }

    public function handleResourceFile(array $cleanPathParts): ?Response
    {
        $pathSuffix = implode(DIRECTORY_SEPARATOR, $cleanPathParts);
        $rootFilePath = $this->config->rootPath . $pathSuffix;
        if (file_exists($rootFilePath)) {
            return $this->resourceMaker->makeResource($rootFilePath);
        }
        $structuralPath = $this->config->structuralAssetsPath . $pathSuffix;
        if (file_exists($structuralPath)) {
            return $this->resourceMaker->makeResource($structuralPath);
        }
        $modulePath = $this->config->rootPath . ModuleManager::getApplicationModule() . DIRECTORY_SEPARATOR . $this->config->applicationAssetsPath . $pathSuffix;
        if (file_exists($modulePath)) {
            return $this->resourceMaker->makeResource($modulePath);
        }
        return null;
    }
}

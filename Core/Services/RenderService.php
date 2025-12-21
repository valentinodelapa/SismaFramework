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

namespace SismaFramework\Core\Services;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Localizator;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\BufferManager;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

/**
 * Render Service - Singleton implementation for view rendering operations
 *
 * @author Valentino de Lapa
 */
class RenderService
{
    private static ?RenderService $instance = null;
    
    private bool $isStructural = false;
    private string $view = "";

    private function __construct()
    {
    }

    public static function getInstance(): RenderService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Allows injecting a custom instance for testing purposes
     */
    public static function setInstance(?RenderService $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Resets the singleton instance (useful for testing)
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function generateView(
        string $view,
        array $vars,
        ResponseType $responseType = ResponseType::httpOk,
        Localizator $localizator = new Localizator(),
        Debugger $debugger = new Debugger(),
        ?Config $customConfig = null
    ): Response {
        $config = $customConfig ?? Config::getInstance();
        $debugger->setVars($vars);
        $this->assemblesComponents($view, $localizator, $vars, $config);
        echo $this->generateDebugBar($debugger, $config);
        return new Response($responseType);
    }

    private function assemblesComponents(string $view, Localizator $localizator, array $vars, Config $config): void
    {
        BufferManager::start();
        $this->view = $view;
        $deviceClass = $this->getDeviceClass();
        $viewPath = $this->getViewPath($this->view, $config);
        if ($this->isStructural === false) {
            $locale = $localizator->getPageLocaleArray($this->view);
            extract($locale);
        }
        extract($vars);
        include($viewPath);
    }

    private function getDeviceClass(): string|false
    {
        $request = new Request();
        if (isset($request->server['HTTP_USER_AGENT'])) {
            $ua = $request->server['HTTP_USER_AGENT'];
            return (stristr($ua, 'mobile') !== false) ? 'mobile' : 'desktop';
        } else {
            return false;
        }
    }

    private function getViewPath(string $view, Config $config): string
    {
        if ($this->isStructural) {
            return $config->structuralViewsPath . $view . '.' . Resource::php->value;
        } else {
            return ModuleManager::getExistingFilePath($config->viewsPath . $view, Resource::php);
        }
    }

    private function generateDebugBar(Debugger $debugger, Config $config): string
    {
        if ($config->developmentEnvironment) {
            return $debugger->generateDebugBar();
        } else {
            return '';
        }
    }

    public function generateData(
        string $view,
        array $vars,
        ResponseType $responseType = ResponseType::httpOk,
        Localizator $localizator = new Localizator(),
        ?Config $customConfig = null
    ): Response {
        $config = $customConfig ?? Config::getInstance();
        $this->assemblesComponents($view, $localizator, $vars, $config);
        return new Response($responseType);
    }

    public function generateJson(array $vars, ResponseType $responseType = ResponseType::httpOk): Response
    {
        BufferManager::start();
        $jsonData = $vars;
        $encodedJsonData = json_encode($jsonData);
        header("Expires: " . gmdate('D, d-M-Y H:i:s \G\M\T', time() + 60));
        header("Accept-Ranges: bytes");
        header("Content-type: " . Resource::json->getContentType()->getMime());
        header('X-Content-Type-Options: nosniff');
        header("Content-Disposition: inline");
        header("Content-Length: " . strlen($encodedJsonData));
        echo $encodedJsonData;
        return new Response($responseType);
    }

    public function setStructural(bool $isStructural = true): void
    {
        $this->isStructural = $isStructural;
    }

    public function isStructural(): bool
    {
        return $this->isStructural;
    }

    public function getView(): string
    {
        return $this->view;
    }
}

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

use SismaFramework\Core\HttpClasses\Communication;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

/**
 * Router Service - Singleton implementation for routing operations
 * 
 * @author Valentino de Lapa
 */
class RouterService
{
    private static ?RouterService $instance = null;
    
    private string $metaUrl = "";
    private ?string $controllerUrl = null;
    private ?string $actionUrl = null;
    private ?string $actualCleanUrl = null;
    private ?string $parsedUrl = null;

    private function __construct()
    {
    }

    public static function getInstance(): RouterService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Allows injecting a custom instance for testing purposes
     */
    public static function setInstance(?RouterService $instance): void
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

    public function redirect(string $relativeUrl, Request $request = new Request()): Response
    {
        header("Location: " . $this->getRootUrl($request) . "/" . rtrim($relativeUrl, "/"));
        return new Response();
    }

    public function concatenateMetaUrl(string $pathToConcatenate): void
    {
        $this->metaUrl .= "/" . rtrim($pathToConcatenate, "/");
    }

    public function setMetaUrl(string $metaUrl): void
    {
        $this->metaUrl = $metaUrl;
    }

    public function getMetaUrl(): string
    {
        return $this->metaUrl;
    }

    public function setActualCleanUrl(string $controllerUrl, string $actionUrl): void
    {
        $this->controllerUrl = $controllerUrl;
        $this->actionUrl = $actionUrl;
        $this->actualCleanUrl = $this->metaUrl . "/" . $controllerUrl . "/" . $actionUrl . "/";
    }

    public function getControllerUrl(): ?string
    {
        return $this->controllerUrl;
    }

    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function getActualCleanUrl(): ?string
    {
        return $this->actualCleanUrl;
    }

    public function getRootUrl(Request $request = new Request()): string
    {
        $httpHost = $request->server["HTTP_HOST"];
        return Communication::getCommunicationProtocol()->value . $httpHost . $this->metaUrl;
    }

    public function getActualUrl(Request $request = new Request()): string
    {
        $requestUri = $request->server["REQUEST_URI"];
        $relativeUrl = str_replace($this->metaUrl, "", $requestUri);
        return substr($relativeUrl, 1);
    }

    public function resetMetaUrl(): void
    {
        $this->metaUrl = "";
    }

    public function reloadWithParsedQueryString(Request $request = new Request()): Response
    {
        $requestUriParts = explode('?', $request->server["REQUEST_URI"], 2);
        $baseUrl = $requestUriParts[0];
        $this->parsedUrl = str_ends_with($baseUrl, "/") ? $baseUrl : $baseUrl . "/";
        foreach ($request->query as $key => $value) {
            $this->parsedUrl .= $key . "/" . urlencode($value ?? "empty") . "/";
        }
        header("Location: " . $this->metaUrl . $this->parsedUrl);
        return new Response();
    }

    public function getParsedUrl(): ?string
    {
        return $this->parsedUrl;
    }
}

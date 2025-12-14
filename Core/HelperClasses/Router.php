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

use SismaFramework\Core\HttpClasses\Communication;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

/**
 * @author Valentino de Lapa
 */
class Router
{
    private static $metaUrl = "";
    private static $controllerUrl = null;
    private static $actionUrl = null;
    private static $actualCleanUrl = null;
    private $parsedUrl = null;

    public static function redirect(string $relativeUrl, $request = new Request()): Response
    {
        header("Location: " . self::getRootUrl($request) . "/" . rtrim($relativeUrl, "/"));
        return new Response();
    }

    public static function concatenateMetaUrl(string $pathToConcatenate)
    {
        self::$metaUrl .= "/" . rtrim($pathToConcatenate, "/");
    }

    public static function setMetaUrl(string $metaUrl): void
    {
        self::$metaUrl = $metaUrl;
    }

    public static function getMetaUrl(): string
    {
        return self::$metaUrl;
    }

    public static function setActualCleanUrl(string $controllerUrl, string $actionUrl): void
    {
        self::$controllerUrl = $controllerUrl;
        self::$actionUrl = $actionUrl;
        self::$actualCleanUrl = self::$metaUrl . "/" . $controllerUrl . "/" . $actionUrl . "/";
    }

    public static function getControllerUrl(): ?string
    {
        return self::$controllerUrl;
    }

    public static function getActionUrl(): ?string
    {
        return self::$actionUrl;
    }

    public static function getActualCleanUrl(): ?string
    {
        return self::$actualCleanUrl;
    }

    public static function getRootUrl($request = new Request()): string
    {
        $httpHost = $request->server["HTTP_HOST"];
        return Communication::getCommunicationProtocol()->value . $httpHost . self::$metaUrl;
    }

    public static function getActualUrl($request = new Request()): string
    {
        $requestUri = $request->server["REQUEST_URI"];
        $relativeUrl = str_replace(self::$metaUrl, "", $requestUri);
        return substr($relativeUrl, 1);
    }

    public static function resetMetaUrl(): void
    {
        self::$metaUrl = "";
    }

    public function reloadWithParsedQueryString($request = new Request()): Response
    {
        $requestUriParts = explode('?', $request->server["REQUEST_URI"], 2);
        $baseUrl = $requestUriParts[0];
        $this->parsedUrl = str_ends_with($baseUrl, "/") ? $baseUrl : $baseUrl . "/";
        foreach ($request->query as $key => $value) {
            $this->parsedUrl .= $key . "/" . urlencode($value ?? "empty") . "/";
        }
        header("Location: " . self::$metaUrl . $this->parsedUrl);
        return new Response();
    }

    public function getParsedUrl(): ?string
    {
        return $this->parsedUrl;
    }
}

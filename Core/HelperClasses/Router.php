<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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

use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Router
{

    private static $metaUrl = \Config\DEFAULT_META_URL;
    private static $controllerUrl;
    private static $actionUrl;
    private static $actualCleanUrl;

    public static function redirect(string $relativeUrl): Response
    {
        header("Location: " . self::$metaUrl . '/' . $relativeUrl);
        return new Response();
    }

    public static function concatenateMetaUrl(string $pathToConcatenate)
    {
        self::$metaUrl .= $pathToConcatenate;
    }

    public static function getMetaUrl(): string
    {
        return self::$metaUrl;
    }

    public static function setActualCleanUrl(string $controllerUrl, string $actionUrl): void
    {
        self::$controllerUrl = $controllerUrl;
        self::$actionUrl = $actionUrl;
        self::$actualCleanUrl = self::$metaUrl . '/' . $controllerUrl . '/' . $actionUrl . '/';
    }

    public static function getControllerUrl(): string
    {
        return self::$controllerUrl;
    }

    public static function getActionUrl(): string
    {
        return self::$actionUrl;
    }

    public static function getActualCleanUrl(): string
    {
        return self::$actualCleanUrl;
    }

    public static function getRootUrl(): string
    {
        $request = new Request();
        $protocol = (isset($request->server['HTTPS']) && ($request->server['HTTPS'] === 'on')) ? 'https://' : 'http://';
        $httpHost = $request->server['HTTP_HOST'];
        return $protocol . $httpHost . self::$metaUrl;
    }

    public static function getActualUrl()
    {
        $request = new Request();
        $requestUri = $request->server['REQUEST_URI'];
        $relativeUrl = str_replace(self::$metaUrl, '', $requestUri);
        return substr($relativeUrl, 1);
    }

    public static function reloadWithParseQuery(string $url): Response
    {
        $parsedUrl = str_replace(["?", "=", "&"], '/', $url);
        $parsedUrl = str_replace('//', '/', $parsedUrl);
        header("Location: " . self::$metaUrl . $parsedUrl);
        return new Response();
    }

}

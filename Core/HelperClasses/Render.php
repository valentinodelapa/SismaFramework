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

use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HelperClasses\BufferManager;
use SismaFramework\Core\HelperClasses\Localizator;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

/**
 *
 * @author Valentino de Lapa
 */
class Render
{

    private static bool $developementEnvironment = \Config\DEVELOPMENT_ENVIRONMENT;
    private static bool $isStructural = false;
    private static string $structuralViewsPath = \Config\STRUCTURAL_VIEWS_PATH;
    private static string $view;
    private static string $viewsPath = \Config\VIEWS_PATH;

    public static function generateView(string $view, array $vars, ResponseType $responseType = ResponseType::httpOk): Response
    {
        $response = self::getResponse($responseType);
        Debugger::setVars($vars);
        self::assemblesComponents($view, $vars);
        echo static::generateDebugBar();
        return $response;
    }

    private static function assemblesComponents(string $view, array $vars): void
    {
        BufferManager::start();
        self::$view = $view;
        $deviceClass = self::getDeviceClass();
        $viewPath = self::getViewPath(self::$view);
        if (self::$isStructural === false) {
            $locale = Localizator::getPageLocaleArray(self::$view);
            extract($locale);
        }
        extract($vars);
        include($viewPath);
    }

    private static function getDeviceClass(): string|false
    {
        $request = new Request();
        if (isset($request->server['HTTP_USER_AGENT'])) {
            $ua = $request->server['HTTP_USER_AGENT'];
            return (stristr($ua, 'mobile') !== false ) ? 'mobile' : 'desktop';
        } else {
            return false;
        }
    }
	
    private static function getViewPath(string $view): string
    {
        if (self::$isStructural) {
            return self::$structuralViewsPath . $view . '.' . Resource::php->value;
        } else {
            return ModuleManager::getExistingFilePath(self::$viewsPath . $view, Resource::php);
        }
    }

    private static function getActualLocaleArray(string $view): array
    {
        $viewParts = \explode('/', $view);
        $locale = Localizator::getLocale();
        $actualLocale = $locale['pages'];
        $commonLocale = $locale['pages']['common'];
        foreach ($viewParts as $part) {
            if (isset($actualLocale['common'])) {
                $commonLocale = array_merge($commonLocale, $actualLocale['common']);
            }
            $actualLocale = $actualLocale[$part] ?? [];
        }
        return array_merge($commonLocale, $actualLocale);
    }

    private static function generateDebugBar(): string
    {
        Debugger::endExecutionTimeCalculation();
        if (self::$developementEnvironment) {
            return Debugger::generateDebugBar();
        } else {
            return '';
        }
    }

    private static function getResponse(ResponseType $responseType): Response
    {
        $response = new Response();
        $response->setResponseType($responseType);
        return $response;
    }

    public static function generateData(string $view, array $vars, ResponseType $responseType = ResponseType::httpOk): Response
    {
        $response = self::getResponse($responseType);
        self::assemblesComponents($view, $vars);
        return $response;
    }

    public static function generateJson(array $vars, ResponseType $responseType = ResponseType::httpOk): Response
    {
        $response = self::getResponse($responseType);
        BufferManager::start();
        if (self::$isStructural) {
            $jsonData = $vars;
        } else {
            $locale = Localizator::getPageLocaleArray(self::$view);
            $jsonData = array_merge($locale, $vars);
        }
        $encodedJsonData = json_encode($jsonData);
        header("Expires: " . gmdate('D, d-M-Y H:i:s \G\M\T', time() + 60));
        header("Accept-Ranges: bytes");
        header("Content-type: " . Resource::json->getMime());
        header('X-Content-Type-Options: nosniff');
        header("Content-Disposition: inline");
        header("Content-Length: " . strlen($encodedJsonData));
        echo $encodedJsonData;
        return $response;
    }

    public static function setDevelopementEnvironment(bool $developementEnvironment = true): void
    {
        self::$developementEnvironment = $developementEnvironment;
    }

    public static function setStructural(bool $isStructural = true): void
    {
        self::$isStructural = $isStructural;
    }
}

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

use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\Exceptions\RenderException;
use SismaFramework\Core\HelperClasses\BufferManager;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 *
 * @author Valentino de Lapa
 */
class Render
{

    private static string $defaultLocaleType = \Config\DEFAULT_LOCALE_TYPE;
    private static bool $developementEnvironment = \Config\DEVELOPMENT_ENVIRONMENT;
    private static bool $isStructural = false;
    private static Language $language;
    private static string $localesPath = \Config\LOCALES_PATH;
    private static Resource $localeType;
    private static string $structuralViewsPath = \Config\STRUCTURAL_VIEWS_PATH;
    private static string $view;
    private static string $viewsPath = \Config\VIEWS_PATH;
    
    public static function setLanguage(Language $language):void
    {
        self::$language = $language;
    }

    public static function generateView(string $view, array $vars, ResponseType $responseType = ResponseType::httpOk, DataMapper $dataMapper = new DataMapper()): Response
    {
        $response = self::getResponse($responseType);
        Debugger::setVars($vars);
        self::assemblesComponents($view, $vars);
        echo static::generateDebugBar($dataMapper);
        return $response;
    }
    
    private static function assemblesComponents(string $view, array $vars):void
    {
        BufferManager::start();
        self::$view = $view;
        $deviceClass = self::getDeviceClass();
        $viewPath = self::getViewPath(self::$view);
        if (self::$isStructural === false) {
            $locale = self::getActualLocaleArray(self::$view);
            extract($locale);
        }
        extract($vars);
        include($viewPath);
    }

    private static function getViewPath(string $view): string
    {
        if (self::$isStructural) {
            return self::$structuralViewsPath . $view . '.' . Resource::php->value;
        } else {
            return ModuleManager::getExistingFilePath(self::$viewsPath . $view, Resource::php);
        }
    }

    private static function getDeviceClass(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        return (stristr($ua, 'mobile') !== false ) ? 'mobile' : 'desktop';
    }

    private static function getActualLocaleArray(string $view): array
    {
        $viewParts = \explode('/', $view);
        $locale = self::getLocale();
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

    private static function getLocale(): array
    {
        $languagePath = self::getLanguagePath();
        switch (self::$localeType) {
            case Resource::json:
                $locale = json_decode(file_get_contents($languagePath), true);
                break;
            case Resource::php:
            default:
                include($languagePath);
                break;
        }
        return $locale;
    }

    private static function getLanguagePath(): string
    {
        if (isset(self::$language) === false) {
            $defaultLanguage = Language::tryFrom(\Config\LANGUAGE);
            if($defaultLanguage instanceof Language){
                self::$language = $defaultLanguage;
            }else{
                throw new RenderException('Formato lingua non corretto');
            }
        }
        return self::getLocalePath(self::$language);
    }

    private static function getLocalePath(Language $language): string
    {
        self::$localeType = Resource::tryFrom(self::$defaultLocaleType);
        if (self::$localeType !== null) {
            return ModuleManager::getConsequentFilePath(self::$localesPath . $language->value, self::$localeType);
        } else {
            throw new RenderException('File non trovato');
        }
    }

    private static function generateDebugBar(DataMapper $dataMapper = new DataMapper()): string
    {
        Debugger::endExecutionTimeCalculation();
        if (self::$developementEnvironment) {
            return Debugger::generateDebugBar($dataMapper);
        } else {
            return '';
        }
    }
    
    private static function getResponse(ResponseType $responseType):Response
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

    public static function getEnumerationLocaleArray(\UnitEnum $enumeration): array
    {
        $reflectionEnumeration = new \ReflectionClass($enumeration);
        $enumerationName = $reflectionEnumeration->getShortName();
        $locale = self::getLocale();
        $field = $locale['enumerations'][$enumerationName];
        return $field[$enumeration];
    }

    public static function setStructural(bool $isStructural = true)
    {
        self::$isStructural = $isStructural;
    }
}

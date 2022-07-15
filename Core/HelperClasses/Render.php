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

use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Exceptions\RenderException;
use SismaFramework\Core\HelperClasses\Dispatcher;
use SismaFramework\Core\HttpClasses\Response;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Render
{

    private static ?string $customRenderModule = null;
    private static Resource $localeType;

    public static function setCustomRenderModule(string $module): void
    {
        self::$customRenderModule = $module;
    }
    
    public static function unsetCustomRenderModule():void
    {
        self::$customRenderModule = null;
    }

    public static function generateView(string $view, array $vars): Response
    {
        $deviceClass = self::getDeviceClass();
        $locale = self::getActualLocaleArray($view);
        extract($locale);
        extract($vars);
        $debugBar = static::generateDebugBar();
        $viewPath = self::getViewPath($view);
        include($viewPath);
        return new Response();
    }

    private static function getDeviceClass(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        return (stristr($ua, 'mobile') !== false ) ? 'mobile' : 'desktop';
    }

    private static function getActualLocaleArray(string $view)
    {
        $viewParts = \explode('/', $view);
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
        $actualLocale = $locale['pages'];
        $commonLocale = $locale['common'];
        foreach ($viewParts as $part) {
            if (isset($actualLocale['common'])) {
                $commonLocale = array_merge($commonLocale, $actualLocale['common']);
            }
            $actualLocale = $actualLocale[$part];
        }
        return array_merge($actualLocale, $commonLocale);
    }

    private static function getLanguagePath(): string
    {
        if (Session::hasItem('lang')) {
            $configLanguage = Language::tryFrom(Session::getItem('lang')) ?? Language::tryFrom(\Config\LANGUAGE);
        }else{
            $configLanguage = Language::tryFrom(\Config\LANGUAGE);
        }
        if ($configLanguage !== null) {
            Session::setItem('lang', $configLanguage->value);
        } else {
            throw new RenderException('Formato lingua non corretto');
        }
        return self::getLocalePath($configLanguage);
    }

    private static function getLocalePath(Language $language): string
    {
        self::$localeType = Resource::tryFrom(\Config\DEFAULT_LOCALE_TYPE);
        if (self::$localeType !== null){
            return self::getExistingFilePath( DIRECTORY_SEPARATOR . \Config\LOCALES_PATH.$language->value,  self::$localeType);
        } else {
            throw new RenderException('File non trovato');
        }
    }
    
    private static function getExistingFilePath(string $path, Resource $resource):string
    {
        if((self::$customRenderModule !== null) && file_exists(\Config\ROOT_PATH. self::$customRenderModule. $path. '.'.$resource->value)){
            return \Config\ROOT_PATH. self::$customRenderModule. $path. '.'.$resource->value;
        }elseif(file_exists(\Config\ROOT_PATH. Dispatcher::$selectedModule. $path. '.'.$resource->value)){
            return \Config\ROOT_PATH. Dispatcher::$selectedModule. $path. '.'.$resource->value;
        }else{
            throw new RenderException('File non trovato');
        }
    }

    private static function getViewPath(string $view): string
    {
        return self::getExistingFilePath(DIRECTORY_SEPARATOR . \Config\VIEWS_PATH . $view, Resource::php);
    }

    private static function generateDebugBar(): string
    {
        Debugger::endExecutionTimeCalculation();
        if (\Config\DEVELOPMENT_ENVIRONMENT) {
            return Debugger::generateDebugBar();
        } else {
            return '';
        }
    }

    public static function generateData(string $view, array $vars): Response
    {
        extract($vars);
        $viewPath = self::getViewPath($view);
        include($viewPath);
        return new Response();
    }

}

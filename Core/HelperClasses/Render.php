<?php

namespace Sisma\Core\HelperClasses;

use Sisma\Core\HttpClasses\Response;

class Render
{

    public static function generateView(string $view, array $vars): Response
    {
        $deviceClass = self::getDeviceClass();
        $locale = self::getActualLocaleArray($view);
        extract($locale);
        extract($vars);
        $viewPath = self::getViewPath($view);
        include($viewPath);
        static::generateDebugBar();
        return new Response();
    }

    private static function getDeviceClass(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $class = (stristr($ua, 'mobile') !== false ) ? 'mobile' : 'desktop';
        return $class;
    }

    private static function getActualLocaleArray(string $view)
    {
        $viewParts = \explode('/', $view);
        $languagePath = self::getLanguagePath();
        include($languagePath);
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
        if (Session::hasItem('lang') === false) {
            Session::setItem('lang', \Config\DEFAULT_LOCALE);
        }
        $path = self::getLocalePath(Session::getItem('lang'));
        return $path;
    }

    private static function getLocalePath(?string $var = null): string
    {
        $path = \Config\LOCALES_PATH;
        return self::getSelectedLocale($path, $var);
    }

    private static function getSelectedLocale($path, $lang): string
    {
        if (file_exists($path . $lang)) {
            return $path . $lang;
        } else {
            return $path . \Config\DEFAULT_LOCALE . '.php';
        }
    }

    private static function getViewPath(string $view): string
    {
        $path = \Config\VIEWS_PATH . $view . '.php';
        return $path;
    }

    private static function generateDebugBar()
    {
        Debugger::endExecutionTimeCalculation();
        if (\Config\DEVELOPMENT_ENVIRONMENT) {
            foreach (Debugger::getInformations() as $name => $value) {
                echo $name . ': ' . $value;
                echo (array_key_last(Debugger::getInformations()) === $name) ?: ' - ';
            }
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

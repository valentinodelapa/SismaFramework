<?php

/*
 * The MIT License
 *
 * Copyright 2024 Valentino de Lapa.
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

/**
 * @author Valentino de Lapa
 */
class Localizator
{

    private static string $defaultLocaleType = \Config\DEFAULT_LOCALE_TYPE;
    private static string $localesPath = \Config\LOCALES_PATH;
    private static Language $injectedLanguage;
    private static Resource $localeType;
    
    public static function setLanguage(Language $language):void
    {
        self::$injectedLanguage = $language;
    }
    
    public static function unsetLanguage():void
    {
        unset(self::$injectedLanguage);
    }

    public function getPageLocaleArray(string $view, ?Language $customLanguage = null): array
    {
        $viewParts = \explode('/', $view);
        $locale = self::getLocale($customLanguage);
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

    private static function getLocale(?Language $customLanguage = null): array
    {
        $language = $customLanguage ?? self::$injectedLanguage ?? self::getDefaultLanguage();
        $localePath = self::getLocalePath($language);
        switch (self::$localeType) {
            case Resource::json:
                $locale = json_decode(file_get_contents($localePath), true);
                break;
            case Resource::php:
            default:
                include($localePath);
                break;
        }
        return $locale;
    }

    private static function getDefaultLanguage(): Language
    {
        $defaultLanguage = Language::tryFrom(\Config\LANGUAGE);
        if ($defaultLanguage instanceof Language) {
            return $defaultLanguage;
        } else {
            throw new LocalizatorException('Formato lingua non corretto');
        }
    }

    private static function getLocalePath(Language $language): string
    {
        self::$localeType = Resource::tryFrom(self::$defaultLocaleType);
        if (self::$localeType !== null) {
            return ModuleManager::getConsequentFilePath(self::$localesPath . $language->value, self::$localeType);
        } else {
            throw new LocalizatorException('File non trovato');
        }
    }

    public function getTemplateLocaleArray(string $template, ?Language $customLanguage = null): array
    {
        $locale = Localizator::getLocale($customLanguage);
        $actualLocale = array_key_exists($template, $locale['templates']) ? $locale['templates'][$template] : [];
        return $actualLocale;
    }

    public function getEnumerationLocaleArray(\UnitEnum $enumeration, ?Language $customLanguage = null): array
    {
        $reflectionEnumeration = new \ReflectionClass($enumeration);
        $enumerationName = $reflectionEnumeration->getShortName();
        $locale = self::getLocale($customLanguage);
        $field = $locale['enumerations'][$enumerationName];
        return $field[$enumeration];
    }
}

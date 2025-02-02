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

use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Exceptions\LocalizatorException;

/**
 * @author Valentino de Lapa
 */
class Localizator
{

    private static Language $injectedLanguage;
    private ?Language $customLanguage;
    private BaseConfig $config;

    public function __construct(?Language $customLanguage = null, ?BaseConfig $customConfig = null)
    {
        $this->customLanguage = $customLanguage;
        $this->config = $customConfig ?? BaseConfig::getDefault();
    }

    public function getPageLocaleArray(string $view): array
    {
        $viewParts = \explode('/', $view);
        $locale = $this->getLocale();
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

    private function getLocale(): array
    {
        $language = $this->customLanguage ?? self::$injectedLanguage ?? $this->getDefaultLanguage();
        $localePath = $this->getLocalePath($language);
        $locale = json_decode(file_get_contents($localePath), true);
        return $locale;
    }

    private function getDefaultLanguage(): Language
    {
        $defaultLanguage = Language::tryFrom($this->config->language);
        if ($defaultLanguage instanceof Language) {
            return $defaultLanguage;
        } else {
            throw new LocalizatorException('Formato lingua non corretto');
        }
    }

    private function getLocalePath(Language $language): string
    {
        return ModuleManager::getConsequentFilePath($this->config->localesPath . $language->value, Resource::json);
    }

    public function getTemplateLocaleArray(string $template, ?BaseConfig $customConfig = null): array
    {
        $config = $customConfig ?? BaseConfig::getDefault();
        $locale = $this->getLocale($config);
        $actualLocale = array_key_exists($template, $locale['templates']) ? $locale['templates'][$template] : [];
        return $actualLocale;
    }

    public function getEnumerationLocaleArray(\UnitEnum $enumeration, ?BaseConfig $customConfig = null): string
    {
        $reflectionEnumeration = new \ReflectionClass($enumeration);
        $config = $customConfig ?? BaseConfig::getDefault();
        $enumerationName = $reflectionEnumeration->getShortName();
        $locale = $this->getLocale($config);
        $field = $locale['enumerations'][lcfirst($enumerationName)];
        return $field[$enumeration->name];
    }

    public static function setLanguage(Language $language): void
    {
        self::$injectedLanguage = $language;
    }

    public static function unsetLanguage(): void
    {
        unset(self::$injectedLanguage);
    }
}

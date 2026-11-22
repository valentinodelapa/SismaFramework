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
use SismaFramework\Core\Exceptions\LocalizatorException;

/**
 * @author Valentino de Lapa
 */
class Localizator
{
    private static ?Language $injectedLanguage = null;
    private ?Language $customLanguage = null;
    private Config $config;

    public function __construct(?Language $customLanguage = null, ?Config $customConfig = null)
    {
        $this->customLanguage = $customLanguage;
        $this->config = $customConfig ?? Config::getInstance();
    }

    /**
     * @internal
     */
    public function getPageLocaleArray(string $view): array
    {
        return $this->getHierarchicalLocaleArray($this->getLocale()['pages'] ?? [], $view);
    }

    private function getHierarchicalLocaleArray(array $sectionLocale, string $path): array
    {
        $actualLocale = $sectionLocale;
        $commonLocale = [];
        foreach (\explode('/', $path) as $part) {
            $commonLocale = array_merge($commonLocale, $actualLocale['common'] ?? []);
            $actualLocale = $actualLocale[$part] ?? [];
        }
        return array_merge($commonLocale, $actualLocale);
    }

    private function getLocale(): array
    {
        $language = $this->customLanguage ?? self::$injectedLanguage ?? $this->config->language;
        $localePath = $this->getLocalePath($language);
        $locale = json_decode(file_get_contents($localePath), true);
        return $locale;
    }

    private function getLocalePath(Language $language): string
    {
        return ModuleManager::getConsequentFilePath($this->config->localesPath . $language->value, Resource::json);
    }

    /**
     * @internal
     */
    public function getTemplateLocaleArray(string $template): array
    {
        $templatesLocale = $this->getLocale()['templates'] ?? [];
        if ($this->isDeprecatedFlatTemplateKey($templatesLocale, $template)) {
            return $templatesLocale[$template];
        }
        return $this->getHierarchicalLocaleArray($templatesLocale, $template);
    }

    private function isDeprecatedFlatTemplateKey(array $templatesLocale, string $template): bool
    {
        return str_contains($template, '/') && array_key_exists($template, $templatesLocale);
    }

    /**
     * @internal
     */
    public function getEnumerationLocaleLabel(\UnitEnum $enumeration, string ...$path): string
    {
        $field = $this->getEnumerationLocaleField($enumeration, $path);
        $label = is_array($field) ? ($field['label'] ?? null) : $field;
        if (is_string($label) === false) {
            throw new LocalizatorException('Il valore di localizzazione per ' . $this->getEnumerationLocaleReference($enumeration, $path) . ' non ha una label');
        }
        return $label;
    }

    /**
     * @internal
     */
    public function getComposedEnumerationLocale(\UnitEnum $enumeration, string ...$path): array
    {
        $field = $this->getEnumerationLocaleField($enumeration, $path);
        if (is_array($field) === false) {
            throw new LocalizatorException('Il valore di localizzazione per ' . $this->getEnumerationLocaleReference($enumeration, $path) . ' non è composto');
        }
        return $field;
    }

    /**
     * @internal
     */
    public function getEnumerationLocaleAttribute(\UnitEnum $enumeration, string ...$path): string|array|null
    {
        return $this->getEnumerationLocaleField($enumeration, $path);
    }

    private function getEnumerationLocaleField(\UnitEnum $enumeration, array $path): string|array|null
    {
        $enumerationName = (new \ReflectionClass($enumeration))->getShortName();
        $field = $this->getLocale();
        foreach (['enumerations', lcfirst($enumerationName), $enumeration->name, ...$path] as $key) {
            if ((is_array($field) && array_key_exists($key, $field)) === false) {
                throw new LocalizatorException('Valore di localizzazione non trovato per ' . $this->getEnumerationLocaleReference($enumeration, $path));
            }
            $field = $field[$key];
        }
        return $field;
    }

    private function getEnumerationLocaleReference(\UnitEnum $enumeration, array $path): string
    {
        return implode('.', [$enumeration::class . '::' . $enumeration->name, ...$path]);
    }

    public static function setLanguage(Language $language): void
    {
        self::$injectedLanguage = $language;
    }

    public static function unsetLanguage(): void
    {
        self::$injectedLanguage = null;
    }
}

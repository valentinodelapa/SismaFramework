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

/**
 *
 * @author Valentino de Lapa
 */
class Templater
{

    private static bool $isStructural = false;
    private static string $structuralTemplatesPath = \Config\STRUCTURAL_TEMPLATES_PATH;
    private static string $templatesPath = \Config\TEMPLATES_PATH;

    public static function setStructural(bool $isStructural = true)
    {
        self::$isStructural = $isStructural;
    }

    public static function generateTemplate(string $template, array $vars): string
    {
        $varsAndLocales = array_merge($vars, self::getActualLocaleArray($template));
        $templateContent = self::getTemplateContent($template);
        $parsedTemplateContent = preg_replace_callback('/\{\{(.*?)\}\}/is', function ($varName) use ($varsAndLocales) {
            $var = str_replace(['{{', '}}'], '', $varName[0]);
            return $varsAndLocales[$var];
        }, $templateContent);
        return $parsedTemplateContent;
    }

    private static function getActualLocaleArray(string $template): array
    {
        $locale = Localizator::getLocale();
        $actualLocale = array_key_exists($template, $locale['templates']) ? $locale['templates'][$template] : [];
        return $actualLocale;
    }

    private static function getTemplateContent(string $template): string
    {
        $path = self::getTemplatePath($template);
        return file_get_contents($path);
    }

    private static function getTemplatePath(string $template)
    {
        if (self::$isStructural) {
            return self::$structuralTemplatesPath . $template . '.' . Resource::html->value;
        } else {
            return ModuleManager::getExistingFilePath(self::$templatesPath . $template, Resource::html);
        }
    }
}

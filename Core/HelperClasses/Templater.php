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

    private static string $structuralTemplatesPath = \Config\STRUCTURAL_TEMPLATES_PATH;
    private static string $templatesPath = \Config\TEMPLATES_PATH;

    public static function generateTemplate(string $template, array $vars, Localizator $localizator = new Localizator()): string
    {
        $templatePath = ModuleManager::getExistingFilePath(self::$templatesPath . $template, Resource::tpl);
        $varsAndLocales = array_merge($vars, $localizator->getTemplateLocaleArray($template));
        return self::parseTemplate($templatePath, $varsAndLocales);
    }

    public static function parseTemplate(string $templatePath, array $vars): string
    {
        $templateContent = file_get_contents($templatePath);
        $parsedTemplateContent = preg_replace_callback('/\{\{(.*?)\}\}/is', function ($varName) use ($vars) {
            $var = str_replace(['{{', '}}'], '', $varName[0]);
            return $vars[$var];
        }, $templateContent);
        return $parsedTemplateContent;
    }

    public static function generateStructuralTemplate(string $template, array $vars): string
    {
        $templatePath = self::$structuralTemplatesPath . $template . '.' . Resource::tpl->value;
        return self::parseTemplate($templatePath, $vars);
    }
}

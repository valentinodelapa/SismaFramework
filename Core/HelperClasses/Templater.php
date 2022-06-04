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

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Templater
{
    private static $isStructural = false;
    
    public static function setStructural(bool $isStructural = true)
    {
        self::$isStructural = $isStructural;
    }

    public static function generateTemplate(string $template, array $vars): string
    {
        $templateContent = self::getTemplateContent($template);
        $templateContent = preg_replace_callback('/\{\{(.*?)\}\}/is', function ($varName) use ($vars) {
            $var = str_replace(['{{', '}}'], '', $varName[0]);
            return $vars[$var];
        }, $templateContent);
        return $templateContent;
    }

    private static function getTemplateContent(string $template): string
    {
        $path = self::getTemplatePath($template);
        return file_get_contents($path);
    }
    
    private static function getTemplatePath(string $template)
    {
        if(self::$isStructural){
            return \Config\STRUCTURAL_TEMPLATES_PATH . $template . '.html';
        }else{
            return \Config\ROOT_PATH . Dispatcher::$selectedModule . DIRECTORY_SEPARATOR .\Config\TEMPLATES_PATH . $template . '.html';
        }
    }

}

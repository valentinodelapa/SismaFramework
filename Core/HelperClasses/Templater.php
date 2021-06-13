<?php

namespace Sisma\Core\HelperClasses;

class Templater
{

    public static function generateTemplate($template, $vars): string
    {
        $templateContent = self::getTemplateContent($template);
        $templateContent = preg_replace_callback('/\{\{(.*?)\}\}/is', function ($varName) use ($vars) {
            $var = str_replace(['{{', '}}'], '', $varName[0]);
            return $vars[$var];
        }, $templateContent);
        return $templateContent;
    }

    private static function getTemplateContent($template): string
    {
        $path = \Config\TEMPLATES_PATH . $template . '.html';
        $templateContent = file_get_contents($path);
        return $templateContent;
    }

}

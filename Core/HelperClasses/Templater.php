<?php

namespace SismaFramework\Core\HelperClasses;

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
        $templateContent = file_get_contents($path);
        return $templateContent;
    }
    
    private static function getTemplatePath(string $template)
    {
        if(self::$isStructural){
            return \Config\STRUCTURAL_TEMPLATES_PATH . $template . '.html';
        }else{
            return \Config\TEMPLATES_PATH . $template . '.html';
        }
    }

}

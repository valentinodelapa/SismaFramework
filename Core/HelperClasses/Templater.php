<?php

namespace Sisma\Core\HelperClasses;

class Templater
{

    public static function generateTemplate($template, $vars): string
    {
        extract($vars);
        $templateContent = self::getTemplateContent($template);
        eval("\$templateContent = \"$templateContent\";");
        return $templateContent;
    }

    private static function getTemplateContent($template): string
    {
        $path = \Sisma\Core\TEMPLATES_PATH . $template . '.php';
        $templateContent = file_get_contents($path);
        return $templateContent;
    }

}

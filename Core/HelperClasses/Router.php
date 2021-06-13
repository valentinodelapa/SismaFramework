<?php

namespace Sisma\Core\HelperClasses;

use Sisma\Core\HttpClasses\Response;

class Router
{

    private static $metaPath = '';

    public static function redirect(string $path): Response
    {
        header("Location: " . self::$metaPath.'/' . $path);
        return new Response();
    }

    public static function concatenateMetaPath(string $pathToConcatenate)
    {
        self::$metaPath .= $pathToConcatenate;
    }

    public static function getMetaPath()
    {
        return self::$metaPath;
    }
    
    public static function getRootLink()
    {
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://';
        $httpHost =  $_SERVER['HTTP_HOST'];
        return $protocol.$httpHost.self::$metaPath;
    }

    public static function reloadWithParseQuery(string $path): Response
    {
        $parsedPath = str_replace(["?", "=", "&"], '/', $path);
        $parsedPath = str_replace('//', '/', $parsedPath);
        header("Location: " . self::$metaPath . $parsedPath);
        return new Response();
    }

}

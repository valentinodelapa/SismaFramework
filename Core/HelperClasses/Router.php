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

use SismaFramework\Core\HttpClasses\Response;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
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

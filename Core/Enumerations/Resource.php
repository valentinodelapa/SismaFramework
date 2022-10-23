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

namespace SismaFramework\Core\Enumerations;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
enum Resource: string
{

    case css = 'css';
    case geojson = 'geojson';
    case htm = 'htm';
    case html = 'html';
    case ico = 'ico';
    case jpg = 'jpg';
    case jpeg = 'jpeg';
    case js = 'js';
    case json = 'json';
    case map = 'map';
    case mp4 = 'mp4';
    case otf = 'otf';
    case php = 'php';
    case png = 'png';
    case svg = 'svg';
    case woff = 'woff';
    case woff2 = 'woff2';

    public function getMime(): string
    {
        return match ($this) {
            self::css => 'text/css',
            self::geojson => 'application/geo+json',
            self::htm, self::html => 'text/html',
            self::ico => 'image/x-icon',
            self::jpg, self::jpeg => 'image/jpeg',
            self::js => 'application/javascript',
            self::json, self::map => 'application/json',
            self::mp4 => 'video/mp4',
            self::png => 'image/png',
            self::otf => 'font/otf',
            self::svg => 'image/svg+xml',
            self::woff => 'font/woff',
            self::woff2 => 'font/woff2',
        };
    }

}

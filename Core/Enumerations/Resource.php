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

use SismaFramework\Core\HelperClasses\Session;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
enum Resource: string
{

    use \SismaFramework\Core\Traits\DataEnumeration;

    case css = 'css';
    case geojson = 'geojson';
    case jpg = 'jpg';
    case jpeg = 'jpeg';
    case js = 'js';
    case json = 'json';
    case php = 'php';
    case png = 'png';
    case svg = 'svg';

    private function matchAdditionalData($language): string
    {
        
    }

    private function matchFunctionalData(): int|string|array|\UnitEnum
    {
        return match ($this) {
            self::css => 'text/css',
            self::geojson => 'application/geo+json',
            self::jpg, self::jpeg => 'image/jpeg',
            self::js => 'application/javascript',
            self::json => 'application/json',
            self::png => 'image/png',
            self::svg => 'image/svg+xml',
        };
    }

}

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

namespace SismaFramework\Core\HttpClasses;

use SismaFramework\Core\Enumerations\ComunicationProtocol;

/**
 * @author Valentino de Lapa
 */
class Comunication
{

    private static bool $httpsIsForced = \Config\HTTPS_IS_FORCED;
    private static bool $developementEnvironment = \Config\DEVELOPMENT_ENVIRONMENT;

    public static function getComunicationProtocol(): ComunicationProtocol
    {
        $request = new Request();
        if (self::$httpsIsForced) {
            return ComunicationProtocol::https;
        } elseif (isset($request->server['HTTPS'])) {
            return ($request->server['HTTPS'] === 'on') ? ComunicationProtocol::https : ComunicationProtocol::http;
        } elseif (isset($request->server['SERVER_PORT'])) {
            return (intval($request->server['SERVER_PORT']) === 443) ? ComunicationProtocol::https : ComunicationProtocol::http;
        } else {
            return self::$developementEnvironment ? ComunicationProtocol::http : ComunicationProtocol::https;
        }
    }
}

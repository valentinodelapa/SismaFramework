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

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Enumerations\CommunicationProtocol;

/**
 * @author Valentino de Lapa
 */
class Communication
{

    public static function getCommunicationProtocol(Request $request = new Request(), ?Config $customConfig = null): CommunicationProtocol
    {
        $config = $customConfig ?? Config::getInstance();
        if ($config->httpsIsForced) {
            return CommunicationProtocol::https;
        } elseif (isset($request->server['HTTPS'])) {
            return ($request->server['HTTPS'] === 'on') ? CommunicationProtocol::https : CommunicationProtocol::http;
        } elseif (isset($request->server['SERVER_PORT'])) {
            return (intval($request->server['SERVER_PORT']) === 443) ? CommunicationProtocol::https : CommunicationProtocol::http;
        } else {
            return $config->developmentEnvironment ? CommunicationProtocol::http : CommunicationProtocol::https;
        }
    }
}

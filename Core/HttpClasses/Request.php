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

/**
 *
 * @author Valentino de Lapa
 */
class Request
{

    public $query;
    public $request;
    public $cookie;
    public $files;
    public $server;
    public $headers;

    public function __construct()
    {
        $this->query = &$_GET;
        $this->request = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    public function getStreamContentResource()
    {
        $opts = [
            $this->server['SERVER_PROTOCOL'] => [
                'method' => 'GET',
                'content' => $this->query
            ]
        ];
        return stream_context_create($opts);
    }

}

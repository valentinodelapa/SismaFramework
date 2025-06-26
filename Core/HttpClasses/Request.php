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

use SismaFramework\Core\Enumerations\RequestType;

/**
 *
 * @author Valentino de Lapa
 */
class Request
{

    public array $query;
    public array $request;
    public array $cookie;
    public array $files;
    public array $server;
    public array $headers = [];
    public mixed $postBody = null;
    public mixed $putData = null;
    public mixed $patchData = null;
    public mixed $deleteData = null;

    public function __construct()
    {
        $this->query = &$_GET;
        $this->request = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->initializeHeaders();
        $this->parseRequestBody();
    }
    
    private function initializeHeaders(): void
    {
        if (function_exists('getallheaders')) {
            $this->headers = getallheaders();
        } else {
            foreach ($this->server as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $this->headers[$headerName] = $value;
                }
            }
        }
    }
    private function parseRequestBody(): void
    {
        $methodString = $this->server['REQUEST_METHOD'] ?? 'GET';
        $method = RequestType::tryFrom($methodString);
        $contentType = $this->headers['Content-Type'] ?? $this->headers['content-type'] ?? '';
        if ($method && in_array($method, [RequestType::methodPost, RequestType::methodPut, RequestType::methodDelete, RequestType::methodPatch])) {
            $rawBody = null;
            $parsedBody = null;
            $readPhpInput = true;
            if ($method === RequestType::methodPost) {
                if (str_starts_with(strtolower($contentType), 'application/x-www-form-urlencoded') ||
                    str_starts_with(strtolower($contentType), 'multipart/form-data')) {
                    $readPhpInput = false;
                }
            }
            if ($readPhpInput) {
                $rawBody = file_get_contents('php://input');
                $parsedBody = $rawBody;
                if (str_starts_with(strtolower($contentType), 'application/json')) {
                    $decodedBody = json_decode($rawBody, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $parsedBody = $decodedBody;
                    } else {
                        error_log('Errore nella decodifica JSON: ' . json_last_error_msg());
                    }
                }
            }
            switch ($method) {
                case RequestType::methodPost:
                    if ($readPhpInput) {
                        $this->postBody = $parsedBody;
                    }
                    break;
                case RequestType::methodPut:
                    $this->putData = $parsedBody;
                    break;
                case RequestType::methodPatch:
                    $this->patchData = $parsedBody;
                    break;
                case RequestType::methodDelete:
                    $this->deleteData = $parsedBody;
                    break;
            }
        }
    }

    public function getStreamContentResource()
    {
        $opts = [
            ($this->server['SERVER_PROTOCOL'] ?? 'HTTP/1.0') => [
                'method' => 'GET',
                'content' => $this->query
            ]
        ];
        return stream_context_create($opts);
    }
}

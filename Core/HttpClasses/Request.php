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

use SismaFramework\Core\Enumerations\ContentType;
use SismaFramework\Core\Enumerations\RequestType;
use SismaFramework\Core\Exceptions\BadRequestException;

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
    public array $data = [];
    public array $input = [];
    public array $headers = [];

    public function __construct()
    {
        $this->query = &$_GET;
        $this->request = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->initializeHeaders();
        $this->parseRequestBody();
        $this->input = !empty($this->data) ? $this->data : $this->request;
    }

    private function initializeHeaders(): void
    {
        if (function_exists('getallheaders')) {
            $this->headers = getallheaders();
        } else {
            $this->getHeadersByServer();
        }
    }

    private function getHeadersByServer(): void
    {
        foreach ($this->server as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $this->headers[$headerName] = $value;
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headerName = str_replace('_', '-', ucwords(strtolower($name), '_'));
                $this->headers[$headerName] = $value;
            }
        }
    }

    private function parseRequestBody(): void
    {
        $methodString = $this->server['REQUEST_METHOD'] ?? 'GET';
        $method = RequestType::tryFrom($methodString) ?? [];
        $contentTypeString = $this->headers['Content-Type'] ?? $this->headers['content-type'] ?? '';
        $contentTypeParts = explode(';', $contentTypeString);
        $contentType = ContentType::getByMime(trim($contentTypeParts[0]));
        if (in_array($method, [RequestType::methodPost, RequestType::methodPut, RequestType::methodDelete, RequestType::methodPatch])) {
            switch ($contentType) {
                case ContentType::applicationJson:
                    $this->parseJsonRequest();
                    break;
            }
        }
    }

    private function parseJsonRequest(): void
    {
        $rawBody = file_get_contents('php://input');
        $decodedBody = json_decode($rawBody, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedBody)) {
            $this->data = $decodedBody;
        } elseif (json_last_error() !== JSON_ERROR_NONE) {
            throw  new BadRequestException(json_last_error_msg());
        }
    }
}

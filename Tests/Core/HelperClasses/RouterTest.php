<?php

/*
 * The MIT License
 *
 * Copyright (c) 2023-present Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\Enumerations\ResponseType;

/**
 * @author Valentino de Lapa
 */
class RouterTest extends TestCase
{
    public function testRedirect()
    {
        $response = Router::redirect('sample/notify/message/notify/');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(ResponseType::httpFound->value, http_response_code());
    }
    
    public function testGetActualUrl()
    {
        Router::concatenateMetaUrl('/meta/url');
        $_SERVER['REQUEST_URI'] = '/meta/url/sample/error/';
        $this->assertEquals('sample/error/', Router::getActualUrl());
    }
    
    public function testReloadWithParsedQueryString()
    {
        $requestMock = $this->createMock(Request::class);
        $requestMock->server['REQUEST_URI'] = '/sample/notify?message=notify';
        $requestMock->query = [
            "message" => "notify",
        ];
        $router = new Router();
        $response = $router->reloadWithParsedQueryString($requestMock);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(ResponseType::httpFound->value, http_response_code());
        $this->assertEquals('/sample/notify/message/notify/', $router->getParsedUrl());
    }
}

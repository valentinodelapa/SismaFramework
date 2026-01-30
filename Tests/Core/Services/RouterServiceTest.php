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

namespace SismaFramework\Tests\Core\Services;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Services\RouterService;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\Enumerations\ResponseType;

/**
 * @author Valentino de Lapa
 */
class RouterServiceTest extends TestCase
{
    private Request $requestMock;

    protected function setUp(): void
    {
        RouterService::resetInstance();
        
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', true],
                    ['httpsIsForced', false],
        ]);
        Config::setInstance($configStub);
        
        $this->requestMock = $this->createStub(Request::class);
        $this->requestMock->server = [
            'HTTP_HOST' => 'http.host',
        ];
    }

    protected function tearDown(): void
    {
        RouterService::resetInstance();
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = RouterService::getInstance();
        $instance2 = RouterService::getInstance();
        
        $this->assertInstanceOf(RouterService::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function testSetInstanceAllowsInjectingCustomInstance(): void
    {
        $stubRouter = $this->createStub(RouterService::class);
        RouterService::setInstance($stubRouter);
        
        $this->assertSame($stubRouter, RouterService::getInstance());
    }

    public function testResetInstanceCreatesNewInstance(): void
    {
        $instance1 = RouterService::getInstance();
        RouterService::resetInstance();
        $instance2 = RouterService::getInstance();
        
        $this->assertNotSame($instance1, $instance2);
    }

    public function testSetAndGetMetaUrl(): void
    {
        $service = RouterService::getInstance();
        $service->setMetaUrl('/custom/meta');
        
        $this->assertEquals('/custom/meta', $service->getMetaUrl());
    }

    public function testConcatenateMetaUrl(): void
    {
        $service = RouterService::getInstance();
        $service->concatenateMetaUrl('first');
        $service->concatenateMetaUrl('second');
        
        $this->assertEquals('/first/second', $service->getMetaUrl());
    }

    public function testSetMetaUrlOverwritesPreviousValue(): void
    {
        $service = RouterService::getInstance();
        $service->concatenateMetaUrl('initial');
        $service->setMetaUrl('/overwritten');
        
        $this->assertEquals('/overwritten', $service->getMetaUrl());
    }

    public function testResetMetaUrl(): void
    {
        $service = RouterService::getInstance();
        $service->setMetaUrl('/some/path');
        $service->resetMetaUrl();
        
        $this->assertEquals('', $service->getMetaUrl());
    }

    public function testSetActualCleanUrl(): void
    {
        $service = RouterService::getInstance();
        $service->setMetaUrl('/meta');
        $service->setActualCleanUrl('controller', 'action');
        
        $this->assertEquals('controller', $service->getControllerUrl());
        $this->assertEquals('action', $service->getActionUrl());
        $this->assertEquals('/meta/controller/action/', $service->getActualCleanUrl());
    }

    public function testGetRootUrl(): void
    {
        $service = RouterService::getInstance();
        $service->setMetaUrl('/meta/url');
        
        $rootUrl = $service->getRootUrl($this->requestMock);
        
        $this->assertEquals('http://http.host/meta/url', $rootUrl);
    }

    public function testGetActualUrl(): void
    {
        $service = RouterService::getInstance();
        $service->setMetaUrl('/meta/url');
        $this->requestMock->server['REQUEST_URI'] = '/meta/url/sample/error/';
        
        $actualUrl = $service->getActualUrl($this->requestMock);
        
        $this->assertEquals('sample/error/', $actualUrl);
    }

    public function testRedirect(): void
    {
        $service = RouterService::getInstance();
        $response = $service->redirect('sample/notify/message/notify/', $this->requestMock);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(ResponseType::httpFound->value, http_response_code());
    }

    public function testReloadWithParsedQueryString(): void
    {
        $service = RouterService::getInstance();
        $this->requestMock->server['REQUEST_URI'] = '/sample/notify?message=notify';
        $this->requestMock->query = [
            "message" => "notify",
        ];
        
        $response = $service->reloadWithParsedQueryString($this->requestMock);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(ResponseType::httpFound->value, http_response_code());
        $this->assertEquals('/sample/notify/message/notify/', $service->getParsedUrl());
    }

    public function testReloadWithParsedQueryStringHandlesEmptyValues(): void
    {
        $service = RouterService::getInstance();
        $this->requestMock->server['REQUEST_URI'] = '/sample/test?param=';
        $this->requestMock->query = [
            "param" => null,
        ];
        
        $response = $service->reloadWithParsedQueryString($this->requestMock);
        
        $this->assertStringContainsString('param/empty/', $service->getParsedUrl());
    }
}

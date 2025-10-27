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

namespace SismaFramework\Tests\Core\HttpClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HttpClasses\Communication;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\Enumerations\CommunicationProtocol;
use SismaFramework\Core\HelperClasses\Config;

/**
 * Test for Communication class
 * @author Valentino de Lapa
 */
class CommunicationTest extends TestCase
{
    private Config $configMock;
    private Request $requestMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->requestMock->server = [];
    }

    public function testGetCommunicationProtocolWithForcedHttps()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->with('httpsIsForced')
            ->willReturn(true);

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::https, $result);
    }

    public function testGetCommunicationProtocolWithHttpsOn()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->with('httpsIsForced')
            ->willReturn(false);

        $this->requestMock->server['HTTPS'] = 'on';

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::https, $result);
    }

    public function testGetCommunicationProtocolWithHttpsOff()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->with('httpsIsForced')
            ->willReturn(false);

        $this->requestMock->server['HTTPS'] = 'off';

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::http, $result);
    }

    public function testGetCommunicationProtocolWithHttpsNotSet()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function($property) {
                return match($property) {
                    'httpsIsForced' => false,
                    'developmentEnvironment' => true,
                    default => null
                };
            });

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertInstanceOf(CommunicationProtocol::class, $result);
    }

    public function testGetCommunicationProtocolWithPort443()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->with('httpsIsForced')
            ->willReturn(false);

        unset($this->requestMock->server['HTTPS']);
        $this->requestMock->server['SERVER_PORT'] = '443';

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::https, $result);
    }

    public function testGetCommunicationProtocolWithPort80()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->with('httpsIsForced')
            ->willReturn(false);

        unset($this->requestMock->server['HTTPS']);
        $this->requestMock->server['SERVER_PORT'] = '80';

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::http, $result);
    }

    public function testGetCommunicationProtocolWithNumericPort443()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->with('httpsIsForced')
            ->willReturn(false);

        unset($this->requestMock->server['HTTPS']);
        $this->requestMock->server['SERVER_PORT'] = 443;

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::https, $result);
    }

    public function testGetCommunicationProtocolWithDevelopmentEnvironment()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function($property) {
                return match($property) {
                    'httpsIsForced' => false,
                    'developmentEnvironment' => true,
                    default => null
                };
            });

        unset($this->requestMock->server['HTTPS']);
        unset($this->requestMock->server['SERVER_PORT']);

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::http, $result);
    }

    public function testGetCommunicationProtocolWithProductionEnvironment()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function($property) {
                return match($property) {
                    'httpsIsForced' => false,
                    'developmentEnvironment' => false,
                    default => null
                };
            });

        unset($this->requestMock->server['HTTPS']);
        unset($this->requestMock->server['SERVER_PORT']);

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::https, $result);
    }

    public function testGetCommunicationProtocolPriorityForcedHttps()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function($property) {
                return match($property) {
                    'httpsIsForced' => true,
                    'developmentEnvironment' => true,
                    default => null
                };
            });

        $this->requestMock->server['HTTPS'] = 'off';
        $this->requestMock->server['SERVER_PORT'] = '80';

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::https, $result);
    }

    public function testGetCommunicationProtocolPriorityHttpsHeader()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->with('httpsIsForced')
            ->willReturn(false);

        $this->requestMock->server['HTTPS'] = 'on';
        $this->requestMock->server['SERVER_PORT'] = '80';

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::https, $result);
    }

    public function testGetCommunicationProtocolPriorityServerPort()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function($property) {
                return match($property) {
                    'httpsIsForced' => false,
                    'developmentEnvironment' => false,
                    default => null
                };
            });

        unset($this->requestMock->server['HTTPS']);
        $this->requestMock->server['SERVER_PORT'] = '80';

        $result = Communication::getCommunicationProtocol($this->requestMock, $this->configMock);

        $this->assertEquals(CommunicationProtocol::http, $result);
    }

    public function testMethodIsStatic()
    {
        $reflection = new \ReflectionMethod(Communication::class, 'getCommunicationProtocol');
        $this->assertTrue($reflection->isStatic());
    }

    public function testMethodReturnType()
    {
        $reflection = new \ReflectionMethod(Communication::class, 'getCommunicationProtocol');
        $returnType = $reflection->getReturnType();
        $this->assertEquals(CommunicationProtocol::class, $returnType->getName());
    }

    public function testMethodParameters()
    {
        $reflection = new \ReflectionMethod(Communication::class, 'getCommunicationProtocol');
        $parameters = $reflection->getParameters();

        $this->assertEquals(2, count($parameters));
        $this->assertEquals('request', $parameters[0]->getName());
        $this->assertEquals('customConfig', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->allowsNull());
    }

    public function testDefaultParameterValues()
    {
        $this->configMock->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function($property) {
                return match($property) {
                    'httpsIsForced' => false,
                    'developmentEnvironment' => true,
                    default => null
                };
            });

        // Test with default request parameter
        $result = Communication::getCommunicationProtocol(customConfig: $this->configMock);

        $this->assertInstanceOf(CommunicationProtocol::class, $result);
    }
}
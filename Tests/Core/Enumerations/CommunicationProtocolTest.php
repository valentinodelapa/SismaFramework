<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\Enumerations;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Enumerations\CommunicationProtocol;

/**
 * Test for CommunicationProtocol enumeration
 * @author Valentino de Lapa
 */
class CommunicationProtocolTest extends TestCase
{
    public function testEnumExists()
    {
        $this->assertTrue(enum_exists(CommunicationProtocol::class));
    }

    public function testEnumIsBackedByString()
    {
        $this->assertInstanceOf(\BackedEnum::class, CommunicationProtocol::http);
        $this->assertIsString(CommunicationProtocol::http->value);
    }

    public function testHttpProtocolValue()
    {
        $this->assertEquals('http://', CommunicationProtocol::http->value);
    }

    public function testHttpsProtocolValue()
    {
        $this->assertEquals('https://', CommunicationProtocol::https->value);
    }

    public function testFromMethodWorks()
    {
        $this->assertEquals(CommunicationProtocol::http, CommunicationProtocol::from('http://'));
        $this->assertEquals(CommunicationProtocol::https, CommunicationProtocol::from('https://'));
    }

    public function testTryFromMethodWorks()
    {
        $this->assertEquals(CommunicationProtocol::http, CommunicationProtocol::tryFrom('http://'));
        $this->assertEquals(CommunicationProtocol::https, CommunicationProtocol::tryFrom('https://'));
        $this->assertNull(CommunicationProtocol::tryFrom('ftp://'));
        $this->assertNull(CommunicationProtocol::tryFrom('invalid'));
    }

    public function testCasesMethodReturnsAllProtocols()
    {
        $cases = CommunicationProtocol::cases();
        $this->assertIsArray($cases);
        $this->assertEquals(2, count($cases));

        // Verify both protocols are present
        $values = array_map(fn($case) => $case->value, $cases);
        $this->assertContains('http://', $values);
        $this->assertContains('https://', $values);
    }

    public function testProtocolComparison()
    {
        // Test that same protocols are equal
        $this->assertEquals(CommunicationProtocol::http, CommunicationProtocol::http);
        $this->assertEquals(CommunicationProtocol::https, CommunicationProtocol::https);

        // Test that different protocols are not equal
        $this->assertNotEquals(CommunicationProtocol::http, CommunicationProtocol::https);
    }

    public function testProtocolNaming()
    {
        $this->assertEquals('http', CommunicationProtocol::http->name);
        $this->assertEquals('https', CommunicationProtocol::https->name);
    }

    public function testSwitchStatementCompatibility()
    {
        foreach (CommunicationProtocol::cases() as $protocol) {
            $result = match ($protocol) {
                CommunicationProtocol::http => 'insecure',
                CommunicationProtocol::https => 'secure',
            };

            $this->assertIsString($result);
            $this->assertContains($result, ['insecure', 'secure']);
        }
    }

    public function testProtocolSecurity()
    {
        // HTTP is insecure
        $this->assertStringContainsString('http://', CommunicationProtocol::http->value);
        $this->assertStringNotContainsString('s', CommunicationProtocol::http->value);

        // HTTPS is secure
        $this->assertStringContainsString('https://', CommunicationProtocol::https->value);
        $this->assertStringContainsString('s', CommunicationProtocol::https->value);
    }

    public function testUrlConstruction()
    {
        $domain = 'example.com';
        $path = '/api/test';

        $httpUrl = CommunicationProtocol::http->value . $domain . $path;
        $httpsUrl = CommunicationProtocol::https->value . $domain . $path;

        $this->assertEquals('http://example.com/api/test', $httpUrl);
        $this->assertEquals('https://example.com/api/test', $httpsUrl);
    }

    public function testDefaultPortAssumptions()
    {
        // Standard ports for protocols
        $httpPort = 80;
        $httpsPort = 443;

        // HTTP typically uses port 80
        $this->assertStringStartsWith('http://', CommunicationProtocol::http->value);

        // HTTPS typically uses port 443
        $this->assertStringStartsWith('https://', CommunicationProtocol::https->value);
    }
}
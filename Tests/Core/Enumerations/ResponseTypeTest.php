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
use SismaFramework\Core\Enumerations\ResponseType;

/**
 * Test for ResponseType enumeration
 * @author Valentino de Lapa
 */
class ResponseTypeTest extends TestCase
{
    public function testEnumValuesAreCorrect()
    {
        $this->assertEquals(200, ResponseType::httpOk->value);
        $this->assertEquals(404, ResponseType::httpNotFound->value);
        $this->assertEquals(500, ResponseType::httpInternalServerError->value);
        $this->assertEquals(401, ResponseType::httpUnauthorized->value);
        $this->assertEquals(403, ResponseType::httpForbidden->value);
    }

    public function testAllHttpStatusCodes()
    {
        // Test informational responses
        $this->assertEquals(100, ResponseType::httpContinue->value);
        $this->assertEquals(101, ResponseType::httpSwitchingProtocols->value);
        $this->assertEquals(102, ResponseType::httpProcessing->value);
        $this->assertEquals(103, ResponseType::httpEarlyHints->value);

        // Test successful responses
        $this->assertEquals(200, ResponseType::httpOk->value);
        $this->assertEquals(201, ResponseType::httpCreated->value);
        $this->assertEquals(204, ResponseType::httpNoContent->value);

        // Test redirection messages
        $this->assertEquals(301, ResponseType::httpMovedPermanently->value);
        $this->assertEquals(302, ResponseType::httpFound->value);
        $this->assertEquals(304, ResponseType::httpNotModified->value);

        // Test client error responses
        $this->assertEquals(400, ResponseType::httpBadRequest->value);
        $this->assertEquals(401, ResponseType::httpUnauthorized->value);
        $this->assertEquals(403, ResponseType::httpForbidden->value);
        $this->assertEquals(404, ResponseType::httpNotFound->value);
        $this->assertEquals(418, ResponseType::httpIAmATeapot->value);
        $this->assertEquals(429, ResponseType::httpTooManyRequests->value);

        // Test server error responses
        $this->assertEquals(500, ResponseType::httpInternalServerError->value);
        $this->assertEquals(501, ResponseType::httpNotImplemented->value);
        $this->assertEquals(502, ResponseType::httpBadGateway->value);
        $this->assertEquals(503, ResponseType::httpServiceUnavailable->value);
    }

    public function testEnumIsBackedByInt()
    {
        $this->assertInstanceOf(\BackedEnum::class, ResponseType::httpOk);
        $this->assertIsInt(ResponseType::httpOk->value);
    }

    public function testFromMethodWorks()
    {
        $this->assertEquals(ResponseType::httpOk, ResponseType::from(200));
        $this->assertEquals(ResponseType::httpNotFound, ResponseType::from(404));
        $this->assertEquals(ResponseType::httpInternalServerError, ResponseType::from(500));
    }

    public function testTryFromMethodWorks()
    {
        $this->assertEquals(ResponseType::httpOk, ResponseType::tryFrom(200));
        $this->assertEquals(ResponseType::httpNotFound, ResponseType::tryFrom(404));
        $this->assertNull(ResponseType::tryFrom(999)); // Non-existent status code
    }

    public function testCasesMethodReturnsAllValues()
    {
        $cases = ResponseType::cases();
        $this->assertIsArray($cases);
        $this->assertGreaterThan(50, count($cases)); // Should have many HTTP status codes

        // Verify some key cases are present
        $values = array_map(fn($case) => $case->value, $cases);
        $this->assertContains(200, $values);
        $this->assertContains(404, $values);
        $this->assertContains(500, $values);
    }

    public function testSpecialStatusCodes()
    {
        // Test null status
        $this->assertEquals(0, ResponseType::httpNull->value);

        // Test RFC7238 permanent redirect
        $this->assertEquals(308, ResponseType::httpPermanentlyRedirect->value);

        // Test security-related codes
        $this->assertEquals(451, ResponseType::httpUnavailableForLegalReasons->value);
        $this->assertEquals(511, ResponseType::httpNetworkAuthenticationRequired->value);
    }
}
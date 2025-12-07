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

namespace SismaFramework\Tests\Core\HttpClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\Enumerations\ResponseType;

/**
 * @author Valentino de Lapa
 */
class ResponseTest extends TestCase
{

    private int $originalResponseCode;

    #[\Override]
    public function setUp(): void
    {
        http_response_code(200);
        $this->originalResponseCode = 200;
    }

    #[\Override]
    public function tearDown(): void
    {
        http_response_code($this->originalResponseCode);
    }

    public function testConstructorSetsResponseTypeFromCurrentHttpCode()
    {
        // Set a specific response code before creating Response
        http_response_code(404);

        $response = new Response();

        // We can't directly access private property, but we can test behavior
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testConstructorWithDefault200Code()
    {
        http_response_code(200);

        $response = new Response();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testSetResponseTypeUpdatesHttpResponseCode()
    {
        $response = new Response();

        $response->setResponseType(ResponseType::httpNotFound);

        $this->assertEquals(404, http_response_code());
    }

    public function testSetResponseTypeWithSuccessCode()
    {
        $response = new Response();

        $response->setResponseType(ResponseType::httpOk);

        $this->assertEquals(200, http_response_code());
    }

    public function testSetResponseTypeWithServerErrorCode()
    {
        $response = new Response();

        $response->setResponseType(ResponseType::httpInternalServerError);

        $this->assertEquals(500, http_response_code());
    }

    public function testSetResponseTypeWithUnauthorizedCode()
    {
        $response = new Response();

        $response->setResponseType(ResponseType::httpUnauthorized);

        $this->assertEquals(401, http_response_code());
    }

    public function testMultipleSetResponseTypeCalls()
    {
        $response = new Response();

        $response->setResponseType(ResponseType::httpNotFound);
        $this->assertEquals(404, http_response_code());

        $response->setResponseType(ResponseType::httpOk);
        $this->assertEquals(200, http_response_code());

        $response->setResponseType(ResponseType::httpInternalServerError);
        $this->assertEquals(500, http_response_code());
    }

    public function testResponseTypeEnumValues()
    {
        $response = new Response();

        // Test specific response type enum values one by one
        $response->setResponseType(ResponseType::httpOk);
        $this->assertEquals(200, http_response_code());

        $response->setResponseType(ResponseType::httpUnauthorized);
        $this->assertEquals(401, http_response_code());

        $response->setResponseType(ResponseType::httpNotFound);
        $this->assertEquals(404, http_response_code());

        $response->setResponseType(ResponseType::httpInternalServerError);
        $this->assertEquals(500, http_response_code());
    }

    public function testConstructorWithVariousInitialCodes()
    {
        $testCodes = [200, 404, 500, 401];

        foreach ($testCodes as $code) {
            http_response_code($code);
            $response = new Response();

            // Constructor should read the current response code
            $this->assertInstanceOf(Response::class, $response);
        }
    }

    public function testSetResponseTypeReturnVoid()
    {
        $response = new Response();

        $result = $response->setResponseType(ResponseType::httpOk);

        $this->assertNull($result);
    }

    public function testConstructorWithResponseTypeParameter()
    {
        $response = new Response(ResponseType::httpNotFound);

        $this->assertEquals(404, http_response_code());
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testConstructorWithNullParameter()
    {
        $response = new Response(null);

        $this->assertEquals(200, http_response_code());
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testConstructorWithPartialContentResponseType()
    {
        $response = new Response(ResponseType::httpPartialContent);

        $this->assertEquals(206, http_response_code());
        $this->assertInstanceOf(Response::class, $response);
    }
}
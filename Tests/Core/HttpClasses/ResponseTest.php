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
        http_response_code(404);

        $response = new Response();

        $this->assertEquals(404, http_response_code());
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testConstructorWithDefault200Code()
    {
        http_response_code(200);

        $response = new Response();

        $this->assertEquals(200, http_response_code());
        $this->assertInstanceOf(Response::class, $response);
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

    public function testConstructorWithVariousResponseTypes()
    {
        $testCases = [
            [ResponseType::httpOk, 200],
            [ResponseType::httpUnauthorized, 401],
            [ResponseType::httpNotFound, 404],
            [ResponseType::httpInternalServerError, 500],
            [ResponseType::httpPartialContent, 206],
            [ResponseType::httpRequestedRangeNotSatisfiable, 416],
        ];

        foreach ($testCases as [$responseType, $expectedCode]) {
            http_response_code(200); // Reset
            $response = new Response($responseType);

            $this->assertEquals($expectedCode, http_response_code());
            $this->assertInstanceOf(Response::class, $response);
        }
    }
}

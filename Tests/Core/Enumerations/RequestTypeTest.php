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
use SismaFramework\Core\Enumerations\RequestType;

/**
 * Test for RequestType enumeration
 * @author Valentino de Lapa
 */
class RequestTypeTest extends TestCase
{
    public function testEnumValuesAreCorrect()
    {
        $this->assertEquals('GET', RequestType::methodGet->value);
        $this->assertEquals('POST', RequestType::methodPost->value);
        $this->assertEquals('PUT', RequestType::methodPut->value);
        $this->assertEquals('DELETE', RequestType::methodDelete->value);
        $this->assertEquals('PATCH', RequestType::methodPatch->value);
    }

    public function testAllHttpMethods()
    {
        $this->assertEquals('HEAD', RequestType::methodHead->value);
        $this->assertEquals('GET', RequestType::methodGet->value);
        $this->assertEquals('POST', RequestType::methodPost->value);
        $this->assertEquals('PUT', RequestType::methodPut->value);
        $this->assertEquals('PATCH', RequestType::methodPatch->value);
        $this->assertEquals('DELETE', RequestType::methodDelete->value);
        $this->assertEquals('PURGE', RequestType::methodPurge->value);
        $this->assertEquals('OPTIONS', RequestType::methodOptions->value);
        $this->assertEquals('TRACE', RequestType::methodTrace->value);
        $this->assertEquals('CONNECT', RequestType::methodConnect->value);
    }

    public function testEnumIsBackedByString()
    {
        $this->assertInstanceOf(\BackedEnum::class, RequestType::methodGet);
        $this->assertIsString(RequestType::methodGet->value);
    }

    public function testFromMethodWorks()
    {
        $this->assertEquals(RequestType::methodGet, RequestType::from('GET'));
        $this->assertEquals(RequestType::methodPost, RequestType::from('POST'));
        $this->assertEquals(RequestType::methodPut, RequestType::from('PUT'));
        $this->assertEquals(RequestType::methodDelete, RequestType::from('DELETE'));
    }

    public function testTryFromMethodWorks()
    {
        $this->assertEquals(RequestType::methodGet, RequestType::tryFrom('GET'));
        $this->assertEquals(RequestType::methodPost, RequestType::tryFrom('POST'));
        $this->assertNull(RequestType::tryFrom('INVALID')); // Non-existent method
        $this->assertNull(RequestType::tryFrom('get')); // Case sensitive
    }

    public function testCasesMethodReturnsAllValues()
    {
        $cases = RequestType::cases();
        $this->assertIsArray($cases);
        $this->assertEquals(10, count($cases)); // Should have exactly 10 HTTP methods

        // Verify all cases are present
        $values = array_map(fn($case) => $case->value, $cases);
        $expectedMethods = ['HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'PURGE', 'OPTIONS', 'TRACE', 'CONNECT'];

        foreach ($expectedMethods as $method) {
            $this->assertContains($method, $values);
        }
    }

    public function testSafeAndIdempotentMethods()
    {
        // Safe methods (read-only)
        $safeMethods = [
            RequestType::methodGet,
            RequestType::methodHead,
            RequestType::methodOptions,
            RequestType::methodTrace
        ];

        foreach ($safeMethods as $method) {
            $this->assertContains($method->value, ['GET', 'HEAD', 'OPTIONS', 'TRACE']);
        }

        // Idempotent methods
        $idempotentMethods = [
            RequestType::methodGet,
            RequestType::methodHead,
            RequestType::methodPut,
            RequestType::methodDelete,
            RequestType::methodOptions,
            RequestType::methodTrace
        ];

        foreach ($idempotentMethods as $method) {
            $this->assertContains($method->value, ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS', 'TRACE']);
        }
    }

    public function testNonStandardMethods()
    {
        // Test less common HTTP methods
        $this->assertEquals('PURGE', RequestType::methodPurge->value);
        $this->assertEquals('CONNECT', RequestType::methodConnect->value);
        $this->assertEquals('TRACE', RequestType::methodTrace->value);
    }
}
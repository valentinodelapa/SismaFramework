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

namespace SismaFramework\Tests\Odm\Enumerations;

use PHPUnit\Framework\TestCase;
use SismaFramework\Odm\Enumerations\Indexing;

/**
 * @author Valentino de Lapa
 */
class IndexingTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(Indexing::class));
    }

    public function testAscValue(): void
    {
        $this->assertEquals(1, Indexing::asc->value);
    }

    public function testDescValue(): void
    {
        $this->assertEquals(-1, Indexing::desc->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(Indexing::asc, Indexing::from(1));
        $this->assertSame(Indexing::desc, Indexing::from(-1));
    }

    public function testValuesAreNativeMongodbSortDirections(): void
    {
        // MongoDB usa 1 per ASC e -1 per DESC nativamente
        $this->assertSame(1, Indexing::asc->value);
        $this->assertSame(-1, Indexing::desc->value);
    }
}

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
use SismaFramework\Odm\Adapters\AdapterMongodb;
use SismaFramework\Odm\Enumerations\OdmAdapterType;

/**
 * @author Valentino de Lapa
 */
class OdmAdapterTypeTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(OdmAdapterType::class));
    }

    public function testMongodbCaseExists(): void
    {
        $cases = OdmAdapterType::cases();
        $caseNames = array_map(fn($case) => $case->name, $cases);
        $this->assertContains('mongodb', $caseNames);
    }

    public function testMongodbValue(): void
    {
        $this->assertEquals('mongodb', OdmAdapterType::mongodb->value);
    }

    public function testGetAdapterClassMongodb(): void
    {
        $this->assertEquals(AdapterMongodb::class, OdmAdapterType::mongodb->getAdapterClass());
    }

    public function testFromValue(): void
    {
        $type = OdmAdapterType::from('mongodb');
        $this->assertSame(OdmAdapterType::mongodb, $type);
    }

    public function testTryFromValidValue(): void
    {
        $type = OdmAdapterType::tryFrom('mongodb');
        $this->assertNotNull($type);
        $this->assertSame(OdmAdapterType::mongodb, $type);
    }

    public function testTryFromInvalidValue(): void
    {
        $type = OdmAdapterType::tryFrom('nonexistent');
        $this->assertNull($type);
    }
}

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
use SismaFramework\Odm\Enumerations\LogicalOperator;
use SismaFramework\Odm\Enumerations\OdmAdapterType;

/**
 * @author Valentino de Lapa
 */
class LogicalOperatorTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(LogicalOperator::class));
    }

    public function testAllCasesExist(): void
    {
        $caseNames = array_map(fn($c) => $c->name, LogicalOperator::cases());
        $this->assertContains('and', $caseNames);
        $this->assertContains('or', $caseNames);
        $this->assertContains('not', $caseNames);
        $this->assertCount(3, $caseNames);
    }

    public function testOdmKeywordTraitIsUsed(): void
    {
        $reflection = new \ReflectionClass(LogicalOperator::class);
        $this->assertContains(
            'SismaFramework\Odm\Traits\OdmKeyword',
            $reflection->getTraitNames()
        );
    }

    public function testGetAdapterVersionMongodb(): void
    {
        $this->assertEquals('$and', LogicalOperator::and->getAdapterVersion(OdmAdapterType::mongodb));
        $this->assertEquals('$or', LogicalOperator::or->getAdapterVersion(OdmAdapterType::mongodb));
        $this->assertEquals('$nor', LogicalOperator::not->getAdapterVersion(OdmAdapterType::mongodb));
    }

    public function testAllOperatorsReturnNonEmptyStringForMongodb(): void
    {
        foreach (LogicalOperator::cases() as $operator) {
            $version = $operator->getAdapterVersion(OdmAdapterType::mongodb);
            $this->assertIsString($version);
            $this->assertNotEmpty($version);
        }
    }
}

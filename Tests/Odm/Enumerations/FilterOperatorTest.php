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
use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\AdapterType;

/**
 * @author Valentino de Lapa
 */
class FilterOperatorTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(FilterOperator::class));
    }

    public function testAllCasesExist(): void
    {
        $expectedCases = [
            'equal', 'notEqual', 'greater', 'greaterOrEqual',
            'less', 'lessOrEqual', 'in', 'notIn',
            'like', 'notLike', 'isNull', 'isNotNull',
        ];
        $caseNames = array_map(fn($c) => $c->name, FilterOperator::cases());
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $caseNames);
        }
    }

    public function testOdmKeywordTraitIsUsed(): void
    {
        $reflection = new \ReflectionClass(FilterOperator::class);
        $this->assertContains(
            'SismaFramework\Odm\Traits\OdmKeyword',
            $reflection->getTraitNames()
        );
    }

    public function testGetAdapterVersionMongodbComparisonOperators(): void
    {
        $this->assertEquals('$eq', FilterOperator::equal->getAdapterVersion(AdapterType::mongodb));
        $this->assertEquals('$ne', FilterOperator::notEqual->getAdapterVersion(AdapterType::mongodb));
        $this->assertEquals('$gt', FilterOperator::greater->getAdapterVersion(AdapterType::mongodb));
        $this->assertEquals('$gte', FilterOperator::greaterOrEqual->getAdapterVersion(AdapterType::mongodb));
        $this->assertEquals('$lt', FilterOperator::less->getAdapterVersion(AdapterType::mongodb));
        $this->assertEquals('$lte', FilterOperator::lessOrEqual->getAdapterVersion(AdapterType::mongodb));
    }

    public function testGetAdapterVersionMongodbListOperators(): void
    {
        $this->assertEquals('$in', FilterOperator::in->getAdapterVersion(AdapterType::mongodb));
        $this->assertEquals('$nin', FilterOperator::notIn->getAdapterVersion(AdapterType::mongodb));
    }

    public function testGetAdapterVersionMongodbPatternOperators(): void
    {
        $this->assertEquals('$regex', FilterOperator::like->getAdapterVersion(AdapterType::mongodb));
        $this->assertEquals('$not', FilterOperator::notLike->getAdapterVersion(AdapterType::mongodb));
    }

    public function testGetAdapterVersionMongodbNullOperators(): void
    {
        $this->assertEquals('$eq', FilterOperator::isNull->getAdapterVersion(AdapterType::mongodb));
        $this->assertEquals('$ne', FilterOperator::isNotNull->getAdapterVersion(AdapterType::mongodb));
    }

    public function testAllOperatorsReturnNonEmptyStringForMongodb(): void
    {
        foreach (FilterOperator::cases() as $operator) {
            $version = $operator->getAdapterVersion(AdapterType::mongodb);
            $this->assertIsString($version);
            $this->assertNotEmpty($version);
        }
    }
}

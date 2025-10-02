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

namespace SismaFramework\Tests\Orm\Enumerations;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\AdapterType;

/**
 * Test for ComparisonOperator enumeration
 * @author Valentino de Lapa
 */
class ComparisonOperatorTest extends TestCase
{
    public function testEnumExists()
    {
        $this->assertTrue(enum_exists(ComparisonOperator::class));
    }

    public function testAllComparisonOperatorCasesExist()
    {
        $expectedCases = [
            'against',
            'equal',
            'notEqual',
            'greater',
            'less',
            'greaterOrEqual',
            'lessOrEqual',
            'in',
            'notIn',
            'like',
            'notLike',
            'isNull',
            'isNotNull'
        ];

        $cases = ComparisonOperator::cases();
        $caseNames = array_map(fn($case) => $case->name, $cases);

        foreach ($expectedCases as $expectedCase) {
            $this->assertContains($expectedCase, $caseNames);
        }
    }

    public function testCasesMethodReturnsAllOperators()
    {
        $cases = ComparisonOperator::cases();
        $this->assertIsArray($cases);
        $this->assertEquals(13, count($cases));
    }

    public function testOrmKeywordTraitIsUsed()
    {
        $reflection = new \ReflectionClass(ComparisonOperator::class);
        $traitNames = $reflection->getTraitNames();
        $this->assertContains('SismaFramework\Orm\Traits\OrmKeyword', $traitNames);
    }

    public function testGetAdapterVersionForMysqlBasicOperators()
    {
        $this->assertEquals('=', ComparisonOperator::equal->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('<>', ComparisonOperator::notEqual->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('>', ComparisonOperator::greater->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('<', ComparisonOperator::less->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('>=', ComparisonOperator::greaterOrEqual->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('<=', ComparisonOperator::lessOrEqual->getAdapterVersion(AdapterType::mysql));
    }

    public function testGetAdapterVersionForMysqlListOperators()
    {
        $this->assertEquals('IN', ComparisonOperator::in->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('NOT IN', ComparisonOperator::notIn->getAdapterVersion(AdapterType::mysql));
    }

    public function testGetAdapterVersionForMysqlLikeOperators()
    {
        $this->assertEquals('LIKE', ComparisonOperator::like->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('NOT LIKE', ComparisonOperator::notLike->getAdapterVersion(AdapterType::mysql));
    }

    public function testGetAdapterVersionForMysqlNullOperators()
    {
        $this->assertEquals('IS NULL', ComparisonOperator::isNull->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('IS NOT NULL', ComparisonOperator::isNotNull->getAdapterVersion(AdapterType::mysql));
    }

    public function testGetAdapterVersionForMysqlSpecialOperators()
    {
        $this->assertEquals('AGAINST', ComparisonOperator::against->getAdapterVersion(AdapterType::mysql));
    }

    public function testAllOperatorsHaveMysqlImplementation()
    {
        $cases = ComparisonOperator::cases();

        foreach ($cases as $operator) {
            $adapterVersion = $operator->getAdapterVersion(AdapterType::mysql);
            $this->assertIsString($adapterVersion);
            $this->assertNotEmpty($adapterVersion);
        }
    }

    public function testComparisonOperatorTypes()
    {
        // Equality operators
        $equalityOperators = [
            ComparisonOperator::equal,
            ComparisonOperator::notEqual
        ];

        foreach ($equalityOperators as $operator) {
            $this->assertInstanceOf(ComparisonOperator::class, $operator);
        }

        // Range operators
        $rangeOperators = [
            ComparisonOperator::greater,
            ComparisonOperator::less,
            ComparisonOperator::greaterOrEqual,
            ComparisonOperator::lessOrEqual
        ];

        foreach ($rangeOperators as $operator) {
            $this->assertInstanceOf(ComparisonOperator::class, $operator);
        }

        // List operators
        $listOperators = [
            ComparisonOperator::in,
            ComparisonOperator::notIn
        ];

        foreach ($listOperators as $operator) {
            $this->assertInstanceOf(ComparisonOperator::class, $operator);
        }

        // Pattern operators
        $patternOperators = [
            ComparisonOperator::like,
            ComparisonOperator::notLike
        ];

        foreach ($patternOperators as $operator) {
            $this->assertInstanceOf(ComparisonOperator::class, $operator);
        }

        // Null operators
        $nullOperators = [
            ComparisonOperator::isNull,
            ComparisonOperator::isNotNull
        ];

        foreach ($nullOperators as $operator) {
            $this->assertInstanceOf(ComparisonOperator::class, $operator);
        }
    }

    public function testOperatorNaming()
    {
        $this->assertEquals('equal', ComparisonOperator::equal->name);
        $this->assertEquals('notEqual', ComparisonOperator::notEqual->name);
        $this->assertEquals('greater', ComparisonOperator::greater->name);
        $this->assertEquals('less', ComparisonOperator::less->name);
        $this->assertEquals('greaterOrEqual', ComparisonOperator::greaterOrEqual->name);
        $this->assertEquals('lessOrEqual', ComparisonOperator::lessOrEqual->name);
        $this->assertEquals('in', ComparisonOperator::in->name);
        $this->assertEquals('notIn', ComparisonOperator::notIn->name);
        $this->assertEquals('like', ComparisonOperator::like->name);
        $this->assertEquals('notLike', ComparisonOperator::notLike->name);
        $this->assertEquals('isNull', ComparisonOperator::isNull->name);
        $this->assertEquals('isNotNull', ComparisonOperator::isNotNull->name);
        $this->assertEquals('against', ComparisonOperator::against->name);
    }

    public function testSwitchStatementCompatibility()
    {
        foreach (ComparisonOperator::cases() as $operator) {
            $result = match ($operator) {
                ComparisonOperator::against => 'fulltext',
                ComparisonOperator::equal => 'equals',
                ComparisonOperator::notEqual => 'not equals',
                ComparisonOperator::greater => 'greater than',
                ComparisonOperator::less => 'less than',
                ComparisonOperator::greaterOrEqual => 'greater or equal',
                ComparisonOperator::lessOrEqual => 'less or equal',
                ComparisonOperator::in => 'in list',
                ComparisonOperator::notIn => 'not in list',
                ComparisonOperator::like => 'pattern match',
                ComparisonOperator::notLike => 'not pattern match',
                ComparisonOperator::isNull => 'is null',
                ComparisonOperator::isNotNull => 'is not null',
            };

            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }
}
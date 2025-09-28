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
use SismaFramework\Orm\Enumerations\LogicalOperator;
use SismaFramework\Orm\Enumerations\AdapterType;

/**
 * Test for LogicalOperator enumeration
 * @author Valentino de Lapa
 */
class LogicalOperatorTest extends TestCase
{
    public function testEnumExists()
    {
        $this->assertTrue(enum_exists(LogicalOperator::class));
    }

    public function testAllLogicalOperatorCasesExist()
    {
        $expectedCases = [
            'and',
            'or',
            'not'
        ];

        $cases = LogicalOperator::cases();
        $caseNames = array_map(fn($case) => $case->name, $cases);

        foreach ($expectedCases as $expectedCase) {
            $this->assertContains($expectedCase, $caseNames);
        }
    }

    public function testCasesMethodReturnsAllOperators()
    {
        $cases = LogicalOperator::cases();
        $this->assertIsArray($cases);
        $this->assertEquals(3, count($cases));
    }

    public function testOrmKeywordTraitIsUsed()
    {
        $reflection = new \ReflectionClass(LogicalOperator::class);
        $traitNames = $reflection->getTraitNames();
        $this->assertContains('SismaFramework\Orm\Traits\OrmKeyword', $traitNames);
    }

    public function testGetAdapterVersionForMysqlOperators()
    {
        $this->assertEquals('AND', LogicalOperator::and->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('OR', LogicalOperator::or->getAdapterVersion(AdapterType::mysql));
        $this->assertEquals('NOT', LogicalOperator::not->getAdapterVersion(AdapterType::mysql));
    }

    public function testAllOperatorsHaveMysqlImplementation()
    {
        $cases = LogicalOperator::cases();

        foreach ($cases as $operator) {
            $adapterVersion = $operator->getAdapterVersion(AdapterType::mysql);
            $this->assertIsString($adapterVersion);
            $this->assertNotEmpty($adapterVersion);
        }
    }

    public function testLogicalOperatorInstances()
    {
        $this->assertInstanceOf(LogicalOperator::class, LogicalOperator::and);
        $this->assertInstanceOf(LogicalOperator::class, LogicalOperator::or);
        $this->assertInstanceOf(LogicalOperator::class, LogicalOperator::not);
    }

    public function testOperatorNaming()
    {
        $this->assertEquals('and', LogicalOperator::and->name);
        $this->assertEquals('or', LogicalOperator::or->name);
        $this->assertEquals('not', LogicalOperator::not->name);
    }

    public function testOperatorComparison()
    {
        // Test that same operators are equal
        $this->assertEquals(LogicalOperator::and, LogicalOperator::and);
        $this->assertEquals(LogicalOperator::or, LogicalOperator::or);
        $this->assertEquals(LogicalOperator::not, LogicalOperator::not);

        // Test that different operators are not equal
        $this->assertNotEquals(LogicalOperator::and, LogicalOperator::or);
        $this->assertNotEquals(LogicalOperator::or, LogicalOperator::not);
        $this->assertNotEquals(LogicalOperator::and, LogicalOperator::not);
    }

    public function testSwitchStatementCompatibility()
    {
        foreach (LogicalOperator::cases() as $operator) {
            $result = match ($operator) {
                LogicalOperator::and => 'conjunction',
                LogicalOperator::or => 'disjunction',
                LogicalOperator::not => 'negation',
            };

            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }

    public function testLogicalOperatorTypes()
    {
        // Binary operators (require two operands)
        $binaryOperators = [
            LogicalOperator::and,
            LogicalOperator::or
        ];

        foreach ($binaryOperators as $operator) {
            $this->assertInstanceOf(LogicalOperator::class, $operator);
            $sqlVersion = $operator->getAdapterVersion(AdapterType::mysql);
            $this->assertContains($sqlVersion, ['AND', 'OR']);
        }

        // Unary operators (require one operand)
        $unaryOperators = [
            LogicalOperator::not
        ];

        foreach ($unaryOperators as $operator) {
            $this->assertInstanceOf(LogicalOperator::class, $operator);
            $sqlVersion = $operator->getAdapterVersion(AdapterType::mysql);
            $this->assertEquals('NOT', $sqlVersion);
        }
    }

    public function testBooleanAlgebraProperties()
    {
        // Test that we have the basic logical operators for boolean algebra
        $basicOperators = [
            LogicalOperator::and,    // Conjunction
            LogicalOperator::or,     // Disjunction
            LogicalOperator::not     // Negation
        ];

        $this->assertEquals(3, count($basicOperators));

        foreach ($basicOperators as $operator) {
            $this->assertInstanceOf(LogicalOperator::class, $operator);
        }
    }

    public function testSqlStandardCompliance()
    {
        // Test that the MySQL adapter returns SQL standard keywords
        $sqlKeywords = [
            LogicalOperator::and->getAdapterVersion(AdapterType::mysql),
            LogicalOperator::or->getAdapterVersion(AdapterType::mysql),
            LogicalOperator::not->getAdapterVersion(AdapterType::mysql)
        ];

        $expectedSqlKeywords = ['AND', 'OR', 'NOT'];

        foreach ($expectedSqlKeywords as $keyword) {
            $this->assertContains($keyword, $sqlKeywords);
        }
    }
}
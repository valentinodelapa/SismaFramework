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
use SismaFramework\Orm\Enumerations\AggregationFunction;
use SismaFramework\Orm\Enumerations\AdapterType;

/**
 * Test for AggregationFunction enumeration
 * @author Valentino de Lapa
 */
class AggregationFunctionTest extends TestCase
{
    public function testEnumExists()
    {
        $this->assertTrue(enum_exists(AggregationFunction::class));
    }

    public function testAllAggregationFunctionCasesExist()
    {
        $expectedCases = [
            'avg',
            'count',
            'max',
            'min',
            'sum'
        ];

        $cases = AggregationFunction::cases();
        $caseNames = array_map(fn($case) => $case->name, $cases);

        foreach ($expectedCases as $expectedCase) {
            $this->assertContains($expectedCase, $caseNames);
        }
    }

    public function testCasesMethodReturnsAllFunctions()
    {
        $cases = AggregationFunction::cases();
        $this->assertIsArray($cases);
        $this->assertEquals(5, count($cases));
    }

    public function testOrmKeywordTraitIsUsed()
    {
        $reflection = new \ReflectionClass(AggregationFunction::class);
        $traitNames = $reflection->getTraitNames();
        $this->assertContains('SismaFramework\Orm\Traits\OrmKeyword', $traitNames);
    }

    public function testGetAdapterVersionForMysqlAVG()
    {
        $this->assertEquals('AVG', AggregationFunction::avg->getAdapterVersion(AdapterType::mysql));
    }

    public function testGetAdapterVersionForMysqlCOUNT()
    {
        $this->assertEquals('COUNT', AggregationFunction::count->getAdapterVersion(AdapterType::mysql));
    }

    public function testGetAdapterVersionForMysqlMAX()
    {
        $this->assertEquals('MAX', AggregationFunction::max->getAdapterVersion(AdapterType::mysql));
    }

    public function testGetAdapterVersionForMysqlMIN()
    {
        $this->assertEquals('MIN', AggregationFunction::min->getAdapterVersion(AdapterType::mysql));
    }

    public function testGetAdapterVersionForMysqlSUM()
    {
        $this->assertEquals('SUM', AggregationFunction::sum->getAdapterVersion(AdapterType::mysql));
    }

    public function testAllFunctionsHaveMysqlImplementation()
    {
        $cases = AggregationFunction::cases();

        foreach ($cases as $function) {
            $adapterVersion = $function->getAdapterVersion(AdapterType::mysql);
            $this->assertIsString($adapterVersion);
            $this->assertNotEmpty($adapterVersion);
        }
    }

    public function testAggregationFunctionTypes()
    {
        $functions = [
            AggregationFunction::avg,
            AggregationFunction::count,
            AggregationFunction::max,
            AggregationFunction::min,
            AggregationFunction::sum
        ];

        foreach ($functions as $function) {
            $this->assertInstanceOf(AggregationFunction::class, $function);
        }
    }

    public function testFunctionNaming()
    {
        $this->assertEquals('avg', AggregationFunction::avg->name);
        $this->assertEquals('count', AggregationFunction::count->name);
        $this->assertEquals('max', AggregationFunction::max->name);
        $this->assertEquals('min', AggregationFunction::min->name);
        $this->assertEquals('sum', AggregationFunction::sum->name);
    }

    public function testSwitchStatementCompatibility()
    {
        foreach (AggregationFunction::cases() as $function) {
            $result = match ($function) {
                AggregationFunction::avg => 'average',
                AggregationFunction::count => 'count rows',
                AggregationFunction::max => 'maximum',
                AggregationFunction::min => 'minimum',
                AggregationFunction::sum => 'total sum',
            };

            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }

    public function testGetAdapterVersionReturnsUppercase()
    {
        foreach (AggregationFunction::cases() as $function) {
            $adapterVersion = $function->getAdapterVersion(AdapterType::mysql);
            $this->assertEquals(strtoupper($function->name), $adapterVersion);
        }
    }
}

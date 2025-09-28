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
use SismaFramework\Orm\Enumerations\DataType;

/**
 * Test for DataType enumeration
 * @author Valentino de Lapa
 */
class DataTypeTest extends TestCase
{
    public function testEnumExists()
    {
        $this->assertTrue(enum_exists(DataType::class));
    }

    public function testAllDataTypeCasesExist()
    {
        $expectedCases = [
            'typeBoolean',
            'typeNull',
            'typeInteger',
            'typeString',
            'typeBinary',
            'typeDecimal',
            'typeDate',
            'typeStatement',
            'typeEntity',
            'typeEnumeration',
            'typeGeneric'
        ];

        $cases = DataType::cases();
        $caseNames = array_map(fn($case) => $case->name, $cases);

        foreach ($expectedCases as $expectedCase) {
            $this->assertContains($expectedCase, $caseNames);
        }
    }

    public function testCasesMethodReturnsAllDataTypes()
    {
        $cases = DataType::cases();
        $this->assertIsArray($cases);
        $this->assertEquals(11, count($cases));
    }

    public function testEachCaseIsInstance()
    {
        $cases = DataType::cases();

        foreach ($cases as $case) {
            $this->assertInstanceOf(DataType::class, $case);
        }
    }

    public function testBasicDataTypes()
    {
        // Test that basic data types are available
        $this->assertInstanceOf(DataType::class, DataType::typeBoolean);
        $this->assertInstanceOf(DataType::class, DataType::typeNull);
        $this->assertInstanceOf(DataType::class, DataType::typeInteger);
        $this->assertInstanceOf(DataType::class, DataType::typeString);
        $this->assertInstanceOf(DataType::class, DataType::typeDecimal);
    }

    public function testAdvancedDataTypes()
    {
        // Test that advanced data types are available
        $this->assertInstanceOf(DataType::class, DataType::typeBinary);
        $this->assertInstanceOf(DataType::class, DataType::typeDate);
        $this->assertInstanceOf(DataType::class, DataType::typeStatement);
        $this->assertInstanceOf(DataType::class, DataType::typeEntity);
        $this->assertInstanceOf(DataType::class, DataType::typeEnumeration);
        $this->assertInstanceOf(DataType::class, DataType::typeGeneric);
    }

    public function testDataTypeComparison()
    {
        // Test that same data types are equal
        $this->assertEquals(DataType::typeString, DataType::typeString);
        $this->assertEquals(DataType::typeInteger, DataType::typeInteger);

        // Test that different data types are not equal
        $this->assertNotEquals(DataType::typeString, DataType::typeInteger);
        $this->assertNotEquals(DataType::typeBoolean, DataType::typeNull);
    }

    public function testEnumNaming()
    {
        // Test that enum cases follow the expected naming pattern
        $this->assertEquals('typeBoolean', DataType::typeBoolean->name);
        $this->assertEquals('typeNull', DataType::typeNull->name);
        $this->assertEquals('typeInteger', DataType::typeInteger->name);
        $this->assertEquals('typeString', DataType::typeString->name);
        $this->assertEquals('typeBinary', DataType::typeBinary->name);
        $this->assertEquals('typeDecimal', DataType::typeDecimal->name);
        $this->assertEquals('typeDate', DataType::typeDate->name);
        $this->assertEquals('typeStatement', DataType::typeStatement->name);
        $this->assertEquals('typeEntity', DataType::typeEntity->name);
        $this->assertEquals('typeEnumeration', DataType::typeEnumeration->name);
        $this->assertEquals('typeGeneric', DataType::typeGeneric->name);
    }

    public function testEnumIsNotBacked()
    {
        // DataType is a pure enum (not backed by string or int)
        $this->assertFalse(DataType::typeString instanceof \BackedEnum);
    }

    public function testSwitchStatementCompatibility()
    {
        // Test that DataType can be used in match statements
        foreach (DataType::cases() as $dataType) {
            $result = match ($dataType) {
                DataType::typeBoolean => 'boolean',
                DataType::typeNull => 'null',
                DataType::typeInteger => 'integer',
                DataType::typeString => 'string',
                DataType::typeBinary => 'binary',
                DataType::typeDecimal => 'decimal',
                DataType::typeDate => 'date',
                DataType::typeStatement => 'statement',
                DataType::typeEntity => 'entity',
                DataType::typeEnumeration => 'enumeration',
                DataType::typeGeneric => 'generic',
            };

            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }
}
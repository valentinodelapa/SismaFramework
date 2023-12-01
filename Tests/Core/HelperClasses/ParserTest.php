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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */
class ParserTest extends TestCase
{

    public function testParseValueWithEmpty()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(true);
        $this->assertNull(Parser::parseValue($reflectionNamedTypeMock, ''));
        $this->assertNull(Parser::parseValue($reflectionNamedTypeMock, null));
    }

    public function testParseValueWithBuiltinInt()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(true);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn('int');
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, 1));
        $this->assertEquals(1, Parser::parseValue($reflectionNamedTypeMock, 1));
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, '1'));
        $this->assertEquals(1, Parser::parseValue($reflectionNamedTypeMock, '1'));
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, ''));
        $this->assertEquals(0, Parser::parseValue($reflectionNamedTypeMock, ''));
    }

    public function testParseValueWithBuiltinString()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(true);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn('string');
        $this->assertIsString(Parser::parseValue($reflectionNamedTypeMock, 'sample string'));
        $this->assertEquals('sample string', Parser::parseValue($reflectionNamedTypeMock, 'sample string'));
        $this->assertIsString(Parser::parseValue($reflectionNamedTypeMock, 1));
        $this->assertEquals('1', Parser::parseValue($reflectionNamedTypeMock, 1));
        $this->assertIsString(Parser::parseValue($reflectionNamedTypeMock, ''));
        $this->assertEquals('', Parser::parseValue($reflectionNamedTypeMock, ''));
    }

    public function testParseValueWithBuiltinFloat()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(true);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn('float');
        $this->assertIsFloat(Parser::parseValue($reflectionNamedTypeMock, 1.1));
        $this->assertEquals(1.1, Parser::parseValue($reflectionNamedTypeMock, 1.1));
        $this->assertIsFloat(Parser::parseValue($reflectionNamedTypeMock, '1.1'));
        $this->assertEquals(1.1, Parser::parseValue($reflectionNamedTypeMock, '1.1'));
        $this->assertIsFloat(Parser::parseValue($reflectionNamedTypeMock, ''));
        $this->assertEquals(0.0, Parser::parseValue($reflectionNamedTypeMock, ''));
    }

    public function testParseValueWithEntity()
    {
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        Cache::setEntity($baseSample);
        $baseResultSetMock = $this->createMock(BaseResultSet::class);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->any())
                ->method('select')
                ->willReturn($baseResultSetMock);
        BaseAdapter::setDefault($baseAdapterMock);
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn(BaseSample::class);
        $this->assertInstanceOf(BaseSample::class, Parser::parseValue($reflectionNamedTypeMock, 1));
        $this->assertInstanceOf(BaseSample::class, Parser::parseValue($reflectionNamedTypeMock, '1'));
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, 1, false));
        $this->assertEquals(1, Parser::parseValue($reflectionNamedTypeMock, 1, false));
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, '1', false));
        $this->assertEquals(1, Parser::parseValue($reflectionNamedTypeMock, '1', false));
    }

    public function testParseValueWithEnumeration()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn(SampleType::class);
        $this->assertInstanceOf(SampleType::class, Parser::parseValue($reflectionNamedTypeMock, 'O'));
    }

    public function testParseValueWithSismaDateTime()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn(SismaDateTime::class);
        $this->assertInstanceOf(SismaDateTime::class, Parser::parseValue($reflectionNamedTypeMock, '2000-01-01 00:00:00'));
        $this->assertInstanceOf(SismaDateTime::class, Parser::parseValue($reflectionNamedTypeMock, '2000-01-01'));
    }

    public function testParseValueWithArray()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn('array');
        $this->assertIsArray(Parser::parseValue($reflectionNamedTypeMock, []));
    }

    /**
     * @runInSeparateProcess
     */
    public function testParseValueWithException()
    {
        $this->expectException(InvalidArgumentException::class);
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn('array');
        Parser::parseValue($reflectionNamedTypeMock, '');
    }

    /**
     * @runInSeparateProcess
     */
    public function testParseEnumerationWithException()
    {
        $this->expectException(InvalidArgumentException::class);
        Parser::parseEnumeration(SampleType::class, 'F');
    }
    
    public function testUnparseValue()
    {
        $dataMapperMock = $this->createMock(DataMapper::class);
        $baseSample = new BaseSample($dataMapperMock);
        $baseSample->id = 1;
        $sampleType = SampleType::one;
        $sismaDatetme = new SismaDateTime();
        $array = [
            'baseSample' => $baseSample,
            'sampleType' => $sampleType,
            'sismaDatetime' => $sismaDatetme,
        ];
        Parser::unparseValues($array);
        $this->assertEquals(1, $array['baseSample']);
        $this->assertEquals('O', $array['sampleType']);
        $this->assertEquals($sismaDatetme->format('Y-m-d H:i:s'), $array['sismaDatetime']);
    }

}

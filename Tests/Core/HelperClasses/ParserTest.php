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

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaTime;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */
class ParserTest extends TestCase
{
    private ParserConfigTest $configTest;
    private DataMapper $dataMapperMock;
    
    #[\Override]
    public function setUp(): void
    {
        $this->configTest = new ParserConfigTest();
        BaseConfig::setInstance($this->configTest);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }

    public function testParseValueWithEmpty()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(true);
        $this->assertNull(Parser::parseValue($reflectionNamedTypeMock, '', true, $this->dataMapperMock));
        $this->assertNull(Parser::parseValue($reflectionNamedTypeMock, null, true, $this->dataMapperMock));
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
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, 1, true, $this->dataMapperMock));
        $this->assertEquals(1, Parser::parseValue($reflectionNamedTypeMock, 1, true, $this->dataMapperMock));
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, '1', true, $this->dataMapperMock));
        $this->assertEquals(1, Parser::parseValue($reflectionNamedTypeMock, '1', true, $this->dataMapperMock));
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, '', true, $this->dataMapperMock));
        $this->assertEquals(0, Parser::parseValue($reflectionNamedTypeMock, '', true, $this->dataMapperMock));
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
        $this->assertIsString(Parser::parseValue($reflectionNamedTypeMock, 'sample string', true, $this->dataMapperMock));
        $this->assertEquals('sample string', Parser::parseValue($reflectionNamedTypeMock, 'sample string', true, $this->dataMapperMock));
        $this->assertIsString(Parser::parseValue($reflectionNamedTypeMock, 1, true, $this->dataMapperMock));
        $this->assertEquals('1', Parser::parseValue($reflectionNamedTypeMock, 1, true, $this->dataMapperMock));
        $this->assertIsString(Parser::parseValue($reflectionNamedTypeMock, '', true, $this->dataMapperMock));
        $this->assertEquals('', Parser::parseValue($reflectionNamedTypeMock, '', true, $this->dataMapperMock));
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
        $this->assertIsFloat(Parser::parseValue($reflectionNamedTypeMock, 1.1, true, $this->dataMapperMock));
        $this->assertEquals(1.1, Parser::parseValue($reflectionNamedTypeMock, 1.1, true, $this->dataMapperMock));
        $this->assertIsFloat(Parser::parseValue($reflectionNamedTypeMock, '1.1', true, $this->dataMapperMock));
        $this->assertEquals(1.1, Parser::parseValue($reflectionNamedTypeMock, '1.1', true, $this->dataMapperMock));
        $this->assertIsFloat(Parser::parseValue($reflectionNamedTypeMock, '', true, $this->dataMapperMock));
        $this->assertEquals(0.0, Parser::parseValue($reflectionNamedTypeMock, '', true, $this->dataMapperMock));
    }

    #[RunInSeparateProcess]
    public function testParseValueWithEntity()
    {
        $baseSample = new BaseSample($this->dataMapperMock);
        $baseSample->id = 1;
        $this->dataMapperMock->expects($this->any())
                ->method('initQuery');
        $this->dataMapperMock->expects($this->any())
                ->method ('findFirst')
                ->willReturn($baseSample);
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn(BaseSample::class);
        $this->assertInstanceOf(BaseSample::class, Parser::parseValue($reflectionNamedTypeMock, 1, true, $this->dataMapperMock, $this->configTest));
        $this->assertInstanceOf(BaseSample::class, Parser::parseValue($reflectionNamedTypeMock, '1', true, $this->dataMapperMock, $this->configTest));
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, 1, false, $this->dataMapperMock, $this->configTest));
        $this->assertEquals(1, Parser::parseValue($reflectionNamedTypeMock, 1, false, $this->dataMapperMock, $this->configTest));
        $this->assertIsInt(Parser::parseValue($reflectionNamedTypeMock, '1', false, $this->dataMapperMock, $this->configTest));
        $this->assertEquals(1, Parser::parseValue($reflectionNamedTypeMock, '1', false, $this->dataMapperMock, $this->configTest));
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
        $this->assertInstanceOf(SampleType::class, Parser::parseValue($reflectionNamedTypeMock, 'O', true, $this->dataMapperMock));
    }

    public function testParseValueWithSismaDate()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn(SismaDate::class);
        $this->assertInstanceOf(SismaDate::class, Parser::parseValue($reflectionNamedTypeMock, '2000-01-01 00:00:00', true, $this->dataMapperMock));
        $this->assertInstanceOf(SismaDate::class, Parser::parseValue($reflectionNamedTypeMock, '2000-01-01', true, $this->dataMapperMock));
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
        $this->assertInstanceOf(SismaDateTime::class, Parser::parseValue($reflectionNamedTypeMock, '2000-01-01 00:00:00', true, $this->dataMapperMock));
        $this->assertInstanceOf(SismaDateTime::class, Parser::parseValue($reflectionNamedTypeMock, '2000-01-01', true, $this->dataMapperMock));
    }

    public function testParseValueWithSismaTime()
    {
        $reflectionNamedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $reflectionNamedTypeMock->method('allowsNull')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('isBuiltin')
                ->willReturn(false);
        $reflectionNamedTypeMock->method('getName')
                ->willReturn(SismaTime::class);
        $this->assertInstanceOf(SismaTime::class, Parser::parseValue($reflectionNamedTypeMock, '1:00:00', true, $this->dataMapperMock));
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
        $this->assertIsArray(Parser::parseValue($reflectionNamedTypeMock, [], true, $this->dataMapperMock));
    }

    #[RunInSeparateProcess]
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
        Parser::parseValue($reflectionNamedTypeMock, '', true, $this->dataMapperMock);
    }

    #[RunInSeparateProcess]
    public function testParseEnumerationWithException()
    {
        $this->expectException(InvalidArgumentException::class);
        Parser::parseEnumeration(SampleType::class, 'F', true, $this->dataMapperMock);
    }
    
    public function testUnparseValue()
    {
        $baseSample = new BaseSample($this->dataMapperMock);
        $baseSample->id = 1;
        $sampleType = SampleType::one;
        $sismaDate = new SismaDate();
        $sismaDateTime = new SismaDateTime();
        $sismaTime = SismaTime::createFromStandardTimeFormat('1:00:00');
        $array = [
            'baseSample' => $baseSample,
            'sampleType' => $sampleType,
            'sismaDate' => $sismaDate,
            'sismaDateTime' => $sismaDateTime,
            'sismaTime' => $sismaTime,
        ];
        Parser::unparseValues($array);
        $this->assertEquals(1, $array['baseSample']);
        $this->assertEquals($sampleType->value, $array['sampleType']);
        $this->assertEquals($sismaDate->format('Y-m-d'), $array['sismaDate']);
        $this->assertEquals($sismaDateTime->format('Y-m-d H:i:s'), $array['sismaDateTime']);
        $this->assertEquals($sismaTime->formatToStandardTimeFormat(), $array['sismaTime']);
    }

}

class ParserConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
                return false;
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->developmentEnvironment = false;
        $this->entityNamespace = 'TestsApplication\\Entities\\';
        $this->logDevelopmentMaxRow = 100;
        $this->logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->logPath = $this->logDirectoryPath . 'log.txt';
        $this->logProductionMaxRow = 2;
        $this->logVerboseActive = true;
        $this->modelNamespace = 'TestsApplication\\Models\\';
        $this->ormCache = true;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        
    }
}

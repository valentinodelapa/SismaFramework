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

namespace SismaFramework\Tests\Core\Enumerations;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Sample\Entities\SampleBaseEntity;
use SismaFramework\TestsApplication\Enumerations\SampleType;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaTime;

/**
 * Description of FilterTypeTest
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class FilterTypeTest extends TestCase
{

    private Filter $filterMock;

    public function setUp(): void
    {
        $this->filterMock = $this->createMock(Filter::class);
    }

    public function testNoFilter()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::noFilter->name)
                ->with('value');
        FilterType::noFilter->applyFilter('value', [], $this->filterMock);
    }

    public function testIsString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isString->name)
                ->with('value');
        FilterType::isString->applyFilter('value', [], $this->filterMock);
    }

    public function testIsMinLimitString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isMinLimitString->name)
                ->with('value', 5);
        FilterType::isMinLimitString->applyFilter('value', [5], $this->filterMock);
    }

    public function testIsMaxLimitString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isMaxLimitString->name)
                ->with('value', 10);
        FilterType::isMaxLimitString->applyFilter('value', [10], $this->filterMock);
    }

    public function testIsLimitString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isLimitString->name)
                ->with('value', 5, 10);
        FilterType::isLimitString->applyFilter('value', [5, 10], $this->filterMock);
    }

    public function testIsAlphabeticString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isAlphabeticString->name)
                ->with('value');
        FilterType::isAlphabeticString->applyFilter('value', [], $this->filterMock);
    }

    public function testIsMinLimitAlphabeticString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isMinLimitAlphabeticString->name)
                ->with('value', 3);
        FilterType::isMinLimitAlphabeticString->applyFilter('value', [3], $this->filterMock);
    }

    public function testIsMaxLimitAlphabeticString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isMaxLimitAlphabeticString->name)
                ->with('value', 5);
        FilterType::isMaxLimitAlphabeticString->applyFilter('value', [5], $this->filterMock);
    }

    public function testIsLimitAlphabeticString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isLimitAlphabeticString->name)
                ->with('value', 3, 5);
        FilterType::isLimitAlphabeticString->applyFilter('value', [3, 5], $this->filterMock);
    }

    public function testIsAlphanumericString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isAlphanumericString->name)
                ->with('value');
        FilterType::isAlphanumericString->applyFilter('value', [], $this->filterMock);
    }

    public function testIsMinLimitAlphanumericString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isMinLimitAlphanumericString->name)
                ->with('value', 3);
        FilterType::isMinLimitAlphanumericString->applyFilter('value', [3], $this->filterMock);
    }

    public function testIsMaxLimitAlphanumericString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isMaxLimitAlphanumericString->name)
                ->with('value', 5);
        FilterType::isMaxLimitAlphanumericString->applyFilter('value', [5], $this->filterMock);
    }

    public function testIsLimitAlphanumericString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isLimitAlphanumericString->name)
                ->with('value', 3, 5);
        FilterType::isLimitAlphanumericString->applyFilter('value', [3, 5], $this->filterMock);
    }

    public function testIsStrictAlphanumericString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isStrictAlphanumericString->name)
                ->with('value');
        FilterType::isStrictAlphanumericString->applyFilter('value', [], $this->filterMock);
    }

    public function testIsMinLimitStrictAlphanumericString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isMinLimitStrictAlphanumericString->name)
                ->with('value', 3);
        FilterType::isMinLimitStrictAlphanumericString->applyFilter('value', [3], $this->filterMock);
    }

    public function testIsMaxLimitStrictAlphanumericString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isMaxLimitStrictAlphanumericString->name)
                ->with('value', 5);
        FilterType::isMaxLimitStrictAlphanumericString->applyFilter('value', [5], $this->filterMock);
    }

    public function testIsLimitStrictAlphanumericString()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isLimitStrictAlphanumericString->name)
                ->with('value', 3, 5);
        FilterType::isLimitStrictAlphanumericString->applyFilter('value', [3, 5], $this->filterMock);
    }

    public function testIsSecurePassword()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isSecurePassword->name)
                ->with('value');
        FilterType::isSecurePassword->applyFilter('value', [], $this->filterMock);
    }

    public function testIsEmail()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isEmail->name)
                ->with('value');
        FilterType::isEmail->applyFilter('value', [], $this->filterMock);
    }

    public function testIsNumeric()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isNumeric->name)
                ->with('value');
        FilterType::isNumeric->applyFilter('value', [], $this->filterMock);
    }

    public function testIsInteger()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isInteger->name)
                ->with('value');
        FilterType::isInteger->applyFilter('value', [], $this->filterMock);
    }

    public function testIsFloat()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isFloat->name)
                ->with('value');
        FilterType::isFloat->applyFilter('value', [], $this->filterMock);
    }

    public function testIsBoolean()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isBoolean->name)
                ->with('value');
        FilterType::isBoolean->applyFilter('value', [], $this->filterMock);
    }

    public function testIsArray()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isArray->name)
                ->with('value');
        FilterType::isArray->applyFilter('value', [], $this->filterMock);
    }

    public function testIsDate()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isDate->name)
                ->with('value');
        FilterType::isDate->applyFilter('value', [], $this->filterMock);
    }

    public function testIsDatetime()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isDatetime->name)
                ->with('value');
        FilterType::isDatetime->applyFilter('value', [], $this->filterMock);
    }

    public function testIsTime()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isTime->name)
                ->with('value');
        FilterType::isTime->applyFilter('value', [], $this->filterMock);
    }

    public function testIsUploadedFile()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isUploadedFile->name)
                ->with('value');
        FilterType::isUploadedFile->applyFilter('value', [], $this->filterMock);
    }

    public function testIsEntity()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isEntity->name)
                ->with('value');
        FilterType::isEntity->applyFilter('value', [], $this->filterMock);
    }

    public function testIsEnumeration()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::isEnumeration->name)
                ->with('value');
        FilterType::isEnumeration->applyFilter('value', [], $this->filterMock);
    }

    public function testCustomFilter()
    {
        $this->filterMock->expects($this->once())
                ->method(FilterType::customFilter->name)
                ->with('value', 'regulatExpression');
        FilterType::customFilter->applyFilter('value', ['regulatExpression'], $this->filterMock);
    }

    public function testFromPhpType()
    {
        $this->filterMock->expects($this->never())
                ->method(FilterType::customFilter->name);
        $reflectionNabedTypeMock = $this->createMock(\ReflectionNamedType::class);
        $matcherOne = $this->exactly(9);
        $reflectionNabedTypeMock->expects($matcherOne)
                ->method('isBuiltin')
                ->willReturnCallback(function ()use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                            return true;
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            return false;
                    }
                });
        $matcherTwo = $this->exactly(19);
        $reflectionNabedTypeMock->expects($matcherTwo)
                ->method('getName')
                ->willReturnCallback(function ()use ($matcherTwo) {
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            return 'int';
                        case 2:
                            return 'float';
                        case 3:
                            return 'string';
                        case 4:
                            return 'bool';
                        case 5:
                            return SampleBaseEntity::class;
                        case 6:
                        case 7:
                            return SampleType::class;
                        case 8:
                        case 9:
                        case 10:
                            return SismaDate::class;
                        case 11:
                        case 12:
                        case 13:
                        case 14:
                            return SismaDateTime::class;
                        case 15:
                        case 16:
                        case 17:
                        case 18:
                        case 19:
                            return SismaTime::class;
                    }
                });
        $this->assertEquals(FilterType::isInteger, FilterType::fromPhpType($reflectionNabedTypeMock));
        $this->assertEquals(FilterType::isFloat, FilterType::fromPhpType($reflectionNabedTypeMock));
        $this->assertEquals(FilterType::isString, FilterType::fromPhpType($reflectionNabedTypeMock));
        $this->assertEquals(FilterType::isBoolean, FilterType::fromPhpType($reflectionNabedTypeMock));
        $this->assertEquals(FilterType::isEntity, FilterType::fromPhpType($reflectionNabedTypeMock));
        $this->assertEquals(FilterType::isEnumeration, FilterType::fromPhpType($reflectionNabedTypeMock));
        $this->assertEquals(FilterType::isDate, FilterType::fromPhpType($reflectionNabedTypeMock));
        $this->assertEquals(FilterType::isDatetime, FilterType::fromPhpType($reflectionNabedTypeMock));
        $this->assertEquals(FilterType::isTime, FilterType::fromPhpType($reflectionNabedTypeMock));
    }
}

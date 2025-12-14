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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaTime;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */
class FilterTest extends TestCase
{
    
    private Filter $filter;
    
    #[\Override]
    public function setUp(): void
    {
        $this->filter = new Filter();
    }

    public function testNoFilter()
    {
        $this->assertTrue($this->filter->noFilter('sample value'));
    }

    public function testIsNotNull()
    {
        $this->assertTrue($this->filter->isNotNull('not null value'));
        $this->assertFalse($this->filter->isNotNull(null));
    }

    public function testIsNotFalse()
    {
        $this->assertTrue($this->filter->isNotFalse('not false value'));
        $this->assertFalse($this->filter->isNotFalse(false));
    }

    public function testIsNotEmpty()
    {
        $this->assertTrue($this->filter->isNotEmpty(0));
        $this->assertTrue($this->filter->isNotEmpty('0'));
        $this->assertTrue($this->filter->isNotEmpty(0.0));
        $this->assertTrue($this->filter->isNotEmpty('0.0'));
        $this->assertTrue($this->filter->isNotEmpty('not empty value'));
        $this->assertFalse($this->filter->isNotEmpty(''));
    }

    public function testIsString()
    {
        $this->assertTrue($this->filter->isString('string value'));
        $this->assertFalse($this->filter->isString(1));
        $this->assertFalse($this->filter->isString(1.1));
        $this->assertFalse($this->filter->isString(null));
        $this->assertFalse($this->filter->isString(true));
        $this->assertFalse($this->filter->isString(false));
        $this->assertFalse($this->filter->isString(''));
        $this->assertFalse($this->filter->isString(['array']));
    }

    public function testIsMinLimitString()
    {
        $this->assertTrue($this->filter->isMinLimitString('string min limit value', 10));
        $this->assertFalse($this->filter->isMinLimitString('fake', 10));
    }

    public function testIsMaxLimitString()
    {
        $this->assertTrue($this->filter->isMaxLimitString('string max limit value', 30));
        $this->assertFalse($this->filter->isMaxLimitString('fake', 3));
    }

    public function testLimitString()
    {
        $this->assertTrue($this->filter->isLimitString('string limit value', 10, 30));
        $this->assertFalse($this->filter->isLimitString('fake', 1, 3));
        $this->assertFalse($this->filter->isLimitString('fake', 10, 30));
        $this->assertFalse($this->filter->isLimitString('fake', 10, 3));
    }

    public function testIsAlphabeticString()
    {
        $this->assertTrue($this->filter->isAlphabeticString('alphabeticStringValue'));
        $this->assertFalse($this->filter->isAlphabeticString('alphabetic string value with spaces'));
        $this->assertFalse($this->filter->isAlphabeticString('123alphanumeric'));
        $this->assertFalse($this->filter->isAlphabeticString('123 alphanumeric'));
        $this->assertFalse($this->filter->isAlphabeticString('123'));
    }

    public function testIsMinLimitAlphabeticString()
    {
        $this->assertTrue($this->filter->isMinLimitAlphabeticString('stringMinLimitValue', 10));
        $this->assertFalse($this->filter->isMinLimitAlphabeticString('string min limit value', 10));
        $this->assertFalse($this->filter->isMinLimitAlphabeticString('stringMaxLimitValue123', 10));
        $this->assertFalse($this->filter->isMinLimitAlphabeticString('fake', 10));
    }

    public function testIsMaxLimitAlphabeticString()
    {
        $this->assertTrue($this->filter->isMaxLimitAlphabeticString('stringMaxLimitValue', 30));
        $this->assertFalse($this->filter->isMaxLimitAlphabeticString('string max limit value', 30));
        $this->assertFalse($this->filter->isMaxLimitAlphabeticString('stringMaxLimitValue123', 30));
        $this->assertFalse($this->filter->isMaxLimitAlphabeticString('fake', 3));
    }

    public function testLimitAlphabeticString()
    {
        $this->assertTrue($this->filter->isLimitAlphabeticString('stringLimitValue', 10, 30));
        $this->assertFalse($this->filter->isLimitAlphabeticString('string limit value', 10, 30));
        $this->assertFalse($this->filter->isLimitAlphabeticString('stringMaxLimitValue123', 10, 30));
        $this->assertFalse($this->filter->isLimitAlphabeticString('fake', 1, 3));
        $this->assertFalse($this->filter->isLimitAlphabeticString('fake', 10, 30));
        $this->assertFalse($this->filter->isLimitAlphabeticString('fake', 10, 3));
    }

    public function testIsAlphanumericString()
    {
        $this->assertTrue($this->filter->isAlphanumericString('123alphanumericStringValue'));
        $this->assertTrue($this->filter->isAlphanumericString('alphabeticStringValue'));
        $this->assertTrue($this->filter->isAlphanumericString('123'));
        $this->assertFalse($this->filter->isAlphanumericString('123 alphanumeric string value'));
        $this->assertFalse($this->filter->isAlphanumericString('alphabetic string value with spaces'));
    }

    public function testIsMinLimitAlphanumericString()
    {
        $this->assertTrue($this->filter->isMinLimitAlphanumericString('123stringMinLimitValue', 10));
        $this->assertTrue($this->filter->isMinLimitAlphanumericString('stringMaxLimitValue', 10));
        $this->assertFalse($this->filter->isMinLimitAlphanumericString('123 string min limit value', 10));
        $this->assertFalse($this->filter->isMinLimitAlphanumericString('fake', 10));
    }

    public function testIsMaxLimitAlphanumericString()
    {
        $this->assertTrue($this->filter->isMaxLimitAlphanumericString('123stringMaxLimitValue', 30));
        $this->assertTrue($this->filter->isMaxLimitAlphanumericString('stringMaxLimitValue', 30));
        $this->assertFalse($this->filter->isMaxLimitAlphanumericString('123 string max limit value', 30));
        $this->assertFalse($this->filter->isMaxLimitAlphanumericString('fake', 3));
    }

    public function testLimitAlphanumericString()
    {
        $this->assertTrue($this->filter->isLimitAlphanumericString('123stringLimitValue', 10, 30));
        $this->assertTrue($this->filter->isLimitAlphanumericString('stringMaxLimitValue', 10, 30));
        $this->assertFalse($this->filter->isLimitAlphanumericString('123 string limit value', 10, 30));
        $this->assertFalse($this->filter->isLimitAlphanumericString('fake', 1, 3));
        $this->assertFalse($this->filter->isLimitAlphanumericString('fake', 10, 30));
        $this->assertFalse($this->filter->isLimitAlphanumericString('fake', 10, 3));
    }

    public function testIsStrictAlphanumericString()
    {
        $this->assertTrue($this->filter->isStrictAlphanumericString('123alphanumericStringValue'));
        $this->assertFalse($this->filter->isStrictAlphanumericString('123 alphanumeric string value'));
        $this->assertFalse($this->filter->isStrictAlphanumericString('alphabeticStringValue'));
        $this->assertFalse($this->filter->isStrictAlphanumericString('alphabetic string value with spaces'));
        $this->assertFalse($this->filter->isStrictAlphanumericString('123'));
    }

    public function testIsMinLimitStrictAlphanumericString()
    {
        $this->assertTrue($this->filter->isMinLimitStrictAlphanumericString('123stringMinLimitValue', 10));
        $this->assertFalse($this->filter->isMinLimitStrictAlphanumericString('123 string min limit value', 10));
        $this->assertFalse($this->filter->isMinLimitStrictAlphanumericString('stringMaxLimitValue', 10));
        $this->assertFalse($this->filter->isMinLimitStrictAlphanumericString('fake', 10));
    }

    public function testIsMaxLimitStrictAlphanumericString()
    {
        $this->assertTrue($this->filter->isMaxLimitStrictAlphanumericString('123stringMaxLimitValue', 30));
        $this->assertFalse($this->filter->isMaxLimitStrictAlphanumericString('123 string max limit value', 30));
        $this->assertFalse($this->filter->isMaxLimitStrictAlphanumericString('stringMaxLimitValue', 30));
        $this->assertFalse($this->filter->isMaxLimitStrictAlphanumericString('fake', 3));
    }

    public function testLimitStrictAlphanumericString()
    {
        $this->assertTrue($this->filter->isLimitStrictAlphanumericString('123stringLimitValue', 10, 30));
        $this->assertFalse($this->filter->isLimitStrictAlphanumericString('123 string limit value', 10, 30));
        $this->assertFalse($this->filter->isLimitStrictAlphanumericString('stringMaxLimitValue', 10, 30));
        $this->assertFalse($this->filter->isLimitStrictAlphanumericString('fake', 1, 3));
        $this->assertFalse($this->filter->isLimitStrictAlphanumericString('fake', 10, 30));
        $this->assertFalse($this->filter->isLimitStrictAlphanumericString('fake', 10, 3));
    }

    public function testIsSecurePassword()
    {
        $this->assertTrue($this->filter->isSecurePassword('SamplePassword1@'));
        $this->assertFalse($this->filter->isSecurePassword('SamplePassword1@ '));
        $this->assertFalse($this->filter->isSecurePassword('SamplePassword1'));
        $this->assertFalse($this->filter->isSecurePassword('SamplePassword@'));
        $this->assertFalse($this->filter->isSecurePassword('samplepassword1@'));
        $this->assertFalse($this->filter->isSecurePassword('SAMPLEPASSWORD1@'));
        $this->assertFalse($this->filter->isSecurePassword('1234567890@'));
        $this->assertFalse($this->filter->isSecurePassword('Sp@1'));
    }

    public function testIsEmal()
    {
        $this->assertTrue($this->filter->isEmail('sample@password.net'));
        $this->assertTrue($this->filter->isEmail('sample123@password.net'));
        $this->assertFalse($this->filter->isEmail('Sample@Password.net'));
        $this->assertFalse($this->filter->isEmail('sample@password'));
        $this->assertFalse($this->filter->isEmail('samplepassword.net'));
    }

    public function testIsNumeric()
    {
        $this->assertTrue($this->filter->isNumeric(1));
        $this->assertTrue($this->filter->isNumeric(0));
        $this->assertTrue($this->filter->isNumeric(1.1));
        $this->assertTrue($this->filter->isNumeric(0.0));
        $this->assertFalse($this->filter->isNumeric('string'));
        $this->assertFalse($this->filter->isNumeric(true));
        $this->assertFalse($this->filter->isNumeric(false));
        $this->assertFalse($this->filter->isNumeric(null));
        $this->assertFalse($this->filter->isNumeric(''));
        $this->assertFalse($this->filter->isNumeric(['array']));
    }

    public function testIsInteger()
    {
        $this->assertTrue($this->filter->isInteger(1));
        $this->assertTrue($this->filter->isInteger(0));
        $this->assertFalse($this->filter->isInteger(1.1));
        $this->assertFalse($this->filter->isInteger(0.0));
        $this->assertFalse($this->filter->isInteger('string'));
        $this->assertFalse($this->filter->isInteger(true));
        $this->assertFalse($this->filter->isInteger(false));
        $this->assertFalse($this->filter->isInteger(null));
        $this->assertFalse($this->filter->isInteger(''));
        $this->assertFalse($this->filter->isInteger(['array']));
    }

    public function testIsFloat()
    {
        $this->assertTrue($this->filter->isFloat(1.1));
        $this->assertTrue($this->filter->isFloat(0.0));
        $this->assertFalse($this->filter->isFloat(1));
        $this->assertFalse($this->filter->isFloat(0));
        $this->assertFalse($this->filter->isFloat('string'));
        $this->assertFalse($this->filter->isFloat(true));
        $this->assertFalse($this->filter->isFloat(false));
        $this->assertFalse($this->filter->isFloat(null));
        $this->assertFalse($this->filter->isFloat(''));
        $this->assertFalse($this->filter->isFloat(['array']));
    }

    public function testIsBoolean()
    {
        $this->assertTrue($this->filter->isBoolean(true));
        $this->assertTrue($this->filter->isBoolean(false));
        $this->assertFalse($this->filter->isBoolean(1.1));
        $this->assertFalse($this->filter->isBoolean(0.0));
        $this->assertFalse($this->filter->isBoolean(1));
        $this->assertFalse($this->filter->isBoolean(0));
        $this->assertFalse($this->filter->isBoolean('string'));
        $this->assertFalse($this->filter->isBoolean(null));
        $this->assertFalse($this->filter->isBoolean(''));
        $this->assertFalse($this->filter->isBoolean(['array']));
    }

    public function testIsArray()
    {
        $this->assertTrue($this->filter->isArray(['array']));
        $this->assertFalse($this->filter->isArray(1.1));
        $this->assertFalse($this->filter->isArray(0.0));
        $this->assertFalse($this->filter->isArray(1));
        $this->assertFalse($this->filter->isArray(0));
        $this->assertFalse($this->filter->isArray('string'));
        $this->assertFalse($this->filter->isArray(true));
        $this->assertFalse($this->filter->isArray(false));
        $this->assertFalse($this->filter->isArray(null));
        $this->assertFalse($this->filter->isArray(''));
    }

    public function testIsDate()
    {
        $this->assertTrue($this->filter->isDate(new SismaDate()));
        $this->assertFalse($this->filter->isDate(new SismaDateTime()));
        $this->assertFalse($this->filter->isDate(SismaTime::createFromStandardTimeFormat('20:15:50')));
        $this->assertFalse($this->filter->isDate(['array']));
        $this->assertFalse($this->filter->isDate(1.1));
        $this->assertFalse($this->filter->isDate(0.0));
        $this->assertFalse($this->filter->isDate(1));
        $this->assertFalse($this->filter->isDate(0));
        $this->assertFalse($this->filter->isDate('string'));
        $this->assertFalse($this->filter->isDate(true));
        $this->assertFalse($this->filter->isDate(false));
        $this->assertFalse($this->filter->isDate(null));
        $this->assertFalse($this->filter->isDate(''));
    }

    public function testIsDatetime()
    {
        $this->assertTrue($this->filter->isDatetime(new SismaDateTime()));
        $this->assertFalse($this->filter->isDatetime(new SismaDate()));
        $this->assertFalse($this->filter->isDatetime(SismaTime::createFromStandardTimeFormat('20:15:50')));
        $this->assertFalse($this->filter->isDatetime(['array']));
        $this->assertFalse($this->filter->isDatetime(1.1));
        $this->assertFalse($this->filter->isDatetime(0.0));
        $this->assertFalse($this->filter->isDatetime(1));
        $this->assertFalse($this->filter->isDatetime(0));
        $this->assertFalse($this->filter->isDatetime('string'));
        $this->assertFalse($this->filter->isDatetime(true));
        $this->assertFalse($this->filter->isDatetime(false));
        $this->assertFalse($this->filter->isDatetime(null));
        $this->assertFalse($this->filter->isDatetime(''));
    }

    public function testIsTime()
    {
        $this->assertTrue($this->filter->isTime(SismaTime::createFromStandardTimeFormat('20:15:50')));
        $this->assertFalse($this->filter->isTime(new SismaDate()));
        $this->assertFalse($this->filter->isTime(new SismaDateTime()));
        $this->assertFalse($this->filter->isTime(['array']));
        $this->assertFalse($this->filter->isTime(1.1));
        $this->assertFalse($this->filter->isTime(0.0));
        $this->assertFalse($this->filter->isTime(1));
        $this->assertFalse($this->filter->isTime(0));
        $this->assertFalse($this->filter->isTime('string'));
        $this->assertFalse($this->filter->isTime(true));
        $this->assertFalse($this->filter->isTime(false));
        $this->assertFalse($this->filter->isTime(null));
        $this->assertFalse($this->filter->isTime(''));
    }

    public function testIsEntity()
    {
        $baseAdapterMock = $this->createStub(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $dataMapperMock = $this->createStub(DataMapper::class);
        $processedEntitiesCollectionMock = $this->createStub(ProcessedEntitiesCollection::class);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
        ]);
        Config::setInstance($configStub);
        $this->assertTrue($this->filter->isEntity(new BaseSample($dataMapperMock, $processedEntitiesCollectionMock, $configStub)));
        $this->assertFalse($this->filter->isEntity(['array']));
        $this->assertFalse($this->filter->isEntity(1.1));
        $this->assertFalse($this->filter->isEntity(0.0));
        $this->assertFalse($this->filter->isEntity(1));
        $this->assertFalse($this->filter->isEntity(0));
        $this->assertFalse($this->filter->isEntity('string'));
        $this->assertFalse($this->filter->isEntity(true));
        $this->assertFalse($this->filter->isEntity(false));
        $this->assertFalse($this->filter->isEntity(null));
        $this->assertFalse($this->filter->isEntity(''));
    }

    public function testIsEnumeration()
    {
        $this->assertTrue($this->filter->isEnumeration(SampleType::one));
        $this->assertFalse($this->filter->isEnumeration(['array']));
        $this->assertFalse($this->filter->isEnumeration(1.1));
        $this->assertFalse($this->filter->isEnumeration(0.0));
        $this->assertFalse($this->filter->isEnumeration(1));
        $this->assertFalse($this->filter->isEnumeration(0));
        $this->assertFalse($this->filter->isEnumeration('string'));
        $this->assertFalse($this->filter->isEnumeration(true));
        $this->assertFalse($this->filter->isEnumeration(false));
        $this->assertFalse($this->filter->isEnumeration(null));
        $this->assertFalse($this->filter->isEnumeration(''));
    }
    
    public function testCustomFilter()
    {
        $this->assertTrue($this->filter->customFilter('Prova1_.', '/^[a-zA-Z0-9_.]+$/'));
        $this->assertTrue($this->filter->customFilter('provauno.', '/^[a-zA-Z0-9_.]+$/'));
        $this->assertFalse($this->filter->customFilter('Prov@1_.', '/^[a-zA-Z0-9_.]+$/'));
        $this->assertFalse($this->filter->customFilter('prova uno', '/^[a-zA-Z0-9_.]+$/'));
        $this->assertFalse($this->filter->customFilter('?rova', '/^[a-zA-Z0-9_.]+$/'));
        $this->assertFalse($this->filter->customFilter('provà', '/^[a-zA-Z0-9_.]+$/'));
    }

}

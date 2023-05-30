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
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */
class FilterTest extends TestCase
{

    public function testNoFilter()
    {
        $this->assertTrue(Filter::noFilter('sample value'));
    }

    public function testIsNotNull()
    {
        $this->assertTrue(Filter::isNotNull('not null value'));
        $this->assertFalse(Filter::isNotNull(null));
    }

    public function testIsNotFalse()
    {
        $this->assertTrue(Filter::isNotFalse('not false value'));
        $this->assertFalse(Filter::isNotFalse(false));
    }

    public function testIsNotEmpty()
    {
        $this->assertTrue(Filter::isNotEmpty(0));
        $this->assertTrue(Filter::isNotEmpty('not empty value'));
        $this->assertFalse(Filter::isNotEmpty(''));
    }

    public function testIsString()
    {
        $this->assertTrue(Filter::isString('string value'));
        $this->assertFalse(Filter::isString(1));
        $this->assertFalse(Filter::isString(1.1));
        $this->assertFalse(Filter::isString(null));
        $this->assertFalse(Filter::isString(true));
        $this->assertFalse(Filter::isString(false));
        $this->assertFalse(Filter::isString(''));
        $this->assertFalse(Filter::isString(['array']));
    }

    public function testIsMinLimitString()
    {
        $this->assertTrue(Filter::isMinLimitString('string min limit value', 10));
        $this->assertFalse(Filter::isMinLimitString('fake', 10));
    }

    public function testIsMaxLimitString()
    {
        $this->assertTrue(Filter::isMaxLimitString('string max limit value', 30));
        $this->assertFalse(Filter::isMaxLimitString('fake', 3));
    }

    public function testLimitString()
    {
        $this->assertTrue(Filter::isLimitString('string limit value', 10, 30));
        $this->assertFalse(Filter::isLimitString('fake', 1, 3));
        $this->assertFalse(Filter::isLimitString('fake', 10, 30));
        $this->assertFalse(Filter::isLimitString('fake', 10, 3));
    }

    public function testIsAlphabeticString()
    {
        $this->assertTrue(Filter::isAlphabeticString('alphabeticStringValue'));
        $this->assertFalse(Filter::isAlphabeticString('alphabetic string value with spaces'));
        $this->assertFalse(Filter::isAlphabeticString('123alphanumeric'));
        $this->assertFalse(Filter::isAlphabeticString('123 alphanumeric'));
        $this->assertFalse(Filter::isAlphabeticString('123'));
    }

    public function testIsMinLimitAlphabeticString()
    {
        $this->assertTrue(Filter::isMinLimitAlphabeticString('stringMinLimitValue', 10));
        $this->assertFalse(Filter::isMinLimitAlphabeticString('string min limit value', 10));
        $this->assertFalse(Filter::isMinLimitAlphabeticString('stringMaxLimitValue123', 10));
        $this->assertFalse(Filter::isMinLimitAlphabeticString('fake', 10));
    }

    public function testIsMaxLimitAlphabeticString()
    {
        $this->assertTrue(Filter::isMaxLimitAlphabeticString('stringMaxLimitValue', 30));
        $this->assertFalse(Filter::isMaxLimitAlphabeticString('string max limit value', 30));
        $this->assertFalse(Filter::isMaxLimitAlphabeticString('stringMaxLimitValue123', 30));
        $this->assertFalse(Filter::isMaxLimitAlphabeticString('fake', 3));
    }

    public function testLimitAlphabeticString()
    {
        $this->assertTrue(Filter::isLimitAlphabeticString('stringLimitValue', 10, 30));
        $this->assertFalse(Filter::isLimitAlphabeticString('string limit value', 10, 30));
        $this->assertFalse(Filter::isLimitAlphabeticString('stringMaxLimitValue123', 10, 30));
        $this->assertFalse(Filter::isLimitAlphabeticString('fake', 1, 3));
        $this->assertFalse(Filter::isLimitAlphabeticString('fake', 10, 30));
        $this->assertFalse(Filter::isLimitAlphabeticString('fake', 10, 3));
    }

    public function testIsAlphanumericString()
    {
        $this->assertTrue(Filter::isAlphanumericString('123alphanumericStringValue'));
        $this->assertFalse(Filter::isAlphanumericString('123 alphanumeric string value'));
        $this->assertFalse(Filter::isAlphanumericString('alphabeticStringValue'));
        $this->assertFalse(Filter::isAlphanumericString('alphabetic string value with spaces'));
        $this->assertFalse(Filter::isAlphanumericString('123'));
    }

    public function testIsMinLimitAlphanumericString()
    {
        $this->assertTrue(Filter::isMinLimitAlphanumericString('123stringMinLimitValue', 10));
        $this->assertFalse(Filter::isMinLimitAlphanumericString('123 string min limit value', 10));
        $this->assertFalse(Filter::isMinLimitAlphanumericString('stringMaxLimitValue', 10));
        $this->assertFalse(Filter::isMinLimitAlphanumericString('fake', 10));
    }

    public function testIsMaxLimitAlphanumericString()
    {
        $this->assertTrue(Filter::isMaxLimitAlphanumericString('123stringMaxLimitValue', 30));
        $this->assertFalse(Filter::isMaxLimitAlphanumericString('123 string max limit value', 30));
        $this->assertFalse(Filter::isMaxLimitAlphanumericString('stringMaxLimitValue', 30));
        $this->assertFalse(Filter::isMaxLimitAlphanumericString('fake', 3));
    }

    public function testLimitAlphanumericString()
    {
        $this->assertTrue(Filter::isLimitAlphanumericString('123stringLimitValue', 10, 30));
        $this->assertFalse(Filter::isLimitAlphanumericString('123 string limit value', 10, 30));
        $this->assertFalse(Filter::isLimitAlphanumericString('stringMaxLimitValue', 10, 30));
        $this->assertFalse(Filter::isLimitAlphanumericString('fake', 1, 3));
        $this->assertFalse(Filter::isLimitAlphanumericString('fake', 10, 30));
        $this->assertFalse(Filter::isLimitAlphanumericString('fake', 10, 3));
    }

    public function testIsSecurePassword()
    {
        $this->assertTrue(Filter::isSecurePassword('SamplePassword1@'));
        $this->assertFalse(Filter::isSecurePassword('SamplePassword1@ '));
        $this->assertFalse(Filter::isSecurePassword('SamplePassword1'));
        $this->assertFalse(Filter::isSecurePassword('SamplePassword@'));
        $this->assertFalse(Filter::isSecurePassword('samplepassword1@'));
        $this->assertFalse(Filter::isSecurePassword('SAMPLEPASSWORD1@'));
        $this->assertFalse(Filter::isSecurePassword('1234567890@'));
        $this->assertFalse(Filter::isSecurePassword('Sp@1'));
    }

    public function testIsEmal()
    {
        $this->assertTrue(Filter::isEmail('sample@password.net'));
        $this->assertTrue(Filter::isEmail('sample123@password.net'));
        $this->assertFalse(Filter::isEmail('Sample@Password.net'));
        $this->assertFalse(Filter::isEmail('sample@password'));
        $this->assertFalse(Filter::isEmail('samplepassword.net'));
    }

    public function testIsNumeric()
    {
        $this->assertTrue(Filter::isNumeric(1));
        $this->assertTrue(Filter::isNumeric(1.1));
        $this->assertFalse(Filter::isNumeric('string'));
        $this->assertFalse(Filter::isNumeric(true));
        $this->assertFalse(Filter::isNumeric(false));
        $this->assertFalse(Filter::isNumeric(null));
        $this->assertFalse(Filter::isNumeric(''));
        $this->assertFalse(Filter::isNumeric(['array']));
    }

    public function testIsInteger()
    {
        $this->assertTrue(Filter::isInteger(1));
        $this->assertFalse(Filter::isInteger(1.1));
        $this->assertFalse(Filter::isInteger('string'));
        $this->assertFalse(Filter::isInteger(true));
        $this->assertFalse(Filter::isInteger(false));
        $this->assertFalse(Filter::isInteger(null));
        $this->assertFalse(Filter::isInteger(''));
        $this->assertFalse(Filter::isInteger(['array']));
    }

    public function testIsFloat()
    {
        $this->assertTrue(Filter::isFloat(1.1));
        $this->assertFalse(Filter::isFloat(1));
        $this->assertFalse(Filter::isFloat('string'));
        $this->assertFalse(Filter::isFloat(true));
        $this->assertFalse(Filter::isFloat(false));
        $this->assertFalse(Filter::isFloat(null));
        $this->assertFalse(Filter::isFloat(''));
        $this->assertFalse(Filter::isFloat(['array']));
    }

    public function testIsBoolean()
    {
        $this->assertTrue(Filter::isBoolean(true));
        $this->assertTrue(Filter::isBoolean(false));
        $this->assertFalse(Filter::isBoolean(1.1));
        $this->assertFalse(Filter::isBoolean(1));
        $this->assertFalse(Filter::isBoolean('string'));
        $this->assertFalse(Filter::isBoolean(null));
        $this->assertFalse(Filter::isBoolean(''));
        $this->assertFalse(Filter::isBoolean(['array']));
    }

    public function testIsArray()
    {
        $this->assertTrue(Filter::isArray(['array']));
        $this->assertFalse(Filter::isArray(1.1));
        $this->assertFalse(Filter::isArray(1));
        $this->assertFalse(Filter::isArray('string'));
        $this->assertFalse(Filter::isArray(true));
        $this->assertFalse(Filter::isArray(false));
        $this->assertFalse(Filter::isArray(null));
        $this->assertFalse(Filter::isArray(''));
    }

    public function testIsDate()
    {
        $this->assertTrue(Filter::isDate(new SismaDateTime()));
        $this->assertFalse(Filter::isDate(['array']));
        $this->assertFalse(Filter::isDate(1.1));
        $this->assertFalse(Filter::isDate(1));
        $this->assertFalse(Filter::isDate('string'));
        $this->assertFalse(Filter::isDate(true));
        $this->assertFalse(Filter::isDate(false));
        $this->assertFalse(Filter::isDate(null));
        $this->assertFalse(Filter::isDate(''));
    }

    public function testIsDatetime()
    {
        $this->assertTrue(Filter::isDatetime(new SismaDateTime()));
        $this->assertFalse(Filter::isDatetime(['array']));
        $this->assertFalse(Filter::isDatetime(1.1));
        $this->assertFalse(Filter::isDatetime(1));
        $this->assertFalse(Filter::isDatetime('string'));
        $this->assertFalse(Filter::isDatetime(true));
        $this->assertFalse(Filter::isDatetime(false));
        $this->assertFalse(Filter::isDatetime(null));
        $this->assertFalse(Filter::isDatetime(''));
    }

    public function testIsEntity()
    {
        $this->assertTrue(Filter::isEntity(new BaseSample()));
        $this->assertFalse(Filter::isEntity(['array']));
        $this->assertFalse(Filter::isEntity(1.1));
        $this->assertFalse(Filter::isEntity(1));
        $this->assertFalse(Filter::isEntity('string'));
        $this->assertFalse(Filter::isEntity(true));
        $this->assertFalse(Filter::isEntity(false));
        $this->assertFalse(Filter::isEntity(null));
        $this->assertFalse(Filter::isEntity(''));
    }

    public function testIsEnumeration()
    {
        $this->assertTrue(Filter::isEnumeration(SampleType::one));
        $this->assertFalse(Filter::isEnumeration(['array']));
        $this->assertFalse(Filter::isEnumeration(1.1));
        $this->assertFalse(Filter::isEnumeration(1));
        $this->assertFalse(Filter::isEnumeration('string'));
        $this->assertFalse(Filter::isEnumeration(true));
        $this->assertFalse(Filter::isEnumeration(false));
        $this->assertFalse(Filter::isEnumeration(null));
        $this->assertFalse(Filter::isEnumeration(''));
    }

}

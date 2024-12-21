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

namespace SismaFramework\Core\Enumerations;

use SismaFramework\Orm\BaseClasses\BaseEntity;

/**
 *
 * @author Valentino de Lapa
 */
enum FilterType: string
{

    case noFilter = 'noFilter';
    case isString = 'isString';
    case isAlphabeticString = 'isAlphabeticString';
    case isStrictAlphanumericString = 'isStrictAlphanumericString';
    case isAlphanumericString = 'isAlphanumericString';
    case isSecurePassword = 'isSecurePassword';
    case isEmail = 'isEmail';
    case isNumeric = 'isNumeric';
    case isInteger = 'isInteger';
    case isFloat = 'isFloat';
    case isBoolean = 'isBoolean';
    case isArray = 'isArray';
    case isDate = 'isDate';
    case isDatetime = 'isDatetime';
    case isTime = 'isTime';
    case isUploadedFile = 'isUploadedFile';
    case isEnumeration = 'isEnumeration';
    case isEntity = 'isEntity';

    public static function fromPhpType(string $type): self 
    {
        if (class_exists($type) && is_subclass_of($type, BaseEntity::class)) {
            return self::isEntity;
        }

        return match($type) {
            'int' => self::isInteger,
            'float' => self::isFloat,
            'string' => self::isString,
            'bool' => self::isBoolean,
            'DateTime' => self::isDatetime,
            default => self::isString
        };
    }

}

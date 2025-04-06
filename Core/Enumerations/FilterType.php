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

use SismaFramework\Core\HelperClasses\Filter;

/**
 *
 * @author Valentino de Lapa
 */
enum FilterType
{

    case noFilter;
    case isString;
    case isMinLimitString;
    case isMaxLimitString;
    case isLimitString;
    case isAlphabeticString;
    case isMinLimitAlphabeticString;
    case isMaxLimitAlphabeticString;
    case isLimitAlphabeticString;
    case isAlphanumericString;
    case isMinLimitAlphanumericString;
    case isMaxLimitAlphanumericString;
    case isLimitAlphanumericString;
    case isStrictAlphanumericString;
    case isMinLimitStrictAlphanumericString;
    case isMaxLimitStrictAlphanumericString;
    case isLimitStrictAlphanumericString;
    case isSecurePassword;
    case isEmail;
    case isNumeric;
    case isInteger;
    case isFloat;
    case isBoolean;
    case isArray;
    case isDate;
    case isDatetime;
    case isTime;
    case isUploadedFile;
    case isEntity;
    case isEnumeration;
    case customFilter;

    public function applyFilter(mixed $value, array $parameters, Filter $filter = new Filter()): bool
    {
        return $filter->{$this->name}($value, ...$parameters);
    }
}

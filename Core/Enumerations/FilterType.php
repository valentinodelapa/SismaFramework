<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
enum FilterType: string
{
    case noFilter = 'noFilter';
    case isString = 'isString';
    case isAlphabeticString = 'isAlphabeticString';
    case isAlphanumericString = 'isAlphanumericString';
    case isSecurePassword = 'isSecurePassword';
    case isEmail = 'isEmail';
    case isNumeric = 'isNumeric';
    case isInteger = 'isInteger';
    case isFloat = 'isFloat';
    case isBoolean = 'isBoolean';
    case isArray = 'isArray';
    case isUploadedFile = 'isUploadedFile';
    case isEnumeration = 'isEnumeration';
    case isEntity = 'isEntity';
}

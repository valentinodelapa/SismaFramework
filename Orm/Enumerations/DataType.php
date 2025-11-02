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

namespace SismaFramework\Orm\Enumerations;

use SismaFramework\Orm\BaseClasses\BaseEntity;

/**
 *
 * @author Valentino de Lapa
 */
enum DataType
{

    case typeBoolean;
    case typeNull;
    case typeInteger;
    case typeString;
    case typeBinary;
    case typeDecimal;
    case typeDate;
    case typeStatement;
    case typeEntity;
    case typeEnumeration;
    case typeGeneric;

    public static function fromReflection(\ReflectionNamedType $reflectionNamedType, mixed $value): self
    {
        if ($reflectionNamedType->getName() === 'bool') {
            return self::typeBoolean;
        } elseif ($reflectionNamedType->getName() === 'int') {
            return self::typeInteger;
        } elseif ($reflectionNamedType->getName() === 'float') {
            return self::typeDecimal;
        } elseif ($reflectionNamedType->getName() === 'string') {
            return self::resolveStringType($value);
        } elseif (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class)) {
            return self::typeEntity;
        } elseif (enum_exists($reflectionNamedType->getName())) {
            return self::typeEnumeration;
        } elseif (is_subclass_of($reflectionNamedType->getName(), \DateTimeInterface::class)) {
            return self::typeDate;
        } else {
            return self::typeGeneric;
        }
    }

    private static function resolveStringType(mixed $value): self
    {
        if (mb_detect_encoding($value ?? '', 'UTF-8', true)) {
            return self::typeString;
        } else {
            return self::typeBinary;
        }
    }
}

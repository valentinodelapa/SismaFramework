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

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Core\Exceptions\InvalidArgumentException;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Parser
{

    public static function parseValue(\ReflectionNamedType $reflectionNamedType, ?string $value, $parseEntity = true): mixed
    {
        if (($value === null) || ($reflectionNamedType->allowsNull() && ($value === ''))) {
            return null;
        } elseif ($reflectionNamedType->isBuiltin()) {
            settype($value, $reflectionNamedType->getName());
            return $value;
        } elseif (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class)) {
            if ($parseEntity) {
                return self::parseEntity($reflectionNamedType->getName(), $value);
            } else {
                return intval($value);
            }
        } elseif (enum_exists($reflectionNamedType->getName())) {
            return self::parseEnumeration($reflectionNamedType->getName(), $value, $reflectionNamedType->allowsNull());
        } elseif (is_a($reflectionNamedType->getName(), SismaDateTime::class, true)) {
            return new SismaDateTime($value);
        } else {
            throw new InvalidArgumentException();
        }
    }

    public static function parseEntity(string $entityName, string $value): BaseEntity
    {
        $modelName = str_replace(\Config\ENTITY_NAMESPACE, \Config\MODEL_NAMESPACE, $entityName) . 'Model';
        $modelInstance = new $modelName();
        return $modelInstance->getEntityById($value);
    }

    public static function parseEnumeration(string $enumerationName, string $value, bool $allowsNull): ?\UnitEnum
    {
        $enumerationValue = $enumerationName::tryFrom($value);
        if (($enumerationValue === null) && ($allowsNull === false)) {
            throw new InvalidArgumentException();
        } else {
            return $enumerationName::from($value);
        }
    }

    public static function unparseValues(array &$arrayValues): void
    {
        foreach ($arrayValues as $key => $value) {
            if ($value instanceof BaseEntity) {
                $arrayValues[$key] = $value->id;
            } elseif ($value instanceof \UnitEnum) {
                $arrayValues[$key] = $value->value;
            } elseif ($value instanceof SismaDateTime) {
                $arrayValues[$key] = $value->format("Y-m-d H:i:s");
            }
        }
    }

}
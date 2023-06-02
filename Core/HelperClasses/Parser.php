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

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Core\Exceptions\InvalidArgumentException;

/**
 *
 * @author Valentino de Lapa
 */
class Parser
{

    public static function parseValue(\ReflectionNamedType $reflectionNamedType, null|string|array $value, $parseEntity = true): mixed
    {
        if (($value === null) || ($reflectionNamedType->allowsNull() && ($value === ''))) {
            return null;
        } elseif ($reflectionNamedType->isBuiltin()) {
            settype($value, $reflectionNamedType->getName());
            return $value;
        } elseif (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class)) {
            if ($parseEntity) {
                return self::parseEntity($reflectionNamedType->getName(), intval($value));
            } else {
                return intval($value);
            }
        } elseif (enum_exists($reflectionNamedType->getName())) {
            return self::parseEnumeration($reflectionNamedType->getName(), $value);
        } elseif (is_a($reflectionNamedType->getName(), SismaDateTime::class, true)) {
            return new SismaDateTime($value);
        } elseif (($reflectionNamedType->getName() === 'array') && is_array($value)) {
            return $value;
        } else {
            throw new InvalidArgumentException();
        }
    }

    public static function parseEntity(string $entityName, int $value): BaseEntity
    {
        $modelName = str_replace(\Config\ENTITY_NAMESPACE, \Config\MODEL_NAMESPACE, $entityName) . 'Model';
        $modelInstance = new $modelName();
        $entity = $modelInstance->getEntityById($value);
        if ($entity instanceof BaseEntity) {
            return $entity;
        } else {
            throw new InvalidArgumentException();
        }
    }

    public static function parseEnumeration(string $enumerationName, string $value): ?\UnitEnum
    {
        $enumerationInstance = $enumerationName::tryFrom($value);
        if (($enumerationInstance instanceof \BackedEnum)) {
            return $enumerationInstance;
        } else {
            throw new InvalidArgumentException();
        }
    }

    public static function unparseValues(array &$arrayValues): void
    {
        foreach ($arrayValues as &$value) {
            $value = self::unparseValue($value);
        }
    }

    public static function unparseValue(mixed $value, bool $persistentUnparse = false, DataMapper $dataMapper = new DataMapper()): null|int|float|string
    {
        if ($value instanceof BaseEntity) {
            if ($persistentUnparse) {
                self::saveEntityModification($value, $dataMapper);
            }
            return $value->id;
        } elseif ($value instanceof \UnitEnum) {
            return $value->value;
        } elseif ($value instanceof SismaDateTime) {
            return $value->format("Y-m-d H:i:s");
        } else {
            return $value;
        }
    }

    private static function saveEntityModification(BaseEntity $entity, DataMapper $dataMapper): void
    {
        $dataMapper->save($entity);
    }
}

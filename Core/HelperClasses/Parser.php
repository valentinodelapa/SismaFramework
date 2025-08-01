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

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaTime;
use SismaFramework\Core\Exceptions\InvalidArgumentException;

/**
 *
 * @author Valentino de Lapa
 */
class Parser
{

    public static function parseValue(\ReflectionNamedType $reflectionNamedType,
            null|string|array $value,
            $parseEntity = true,
            DataMapper $dataMapper = new DataMapper(),
            ?Config $customConfig = null): mixed
    {
        if (($value === null) || ($reflectionNamedType->allowsNull() && ($value === ''))) {
            return null;
        } elseif ($reflectionNamedType->isBuiltin()) {
            settype($value, $reflectionNamedType->getName());
            return $value;
        } elseif (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class)) {
            if ($parseEntity) {
                $config = $customConfig ?? Config::getInstance();
                return self::parseEntity($reflectionNamedType->getName(), intval($value), $dataMapper, $config);
            } else {
                return intval($value);
            }
        } elseif (enum_exists($reflectionNamedType->getName())) {
            return self::parseEnumeration($reflectionNamedType->getName(), $value);
        } elseif (is_a($reflectionNamedType->getName(), SismaDate::class, true)) {
            return new SismaDate($value);
        } elseif (is_a($reflectionNamedType->getName(), SismaDateTime::class, true)) {
            return new SismaDateTime($value);
        } elseif (is_a($reflectionNamedType->getName(), SismaTime::class, true)) {
            return SismaTime::createFromStandardTimeFormat($value);
        } elseif (($reflectionNamedType->getName() === 'array') && is_array($value)) {
            return $value;
        } else {
            throw new InvalidArgumentException($reflectionNamedType->getName());
        }
    }

    public static function parseEntity(string $entityName, int $value, DataMapper $dataMapper = new DataMapper(), ?Config $customConfig = null): BaseEntity
    {
        $config = $customConfig ?? Config::getInstance();
        $modelName = str_replace($config->entityNamespace, $config->modelNamespace, $entityName) . 'Model';
        $modelInstance = new $modelName($dataMapper);
        $entity = $modelInstance->getEntityById($value);
        if ($entity instanceof BaseEntity) {
            return $entity;
        } else {
            throw new InvalidArgumentException($entityName);
        }
    }

    public static function parseEnumeration(string $enumerationName, string $value): ?\UnitEnum
    {
        $enumerationInstance = $enumerationName::tryFrom($value);
        if (($enumerationInstance instanceof \BackedEnum)) {
            return $enumerationInstance;
        } else {
            throw new InvalidArgumentException($enumerationName);
        }
    }

    public static function unparseValues(array &$arrayValues): void
    {
        foreach ($arrayValues as &$value) {
            $value = self::unparseValue($value);
        }
    }

    public static function unparseValue(mixed $value): null|int|float|string
    {
        if ($value instanceof BaseEntity) {
            return $value->id;
        } elseif ($value instanceof \UnitEnum) {
            return $value->value;
        } elseif ($value instanceof SismaDate) {
            return $value->format("Y-m-d");
        } elseif ($value instanceof SismaDateTime) {
            return $value->format("Y-m-d H:i:s");
        } elseif ($value instanceof SismaTime) {
            return $value->formatToStandardTimeFormat();
        } else {
            return $value;
        }
    }

    public static function simpleParseValue(\ReflectionNamedType $reflectionNamedType,
            null|string|array $value): mixed
    {
        if ($reflectionNamedType->isBuiltin()) {
            settype($value, $reflectionNamedType->getName());
            return $value;
        } elseif (enum_exists($reflectionNamedType->getName())) {
            return self::parseEnumeration($reflectionNamedType->getName(), $value);
        } else {
            return new $value();
        }
    }
}

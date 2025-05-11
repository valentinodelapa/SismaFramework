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

namespace SismaFramework\Orm\ExtendedClasses;

use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\Exceptions\InvalidPropertyException;
use SismaFramework\Orm\HelperClasses\Cache;

/**
 *
 * @author Valentino de Lapa
 */
abstract class SelfReferencedEntity extends ReferencedEntity
{

    public const PARENT_PREFIX_PROPERTY_NAME = 'parent';
    public const SON_COLLECTION_PROPERTY_NAME = 'sonCollection';

    public function __get($name)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->forceForeignKeyPropertySet($name);
            return $this->$name;
        } elseif ($name === self::SON_COLLECTION_PROPERTY_NAME) {
            $calledClassNamePartes = explode("\\", static::class);
            $collectionName = lcfirst(end($calledClassNamePartes)) . self::FOREIGN_KEY_SUFFIX . ucfirst(self::PARENT_PREFIX_PROPERTY_NAME) . end($calledClassNamePartes);
            $this->forceCollectionPropertySet($collectionName);
            return $this->collections[lcfirst(end($calledClassNamePartes))][self::PARENT_PREFIX_PROPERTY_NAME . end($calledClassNamePartes)];
        } elseif ($this->checkCollectionExists($name)) {
            $this->forceCollectionPropertySet($name);
            return $this->collections[$this->getForeignKeyReference($name)][static::getForeignKeyName($name)];
        } else {
            throw new InvalidPropertyException($name);
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->switchSettingType($name, $value);
        } elseif ($name === self::SON_COLLECTION_PROPERTY_NAME) {
            $calledClassNamePartes = explode("\\", static::class);
            $this->collections[lcfirst(end($calledClassNamePartes))][self::PARENT_PREFIX_PROPERTY_NAME . end($calledClassNamePartes)] = $value;
        } elseif ($this->checkCollectionExists($name)) {
            $this->checkCollectionTypeConsistency($name, $value);ue;
            $this->collections[$this->getForeignKeyReference($name)][static::getForeignKeyName($name)] = $value;
        } else {
            throw new InvalidPropertyException($name);
        }
    }

    public function getForeignKeyReference(string $collectionName): string
    {
        if ($collectionName === self::SON_COLLECTION_PROPERTY_NAME) {
            $calledClassNamePartes = explode("\\", static::class);
            return lcfirst(end($calledClassNamePartes));
        } else {
            return parent::getForeignKeyReference($collectionName);
        }
    }

    public static function getForeignKeyName(string $collectionName): ?string
    {
        $collectionNameParts = array_diff(explode(self::FOREIGN_KEY_SUFFIX, $collectionName), ['']);
        if ($collectionName === self::SON_COLLECTION_PROPERTY_NAME) {
            $calledClassNamePartes = explode("\\", static::class);
            return self::PARENT_PREFIX_PROPERTY_NAME . end($calledClassNamePartes);
        } elseif (str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) && count(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0])) === 1) {
            return array_key_first(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0]));
        } elseif ((str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) === false)&& isset($collectionNameParts[1]) && (isset(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0])[lcfirst($collectionNameParts[1])]))) {
            return lcfirst($collectionNameParts[1]);
        } else {
            return null;
        }
    }

}

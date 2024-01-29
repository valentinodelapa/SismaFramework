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

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Core\Exceptions\EntityException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Orm\Exceptions\InvalidPropertyException;
use SismaFramework\Orm\HelperClasses\Cache;

/**
 *
 * @author Valentino de Lapa
 */
abstract class ReferencedEntity extends BaseEntity
{
    public bool $collectionNestedChanges = false;
    protected array $collectionPropertiesSetted = [];
    protected array $collections = [];

    public const FOREIGN_KEY_TYPE = 'foreignKeyType';
    public const FOREIGN_KEY_NAME = 'foreignKeyName';
    public const FOREIGN_KEY_SUFFIX = 'Collection';

    public function getCollections(): array
    {
        return $this->collections;
    }

    public function getCollectionNames()
    {
        $collectionNames = [];
        $collections = Cache::getForeignKeyData(get_called_class());
        foreach ($collections as $collectionName => $reference) {
            foreach (array_keys($reference) as $refenrenceName) {
                $collectionNames[] = $collectionName . self::FOREIGN_KEY_SUFFIX . ucfirst($refenrenceName);
            }
        }
        return $collectionNames;
    }

    public function __get($name)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->forceForeignKeyPropertySet($name);
            return $this->$name;
        } elseif ($this->checkCollectionExists($name)) {
            $this->forceCollectionPropertySet($name);
            return $this->collections[$this->getForeignKeyReference($name)][static::getForeignKeyName($name)];
        } else {
            throw new InvalidPropertyException($name);
        }
    }

    public function checkCollectionExists(string $collectionName): bool
    {
        if (str_contains($collectionName, self::FOREIGN_KEY_SUFFIX) === false) {
            return false;
        } elseif (str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) && count(Cache::getForeignKeyData(get_called_class(), $this->getForeignKeyReference($collectionName))) === 1) {
            return true;
        } elseif ((str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) === false) && (isset(Cache::getForeignKeyData(get_called_class(), $this->getForeignKeyReference($collectionName))[static::getForeignKeyName($collectionName)]))) {
            return true;
        } else {
            return false;
        }
    }

    protected function forceCollectionPropertySet(string $propertyName): void
    {
        if ((isset($this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)]) === false) ||
                ((count($this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)]) === 0) &&
                ($this->collectionPropertiesSetted[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)] === false))) {
            $modelName = str_replace('Entities', 'Models', $this->getCollectionDataInformation($propertyName)) . 'Model';
            $foreignKeyName = static::getForeignKeyName($propertyName);
            $model = new $modelName($this->dataMapper);
            $entityCollection = isset($this->id) ? $model->getEntityCollectionByEntity([$foreignKeyName => $this]) : new SismaCollection($this->getCollectionDataInformation($propertyName));
            $this->collectionPropertiesSetted[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)] = true;
            $this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)] = $entityCollection;
        }
    }

    public function __isset($name)
    {
        if ($this->checkCollectionExists($name)) {
            $this->forceCollectionPropertySet($name);
            return isset($this->collections[$this->getForeignKeyReference($name)][static::getForeignKeyName($name)]);
        } else {
            return parent::__isset($name);
        }
    }

    public function getCollectionDataInformation(string $collectionName): string
    {
        return Cache::getForeignKeyData(get_called_class(), $this->getForeignKeyReference($collectionName))[static::getForeignKeyName($collectionName)];
    }

    public function getForeignKeyReference(string $collectionName): string
    {
        $collectionNameParts = explode(self::FOREIGN_KEY_SUFFIX, $collectionName);
        return $collectionNameParts[0];
    }

    public static function getForeignKeyName(string $collectionName): ?string
    {
        $collectionNameParts = array_diff(explode(self::FOREIGN_KEY_SUFFIX, $collectionName), ['']);
        if (str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) && count(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0])) === 1) {
            return array_key_first(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0]));
        } elseif ((str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) === false) && isset($collectionNameParts[1]) && (isset(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0])[lcfirst($collectionNameParts[1])]))) {
            return lcfirst($collectionNameParts[1]);
        } else {
            return null;
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->switchSettingType($name, $value);
        } elseif ($this->checkCollectionExists($name) && ($value instanceof SismaCollection)) {
            $this->checkCollectionTypeConsistency($name, $value);
            $this->collectionPropertiesSetted[$this->getForeignKeyReference($name)][static::getForeignKeyName($name)] = true;
            $this->collections[$this->getForeignKeyReference($name)][static::getForeignKeyName($name)] = $value;
        } else {
            throw new InvalidPropertyException($name);
        }
    }
    
    protected function checkCollectionTypeConsistency(string $collectionName, SismaCollection $value)
    {
        if(is_a($value->getRestrictiveType(), $this->getCollectionDataInformation($collectionName), true) === false){
            throw new InvalidArgumentException($collectionName);
        }
    }

    public function __call($methodName, $arguments)
    {
        $methodNameParts = preg_split('/(?=[A-Z])/', $methodName);
        $methodType = array_shift($methodNameParts);
        $propertyName = lcfirst(implode($methodNameParts));
        switch ($methodType) {
            case 'set':
                if (isset($arguments[0])) {
                    $this->setEntityCollection($propertyName, $arguments[0]);
                    break;
                } else {
                    throw new InvalidArgumentException($methodName);
                }
            case 'add':
                $this->addEntityToEntityCollection($propertyName . static::FOREIGN_KEY_SUFFIX, $arguments[0]);
                break;
            case 'count':
                return $this->countEntityCollection($propertyName);
            default:
                throw new EntityException($methodName);
        }
    }

    public function setEntityCollection(string $propertyName, SismaCollection $sismaCollection): void
    {
        $this->inizializeEntityCollection($propertyName);
        $this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)]->exchangeArray($sismaCollection);
        $entityPropertyName = static::getForeignKeyName($propertyName);
        foreach ($this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)] as $entity) {
            $entity->$entityPropertyName = $this;
            $entity->collectionCallingEntity = $this;
            $this->setCollectionNestedChanges($entity);
        }
        $this->collectionPropertiesSetted[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)] = true;
    }

    private function inizializeEntityCollection(string $propertyName): void
    {
        if (isset($this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)]) === false) {
            $this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)] = new SismaCollection($this->getCollectionDataInformation($propertyName));
        }
    }

    protected function setCollectionNestedChanges(BaseEntity $entity)
    {
        if ((isset($entity->id) === false) || $entity->modified) {
            $this->collectionNestedChanges = true;
        }
    }

    public function addEntityToEntityCollection(string $propertyName, BaseEntity $entity): void
    {
        $entityPropertyName = static::getForeignKeyName($propertyName);
        $this->switchAdditionType($propertyName, $entity);
        $entity->$entityPropertyName = $this;
        $entity->collectionCallingEntity = $this;
        $this->setCollectionNestedChanges($entity);
    }

    private function switchAdditionType(string $propertyName, BaseEntity $entity): void
    {
        $found = false;
        $this->inizializeEntityCollection($propertyName);
        foreach ($this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)] as &$includedEntity) {
            if (isset($entity->id) && ($includedEntity->id === $entity->id)) {
                $includedEntity = $entity;
                $found = true;
            }
        }
        if ($found === false) {
            $this->collections[$this->getForeignKeyReference($propertyName)][static::getForeignKeyName($propertyName)]->append($entity);
        }
    }

    public function countEntityCollection(string $propertyName): int
    {
        $modelName = str_replace('Entities', 'Models', $this->getCollectionDataInformation($propertyName)) . 'Model';
        $foreignKeyName = static::getForeignKeyName($propertyName);
        $model = new $modelName($this->dataMapper);
        return $model->countEntityCollectionByEntity([$foreignKeyName => $this]);
    }
}

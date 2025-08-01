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

    protected array $collections = [];

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
                $collectionNames[] = $collectionName . $this->config->foreignKeySuffix . ucfirst($refenrenceName);
            }
        }
        return $collectionNames;
    }

    #[\Override]
    public function __get($name)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->forceForeignKeyPropertySet($name);
            return $this->$name;
        } elseif ($this->checkCollectionExists($name)) {
            $this->forceCollectionPropertySet($name);
            return $this->collections[$this->getForeignKeyReference($name)][$this->getForeignKeyName($name)];
        } else {
            throw new InvalidPropertyException($name);
        }
    }

    public function checkCollectionExists(string $collectionName): bool
    {
        if (str_contains($collectionName, $this->config->foreignKeySuffix) === false) {
            return false;
        } elseif (str_ends_with($collectionName, $this->config->foreignKeySuffix) && count(Cache::getForeignKeyData(get_called_class(), $this->getForeignKeyReference($collectionName))) === 1) {
            return true;
        } elseif ((str_ends_with($collectionName, $this->config->foreignKeySuffix) === false) && (isset(Cache::getForeignKeyData(get_called_class(), $this->getForeignKeyReference($collectionName))[$this->getForeignKeyName($collectionName)]))) {
            return true;
        } else {
            return false;
        }
    }

    protected function forceCollectionPropertySet(string $propertyName): void
    {
        if ($this->collectionPropertyIsSetted($propertyName) === false) {
            $modelName = str_replace('Entities', 'Models', $this->getCollectionDataInformation($propertyName)) . 'Model';
            $foreignKeyName = $this->getForeignKeyName($propertyName);
            $model = new $modelName($this->dataMapper);
            $entityCollection = isset($this->id) ? $model->getEntityCollectionByEntity([$foreignKeyName => $this]) : new SismaCollection($this->getCollectionDataInformation($propertyName));
            $this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)] = $entityCollection;
        }
    }

    #[\Override]
    public function __isset($name)
    {
        if ($this->checkCollectionExists($name)) {
            $this->forceCollectionPropertySet($name);
            return isset($this->collections[$this->getForeignKeyReference($name)][$this->getForeignKeyName($name)]);
        } else {
            return parent::__isset($name);
        }
    }

    public function getCollectionDataInformation(string $collectionName): string
    {
        return Cache::getForeignKeyData(get_called_class(), $this->getForeignKeyReference($collectionName))[$this->getForeignKeyName($collectionName)];
    }

    protected function getForeignKeyReference(string $collectionName): string
    {
        $collectionNameParts = explode($this->config->foreignKeySuffix, $collectionName);
        return $collectionNameParts[0];
    }

    protected function getForeignKeyName(string $collectionName): ?string
    {
        $collectionNameParts = array_diff(explode($this->config->foreignKeySuffix, $collectionName), ['']);
        if (str_ends_with($collectionName, $this->config->foreignKeySuffix) && count(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0])) === 1) {
            return array_key_first(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0]));
        } elseif ((str_ends_with($collectionName, $this->config->foreignKeySuffix) === false) && isset($collectionNameParts[1]) && (isset(Cache::getForeignKeyData(get_called_class(), $collectionNameParts[0])[lcfirst($collectionNameParts[1])]))) {
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
            $this->collections[$this->getForeignKeyReference($name)][$this->getForeignKeyName($name)] = $value;
        } else {
            throw new InvalidPropertyException($name);
        }
    }

    protected function checkCollectionTypeConsistency(string $collectionName, SismaCollection $value)
    {
        if (is_a($value->getRestrictiveType(), $this->getCollectionDataInformation($collectionName), true) === false) {
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
                if (isset($arguments[0]) && ($arguments[0] instanceof SismaCollection)) {
                    $this->setEntityCollection($propertyName, $arguments[0]);
                    break;
                } else {
                    throw new InvalidArgumentException($methodName);
                }
            case 'add':
                if (isset($arguments[0]) && ($arguments[0] instanceof BaseEntity)) {
                    $this->addEntityToEntityCollection($propertyName . $this->config->foreignKeySuffix, $arguments[0]);
                    break;
                } else {
                    throw new InvalidArgumentException($methodName);
                }
            case 'count':
                return $this->countEntityCollection($propertyName);
            default:
                throw new EntityException($methodName);
        }
    }

    public function setEntityCollection(string $propertyName, SismaCollection $sismaCollection): void
    {
        $this->inizializeEntityCollection($propertyName);
        $this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)]->exchangeArray($sismaCollection);
        $entityPropertyName = $this->getForeignKeyName($propertyName);
        foreach ($this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)] as $entity) {
            $entity->$entityPropertyName = $this;
        }
    }

    private function inizializeEntityCollection(string $propertyName): void
    {
        if (isset($this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)]) === false) {
            $this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)] = new SismaCollection($this->getCollectionDataInformation($propertyName));
        }
    }

    public function addEntityToEntityCollection(string $propertyName, BaseEntity $entity): void
    {
        $entityPropertyName = $this->getForeignKeyName($propertyName);
        $this->switchAdditionType($propertyName, $entity);
        $entity->$entityPropertyName = $this;
    }

    private function switchAdditionType(string $propertyName, BaseEntity $entity): void
    {
        $found = false;
        $this->inizializeEntityCollection($propertyName);
        foreach ($this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)] as &$includedEntity) {
            if (isset($entity->id) && ($includedEntity->id === $entity->id)) {
                $includedEntity = $entity;
                $found = true;
            }
        }
        if ($found === false) {
            $this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)]->append($entity);
        }
    }

    public function countEntityCollection(string $propertyName): int
    {
        $modelName = str_replace('Entities', 'Models', $this->getCollectionDataInformation($propertyName)) . 'Model';
        $foreignKeyName = $this->getForeignKeyName($propertyName);
        $model = new $modelName($this->dataMapper);
        return $model->countEntityCollectionByEntity([$foreignKeyName => $this]);
    }

    #[\Override]
    public function toArray(): array
    {
        $result = [];
        $reflectionClass = new \ReflectionClass($this);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (self::checkFinalClassReflectionProperty($reflectionProperty)) {
                $result[$reflectionProperty->getName()] = $this->parsePropterty($reflectionProperty);
            }
        }
        return array_merge($result, $this->collections);
    }

    public function collectionPropertyIsSetted(string $propertyName): bool
    {
        return isset($this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)]);
    }
}

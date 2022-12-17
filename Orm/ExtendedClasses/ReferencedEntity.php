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

namespace SismaFramework\Orm\ExtendedClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\Exceptions\EntityException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\BaseClasses\BaseAdapter;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class ReferencedEntity extends BaseEntity
{

    //protected array $collectionPropertiesName = [];
    protected array $collectionPropertiesSetted = [];
    protected array $collections = [];

    public const FOREIGN_KEY_TYPE = 'foreignKeyType';
    public const FOREIGN_KEY_NAME = 'foreignKeyName';
    public const FOREIGN_KEY_SUFFIX = 'Collection';

    public function __construct(?BaseAdapter &$adapter = null)
    {
        parent::__construct($adapter);
        foreach (Cache::getForeignKeyData($this) as $foreignKeyReference => $foreignKeyData) {
            foreach (array_keys($foreignKeyData) as $foreignKeyName) {
                $this->collections[$foreignKeyReference][$foreignKeyName] = new SismaCollection();
                $this->collectionPropertiesSetted[$foreignKeyReference][$foreignKeyName] = false;
            }
        }
    }
    
    public function getCollectionNames()
    {
        $collectionNames = array_keys($this->collections);
        array_walk($collectionNames, function(&$value){
            $value .= self::FOREIGN_KEY_SUFFIX;
        });
        return $collectionNames;
    }

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
        if (str_contains($collectionName, self::FOREIGN_KEY_SUFFIX) === false) {
            return false;
        } elseif (str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) && count(Cache::getForeignKeyData($this)[$this->getForeignKeyReference($collectionName)]) === 1) {
            return true;
        } elseif ((str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) === false) && (isset(Cache::getForeignKeyData($this)[$this->getForeignKeyReference($collectionName)][$this->getForeignKeyName($collectionName)]))) {
            return true;
        } else {
            return false;
        }
    }

    protected function forceCollectionPropertySet(string $propertyName): void
    {
        if ((count($this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)]) === 0) &&
                ($this->collectionPropertiesSetted[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)] === false)) {
            $modelName = str_replace('Entities', 'Models', $this->getCollectionDataInformation($propertyName)) . 'Model';
            $foreignKeyName = $this->getForeignKeyName($propertyName);
            $model = new $modelName();
            $entityCollection = isset($this->id) ? $model->getEntityCollectionByEntity([$foreignKeyName => $this]) : new SismaCollection();
            $this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)] = $entityCollection;
        }
    }

    public function __isset($name)
    {
        if ($this->checkCollectionExists($name)) {
            return isset($this->collections[$this->getForeignKeyReference($name)][$this->getForeignKeyName($name)]);
        } else {
            return parent::__isset($name);
        }
    }

    public function getCollectionDataInformation(string $collectionName): string
    {
        return Cache::getForeignKeyData($this)[$this->getForeignKeyReference($collectionName)][$this->getForeignKeyName($collectionName)];
    }

    public function getForeignKeyReference(string $collectionName): string
    {
        $collectionNameParts = explode(self::FOREIGN_KEY_SUFFIX, $collectionName);
        return $collectionNameParts[0];
    }

    public function getForeignKeyName(string $collectionName): ?string
    {
        $collectionNameParts = array_diff(explode(self::FOREIGN_KEY_SUFFIX, $collectionName), ['']);
        if (str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) && count(Cache::getForeignKeyData($this)[$collectionNameParts[0]]) === 1) {
            return array_key_first(Cache::getForeignKeyData($this)[$collectionNameParts[0]]);
        } elseif ((str_ends_with($collectionName, self::FOREIGN_KEY_SUFFIX) === false) && isset($collectionNameParts[1]) && (isset(Cache::getForeignKeyData($this)[$collectionNameParts[0]][lcfirst($collectionNameParts[1])]))) {
            return lcfirst($collectionNameParts[1]);
        } else {
            return null;
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->switchSettingType($name, $value);
        } elseif ($this->checkCollectionExists($name)) {
            $this->collectionPropertiesSetted[$this->getForeignKeyReference($name)][$this->getForeignKeyName($name)] = true;
            $this->collections[$this->getForeignKeyReference($name)][$this->getForeignKeyName($name)] = $value;
        } else {
            throw new InvalidPropertyException();
        }
    }

    private function checkCollectionDataConsistency(string $collectionName): void
    {
        if (($this->checkRelatedPropertyPresence($collectionName) === false) ||
                ($this->checkRelatedPropertyName($collectionName) === false)) {
            throw new InvalidArgumentException();
        }
    }

    private function checkRelatedPropertyPresence(string $collectionName): bool
    {
        return (property_exists($this->collectionData[$collectionName][static::FOREIGN_KEY_TYPE], $this->collectionData[$collectionName][static::FOREIGN_KEY_NAME]));
    }

    private function checkRelatedPropertyName(string $collectionName): bool
    {
        $calledClassName = get_called_class();
        $reflectionRelatedProperty = new \ReflectionProperty($this->collectionData[$collectionName][static::FOREIGN_KEY_TYPE], $this->collectionData[$collectionName][static::FOREIGN_KEY_NAME]);
        return ($reflectionRelatedProperty->getType()->getName() === $calledClassName);
    }

    public function __call($methodName, $arguments)
    {
        $methodNameParts = preg_split('/(?=[A-Z])/',$methodName);
        $methodType = array_shift($methodNameParts);
        $propertyName = lcfirst(implode($methodNameParts));
        switch ($methodType) {
            case 'set':
                if (isset($arguments[0])) {
                    $this->setEntityCollection($propertyName, $arguments[0]);
                    break;
                } else {
                    throw new InvalidArgumentException();
                }
            case 'add':
                $this->addEntityToEntityCollection($propertyName . static::FOREIGN_KEY_SUFFIX, $arguments[0]);
                break;
            case 'count':
                return $this->countEntityCollection($propertyName);
            default:
                throw new EntityException('Metodo non trovato');
        }
    }

    protected function saveEntityCollection(): void
    {
        foreach ($this->collections as $foreignKey) {
            foreach ($foreignKey as $collection) {
                foreach ($collection as $entity) {
                    $entity->save();
                }
            }
        }
    }

    public function setEntityCollection(string $propertyName, SismaCollection $sismaCollection): void
    {
        if ($this->checkCollectionElementTypeConsistency($propertyName, $sismaCollection)) {
            $this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)]->exchangeArray($sismaCollection);
            $entityPropertyName = $this->getForeignKeyName($propertyName);
            foreach ($this->collections[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)] as $entity) {
                $entity->$entityPropertyName = $this;
            }
            $this->collectionPropertiesSetted[$this->getForeignKeyReference($propertyName)][$this->getForeignKeyName($propertyName)] = true;
        } else {
            throw new InvalidArgumentException();
        }
    }

    public function checkCollectionElementTypeConsistency(string $propertyName, SismaCollection $sismaCollection): bool
    {

        $entityType = $this->getCollectionDataInformation($propertyName);
        $isConsistent = true;
        foreach ($sismaCollection as $entity) {
            if (($entity instanceof $entityType) === false) {
                $isConsistent = false;
            }
        }
        return $isConsistent;
    }

    public function addEntityToEntityCollection(string $propertyName, BaseEntity $entity): void
    {
        $entityPropertyName = $this->getForeignKeyName($propertyName);
        $entityType = $this->getCollectionDataInformation($propertyName);
        if ($entity instanceof $entityType) {
            $this->switchAdditionType($propertyName, $entity);
            $entity->$entityPropertyName = $this;
        } else {
            throw new InvalidArgumentException();
        }
    }

    private function switchAdditionType(string $propertyName, BaseEntity $entity): void
    {
        $found = false;
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
        $model = new $modelName();
        return $model->countEntityCollectionByEntity([$foreignKeyName => $this]);
    }

}

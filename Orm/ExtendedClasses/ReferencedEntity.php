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
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\Exceptions\EntityException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class ReferencedEntity extends BaseEntity
{

    use \SismaFramework\Core\Traits\ParseValue;

    private array $collectionData;

    public const FOREIGN_KEY_TYPE = 'foreignKeyType';
    public const FOREIGN_KEY_NAME = 'foreignKeyName';
    public const FOREIGN_KEY_SUFFIX = 'Collection';

    protected function forceForeignKeyPropertySet($propertyName): void
    {
        $reflectionProperty = new \ReflectionProperty($this, $propertyName);
        $reflectionTypeName = $reflectionProperty->getType()->getName();
        if (($reflectionProperty->class === get_class($this))) {
            if ((isset($this->$propertyName) === false) && isset($this->foreignKeyIndexes[$propertyName]) && is_subclass_of($reflectionTypeName, BaseEntity::class)) {
                $this->$propertyName = $this->parseEntity($reflectionTypeName, $this->foreignKeyIndexes[$propertyName]);
                unset($this->foreignKeyIndexes[$propertyName]);
            } elseif (($reflectionTypeName === SismaCollection::class) && ((isset($this->$propertyName) === false) || (count($this->$propertyName) === 0))) {
                $modelName = str_replace('Entities', 'Models', $this->getCollectionDataInformation($propertyName, static::FOREIGN_KEY_TYPE)) . 'Model';
                $foreignKeyName = $this->getCollectionDataInformation($propertyName, static::FOREIGN_KEY_NAME);
                $model = new $modelName();
                $entityCollection = isset($this->id) ? $model->getEntityCollectionByEntity([$foreignKeyName => $this]) : [] ; 
                $this->$propertyName = new SismaCollection($entityCollection);
            }
        }
    }

    public function getCollectionDataInformation(string $collectionName, string $information): string
    {
        $this->setCollectionData();
        return $this->collectionData[$collectionName][$information];
    }

    abstract protected function setCollectionData(): void;

    protected function addCollectionData(string $collectionName, string $foreignKeyType, string $foreignKeyName): self
    {
        $this->collectionData[$collectionName] = [
            static::FOREIGN_KEY_TYPE => $foreignKeyType,
            static::FOREIGN_KEY_NAME => $foreignKeyName,
        ];
        $this->checkCollectionDataConsistency($collectionName);
        return $this;
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
        $methodType = substr($methodName, 0, 3);
        $propertyName = lcfirst(substr($methodName, 3));
        switch ($methodType) {
            case 'set':
                $argument = isset($arguments[0]) ? $arguments[0] : null;
                return $this->setEntityCollection($propertyName, $argument);
            case 'add':
                return $this->addEntityToEntityCollection($propertyName . static::FOREIGN_KEY_SUFFIX, $arguments[0]);
            default:
                throw new EntityException('Metodo non trovato');
        }
    }

    protected function saveEntityCollection(): void
    {
        foreach ($this->collectionPropertiesName as $collectionName) {
            $collectionProperty = $this->$collectionName;
            foreach ($collectionProperty as $entity) {
                $entity->save();
            }
        }
    }

    public function setEntityCollection(string $propertyName, SismaCollection $sismaCollection): void
    {
        if ($this->checkCollectionElementTypeConsistency($propertyName, $sismaCollection)) {
            $this->$propertyName->exchangeArray($sismaCollection);
            $entityPropertyName = $this->getCollectionDataInformation($propertyName, static::FOREIGN_KEY_NAME);
            foreach ($this->$propertyName as $entity) {
                $entity->$entityPropertyName = $this;
            }
        } else {
            throw new InvalidArgumentException();
        }
    }

    public function checkCollectionElementTypeConsistency(string $propertyName, SismaCollection $sismaCollection): bool
    {
        $entityType = $this->getCollectionDataInformation($propertyName, static::FOREIGN_KEY_TYPE);
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
        $entityPropertyName = $this->getCollectionDataInformation($propertyName, static::FOREIGN_KEY_NAME);
        $entityType = $this->getCollectionDataInformation($propertyName, static::FOREIGN_KEY_TYPE);
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
        foreach ($this->$propertyName as $key => $includedEntity) {
            if (isset($entity->id) && ($includedEntity->id === $entity->id)) {
                $this->$propertyName[$key] = $entity;
                $found = true;
            }
        }
        if ($found === false) {
            $this->$propertyName->append($entity);
        }
    }

}

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

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Exceptions\InvalidPropertyException;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\ProprietaryTypes\SismaDateTime;

/**
 * @author Valentino de Lapa
 */
abstract class BaseEntity
{

    public bool $modified = false;
    public bool $nestedChanges = false;
    public array $foreignKeys = [];
    public string $initializationVectorPropertyName = 'initializationVector';
    protected BaseEntity $callingEntity;
    protected string $primaryKey = 'id';
    protected static ?BaseEntity $instance = null;
    protected bool $isActiveTransaction = false;
    protected ?BaseAdapter $adapter = null;
    protected array $foreignKeyIndexes = [];
    private array $encryptedColumns = [];

    public function __construct()
    {
        $this->setPropertyDefaultValue();
        $this->setEncryptedProperties();
        $reflectionClass = new \ReflectionClass($this);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (($reflectionProperty->class === get_called_class()) && is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class)) {
                $this->foreignKeys[] = $reflectionProperty->getName();
            }
        }
    }

    public function unsetPrimaryKey(): void
    {
        unset($this->{$this->primaryKey});
    }

    public function checkCollectionExists(string $collectionName): bool
    {
        return false;
    }

    public function __get($name)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->forceForeignKeyPropertySet($name);
            return $this->$name;
        } else {
            throw new InvalidPropertyException($name);
        }
    }

    protected function checkFinalClassProperty(string $propertyName): bool
    {
        $reflectionProperty = new \ReflectionProperty($this, $propertyName);
        return $reflectionProperty->class === get_class($this);
    }

    protected function forceForeignKeyPropertySet(string $propertyName): void
    {
        $reflectionProperty = new \ReflectionProperty($this, $propertyName);
        $reflectionTypeName = $reflectionProperty->getType()->getName();
        if ((isset($this->$propertyName) === false) && isset($this->foreignKeyIndexes[$propertyName]) && is_subclass_of($reflectionTypeName, BaseEntity::class)) {
            $this->$propertyName = Parser::parseEntity($reflectionTypeName, $this->foreignKeyIndexes[$propertyName]);
            $this->$propertyName->callingEntity = $this;
            unset($this->foreignKeyIndexes[$propertyName]);
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->switchSettingType($name, $value);
        } else {
            throw new InvalidPropertyException();
        }
    }

    protected function switchSettingType(string $name, mixed $value): void
    {
        $reflectionProperty = new \ReflectionProperty($this, $name);
        if (is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class)) {
            if (is_int($value)) {
                if (Cache::checkEntityPresenceInCache($reflectionProperty->getType()->getName(), $value)) {
                    $cachedEntity = Cache::getEntityById($reflectionProperty->getType()->getName(), $value);
                    $this->setEntityProperty($name, $cachedEntity);
                } else {
                    $this->trackForeignKeyPropertyWithIndexNotConvertedChanges($name, $value);
                    $this->foreignKeyIndexes[$name] = $value;
                    unset($this->$name);
                }
            } elseif (($value instanceof BaseEntity)) {
                $this->setEntityProperty($name, $value);
            } elseif ($value === null) {
                $this->trackForeignKeyPropertyWithNullValueChanges($name);
                $this->$name = $value;
                unset($this->foreignKeyIndexes[$name]);
            }
        } else {
            $this->trackOtherPropertyChanges($reflectionProperty->getType(), $name, $value);
            $this->$name = $value;
        }
    }

    private function trackForeignKeyPropertyWithIndexNotConvertedChanges(mixed $name, mixed $value): void
    {
        if (((isset($this->foreignKeyIndexes[$name]) && ($this->foreignKeyIndexes[$name] !== $value)) ||
                (!isset($this->foreignKeyIndexes[$name]) && (!isset($this->$name->id) || ($this->$name->id !== $value))))) {
            $this->modified = true;
            $this->setNestedChangesOnCallingEntityWhenEntityChanges();
        }
    }

    private function setNestedChangesOnCallingEntityWhenEntityChanges(): void
    {
        if (isset($this->callingEntity)) {
            $this->callingEntity->nestedChanges = true;
        }
    }

    private function setEntityProperty(string $name, BaseEntity $value): void
    {
        $value->callingEntity = $this;
        $this->trackForeignKeyPropertyWithIndexConvertedChanges($name, $value);
        $this->$name = $value;
        $this->setNestedChangesOnEntityWhenCalledEntitiesIsModified($value);
        unset($this->foreignKeyIndexes[$name]);
    }

    protected function setNestedChangesOnEntityWhenCalledEntitiesIsModified(BaseEntity $entity)
    {
        if ((isset($entity->id) === false) || $entity->modified) {
            $this->nestedChanges = true;
        }
    }

    private function trackForeignKeyPropertyWithIndexConvertedChanges(mixed $name, mixed $value): void
    {
        if (((isset($this->foreignKeyIndexes[$name]) && (!isset($value->id) || ($this->foreignKeyIndexes[$name] !== $value->id)) ||
                (!isset($this->foreignKeyIndexes[$name]) && (!isset($this->$name->id) || ($this->$name != $value)))))) {
            $this->modified = true;
            $this->setNestedChangesOnCallingEntityWhenEntityChanges();
        }
    }

    private function trackForeignKeyPropertyWithNullValueChanges(mixed $name): void
    {
        if ((isset($this->foreignKeyIndexes[$name]) || isset($this->$name))) {
            $this->modified = true;
            $this->setNestedChangesOnCallingEntityWhenEntityChanges();
        }
    }

    private function trackOtherPropertyChanges(\ReflectionNamedType $reflectionNamedType, mixed $name, mixed $value): void
    {
        if ($this->checkBuiltinOrEnumPropertyChange($reflectionNamedType, $name, $value) ||
                $this->checkSismaDateTimePropertyChange($reflectionNamedType, $name, $value)) {
            $this->modified = true;
            $this->setNestedChangesOnCallingEntityWhenEntityChanges();
        }
    }

    private function checkBuiltinOrEnumPropertyChange(\ReflectionNamedType $reflectionNamedType, mixed $name, mixed $value): bool
    {
        return (($reflectionNamedType->isBuiltin() || enum_exists($reflectionNamedType->getName())) &&
                ((isset($this->$name) && ($this->$name !== $value)) ||
                ((isset($this->$name) === false) && ($value !== null))));
    }

    private function checkSismaDateTimePropertyChange(\ReflectionNamedType $reflectionNamedType, mixed $name, mixed $value): bool
    {
        return (is_a($reflectionNamedType->getName(), SismaDateTime::class, true) &&
                ((isset($this->$name) && ((($value instanceof SismaDateTime) && ($this->$name != $value)) || ($value === null))) ||
                ((isset($this->$name) === false) && ($value !== null))));
    }

    public function __isset($name)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->forceForeignKeyPropertySet($name);
        }
        return isset($this->$name);
    }

    abstract protected function setEncryptedProperties(): void;

    protected function addEncryptedProperty(string $columnName): void
    {
        $this->encryptedColumns[] = $columnName;
    }

    public function isEncryptedProperty(string $columnName): bool
    {
        return (in_array($columnName, $this->encryptedColumns) && (property_exists($this, $this->initializationVectorPropertyName)));
    }

    public function getInitializationVectorPropertyName(): string
    {
        return $this->initializationVectorPropertyName;
    }

    abstract protected function setPropertyDefaultValue(): void;

    public function isPrimaryKey(string $propertyName): bool
    {
        return ($propertyName === $this->primaryKey);
    }

    public function getPrimaryKeyPropertyName(): string
    {
        return $this->primaryKey;
    }

    static public function create(): BaseEntity
    {
        $class = get_called_class();
        if ((static::$instance === null) || ((static::$instance instanceof $class) === false)) {
            static::$instance = new $class();
        }
        $ret = clone static::$instance;
        return $ret;
    }

    public function getField(string $name)
    {
        $prop = new \ReflectionProperty(get_called_class(), $name);
        if ($prop->class !== get_called_class()) {
            return null;
        }
        unset($prop);
        return $this->$name;
    }

    public function getForeignKeyIndexes(): array
    {
        return $this->foreignKeyIndexes;
    }
}

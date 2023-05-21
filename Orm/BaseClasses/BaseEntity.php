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

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Exceptions\InvalidPropertyException;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseEntity
{

    public bool $modified = false;
    public bool $nestedChanges = false;
    public array $foreignKeys = [];
    public string $initializationVectorPropertyName = 'initializationVector';
    protected BaseEntity $callingEntity;
    protected string $tableName = '';
    protected string $primaryKey = 'id';
    protected static ?BaseEntity $instance = null;
    protected bool $isActiveTransaction = false;
    protected ?BaseAdapter $adapter = null;
    protected array $foreignKeyIndexes = [];
    private array $encryptedColumns = [];

    public function __construct()
    {
        $this->tableName = $this->buildTableName();
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
        if (is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class) && is_int($value)) {
            $this->trackForeignKeyPropertyWithIndexNotConvertedChanges($reflectionProperty->getType(), $name, $value);
            $this->foreignKeyIndexes[$name] = $value;
            unset($this->$name);
        } elseif (is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class) && ($value instanceof BaseEntity)) {
            $value->callingEntity = $this;
            $this->trackForeignKeyPropertyChanges($reflectionProperty->getType(), $name, $value);
            $this->$name = $value;
            unset($this->foreignKeyIndexes[$name]);
        } else {
            $this->trackbuiltinPropertyChanges($reflectionProperty->getType(), $name, $value);
            $this->$name = $value;
        }
    }

    private function trackForeignKeyPropertyWithIndexNotConvertedChanges(\ReflectionNamedType $reflectionNamedType, mixed $name, mixed $value): void
    {
        if (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class) && is_int($value) &&
                ((isset($this->foreignKeyIndexes[$name]) && ($this->foreignKeyIndexes[$name] !== $value)) ||
                (isset($this->$name) && (!isset($this->$name->id) || ($this->$name->id !== $value))))) {
            $this->modified = true;
            if (isset($this->callingEntity)) {
                $this->callingEntity->nestedChanges = true;
            }
        }
    }

    private function trackForeignKeyPropertyChanges(\ReflectionNamedType $reflectionNamedType, mixed $name, mixed $value): void
    {
        if (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class) &&
                is_subclass_of($value, BaseEntity::class) &&
                ((isset($this->foreignKeyIndexes[$name]) && (!isset($value->id) || ($this->foreignKeyIndexes[$name] !== $value->id)) ||
                (isset($this->$name) && ($this->$name != $value))))) {
            $this->modified = true;
            if (isset($this->callingEntity)) {
                $this->callingEntity->nestedChanges = true;
            }
        }
    }

    private function trackbuiltinPropertyChanges(\ReflectionNamedType $reflectionNamedType, mixed $name, mixed $value): void
    {
        if ($reflectionNamedType->isBuiltin() && isset($this->$name) && ($this->$name !== $value)) {
            $this->modified = true;
            if (isset($this->callingEntity)) {
                $this->callingEntity->nestedChanges = true;
            }
        }
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

    public function getEntityTableName(): string
    {
        return $this->tableName;
    }

    public static function getTableName(): string
    {
        $obj = static::create();
        $name = $obj->buildTableName();
        unset($obj);
        return $name;
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

    public function buildTableName(): string
    {
        $class = get_class($this);
        $poppedParts = explode('\\', $class);
        $parts = preg_split('/(?=[A-Z])/', array_pop($poppedParts));
        $tmp = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p != '') {
                $tmp[] = $p;
            }
        }
        return strtolower(implode('_', $tmp));
    }

    public function getForeignKeyIndexes(): array
    {
        return $this->foreignKeyIndexes;
    }

    public function delete(DataMapper $dataMapper = new DataMapper()): bool
    {
        return $dataMapper->delete($this);
    }

}

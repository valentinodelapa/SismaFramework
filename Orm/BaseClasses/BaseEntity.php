<?php

/*
 * Questo file contiene codice derivato dalla libreria SimpleORM
 * (https://github.com/davideairaghi/php) rilasciata sotto licenza Apache License 2.0
 * (fare riferimento alla licenza in third-party-licenses/SimpleOrm/LICENSE).
 *
 * Copyright (c) 2015-present Davide Airaghi.
 *
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
 *
 * MODIFICHE APPORTATE RISPETTO ALLA CLASSE `MODEL` DI SIMPLEORM:
 * - **Passaggio dal pattern Active Record al pattern Data Mapper:** La logica di persistenza è stata separata in una classe `DataMapper`.
 * - **Introduzione di una classe `Entity` per la rappresentazione dei dati:** Sostituisce il ruolo della classe `Model` nella rappresentazione diretta della tabella.
 * - **Gestione delle relazioni tramite proprietà tipizzate:** Le chiavi esterne sono gestite come proprietà che fanno riferimento ad altre `Entity`.
 * - **Implementazione di comportamenti specifici delle `Entity`:** Sono state aggiunte funzionalità non presenti nella concezione originale di `Model`.
 * - **Separazione della logica di costruzione delle query:** Demandata a classi dedicate (come `Query` e `Adapter`).
 */

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\Exceptions\InvalidPropertyException;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\Interfaces\CustomDateTimeInterface;

/**
 * @author Valentino de Lapa
 */
abstract class BaseEntity
{

    public bool $modified = false;
    public array $foreignKeys = [];
    protected DataMapper $dataMapper;
    protected Config $config;
    protected ProcessedEntitiesCollection $processedEntitesCollection;
    protected string $primaryKey = 'id';
    protected string $initializationVectorPropertyName = 'initializationVector';
    protected bool $isActiveTransaction = false;
    private array $encryptedColumns = [];
    private array $foreignKeyIndexes = [];

    public function __construct(DataMapper $dataMapper = new DataMapper(), ?ProcessedEntitiesCollection $processedEntitesCollection = null, ?Config $config = null)
    {
        $this->dataMapper = $dataMapper;
        $this->config = $config ?? Config::getInstance();
        $this->processedEntitesCollection = $processedEntitesCollection ?? ProcessedEntitiesCollection::getInstance();
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
        return self::checkFinalClassReflectionProperty($reflectionProperty);
    }

    public static function checkFinalClassReflectionProperty(\ReflectionProperty $reflectionProperty): bool
    {
        return $reflectionProperty->getDeclaringClass()->isAbstract() === false;
    }

    protected function forceForeignKeyPropertySet(string $propertyName): void
    {
        $reflectionProperty = new \ReflectionProperty($this, $propertyName);
        $reflectionTypeName = $reflectionProperty->getType()->getName();
        if ((isset($this->$propertyName) === false) && isset($this->foreignKeyIndexes[$propertyName]) && is_subclass_of($reflectionTypeName, BaseEntity::class)) {
            $this->$propertyName = Parser::parseEntity($reflectionTypeName, $this->foreignKeyIndexes[$propertyName], $this->dataMapper);
            unset($this->foreignKeyIndexes[$propertyName]);
        }
    }

    public function setPrimaryKeyAfterSave(int $value): void
    {
        $this->{$this->primaryKey} = $value;
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->switchSettingType($name, $value);
        } else {
            throw new InvalidPropertyException($name);
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
            $this->processedEntitesCollection->remove($this);
            $this->modified = true;
        }
    }

    private function setEntityProperty(string $name, BaseEntity $value): void
    {
        $this->trackForeignKeyPropertyWithIndexConvertedChanges($name, $value);
        $this->$name = $value;
        unset($this->foreignKeyIndexes[$name]);
    }

    private function trackForeignKeyPropertyWithIndexConvertedChanges(string $name, mixed $value): void
    {
        if (((isset($this->foreignKeyIndexes[$name]) && (!isset($value->id) || ($this->foreignKeyIndexes[$name] !== $value->id)) ||
                (!isset($this->foreignKeyIndexes[$name]) && (!isset($this->$name->id) || ($this->$name != $value)))))) {
            $this->processedEntitesCollection->remove($this);
            $this->modified = true;
        }
    }

    private function trackForeignKeyPropertyWithNullValueChanges(string $name): void
    {
        if ((isset($this->foreignKeyIndexes[$name]) || isset($this->$name))) {
            $this->processedEntitesCollection->remove($this);
            $this->modified = true;
        }
    }

    private function trackOtherPropertyChanges(\ReflectionNamedType $reflectionNamedType, string $name, mixed $value): void
    {
        if ($this->checkBuiltinOrEnumPropertyChange($reflectionNamedType, $name, $value) ||
                $this->checkCustomDateTimeInterfacePropertyChange($reflectionNamedType, $name, $value)) {
            $this->processedEntitesCollection->remove($this);
            $this->modified = true;
        }
    }

    private function checkBuiltinOrEnumPropertyChange(\ReflectionNamedType $reflectionNamedType, string $name, mixed $value): bool
    {
        return (($reflectionNamedType->isBuiltin() || enum_exists($reflectionNamedType->getName())) &&
                ((isset($this->$name) && ($this->$name !== $value)) ||
                ((isset($this->$name) === false) && ($value !== null))));
    }

    private function checkCustomDateTimeInterfacePropertyChange(\ReflectionNamedType $reflectionNamedType, string $name, mixed $value): bool
    {
        return (is_a($reflectionNamedType->getName(), CustomDateTimeInterface::class, true) &&
                ((isset($this->$name) && ((is_a($value, $reflectionNamedType->getName()) && ($this->$name->equals($value) === false)) || ($value === null))) ||
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

    protected function addEncryptedProperty(string $columnName): self
    {
        $this->encryptedColumns[] = $columnName;
        return $this;
    }

    public function isEncryptedProperty(string $columnName): bool
    {
        return ((empty($this->config->encryptionPassphrase) === false) && in_array($columnName, $this->encryptedColumns) && (property_exists($this, $this->initializationVectorPropertyName)));
    }

    public function isInitializationVector(string $propertyName): bool
    {
        return ($propertyName === $this->initializationVectorPropertyName);
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

    public function setPrimaryKeyPropertyName(string $propertyName): void
    {
        $this->primaryKey = $propertyName;
    }

    public function getPrimaryKeyPropertyName(): string
    {
        return $this->primaryKey;
    }

    public function getForeignKeyIndexes(): array
    {
        return $this->foreignKeyIndexes;
    }

    public function toArray(): array
    {
        $result = [];
        $reflectionClass = new \ReflectionClass($this);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (self::checkFinalClassReflectionProperty($reflectionProperty)) {
                $result[$reflectionProperty->getName()] = $this->parsePropterty($reflectionProperty);
            }
        }
        return $result;
    }

    protected function parsePropterty(\ReflectionProperty $reflectionProperty)
    {
        if (is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class)) {
            if (isset($this->foreignKeyIndexes[$reflectionProperty->getName()])) {
                return $this->foreignKeyIndexes[$reflectionProperty->getName()];
            } elseif ($reflectionProperty->isInitialized($this)) {
                return $reflectionProperty->getValue($this)->toArray();
            } else {
                throw new InvalidPropertyException($reflectionProperty->getName());
            }
        } elseif ($reflectionProperty->isInitialized($this)) {
            return Parser::unparseValue($reflectionProperty->getValue($this));
        } else {
            throw new InvalidPropertyException($reflectionProperty->getName());
        }
    }
}

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
 * Copyright 2023 Valentino de Lapa.
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
 * - Significativa riorganizzazione e rifattorizzazione per l'applicazione della tipizzazione forte.
 * - Passaggio dal pattern Active Record al pattern Data Mapper: La logica di persistenza è stata separata in una classe DataMapper.
 * - Separazione delle responsabilità:** Le operazioni di persistenza sono state estratte dalla classe che rappresentava i dati (Model).
 * - Nessun riferimento diretto alla rappresentazione dei dati (Entity): Il DataMapper si concentra sulla persistenza, interagendo con le `Entity` tramite interfacce.
 * - Implementazione di meccanismi e comportamenti specifici non presenti nella classe Model originale
 * - Implementazione meccanismo dei tipo di binding specifici nelle query di insert e update. 
 */

namespace SismaFramework\Orm\HelperClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Exceptions\DataMapperException;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\Permissions\ReferencedEntityDeletionPermission;
use SismaFramework\Security\Enumerations\AccessControlEntry;

/**
 * @author Valentino de Lapa
 */
class DataMapper
{

    private bool $ormCacheStatus;
    private BaseAdapter $adapter;
    private Config $config;
    private ProcessedEntitiesCollection $processedEntitiesCollection;
    private static bool $isActiveTransaction = false;

    public function __construct(?BaseAdapter $adapter = null, ?ProcessedEntitiesCollection $processedEntityCollection = null, ?Config $config = null)
    {
        $this->config = $config ?? Config::getInstance();
        $this->adapter = $adapter ?? BaseAdapter::getDefault();
        $this->processedEntitiesCollection = $processedEntityCollection ?? ProcessedEntitiesCollection::getInstance();
        $this->ormCacheStatus = $this->config->ormCache;
    }

    public function setOrmCacheStatus(bool $ormCacheStatus = true): void
    {
        $this->ormCacheStatus = $ormCacheStatus;
    }

    public function initQuery(string $entityName, Query $query = new Query()): Query
    {
        if (is_a($entityName, BaseEntity::class, true)) {
            $query->setTable($entityName);
            return $query;
        } else {
            throw new DataMapperException($entityName);
        }
    }

    public function setVariable(string $variable, string $bindValue, DataType $bindType, Query $query = new Query()): bool
    {
        $query->setVariable($variable);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::set);
        Parser::unparseValue($bindValue);
        $result = $this->adapter->execute($cmd, [$bindValue], [$bindType]);
        return $result;
    }

    public function save(BaseEntity $entity, Query $query = new Query()): bool
    {
        if ($this->processedEntitiesCollection->has($entity)) {
            return true;
        } else {
            $this->processedEntitiesCollection->append($entity);
            $isFirstExecution = $this->startTransaction();
            if (empty($entity->{$entity->getPrimaryKeyPropertyName()})) {
                $result = $this->insert($entity, $query);
            } elseif ($entity->modified) {
                $result = $this->update($entity, $query);
            } else {
                $result = $this->saveForeignKeys($entity) && $this->checkIsReferencedEntity($entity);
            }
            if ($result) {
                $this->commitTransaction($isFirstExecution);
                return true;
            } else {
                $this->adapter->rollbackTransaction();
                throw new DataMapperException();
            }
        }
    }

    private function insert(BaseEntity $entity, Query $query = new Query()): bool
    {
        $bindValues = $bindTypes = [];
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        $this->parseValues($entity, $query, $bindValues, $bindTypes);
        $this->parseForeignKeyIndexes($entity, $query, $bindValues, $bindTypes);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::insert);
        $result = $this->adapter->execute($cmd, $bindValues, $bindTypes);
        if ($result) {
            $entity->setPrimaryKeyAfterSave($this->adapter->lastInsertId());
            $entity->modified = false;
            $this->checkIsReferencedEntity($entity);
            if ($this->ormCacheStatus) {
                Cache::setEntity($entity);
            }
        }
        return $result;
    }

    private function parseValues(BaseEntity $entity, Query $query, array &$bindValues, array &$bindTypes): void
    {
        $reflectionClass = new \ReflectionClass($entity);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($this->propertyIsParsable($reflectionProperty, $entity)) {
                $this->parseSingleProperty($reflectionProperty, $entity, $query, $bindValues, $bindTypes);
            }
        }
    }

    private function propertyIsParsable(\ReflectionProperty $reflectionProperty, BaseEntity $entity): bool
    {
        if (BaseEntity::checkFinalClassReflectionProperty($reflectionProperty) === false) {
            return false;
        } elseif ($reflectionProperty->isInitialized($entity) === false) {
            return false;
        } elseif ($entity->isPrimaryKey($reflectionProperty->getName())) {
            return false;
        } elseif ($entity->isInitializationVector($reflectionProperty->getName())) {
            return false;
        } else {
            return true;
        }
    }

    private function parseSingleProperty(\ReflectionProperty $reflectionProperty, BaseEntity $entity, Query $query, array &$bindValues, array &$bindTypes): void
    {
        $currentValue = $reflectionProperty->getValue($entity);
        if ($currentValue instanceof BaseEntity) {
            $this->save($currentValue);
        }
        $parsedValue = Parser::unparseValue($currentValue);
        if ($entity->isEncryptedProperty($reflectionProperty->getName())) {
            $initializationVectorPropertyName = $entity->getInitializationVectorPropertyName();
            if (empty($entity->$initializationVectorPropertyName)) {
                $entity->{$entity->getInitializationVectorPropertyName()} = Encryptor::createInizializationVector();
            }
            if ($query->hasColumn($initializationVectorPropertyName) === false) {
                $query->appendColumnValue($initializationVectorPropertyName);
                $bindTypes[] = DataType::typeBinary;
                $bindValues[] = Parser::unparseValue($entity->$initializationVectorPropertyName);
            }
            $parsedValue = Encryptor::encryptString($parsedValue, $entity->$initializationVectorPropertyName);
        }
        $bindTypes[] = $this->getType($reflectionProperty->getType(), $parsedValue);
        $query->appendColumnValue($reflectionProperty->getName(), Placeholder::placeholder, is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class));
        $bindValues[] = $parsedValue;
    }

    private function getType(\ReflectionNamedType $reflectionNamedType, mixed $value): DataType
    {
        if ($reflectionNamedType->getName() === 'bool') {
            return DataType::typeBoolean;
        } elseif ($reflectionNamedType->getName() === 'int') {
            return DataType::typeInteger;
        } elseif ($reflectionNamedType->getName() === 'float') {
            return DataType::typeDecimal;
        } elseif ($reflectionNamedType->getName() === 'string') {
            if (mb_detect_encoding($value ?? '', 'UTF-8', true)) {
                return DataType::typeString;
            } else {
                return DataType::typeBinary;
            }
        } elseif (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class)) {
            return DataType::typeEntity;
        } elseif (enum_exists($reflectionNamedType->getName())) {
            return DataType::typeEnumeration;
        } elseif (is_subclass_of($reflectionNamedType->getName(), \DateTimeInterface::class)) {
            return DataType::typeDate;
        } else {
            return DataType::typeGeneric;
        }
    }

    private function parseForeignKeyIndexes(BaseEntity $entity, Query $query, array &$bindValues, array &$bindTypes): void
    {
        foreach ($entity->getForeignKeyIndexes() as $propertyName => $propertyValue) {
            $query->appendColumnValue($propertyName, Placeholder::placeholder, true);
            $bindTypes[] = DataType::typeInteger;
            $bindValues[] = $propertyValue;
        }
    }

    public function startTransaction(): bool
    {
        if (self::$isActiveTransaction === false) {
            if ($this->adapter->beginTransaction()) {
                self::$isActiveTransaction = true;
                return true;
            }
        }
        return false;
    }

    private function checkIsReferencedEntity(BaseEntity $entity): bool
    {
        if (($entity instanceof ReferencedEntity)) {
            return $this->saveEntityCollection($entity);
        } else {
            return true;
        }
    }

    private function saveEntityCollection(ReferencedEntity $entity): bool
    {
        foreach ($entity->getCollections() as $foreignKey) {
            foreach ($foreignKey as $collection) {
                foreach ($collection as $entityFromCollection) {
                    if ($this->save($entityFromCollection) === false) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function commitTransaction(bool $checkAnnidation = true): void
    {
        if (self::$isActiveTransaction && $checkAnnidation) {
            if ($this->adapter->commitTransaction()) {
                self::$isActiveTransaction = false;
                $this->processedEntitiesCollection->clear();
            }
        }
    }

    private function update(BaseEntity $entity, Query $query = new Query()): bool
    {
        $bindValues = $bindTypes = [];
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        $this->parseValues($entity, $query, $bindValues, $bindTypes);
        $this->parseForeignKeyIndexes($entity, $query, $bindValues, $bindTypes);
        $query->setWhere();
        $query->appendCondition($entity->getPrimaryKeyPropertyName(), ComparisonOperator::equal, Placeholder::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::update);
        $bindValues[] = $entity->{$entity->getPrimaryKeyPropertyName()};
        $bindTypes[] = DataType::typeInteger;
        $result = $this->adapter->execute($cmd, $bindValues, $bindTypes);
        if ($result) {
            $entity->modified = false;
            $this->checkIsReferencedEntity($entity);
            if ($this->ormCacheStatus) {
                Cache::setEntity($entity);
            }
        }
        return $result;
    }

    private function saveForeignKeys(BaseEntity $entity): bool
    {
        foreach ($entity->foreignKeys as $foreignKey) {
            if ($entity->$foreignKey instanceof BaseEntity) {
                if ($this->save($entity->$foreignKey) === false) {
                    return false;
                }
            }
        }
        return true;
    }

    public function delete(BaseEntity $entity, Query $query = new Query()): bool
    {
        if ($entity instanceof ReferencedEntity) {
            ReferencedEntityDeletionPermission::isAllowed($entity, AccessControlEntry::allow);
        }
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        if (empty($entity->getPrimaryKeyPropertyName())) {
            return false;
        }
        $query->setWhere();
        $query->appendCondition($entity->getPrimaryKeyPropertyName(), ComparisonOperator::equal, Placeholder::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::delete);
        $bindValues = [$entity];
        $bindTypes = [DataType::typeEntity];
        Parser::unparseValues($bindValues);
        $result = $this->adapter->execute($cmd, $bindValues, $bindTypes);
        if ($result) {
            Cache::clearEntityCache();
            $entity->unsetPrimaryKey();
        }
        return $result;
    }

    public function deleteBatch(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): bool
    {
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::delete);
        Parser::unparseValues($bindValues);
        $result = $this->adapter->execute($cmd, $bindValues, $bindTypes);
        if ($result) {
            Cache::clearEntityCache();
        }
        return $result;
    }

    public function find(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): SismaCollection
    {
        $result = $this->getResultSet($entityName, $query, $bindValues, $bindTypes);
        $collection = new SismaCollection($entityName);
        if ($result instanceof BaseResultSet) {
            foreach ($result as $entity) {
                $collection->append($this->selectLastModifiedEntity($entityName, $entity));
            }
        }
        return $collection;
    }

    private function selectLastModifiedEntity(string $entityName, BaseEntity $entity): BaseEntity
    {
        if ($this->ormCacheStatus && Cache::checkEntityPresenceInCache($entityName, $entity->id)) {
            return Cache::getEntityById($entityName, $entity->id);
        } elseif ($this->ormCacheStatus) {
            Cache::setEntity($entity);
        }
        return $entity;
    }

    private function getResultSet(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): ?BaseResultSet
    {
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::select);
        Parser::unparseValues($bindValues);
        $result = $this->adapter->select($cmd, $bindValues, $bindTypes);
        if (!$result) {
            return null;
        }
        $result->setReturnType($entityName);
        return $result;
    }

    public function getCount(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): int
    {
        $query->setCount('');
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::select);
        Parser::unparseValues($bindValues);
        $result = $this->adapter->select($cmd, $bindValues, $bindTypes);
        if ($result === null) {
            return 0;
        }
        $data = $result->fetch();
        $result->release();
        unset($result);
        if ($data === null) {
            return 0;
        }
        return $data->_numrows;
    }

    public function findFirst(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): ?BaseEntity
    {
        $query->setOffset(0);
        $query->setLimit(1);
        $result = $this->getResultSet($entityName, $query, $bindValues, $bindTypes);
        if ($result === null) {
            return null;
        } else {
            switch ($result->numRows()) {
                case 0:
                    return null;
                case 1:
                    return $this->selectLastModifiedEntity($entityName, $result->fetch());
                default:
                    throw new DataMapperException();
            }
        }
    }
}

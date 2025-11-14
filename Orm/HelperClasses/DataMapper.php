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
 * - Significativa riorganizzazione e rifattorizzazione per l'applicazione della tipizzazione forte.
 * - Passaggio dal pattern Active Record al pattern Data Mapper: La logica di persistenza è stata separata in una classe DataMapper.
 * - Separazione delle responsabilità: Le operazioni di persistenza sono state estratte dalla classe che rappresentava i dati (Model).
 * - Nessun riferimento diretto alla rappresentazione dei dati (Entity): Il DataMapper si concentra sulla persistenza, interagendo con le `Entity` tramite interfacce.
 * - Implementazione di meccanismi e comportamenti specifici non presenti nella classe Model originale.
 * - Implementazione meccanismo dei tipo di binding specifici nelle query di insert e update.
 * - Refactoring (2025): Estrazione parziale delle responsabilità in classi @internal separate:
 *   - TransactionManager: Gestione delle transazioni database (separazione mantenuta)
 *   - QueryExecutor: Esecuzione query di lettura (find, findFirst, getCount) con integrazione cache (separazione mantenuta)
 *   - Logica di persistenza (insert, update, delete): Riportata come metodi privati in DataMapper per evitare accoppiamento circolare
 *   - DataType::fromReflection(): Risoluzione automatica dei tipi di dato per il binding
 * - Ottimizzazione dipendenze: Utilizzo di singleton pattern per BaseAdapter e ProcessedEntitiesCollection
 */

namespace SismaFramework\Orm\HelperClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Exceptions\DataMapperException;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\HelperClasses\DataMapper\QueryExecutor;
use SismaFramework\Orm\HelperClasses\DataMapper\TransactionManager;
use SismaFramework\Orm\Permissions\ReferencedEntityDeletionPermission;
use SismaFramework\Security\Enumerations\AccessControlEntry;

/**
 * @author Valentino de Lapa
 */
class DataMapper
{

    private Config $config;
    private BaseAdapter $adapter;
    private ProcessedEntitiesCollection $processedEntitiesCollection;
    private bool $ormCacheStatus;

    public function __construct(
            ?BaseAdapter $adapter = null,
            ?ProcessedEntitiesCollection $processedEntityCollection = null,
            ?Config $config = null,
            private TransactionManager $transactionManager = new TransactionManager(),
            private QueryExecutor $queryExecutor = new QueryExecutor())
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
        return $this->queryExecutor->setVariable($variable, $bindValue, $bindType, $query);
    }

    public function save(BaseEntity $entity, Query $query = new Query()): bool
    {
        if ($this->processedEntitiesCollection->has($entity)) {
            return true;
        } else {
            $this->processedEntitiesCollection->append($entity);
            $isFirstExecution = $this->transactionManager->start();
            if (empty($entity->{$entity->getPrimaryKeyPropertyName()})) {
                $result = $this->insert($entity, $query);
            } elseif ($entity->modified) {
                $result = $this->update($entity, $query);
            } else {
                $result = $this->saveForeignKeys($entity) && $this->checkIsReferencedEntity($entity);
            }
            if ($result) {
                $this->transactionManager->commit($isFirstExecution);
                return true;
            } else {
                $this->transactionManager->rollback();
                throw new DataMapperException('Failed to save entity: ' . get_class($entity));
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
        $bindTypes[] = DataType::fromReflection($reflectionProperty->getType(), $parsedValue);
        $query->appendColumnValue($reflectionProperty->getName(), Placeholder::placeholder, is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class));
        $bindValues[] = $parsedValue;
    }

    private function parseForeignKeyIndexes(BaseEntity $entity, Query $query, array &$bindValues, array &$bindTypes): void
    {
        foreach ($entity->getForeignKeyIndexes() as $propertyName => $propertyValue) {
            $query->appendColumnValue($propertyName, Placeholder::placeholder, true);
            $bindTypes[] = DataType::typeInteger;
            $bindValues[] = $propertyValue;
        }
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

    public function startTransaction(): bool
    {
        return $this->transactionManager->start();
    }

    public function commitTransaction(bool $checkAnnidation = true): void
    {
        $this->transactionManager->commit($checkAnnidation);
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
        return $this->queryExecutor->find($entityName, $query, $bindValues, $bindTypes, $this->ormCacheStatus);
    }

    public function getCount(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): int
    {
        return $this->queryExecutor->getCount($query, $bindValues, $bindTypes);
    }

    public function findFirst(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): ?BaseEntity
    {
        return $this->queryExecutor->findFirst($entityName, $query, $bindValues, $bindTypes, $this->ormCacheStatus);
    }
}

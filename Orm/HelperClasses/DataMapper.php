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
 * - Refactoring (2025): Estrazione delle responsabilità in classi interne @internal per migliorare la manutenibilità:
 *   - TransactionManager: Gestione delle transazioni database
 *   - EntityPersister: Logica di persistenza (insert, update, delete) con supporto encryption e foreign keys
 *   - QueryExecutor: Esecuzione query di lettura (find, findFirst, getCount) con integrazione cache
 *   - DataType::fromReflection(): Risoluzione automatica dei tipi di dato per il binding
 * - Ottimizzazione dipendenze: Utilizzo di singleton pattern per BaseAdapter e ProcessedEntitiesCollection
 * - Cache dinamica: Implementazione callable per ormCacheStatus per supportare modifiche runtime via setOrmCacheStatus()
 */

namespace SismaFramework\Orm\HelperClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Exceptions\DataMapperException;
use SismaFramework\Orm\HelperClasses\DataMapper\EntityPersister;
use SismaFramework\Orm\HelperClasses\DataMapper\QueryExecutor;
use SismaFramework\Orm\HelperClasses\DataMapper\TransactionManager;

/**
 * @author Valentino de Lapa
 */
class DataMapper
{

    private Config $config;
    private ProcessedEntitiesCollection $processedEntitiesCollection;
    private TransactionManager $transactionManager;
    private EntityPersister $entityPersister;
    private QueryExecutor $queryExecutor;
    private bool $ormCacheStatus;

    public function __construct(
            ?ProcessedEntitiesCollection $processedEntityCollection = null,
            ?Config $config = null,
            ?TransactionManager $transactionManager = null,
            ?EntityPersister $entityPersister = null,
            ?QueryExecutor $queryExecutor = null)
    {
        $this->config = $config ?? Config::getInstance();
        $this->processedEntitiesCollection = $processedEntityCollection ?? ProcessedEntitiesCollection::getInstance();
        $this->ormCacheStatus = $this->config->ormCache;
        $this->transactionManager = $transactionManager ?? new TransactionManager();
        $this->entityPersister = $entityPersister ?? new EntityPersister(null, fn() => $this->ormCacheStatus);
        $this->queryExecutor = $queryExecutor ?? new QueryExecutor(null, fn() => $this->ormCacheStatus);
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
                $result = $this->entityPersister->insert($entity, $query, fn($e) => $this->save($e));
            } elseif ($entity->modified) {
                $result = $this->entityPersister->update($entity, $query, fn($e) => $this->save($e));
            } else {
                $result = $this->entityPersister->saveForeignKeysAndCollections($entity, fn($e) => $this->save($e));
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
        return $this->entityPersister->delete($entity, $query);
    }

    public function deleteBatch(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): bool
    {
        return $this->entityPersister->deleteBatch($query, $bindValues, $bindTypes);
    }

    public function find(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): SismaCollection
    {
        return $this->queryExecutor->find($entityName, $query, $bindValues, $bindTypes);
    }

    public function getCount(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): int
    {
        return $this->queryExecutor->getCount($query, $bindValues, $bindTypes);
    }

    public function findFirst(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): ?BaseEntity
    {
        return $this->queryExecutor->findFirst($entityName, $query, $bindValues, $bindTypes);
    }
}

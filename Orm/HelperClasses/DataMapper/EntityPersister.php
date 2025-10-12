<?php

/*
 * Questo file contiene codice estratto dalla classe DataMapper,
 * che a sua volta deriva dalla libreria SimpleORM
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
 * MODIFICHE APPORTATE AL CODICE ORIGINALE:
 * - Estrazione (2025): Responsabilità di persistenza estratta da DataMapper in classe @internal separata
 * - Applicazione tipizzazione forte PHP 8.1+
 * - BaseAdapter e ProcessedEntitiesCollection resi parametri opzionali del costruttore con utilizzo dei singleton come default
 * - Cache dinamica: Utilizzo di callable per ormCacheStatus per supportare modifiche runtime
 * - Gestione encryption per proprietà crittografate con initialization vector
 * - Supporto per ReferencedEntity con salvataggio automatico delle collection associate
 * - Integrazione DataType::fromReflection() per binding automatico dei tipi nelle query
 * - Gestione foreign keys e relative collection tramite callback ricorsivo
 */

namespace SismaFramework\Orm\HelperClasses\DataMapper;

use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\Permissions\ReferencedEntityDeletionPermission;
use SismaFramework\Security\Enumerations\AccessControlEntry;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class EntityPersister
{

    private BaseAdapter $adapter;
    private mixed $ormCacheStatusGetter;
    private ProcessedEntitiesCollection $processedEntitiesCollection;

    public function __construct(?BaseAdapter $adapter, callable $ormCacheStatusGetter, ?ProcessedEntitiesCollection $processedEntitiesCollection = null)
    {
        $this->adapter = $adapter ?? BaseAdapter::getDefault();
        $this->ormCacheStatusGetter = $ormCacheStatusGetter;
        $this->processedEntitiesCollection = $processedEntitiesCollection ?? ProcessedEntitiesCollection::getInstance();
    }

    private function isOrmCacheEnabled(): bool
    {
        return ($this->ormCacheStatusGetter)();
    }

    public function insert(BaseEntity $entity, Query $query, callable $saveCallback): bool
    {
        $bindValues = $bindTypes = [];
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        $this->parseValues($entity, $query, $bindValues, $bindTypes, $saveCallback);
        $this->parseForeignKeyIndexes($entity, $query, $bindValues, $bindTypes);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::insert);
        $result = $this->adapter->execute($cmd, $bindValues, $bindTypes);
        if ($result) {
            $entity->setPrimaryKeyAfterSave($this->adapter->lastInsertId());
            $entity->modified = false;
            $this->checkIsReferencedEntity($entity, $saveCallback);
            if ($this->isOrmCacheEnabled()) {
                Cache::setEntity($entity);
            }
        }
        return $result;
    }

    public function update(BaseEntity $entity, Query $query, callable $saveCallback): bool
    {
        $bindValues = $bindTypes = [];
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        $this->parseValues($entity, $query, $bindValues, $bindTypes, $saveCallback);
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
            $this->checkIsReferencedEntity($entity, $saveCallback);
            if ($this->isOrmCacheEnabled()) {
                Cache::setEntity($entity);
            }
        }
        return $result;
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

    public function saveForeignKeys(BaseEntity $entity, callable $saveCallback): bool
    {
        foreach ($entity->foreignKeys as $foreignKey) {
            if ($entity->$foreignKey instanceof BaseEntity) {
                if ($saveCallback($entity->$foreignKey) === false) {
                    return false;
                }
            }
        }
        return true;
    }

    public function saveForeignKeysAndCollections(BaseEntity $entity, callable $saveCallback): bool
    {
        return $this->saveForeignKeys($entity, $saveCallback) && $this->checkIsReferencedEntity($entity, $saveCallback);
    }

    private function parseValues(BaseEntity $entity, Query $query, array &$bindValues, array &$bindTypes, callable $saveCallback): void
    {
        $reflectionClass = new \ReflectionClass($entity);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($this->propertyIsParsable($reflectionProperty, $entity)) {
                $this->parseSingleProperty($reflectionProperty, $entity, $query, $bindValues, $bindTypes, $saveCallback);
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

    private function parseSingleProperty(\ReflectionProperty $reflectionProperty, BaseEntity $entity, Query $query, array &$bindValues, array &$bindTypes, callable $saveCallback): void
    {
        $currentValue = $reflectionProperty->getValue($entity);
        if ($currentValue instanceof BaseEntity) {
            $saveCallback($currentValue);
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

    private function checkIsReferencedEntity(BaseEntity $entity, callable $saveCallback): bool
    {
        if (($entity instanceof ReferencedEntity)) {
            return $this->saveEntityCollection($entity, $saveCallback);
        } else {
            return true;
        }
    }

    private function saveEntityCollection(ReferencedEntity $entity, callable $saveCallback): bool
    {
        foreach ($entity->getCollections() as $foreignKey) {
            foreach ($foreignKey as $collection) {
                foreach ($collection as $entityFromCollection) {
                    if ($saveCallback($entityFromCollection) === false) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}

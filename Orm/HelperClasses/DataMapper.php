<?php

/*
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
 */

namespace SismaFramework\Orm\HelperClasses;

use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Exceptions\DataMapperException;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\Permissions\ReferencedEntityDeletionPermission;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Security\Enumerations\AccessControlEntry;

/**
 * @author Valentino de Lapa
 */
class DataMapper
{

    private bool $ormCacheStatus = \Config\ORM_CACHE;
    private BaseAdapter $adapter;
    private static bool $isActiveTransaction = false;
    private array $processedEntity = [];

    public function __construct(?BaseAdapter $adapter = null)
    {
        $this->adapter = $adapter ?? BaseAdapter::getDefault();
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
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::set, ["variable" => $variable, "value" => $this->adapter->getPlaceholder()]);
        Parser::unparseValue($bindValue);
        $result = $this->adapter->execute($cmd, [$bindValue], [$bindType]);
        return $result;
    }

    public function save(BaseEntity $entity, Query $query = new Query()): bool
    {
        if (in_array($entity, $this->processedEntity, true)) {
            return true;
        } else {
            $this->processedEntity[] = $entity;
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
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        $query->close();
        $columns = $values = $markers = [];
        $this->parseValues($entity, $columns, $values, $markers);
        $this->parseForeignKeyIndexes($entity, $columns, $values, $markers);
        $cmd = $query->getCommandToExecute(Statement::insert, ['columns' => $columns, 'values' => $markers]);
        $result = $this->adapter->execute($cmd, $values);
        if ($result) {
            $entity->{$entity->getPrimaryKeyPropertyName()} = $this->adapter->lastInsertId();
            $entity->modified = false;
            $this->checkIsReferencedEntity($entity);
            if ($this->ormCacheStatus) {
                Cache::setEntity($entity);
            }
        }
        return $result;
    }

    private function parseValues(BaseEntity $entity, array &$columns, array &$values, array &$markers): void
    {
        $reflectionClass = new \ReflectionClass($entity);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (BaseEntity::checkFinalClassReflectionProperty($reflectionProperty) && $reflectionProperty->isInitialized($entity) && ($entity->isPrimaryKey($reflectionProperty->getName()) === false)) {
                $markers[] = $this->adapter->getPlaceholder();
                $columns[] = $this->adapter->escapeColumn($reflectionProperty->getName(), is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class));
                $currentValue = $reflectionProperty->getValue($entity);
                if ($currentValue instanceof BaseEntity) {
                    $this->save($currentValue);
                }
                $parsedValue = Parser::unparseValue($currentValue);
                if ($entity->isEncryptedProperty($reflectionProperty->getName())) {
                    if (empty($entity->{$entity->getInitializationVectorPropertyName()})) {
                        $entity->{$entity->getInitializationVectorPropertyName()} = Encryptor::createInizializationVector();
                    }
                    $parsedValue = Encryptor::encryptString($parsedValue, $entity->{$entity->getInitializationVectorPropertyName()});
                }
                $values[] = $parsedValue;
            }
        }
    }

    private function parseForeignKeyIndexes(BaseEntity $entity, array &$columns, array &$values, array &$markers): void
    {
        foreach ($entity->getForeignKeyIndexes() as $propertyName => $propertyValue) {
            $markers[] = $this->adapter->getPlaceholder();
            $columns[] = $this->adapter->escapeColumn($propertyName, true);
            $values[] = $propertyValue;
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
                $this->processedEntity = [];
            }
        }
    }

    private function update(BaseEntity $entity, Query $query = new Query()): bool
    {
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        $query->setWhere();
        $query->appendCondition($entity->getPrimaryKeyPropertyName(), ComparisonOperator::equal, Placeholder::placeholder);
        $query->close();
        $columns = $values = $markers = [];
        $this->parseValues($entity, $columns, $values, $markers);
        $this->parseForeignKeyIndexes($entity, $columns, $values, $markers);
        $cmd = $query->getCommandToExecute(Statement::update, array('columns' => $columns, 'values' => $markers));
        $values[] = $entity->{$entity->getPrimaryKeyPropertyName()};
        $result = $this->adapter->execute($cmd, $values);
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

    public function updateBatch(array $columns, array $values, Query $query = new Query(), array $bindValues = [], array $bindTypes = []): bool
    {
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::update, ['columns' => $columns, 'values' => $values]);
        Parser::unparseValues($bindValues);
        $result = $this->adapter->execute($cmd, $bindValues, $bindTypes);
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

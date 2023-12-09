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
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Exceptions\DataMapperException;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\Permissions\ReferencedEntityDeletionPermission;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\Security\Enumerations\AccessControlEntry;

/**
 * @author Valentino de Lapa
 */
class DataMapper
{

    private bool $ormCacheStatus = \Config\ORM_CACHE;
    private array $columns = [];
    private array $values = [];
    private array $markers = [];
    private BaseAdapter $adapter;
    protected bool $isActiveTransaction = false;
    private static bool $isFirstExecutedEntity = true;
    private static bool $manualTransactionStarted = false;

    public function __construct(?BaseAdapter $adapter = null)
    {
        $this->adapter = $adapter ?? BaseAdapter::getDefault();
    }

    public function initQuery(string $entityName, Query $query = new Query()): Query
    {
        $query->setTable(NotationManager::convertEntityNameToTableName($entityName));
        return $query;
    }

    public function save(BaseEntity $entity, bool $nestedChangesTracking = true): bool
    {
        if (empty($entity->{$entity->getPrimaryKeyPropertyName()})) {
            return $this->insert($entity);
        } elseif ($entity->modified) {
            return $this->update($entity);
        } else {
            if ($nestedChangesTracking) {
                $this->saveForeignKeys($entity);
                $this->checkIsReferencedEntity($entity);
            }
            return true;
        }
    }

    private function insert(BaseEntity $entity, Query $query = new Query()): bool
    {
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        $this->columns = $this->values = $this->markers = [];
        $this->parseValues($entity);
        $this->parseForeignKeyIndexes($entity);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::insert, array('columns' => $this->columns, 'values' => $this->markers));
        $this->checkStartTransaction();
        $result = $this->adapter->execute($cmd, $this->values);
        if ($result) {
            $entity->{$entity->getPrimaryKeyPropertyName()} = $this->adapter->lastInsertId();
            $entity->modified = false;
            $this->checkIsReferencedEntity($entity);
            $this->checkEndTransaction();
            if ($this->ormCacheStatus) {
                Cache::setEntity($entity);
            }
        }
        return $result;
    }

    private function parseValues(BaseEntity $entity): void
    {
        $entity->propertyNestedChanges = false;
        $reflectionClass = new \ReflectionClass($entity);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (($reflectionProperty->class === get_class($entity)) && ($entity->isPrimaryKey($reflectionProperty->getName()) === false)) {
                $this->markers[] = '?';
                $this->columns[] = $this->adapter->escapeColumn($reflectionProperty->getName(), is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class));
                $parsedValue = Parser::unparseValue($reflectionProperty->getValue($entity), true);
                if ($entity->isEncryptedProperty($reflectionProperty->getName())) {
                    if (empty($entity->{$entity->initializationVectorPropertyName})) {
                        $entity->{$entity->initializationVectorPropertyName} = Encryptor::createInizializationVector();
                    }
                    $parsedValue = Encryptor::encryptString($parsedValue, $entity->{$entity->initializationVectorPropertyName});
                }
                $this->values[] = $parsedValue;
            }
        }
    }

    private function parseForeignKeyIndexes(BaseEntity $entity): void
    {
        foreach ($entity->getForeignKeyIndexes() as $propertyName => $propertyValue) {
            $this->markers[] = '?';
            $this->columns[] = $this->adapter->escapeColumn($propertyName, true);
            $this->values[] = $propertyValue;
        }
    }

    private function checkStartTransaction()
    {
        if (self::$isFirstExecutedEntity && (self::$manualTransactionStarted === false)) {
            $this->adapter->beginTransaction();
            self::$isFirstExecutedEntity = false;
            $this->isActiveTransaction = true;
        }
    }

    private function checkIsReferencedEntity(BaseEntity $entity)
    {
        if (($entity instanceof ReferencedEntity) && $entity->collectionNestedChanges) {
            $entity->collectionNestedChanges = false;
            $this->saveEntityCollection($entity);
        }
    }

    private function saveEntityCollection(BaseEntity $entity): void
    {
        foreach ($entity->getCollections() as $foreignKey) {
            foreach ($foreignKey as $collection) {
                foreach ($collection as $entityFromCollection) {
                    $this->save($entityFromCollection);
                }
            }
        }
    }

    private function checkEndTransaction()
    {
        if ($this->isActiveTransaction && (self::$manualTransactionStarted === false)) {
            $this->adapter->commitTransaction();
            self::$isFirstExecutedEntity = true;
            $this->isActiveTransaction = false;
        }
    }

    public function update(BaseEntity $entity, Query $query = new Query()): bool
    {
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        $this->columns = $this->values = $this->markers = [];
        $this->parseValues($entity);
        $this->parseForeignKeyIndexes($entity);
        $query->setWhere();
        $query->appendCondition($entity->getPrimaryKeyPropertyName(), ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::update, array('columns' => $this->columns, 'values' => $this->markers));
        $this->values[] = $entity->{$entity->getPrimaryKeyPropertyName()};
        $this->checkStartTransaction();
        $result = $this->adapter->execute($cmd, $this->values);
        if ($result) {
            $entity->modified = false;
            $this->checkIsReferencedEntity($entity);
            $this->checkEndTransaction();
            if ($this->ormCacheStatus) {
                Cache::setEntity($entity);
            }
        }
        return $result;
    }

    public function startTransaction(): void
    {
        $this->adapter->beginTransaction();
        self::$manualTransactionStarted = true;
    }

    public function commitTransaction(): void
    {
        $this->adapter->commitTransaction();
        self::$manualTransactionStarted = false;
    }

    private function saveForeignKeys(BaseEntity $entity)
    {
        if ($entity->propertyNestedChanges) {
            $entity->propertyNestedChanges = false;
            foreach ($entity->foreignKeys as $foreignKey) {
                if ($entity->$foreignKey instanceof BaseEntity) {
                    $this->save($entity->$foreignKey);
                }
            }
        }
    }

    public function delete(BaseEntity $entity, Query $query = new Query()): bool
    {
        if ($entity instanceof ReferencedEntity) {
            ReferencedEntityDeletionPermission::isAllowed($entity, AccessControlEntry::allow);
        }
        $query->setTable(NotationManager::convertEntityToTableName($entity));
        if ($entity->getPrimaryKeyPropertyName() == '') {
            return false;
        }
        $query->setWhere();
        $query->appendCondition($entity->getPrimaryKeyPropertyName(), ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::delete);
        $bindValues = [$entity];
        $bindTypes = [DataType::typeEntity];
        Parser::unparseValues($bindValues);
        $result = $this->adapter->execute($cmd, $bindValues, $bindTypes);
        if ($result) {
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
        return $result;
    }

    public function find($entityName, Query $query, array $bindValues = [], array $bindTypes = []): SismaCollection
    {
        $result = $this->getResultSet($entityName, $query, $bindValues, $bindTypes);
        $collection = new SismaCollection($entityName);
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

    private function getResultSet($entityName, Query $query, array $bindValues = [], array $bindTypes = []): ?BaseResultSet
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
        if (!$result) {
            return 0;
        }
        $data = $result->fetch();
        $result->release();
        unset($result);
        if (!$data) {
            return 0;
        }
        return $data->_numrows;
    }

    public function findFirst($entityName, Query $query, array $bindValues = [], array $bindTypes = []): ?BaseEntity
    {
        $query->setOffset(0);
        $query->setLimit(1);
        $result = $this->getResultSet($entityName, $query, $bindValues, $bindTypes);
        switch ($result->numRows()) {
            case 0:
                return null;
            case 1:
                return $result->fetch();
            default:
                throw new DataMapperException();
        }
    }
}

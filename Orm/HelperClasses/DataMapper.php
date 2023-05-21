<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa <valentino.delapa@gmail.com>.
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
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\ProprietaryTypes\SismaCollection;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class DataMapper
{

    private bool $ormCacheStatus = \Config\ORM_CACHE;
    private array $columns = [];
    private array $values = [];
    private array $markers = [];
    private string $entityName;
    private BaseEntity $entity;
    private BaseAdapter $adapter;
    protected bool $isActiveTransaction = false;
    private static bool $isFirstExecutedEntity = true;
    private static bool $manualTransactionStarted = false;

    public function __construct(?BaseAdapter $adapter = null)
    {
        $this->adapter = $adapter ?? BaseAdapter::getDefault();
    }

    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }

    public function initQuery(Query $query = new Query()): Query
    {
        $query->setTable($this->entityName::getTableName());
        return $query;
    }

    public function save(BaseEntity $entity)
    {

        if (empty($entity->{$entity->getPrimaryKeyPropertyName()})) {
            return $this->insert($entity);
            //}else{
        } elseif ($entity->modified) {
            return $this->update($entity);
        } elseif ($entity->nestedChanges) {
            $this->entity = $entity;
            $this->entity->nestedChanges = false;
            $this->saveForeignKeys();
            $this->checkIsReferencedEntity();
            return true;
        } else {
            return true;
        }
    }

    public function update(BaseEntity $entity, Query $query = new Query()): bool
    {
        $this->entity = $entity;
        $query->setTable($this->entity->getEntityTableName());
        $this->columns = $this->values = $this->markers = [];
        $this->parseValues();
        $this->parseForeignKeyIndexes();
        $query->setWhere();
        $query->appendCondition($this->entity->getPrimaryKeyPropertyName(), ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::update, array('columns' => $this->columns, 'values' => $this->markers));
        $this->values[] = $this->entity->{$this->entity->getPrimaryKeyPropertyName()};
        $this->checkStartTransaction();
        $ok = $this->adapter->execute($cmd, $this->values);
        if ($ok) {
            $this->entity->modified = false;
            $this->entity->nestedChanges = false;
            $this->checkIsReferencedEntity();
        }
        $this->checkEndTransaction();
        if ($this->ormCacheStatus) {
            Cache::setEntity($this->entity);
        }
        return $ok;
    }

    private function parseValues(): void
    {
        $reflectionClass = new \ReflectionClass($this->entity);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (($reflectionProperty->class === get_class($this->entity)) && ($this->entity->isPrimaryKey($reflectionProperty->getName()) === false)) {
                $this->markers[] = '?';
                $this->columns[] = $this->adapter->escapeColumn($reflectionProperty->getName(), is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class));
                $parsedValue = Parser::unparseValue($reflectionProperty->getValue($this->entity), true);
                if ($this->entity->isEncryptedProperty($reflectionProperty->getName())) {
                    $this->entity->{$this->entity->initializationVectorPropertyName} = Encryptor::createInizializationVector();
                    $parsedValue = Encryptor::encryptString($parsedValue, $this->entity->{$this->entity->initializationVectorPropertyName});
                }
                $this->values[] = $parsedValue;
            }
        }
    }

    private function parseForeignKeyIndexes(): void
    {
        foreach ($this->entity->getForeignKeyIndexes() as $propertyName => $propertyValue) {
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

    public function startTransaction(): void
    {
        $this->adapter->beginTransaction();
        self::$manualTransactionStarted = true;
    }

    private function checkIsReferencedEntity()
    {
        if ($this->entity instanceof ReferencedEntity) {
            $this->saveEntityCollection();
        }
    }

    private function saveEntityCollection(self $dataMapper = new DataMapper()): void
    {
        foreach ($this->entity->getCollections() as $foreignKey) {
            foreach ($foreignKey as $collection) {
                foreach ($collection as $entity) {
                    $dataMapper->save($entity);
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

    public function commitTransaction(): void
    {
        $this->adapter->commitTransaction();
        self::$manualTransactionStarted = false;
    }

    public function insert(BaseEntity $entity, Query $query = new Query()): bool
    {
        $this->entity = $entity;
        $query->setTable($this->entity->getEntityTableName());
        $this->columns = $this->values = $this->markers = [];
        $this->parseValues();
        $this->parseForeignKeyIndexes();
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::insert, array('columns' => $this->columns, 'values' => $this->markers));
        $this->checkStartTransaction();
        $ok = $this->adapter->execute($cmd, $this->values);
        if ($ok) {
            $this->entity->{$this->entity->getPrimaryKeyPropertyName()} = $this->adapter->lastInsertId();
            $this->checkIsReferencedEntity();
        }
        $this->checkEndTransaction();
        if ($this->ormCacheStatus) {
            Cache::setEntity($this->entity);
        }
        return $ok;
    }

    private function saveForeignKeys(self $dataMapper = new DataMapper())
    {
        foreach ($this->entity->foreignKeys as $foreignKey) {
            if ($this->entity->$foreignKey instanceof BaseEntity) {
                $dataMapper->save($this->entity->$foreignKey);
            }
        }
    }

    public function delete(BaseEntity $entity, Query $query = new Query()): bool
    {
        $this->entity = $entity;
        $query->setTable($this->entity->getEntityTableName());
        if ($this->entity->getPrimaryKeyPropertyName() == '') {
            return false;
        }
        $query->setWhere();
        $query->appendCondition($this->entity->getPrimaryKeyPropertyName(), ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::delete);
        $bindValues = [$this->entity];
        $bindTypes = [DataType::typeEntity];
        Parser::unparseValues($bindValues);
        $ok = $this->adapter->execute($cmd, $bindValues, $bindTypes);
        if ($ok) {
            $this->entity->unsetPrimaryKey();
        }
        return $ok;
    }

    public function deleteBatch(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): bool
    {
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::delete);
        Parser::unparseValues($bindValues);
        $ok = $this->adapter->execute($cmd, $bindValues, $bindTypes);
        return $ok;
    }

    public function find(Query $query, array $bindValues = [], array $bindTypes = []): SismaCollection
    {
        $result = $this->getResultSet($query, $bindValues, $bindTypes);
        $collection = new SismaCollection($this->entityName);
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

    private function getResultSet(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): BaseResultSet
    {
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::select);
        Parser::unparseValues($bindValues);
        $result = $this->adapter->select($cmd, $bindValues, $bindTypes);
        if (!$result) {
            return null;
        }
        $result->setReturnType($this->entityName);
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

    public function findFirst(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): ?BaseEntity
    {
        $query->setOffset(0);
        $query->setLimit(1);
        $list = $this->getResultSet($query, $bindValues, $bindTypes);
        if (!$list) {
            return null;
        }
        $ret = null;
        foreach ($list as $x) {
            $ret = $x;
            break;
        }
        return $ret;
    }

}

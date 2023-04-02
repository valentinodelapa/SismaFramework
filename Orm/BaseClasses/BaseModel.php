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
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\Query;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseModel
{

    protected ?BaseAdapter $adapter = null;
    protected BaseEntity $entity;
    protected string $entityName;

    public function __construct(?BaseAdapter $adapter = null)
    {
        if ($adapter instanceof BaseAdapter) {
            $this->adapter = $adapter;
        } else {
            $this->adapter = BaseAdapter::create(\Config\DATABASE_ADAPTER_TYPE, [
                        'database' => \Config\DATABASE_NAME,
                        'hostname' => \Config\DATABASE_HOST,
                        'password' => \Config\DATABASE_PASSWORD,
                        'port' => \Config\DATABASE_PORT,
                        'username' => \Config\DATABASE_USERNAME,
            ]);
        }
        $this->implementEmbeddedEntity();
        $this->entityName = get_class($this->entity);
    }

    abstract public function implementEmbeddedEntity(): void;

    public function getEmbeddedEntity(): BaseEntity
    {
        return $this->entity;
    }

    public function setEntityByData(array $data): void
    {
        $classProperty = get_class_vars(get_class($this->entity));
        $orderedData = array_intersect_key($data, $classProperty);
        foreach ($orderedData as $key => $value) {
            $this->entity->$key = $value;
        }
    }

    public function countEntityCollection(?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $bindValues = $bindTypes = [];
        if ($searchKey !== null) {
            $query->setWhere();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->entityName::getCount($query, $bindValues, $bindTypes);
    }

    protected function initQuery(): Query
    {
        $query = $this->entityName::initQuery($this->adapter);
        return $query;
    }

    public function getEntityCollection(?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $bindValues = $bindTypes = [];
        if ($searchKey !== null) {
            $query->setWhere();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->setOrderBy($order);
        if ($offset !== null) {
            $query->setOffset($offset);
        }
        if ($limit != null) {
            $query->setLimit($limit);
        }
        $query->close();
        return $this->getMultipleRowResult($query, $bindValues, $bindTypes);
    }

    abstract protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void;

    protected function getMultipleRowResult(Query $query, array $bindValues = [], array $bindTypes = []): SismaCollection
    {
        $result = $this->entityName::find($query, $bindValues, $bindTypes);
        $collection = new SismaCollection($this->entityName);
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

    public function getOtherEntityCollection(BaseEntity $excludedEntity): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::notEqualTwo, Keyword::placeholder);
        $bindValues = [
            $excludedEntity,
        ];
        $bindTypes = [
            DataType::typeEntity,
        ];
        $query->close();
        return $this->getMultipleRowResult($query, $bindValues, $bindTypes);
    }

    public function getEntityById(int $id): ?BaseEntity
    {
        if (\Config\ORM_CACHE && Cache::checkEntityPresenceInCache($this->entityName, $id)) {
            return Cache::getEntityById($this->entityName, $id);
        } else {
            $query = $this->initQuery();
            $query->setWhere();
            $query->appendCondition('id', ComparisonOperator::equal, Keyword::placeholder);
            $query->close();
            $entity = $this->entityName::findFirst($query, [
                        $id,
                            ], [
                        DataType::typeInteger,
            ]);
            if (\Config\ORM_CACHE && ($entity instanceof $this->entityName)) {
                Cache::setEntity($entity);
            }
            return $entity;
        }
    }

    public function saveEntityByData(array $data): void
    {
        $this->setEntityByData($data);
        if (!$this->entity->save()) {
            Throw new ModelException();
        }
    }

    public function deleteEntityById(int $id): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        return $this->entityName::deleteBatch($query, [
                    $id,
                        ], [
                    DataType::typeInteger,
        ]);
    }

    public function __destruct()
    {
        $this->adapter = null;
    }

}

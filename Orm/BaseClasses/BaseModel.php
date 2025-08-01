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
 */

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @author Valentino de Lapa
 */
abstract class BaseModel
{

    protected DataMapper $dataMapper;
    protected Config $config;
    protected readonly string $entityName;

    public function __construct(DataMapper $dataMapper = new DataMapper(), ?Config $config = null)
    {
        $this->dataMapper = $dataMapper;
        $this->config = $config ?? Config::getInstance();
        $this->entityName = $this->getEntityName();
        $this->checkEntityName();
    }

    private function checkEntityName()
    {
        if (is_subclass_of($this->entityName, BaseEntity::class) === false) {
            throw new ModelException();
        }
    }

    abstract protected function getEntityName(): string;

    public function countEntityCollection(?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $bindValues = $bindTypes = [];
        if ($searchKey !== null) {
            $query->setWhere();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    protected function initQuery(): Query
    {
        $query = $this->dataMapper->initQuery($this->entityName);
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
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    abstract protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void;

    public function getOtherEntityCollection(BaseEntity $excludedEntity): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::notEqual, Placeholder::placeholder);
        $bindValues = [
            $excludedEntity,
        ];
        $bindTypes = [
            DataType::typeEntity,
        ];
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    public function convertArrayIntoEntityCollection(array $entitiesId): SismaCollection
    {
        $collection = new SismaCollection($this->entityName);
        foreach ($entitiesId as $entityId) {
            $collection->append($this->getEntityById($entityId));
        }
        return $collection;
    }

    public function getEntityById(int $id): ?BaseEntity
    {
        if ($this->config->ormCache && Cache::checkEntityPresenceInCache($this->entityName, $id)) {
            return Cache::getEntityById($this->entityName, $id);
        } else {
            $query = $this->initQuery();
            $query->setWhere();
            $query->appendCondition('id', ComparisonOperator::equal, Placeholder::placeholder);
            $query->close();
            $entity = $this->dataMapper->findFirst($this->entityName, $query, [
                $id,
                    ], [
                DataType::typeInteger,
            ]);
            return $entity;
        }
    }

    public function deleteEntityById(int $id): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::equal, Placeholder::placeholder);
        $query->close();
        return $this->dataMapper->deleteBatch($query, [
                    $id,
                        ], [
                    DataType::typeInteger,
        ]);
    }

    public function findSingleColumn(string $entityName, string $columnName, bool $isForeignKey): ?BaseEntity
    {
        $query = $this->initQuery();
        $query->setColumn($columnName . (($isForeignKey) ? 'Id' : null))
                ->setLimit(1);
        $this->dataMapper->setOrmCacheStatus(false);
        $result = $this->dataMapper->findFirst($entityName, $query);
        $this->dataMapper->setOrmCacheStatus($this->config->ormCache);
        return $result;
    }
}

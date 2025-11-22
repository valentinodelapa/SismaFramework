<?php

/*
 * Questo file è ispirato concettualmente alla classe Model della libreria SimpleORM
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
 * CAMBIAMENTI ARCHITETTURALI RISPETTO ALLA CLASSE `MODEL` DI SIMPLEORM:
 * - Completa riscrittura seguendo il pattern Data Mapper invece di Active Record.
 * - La classe Model di SimpleORM combinava rappresentazione dati e logica di persistenza (Active Record).
 * - BaseModel è ora un repository/service layer separato che delega la persistenza a DataMapper.
 * - I metodi find(), findFirst(), save(), insert(), delete() di SimpleORM Model sono stati completamente ridisegnati.
 * - Introduzione di getEntityCollection(), getEntityById(), deleteEntityById() come astrazione su DataMapper.
 * - Rimozione completa della logica di persistenza diretta: ora delegata a DataMapper.
 * - Introduzione di dependency injection per DataMapper e Config invece di singleton.
 * - Aggiunta di metodo astratto appendSearchCondition() per logica di ricerca specifica per entità.
 * - Integrazione con sistema di cache ORM del framework.
 * - Supporto per SismaCollection tipizzata invece di array.
 */

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Orm\BaseClasses\BaseEntity;
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

    public function findSingleColumn(string $propertyName, string $columnName, bool $isForeignKey): ?BaseEntity
    {
        $query = $this->initQuery();
        $query->setColumn($columnName . (($isForeignKey) ? 'Id' : null))
                ->setLimit(1);
        $this->dataMapper->setOrmCacheStatus(false);
        $result = $this->dataMapper->findFirst($propertyName, $query);
        $this->dataMapper->setOrmCacheStatus($this->config->ormCache);
        return $result;
    }

    public function __call($name, $arguments): SismaCollection|int|bool
    {
        $nameParts = explode('By', $name);
        $sismaCollectionParts = array_filter(preg_split('/(?=[A-Z])/', $nameParts[0]));
        $action = array_shift($sismaCollectionParts);
        $properties = [];
        $propertyNames = array_map('lcfirst', explode('And', $nameParts[1]));
        $this->buildPropertiesArray($propertyNames, $arguments, $properties);
        switch ($action) {
            case 'count':
                return $this->countEntityCollectionByProperties($properties, ...$arguments);
            case 'get':
                return $this->getEntityCollectionByProperties($properties, ...$arguments);
            case 'delete':
                return $this->deleteEntityCollectionByProperties($properties, ...$arguments);
            default:
                throw new ModelException($name);
        }
    }

    protected function buildPropertiesArray(array $propertyNames, array &$arguments, array &$properties): void
    {
        foreach ($propertyNames as $propertyName) {
            $propertyValue = array_shift($arguments);
            $reflectionProperty = new \ReflectionProperty($this->entityName, $propertyName);
            $propertyType = $reflectionProperty->getType();
            if (($propertyType->allowsNull() && ($propertyValue === null)) || ($this->isVariableOfType($propertyValue, $propertyType))) {
                $properties[$propertyName] = $propertyValue;
            } else {
                throw new InvalidArgumentException($propertyName);
            }
        }
    }

    protected function isVariableOfType(mixed $propertyValue, \ReflectionNamedType $reflectionType): bool
    {
        $typeName = $reflectionType->getName();
        if ($reflectionType->isBuiltin()) {
            return match ($typeName) {
                'bool' => is_bool($propertyValue),
                'int' => is_int($propertyValue),
                'float' => is_float($propertyValue),
                'string' => is_string($propertyValue),
                default => false,
            };
        } elseif (is_object($propertyValue)) {
            return $propertyValue instanceof $typeName;
        } else {
            return false;
        }
    }

    protected function countEntityCollectionByProperties(array $properties, ?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $properties, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    protected function buildPropertiesConditions(Query $query, array $properties, array &$bindValues, array &$bindTypes): void
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if ($propertyValue === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '');
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Placeholder::placeholder);
                $reflectionNamedType = new \ReflectionProperty($this->entityName, $propertyName);
                $bindValues[] = $propertyValue;
                $bindTypes[] = DataType::fromReflection($reflectionNamedType->getType(), $propertyValue);
            }
            if ($propertyName !== array_key_last($properties)) {
                $query->appendAnd();
            }
        }
    }

    public function getEntityCollectionByProperties(array $properties, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $properties, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
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

    public function deleteEntityCollectionByProperties(array $properties, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $properties, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->deleteBatch($query, $bindValues, $bindTypes);
    }
}

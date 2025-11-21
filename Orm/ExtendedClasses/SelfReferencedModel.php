<?php

/*
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

namespace SismaFramework\Orm\ExtendedClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\ExtendedClasses\DependentModel;

/**
 *
 * @author Valentino de Lapa
 */
abstract class SelfReferencedModel extends DependentModel
{

    private string $parentForeignKey;

    public function __construct(DataMapper $dataMapper = new DataMapper(), ?Config $config = null)
    {
        parent::__construct($dataMapper, $config);
        $entityNameParts = explode("\\", $this->entityName);
        $this->parentForeignKey = $this->config->parentPrefixPropertyName . end($entityNameParts);
    }

    public function __call($name, $arguments): SismaCollection|int|bool
    {
        $nameParts = explode('By', $name);
        if (str_starts_with($nameParts[1], 'ParentAnd')) {
            $sismaCollectionParts = array_filter(preg_split('/(?=[A-Z])/', $nameParts[0]));
            $action = array_shift($sismaCollectionParts);
            $properties = [];
            $parentEntity = array_shift($arguments);
            $propertyNames = array_map('lcfirst', explode('And', $nameParts[1]));
            $this->buildPropertiesArray(array_slice($propertyNames, 1), $arguments, $properties);
            switch ($action) {
                case 'count':
                    return $this->countEntityCollectionByParentAndProperties($properties, $parentEntity, ...$arguments);
                case 'get':
                    return $this->getEntityCollectionByParentAndProperties($properties, $parentEntity, ...$arguments);
                case 'delete':
                    return $this->deleteEntityCollectionByParentAndProperties($properties, $parentEntity, ...$arguments);
                default:
                    throw new ModelException($name);
            }
        } else {
            return parent::__call($name, $arguments);
        }
    }

    public function countEntityCollectionByParent(?BaseEntity $parentEntity = null, ?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    /**
     * @deprecated dalla versione 11.0.0, verrà rimosso. Utilizzare la metaprogrammazione tramite __call()
     */
    public function countEntityCollectionByParentAndEntity(array $referencedEntities, ?BaseEntity $parentEntity = null, ?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        $this->buildPropertyConditions($query, $referencedEntities, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    private function countEntityCollectionByParentAndProperties(array $properties, ?BaseEntity $parentEntity = null, ?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        $this->buildPropertyConditions($query, $properties, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    public function getEntityCollectionByParent(?BaseEntity $parentEntity = null, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
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

    /**
     * @deprecated dalla versione 11.0.0, verrà rimosso. Utilizzare la metaprogrammazione tramite __call()
     */
    public function getEntityCollectionByParentAndEntity(array $referencedEntities, ?BaseEntity $parentEntity = null, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        $this->buildPropertyConditions($query, $referencedEntities, $bindValues, $bindTypes);
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

    private function getEntityCollectionByParentAndProperties(array $properties, ?BaseEntity $parentEntity = null, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        $this->buildPropertyConditions($query, $properties, $bindValues, $bindTypes);
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

    public function getOtherEntityCollectionByParent(BaseEntity $excludedEntity, ?BaseEntity $parentEntity = null, ?array $order = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::notEqual, Placeholder::placeholder, true);
        $bindValues = [
            $excludedEntity,
        ];
        $bindTypes = [
            DataType::typeEntity,
        ];
        $query->appendAnd();
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->setOrderBy($order);
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    public function getEntityTree(?BaseEntity $parentEntity = null, ?array $order = null): SismaCollection
    {
        $entityTree = $this->getEntityCollectionByParent($parentEntity, null, $order);
        foreach ($entityTree as $key => $entity) {
            $entityTree[$key]->setEntityCollection($this->config->sonCollectionPropertyName, $this->getEntityTree($entity, $order));
        }
        return $entityTree;
    }

    public function deleteEntityCollectionByParent(?BaseEntity $parentEntity = null, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->deleteBatch($query, $bindValues, $bindTypes);
    }

    /**
     * @deprecated dalla versione 11.0.0, verrà rimosso. Utilizzare la metaprogrammazione tramite __call()
     */
    public function deleteEntityCollectionByParentAndEntity(array $referencedEntities, ?BaseEntity $parentEntity = null, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        $this->buildPropertyConditions($query, $referencedEntities, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->deleteBatch($query, $bindValues, $bindTypes);
    }

    private function deleteEntityCollectionByParentAndProperties(array $properties, ?BaseEntity $parentEntity = null, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->parentForeignKey, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        $this->buildPropertyConditions($query, $properties, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->deleteBatch($query, $bindValues, $bindTypes);
    }

    public function deleteEntityTree(BaseEntity $entityTree): void
    {
        foreach ($entityTree->{$this->config->sonCollectionGetterMethod}() as $entity) {
            $this->deleteEntityTree($entity);
        }
        $this->dataMapper->delete($entityTree);
    }
}

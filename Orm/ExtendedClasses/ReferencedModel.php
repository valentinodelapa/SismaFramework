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

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;

/**
 *
 * @author Valentino de Lapa
 */
abstract class ReferencedModel extends BaseModel
{

    public function __call($name, $arguments): SismaCollection|int|bool
    {
        $nameParts = explode('By', $name);
        $sismaCollectionParts = array_filter(preg_split('/(?=[A-Z])/', $nameParts[0]));
        $action = array_shift($sismaCollectionParts);
        $referencedEntities = [];
        $entityNames = explode('And', $nameParts[1]);
        $this->buildReferencedEntitiesArray($entityNames, $arguments, $referencedEntities);
        switch ($action) {
            case 'count':
                return $this->countEntityCollectionByEntity($referencedEntities, ...$arguments);
            case 'get':
                return $this->getEntityCollectionByEntity($referencedEntities, ...$arguments);
            case 'delete':
                return $this->deleteEntityCollectionByEntity($referencedEntities, ...$arguments);
            default:
                throw new ModelException('Metodo non trovato');
        }
    }

    protected function buildReferencedEntitiesArray(array $entityNames, array &$arguments, array &$referencedEntities): void
    {
        foreach ($entityNames as $entityName) {
            $entity = array_shift($arguments);
            $reflectionProperty = new \ReflectionProperty($this->entityName, lcfirst($entityName));
            $fullEntityName = $reflectionProperty->getType()->getName();
            if (($entity instanceof $fullEntityName) || ($entity === null)) {
                $entityNameParts = array_filter(preg_split('/(?=[A-Z])/', $entityName));
                $propertyName = strtolower(implode('_', $entityNameParts));
                $referencedEntities[$propertyName] = $entity;
            } else {
                throw new InvalidArgumentException();
            }
        }
    }

    public function countEntityCollectionByEntity(array $referencedEntities, ?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        foreach ($referencedEntities as $propertyName => $baseEntity) {
            if ($baseEntity === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
                $bindValues[] = $baseEntity;
                $bindTypes[] = DataType::typeEntity;
            }
            if ($propertyName !== array_key_last($referencedEntities)) {
                $query->appendAnd();
            }
        }
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    public function getEntityCollectionByEntity(array $referencedEntities, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        foreach ($referencedEntities as $propertyName => $baseEntity) {
            if ($baseEntity === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
                $bindValues[] = $baseEntity;
                $bindTypes[] = DataType::typeEntity;
            }
            if ($propertyName !== array_key_last($referencedEntities)) {
                $query->appendAnd();
            }
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
        return $this->dataMapper->find($query, $bindValues, $bindTypes);
    }

    public function deleteEntityCollectionByEntity(array $referencedEntities, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        foreach ($referencedEntities as $propertyName => $baseEntity) {
            if ($baseEntity === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
                $bindValues[] = $baseEntity;
                $bindTypes[] = DataType::typeEntity;
            }
            if ($propertyName !== array_key_last($referencedEntities)) {
                $query->appendAnd();
            }
        }
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->deleteBatch($query, $bindValues, $bindTypes);
    }

    public function getOtherEntityCollectionByEntity(BaseEntity $excludedEntity, string $propertyName, BaseEntity $baseEntity): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::notEqualTwo, Keyword::placeholder, true);
        $bindValues = [
            $excludedEntity,
        ];
        $bindTypes = [
            DataType::typeEntity,
        ];
        $query->appendAnd();
        if ($baseEntity === null) {
            $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues = [$baseEntity];
            $bindTypes = [DataType::typeEntity];
        }
        $query->close();
        return $this->dataMapper->find($query, $bindValues, $bindTypes);
    }

}

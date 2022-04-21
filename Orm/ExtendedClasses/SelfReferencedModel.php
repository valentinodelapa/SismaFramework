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

namespace SismaFramework\Orm\ExtendedClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\ReferencedModel;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class SelfReferencedModel extends ReferencedModel
{

    const SISMA_COLLECTION_PROPERTY_NAME = 'sonCollection';
    const SISMA_COLLECTION_GETTER_METHOD = 'getSonCollection';

    public function __call($name, $arguments): SismaCollection|int|bool
    {
        $nameParts = explode('By', $name);
        if (str_contains($nameParts[1], 'ParentAnd')) {
            $sismaCollectionParts = array_filter(preg_split('/(?=[A-Z])/', $nameParts[0]));
            $action = array_shift($sismaCollectionParts);
            $referencedEntities = [];
            $parentEntity = array_shift($arguments);
            $entityNames = explode('And', $nameParts[1]);
            $this->buildReferencedEntitiesArray(array_slice($entityNames, 1), $arguments, $referencedEntities);
            switch ($action) {
                case 'count':
                    return $this->countEntityCollectionByParentAndEntity($referencedEntities, $parentEntity, ...$arguments);
                case 'get':
                    return $this->getEntityCollectionByParentAndEntity($referencedEntities, $parentEntity, ...$arguments);
                case 'delete':
                    return $this->deleteEntityCollectionByParentAndEntity($referencedEntities, $parentEntity, ...$arguments);
                default:
                    throw new ModelException('Metodo non trovato');
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
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->entityName::getCount($query, $bindValues, $bindTypes);
    }

    public function countEntityCollectionByParentAndEntity(array $referencedEntities, ?BaseEntity $parentEntity = null, ?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        foreach ($referencedEntities as $propertyName => $baseEntity) {
            if ($baseEntity === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
                $bindValues[] = $baseEntity;
                $bindTypes[] = DataType::typeEntity;
            }
            if($propertyName !== array_key_last($referencedEntities)){
                $query->appendAnd();
            }
        }
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->entityName::getCount($query, $bindValues, $bindTypes);
    }

    public function getEntityCollectionByParent(?BaseEntity $parentEntity = null, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
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
        return $this->getMultipleRowResult($query, $bindValues, $bindTypes);
    }

    public function getEntityCollectionByParentAndEntity(array $referencedEntities, ?BaseEntity $parentEntity = null, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        foreach ($referencedEntities as $propertyName => $baseEntity) {
            if ($baseEntity === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
                $bindValues[] = $baseEntity;
                $bindTypes[] = DataType::typeEntity;
            }
            if($propertyName !== array_key_last($referencedEntities)){
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
        return $this->getMultipleRowResult($query, $bindValues, $bindTypes);
    }

    public function getOtherEntityCollectionByParent(BaseEntity $excludedEntity, ?BaseEntity $parentEntity = null, ?array $order = null): SismaCollection
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
        if ($parentEntity === null) {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->setOrderBy($order);
        $query->close();
        return $this->getMultipleRowResult($query, $bindValues, $bindTypes);
    }

    public function getEntityTree(?BaseEntity $parentEntity = null, array $order = null): SismaCollection
    {
        $entityTree = $this->getEntityCollectionByParent($parentEntity, null, $order);
        foreach ($entityTree as $key => $entity) {
            $entityTree[$key]->setEntityCollection(self::SISMA_COLLECTION_PROPERTY_NAME, $this->getEntityTree($entity, $order));
        }
        return $entityTree;
    }

    public function deleteEntityCollectionByParent(BaseEntity $parentEntity = null, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->entityName::deleteBatch($query, $bindValues, $bindTypes);
    }

    public function deleteEntityCollectionByParentAndEntity(array $referencedEntities, ?BaseEntity $parentEntity = null, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($parentEntity === null) {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->entity->getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->appendAnd();
        foreach ($referencedEntities as $propertyName => $baseEntity) {
            if ($baseEntity === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
                $bindValues[] = $baseEntity;
                $bindTypes[] = DataType::typeEntity;
            }
            if($propertyName !== array_key_last($referencedEntities)){
                $query->appendAnd();
            }
        }
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->entityName::deleteBatch($query, $bindValues, $bindTypes);
    }

    public function deleteEntityTree(BaseEntity $entityTree): void
    {
        foreach ($entityTree->self::SISMA_COLLECTION_GETTER_METHOD() as $entity) {
            $this->deleteEntityTree($entity);
        }
        $entityTree->delete();
    }

}

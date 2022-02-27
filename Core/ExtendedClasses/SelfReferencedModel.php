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

namespace SismaFramework\Core\ExtendedClasses;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\ExtendedClasses\ReferencedModel;
use SismaFramework\ORM\Enumerations\Keyword;
use SismaFramework\ORM\Enumerations\ComparisonOperator;
use SismaFramework\ORM\Enumerations\DataType;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class SelfReferencedModel extends ReferencedModel
{
    const SISMA_COLLECTION_PROPERTY_NAME = 'sonCollection';
    const SISMA_COLLECTION_GETTER_METHOD = 'getSonCollection';

    public function countEntityCollectionByParent(?BaseEntity $parentEntity = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        if ($parentEntity === null) {
            $query->appendCondition($this->entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
            $bindValues = [];
            $bindTypes = [];
        } else {
            $query->appendCondition($this->entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues = [$parentEntity];
            $bindTypes = [DataType::typeEntity];
        }
        $query->close();
        return $this->entityName::getCount($query, $bindValues, $bindTypes);
    }

    public function getEntityCollectionByParent(?BaseEntity $parentEntity = null, ?array $order = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        if ($parentEntity === null) {
            $query->appendCondition($this->entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
            $bindValues = [];
            $bindTypes = [];
        } else {
            $query->appendCondition($this->entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues = [$parentEntity];
            $bindTypes = [DataType::typeEntity];
        }
        $query->setOrderBy($order);
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
            $query->appendCondition($this->entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($this->entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $this->entityName::FOREIGN_KEY_NAME), ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues[] = $parentEntity;
            $bindTypes[] = DataType::typeEntity;
        }
        $query->setOrderBy($order);
        $query->close();
        return $this->getMultipleRowResult($query, $bindValues, $bindTypes);
    }

    public function getEntityTree(?BaseEntity $parentEntity = null, array $order = null): SismaCollection
    {
        $entityTree = $this->getEntityCollectionByParent($parentEntity, $order);
        foreach ($entityTree as $key => $entity) {
            $entityTree[$key]->setSismaCollection(self::SISMA_COLLECTION_PROPERTY_NAME, $this->getEntityTree($entity, $order));
        }
        return $entityTree;
    }
    
    public function deleteEntityTree(BaseEntity $entityTree):void
    {
        foreach ($entityTree->self::SISMA_COLLECTION_GETTER_METHOD() as $entity) {
            $this->deleteEntityTree($entity);
        }
        $entityTree->delete();
    }

}

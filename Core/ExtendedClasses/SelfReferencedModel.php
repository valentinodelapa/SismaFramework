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
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmKeyword;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmOperator;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmType;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class SelfReferencedModel extends ReferencedModel
{
    const SISMA_COLLECTION_PROPERTY_NAME = 'sonCollection';
    const SISMA_COLLECTION_GETTER_METHOD = 'getSonCollection';

    public function getEntityCollectionByParent(?BaseEntity $parentEntity = null, ?array $order = null): SismaCollection
    {
        $entityName = get_class($this->entity);
        $query = $entityName::initQuery();
        $query->setWhere();
        if ($parentEntity === null) {
            $query->appendCondition($entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $entityName::FOREIGN_KEY_NAME), OrmOperator::isNull, '', true);
            $bindValues = [];
            $bindTypes = [];
        } else {
            $query->appendCondition($entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $entityName::FOREIGN_KEY_NAME), OrmOperator::equal, OrmKeyword::placeholder, true);
            $bindValues = [$parentEntity];
            $bindTypes = [OrmType::typeEntity];
        }
        $query->setOrderBy($order);
        $query->close();
        $result = $entityName::find($query, $bindValues, $bindTypes);
        $collection = new SismaCollection;
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
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

    public function getOtherEntityCollection(BaseEntity $excludedEntity): SismaCollection
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->setWhere();
        $query->appendCondition('parent_card', OrmOperator::equal, OrmKeyword::placeholder, true);
        $bindValues = [
            $excludedEntity,
        ];
        $bindTypes = [
            OrmType::typeEntity,
        ];
        $query->close();
        $result = $class::find($query, $bindValues, $bindTypes);
        $collection = new SismaCollection;
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

}

<?php

namespace SismaFramework\Core\ExtendedClasses;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\ExtendedClasses\ReferencedModel;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmKeyword;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmOperator;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmType;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;

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

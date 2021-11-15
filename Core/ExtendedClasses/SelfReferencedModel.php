<?php

namespace Sisma\Core\ExtendedClasses;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\ExtendedClasses\ReferencedModel;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmKeyword;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmOperator;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmType;
use Sisma\Core\ProprietaryTypes\SismaCollection;

abstract class SelfReferencedModel extends ReferencedModel
{
    const SISMA_COLLECTION_PROPERTY_NAME = 'sons';
    const SISMA_COLLECTION_GETTER_METHOD = 'getSons';

    public function getEntityCollectionByParent(?BaseEntity $parentEntity = null, ?array $order = null): SismaCollection
    {
        $entityName = get_class($this->entity);
        $query = $entityName::initQuery();
        $query->setWhere();
        if ($parentEntity === null) {
            $query->appendCondition($entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $entityName::FOREIGN_KEY_NAME), OrmOperator::IS_NULL(), '', true);
            $bindValues = [];
            $bindTypes = [];
        } else {
            $query->appendCondition($entityName::getCollectionDataInformation(self::SISMA_COLLECTION_PROPERTY_NAME, $entityName::FOREIGN_KEY_NAME), OrmOperator::EQUAL(), OrmKeyword::PLACEHOLDER(), true);
            $bindValues = [$parentEntity];
            $bindTypes = [OrmType::ENTITY()];
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

}

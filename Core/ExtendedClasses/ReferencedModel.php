<?php

namespace Sisma\Core\ExtendedClasses;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\BaseClasses\BaseModel;
use Sisma\Core\ProprietaryTypes\SismaCollection;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmKeyword;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmOperator;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmType;

abstract class ReferencedModel extends BaseModel
{

    public function __call($name, $arguments): SismaCollection
    {
        $nameParts = explode('By', substr($name, 3));
        $entityNameParts = array_filter(preg_split('/(?=[A-Z])/', $nameParts[1]));
        $entityName = strtolower(implode('_', $entityNameParts));
        return $this->getSismaCollectionByEntity($entityName, $arguments[0]);
    }
    
    public function getSismaCollectionByEntity(string $propertyName, BaseEntity $baseEntity): SismaCollection
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->setWhere();
        $query->appendCondition($propertyName, OrmOperator::EQUAL(), OrmKeyword::PLACEHOLDER(), true);
        $bindValues = [$baseEntity];
        $bindTypes = [OrmType::ENTITY()];
        $query->close();
        $result = $class::find($query, $bindValues, $bindTypes);
        $collection = new SismaCollection;
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

}

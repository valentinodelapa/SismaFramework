<?php

namespace SismaFramework\Core\ExtendedClasses;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\BaseClasses\BaseModel;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmKeyword;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmOperator;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmType;

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
        $query->appendCondition($propertyName, OrmOperator::equal, OrmKeyword::placeholder, true);
        $bindValues = [$baseEntity];
        $bindTypes = [OrmType::typeEntity];
        $query->close();
        $result = $class::find($query, $bindValues, $bindTypes);
        $collection = new SismaCollection;
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

}

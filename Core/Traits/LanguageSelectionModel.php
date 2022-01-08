<?php

namespace SismaFramework\Core\Traits;;

use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmKeyword;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmOperator;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmType;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;

trait LanguageSelectionModel
{

    public function getEntityCollectionByLanguage(?Language $language = null): SismaCollection
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($language !== null){
            $query->appendCondition('language', OrmOperator::equal, OrmKeyword::placeholder);
            $bindValues = [$language];
            $bindTypes = [OrmType::typeEnumeration];
        }
        $query->close();
        $result = $class::find($query, $bindValues, $bindTypes);
        $collection = new SismaCollection;
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

}

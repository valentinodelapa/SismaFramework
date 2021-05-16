<?php

namespace Sisma\Core\ExtendedClasses;

abstract class MultipleSelfReferencedEnumerator extends DataEnumerator
{

    public function getAssignableEnumerators(string $propertyName): ?array
    {
        $additionalDataArray = $this->setAdditionalData();
        if ($this->hasAdditionalData($propertyName)) {
            return $this->getAdditionalData($propertyName);
        } else {
            return null;
        }
    }

    public static function getSubordinateChoiceFromEnum(string $propertyName, ?self $parentEnum = null, $additionalDataName = 'name'): array
    {
        $className = get_called_class();
        if ($parentEnum instanceof self){
            $propertyData = $parentEnum->getAdditionalData($propertyName);
        }else{
            $propertyData = self::toArray();
        }
        $choice = [];
        foreach ($propertyData as $value) {
            $enumObject = new $className($value);
            $choice[$enumObject->getAdditionalData($additionalDataName)] = $value;
        }
        return $choice;
    }

}

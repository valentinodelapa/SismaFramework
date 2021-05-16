<?php

namespace Sisma\Core\ExtendedClasses;

abstract class SelfReferencedEnumerator extends DataEnumerator
{

    public function getChildEnums(?SelfReferencedEnumerator $parentEnum = null): array
    {
        $enumsArray = [];
        $additionalDataArray = $this->setAdditionalData();
        foreach ($additionalDataArray as $key => $value) {
            if ($value['parent'] == $parentEnum) {
                $enumsArray[] = $key;
            }
        }
        return $enumsArray;
    }

    public static function getSubordinateChoiceFromEnum(?self $parentEnum = null, string $propertyName= 'parent', $additionalDataName = 'name'): array
    {
        $className = get_called_class();
        $enumArray = self::toArray();
        foreach ($enumArray as $value) {
            $enumObject = new $className($value);
            if ($enumObject->getAdditionalData($propertyName) == $parentEnum) {
                $choice[$enumObject->getAdditionalData($additionalDataName)] = $value;
            }
        }
        return $choice;
    }

}

<?php

namespace SismaFramework\Core\Traits;

trait ReferencedEnumeration
{

    use \SismaFramework\Core\Traits\DataEnumeration;
    
    public function getEnumerationsFromParent(\UnitEnum $parentEnumeration = null, mixed ...$offsets): array
    {
        $enumerations = [];
        foreach (self::cases() as $case) {
            $additionalDataField = self::$case->getAdditionalDataField(...$offsets);
            if ($additionalDataField == self) {
                $enumerations[] = $case;
            }
        }
        return $enumerations;
    }
    
    public static function getChoiceFromParent(\UnitEnum $parentEnumeration = null, int|string|\UnitEnum $labelOffset = 'name', mixed ...$offsets): array
    {
        $choice = [];
        foreach ($this->getChildEnumerations($parentEnumeration, ...$offsets) as $case) {
            $choice[$case->getAdditionalDataField($labelOffset)] = $case->value;
        }
        return $choice;
    }
}

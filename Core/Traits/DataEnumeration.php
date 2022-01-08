<?php

namespace SismaFramework\Core\Traits;

use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\Exceptions\EnumerationException;

trait DataEnumeration
{

    abstract private function getAdditionalData(): int|string|array|\UnitEnum;
    
    abstract private function getFunctionalData() :int|string|array|\UnitEnum;

    public function getAdditionalDataField(int|string|\UnitEnum ...$offsets): int|string|array|\UnitEnum
    {
        $field = $this->getAdditionalData();
        foreach ($offsets as $offset) {
            if (isset($field[$offset])) {
                $field = $field[$offset];
            } else {
                throw new EnumerationException();
            }
        }
        return $field;
    }
    
    public static function getChoiceFromEnumerations(mixed $labelOffset = null): array
    {
        $choice = [];
        foreach (self::cases() as $value) {
            $choiceKey = ($labelOffset === null) ? $value->getAdditionalDataField() : $value->getAdditionalDataField($labelOffset);
            $choice[$choiceKey] = $value->value;
        }
        return $choice;
    }

}

<?php

namespace SismaFramework\Core\Traits;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\ProprietaryTypes\SismaDateTime;

trait UnparseValue
{

    private static function unparseValue(array &$arrayValues): void
    {
        foreach ($arrayValues as $key => $value) {
            if ($value instanceof BaseEntity) {
                $arrayValues[$key] = $value->id;
            } elseif (is_subclass_of($value, \UnitEnum::class)) {
                $arrayValues[$key] = $value->value;
            } elseif ($value instanceof SismaDateTime) {
                $arrayValues[$key] = $value->format("Y-m-d H:i:s");
            }
        }
    }

}

<?php

namespace Sisma\Core\Traits;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\BaseClasses\BaseEnumerator;
use Sisma\Core\ProprietaryTypes\SismaDateTime;

trait UnparseValue
{

    private static function unparseValue(array &$arrayValues): void
    {
        foreach ($arrayValues as $key => $value) {
            if ($value instanceof BaseEntity) {
                $arrayValues[$key] = $value->id;
            } elseif ($value instanceof BaseEnumerator) {
                $arrayValues[$key] = $value->__toString();
            } elseif ($value instanceof SismaDateTime) {
                $arrayValues[$key] = $value->format("Y-m-d H:i:s");
            }
        }
    }

}

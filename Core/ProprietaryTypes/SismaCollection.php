<?php

namespace Sisma\Core\ProprietaryTypes;

class SismaCollection extends \ArrayObject
{
    public function mergeWith(SismaCollection $sismaCollection)
    {
        foreach ($sismaCollection as $object) {
            $this->append($object);
        }
    }
    
    public function findFromProperty(string $propertyName, mixed $propertyValue):mixed
    {
        $result = null;
        foreach ($this as $value) {
            var_dump($value->$propertyName, $propertyValue);
            if ($value->$propertyName === $propertyValue){
                $result = $value;
            }
        }
        return $result;
    }
}

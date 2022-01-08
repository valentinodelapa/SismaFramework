<?php

namespace SismaFramework\Core\ProprietaryTypes;

class SismaCollection extends \ArrayObject
{

    public function mergeWith(SismaCollection $sismaCollection)
    {
        foreach ($sismaCollection as $object) {
            $this->append($object);
        }
    }

    public function findFromProperty(string $propertyName, mixed $propertyValue): mixed
    {
        $result = null;
        foreach ($this as $value) {
            if ($value->$propertyName === $propertyValue) {
                $result = $value;
            }
        }
        return $result;
    }

    public function has($value)
    {
        return in_array($value, (array) $this);
    }

}

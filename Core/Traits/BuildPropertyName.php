<?php

namespace SismaFramework\Core\Traits;

trait BuildPropertyName
{
    protected static function buildPropertyName(string $propertyName): string
    {
        $propertyName = (substr($propertyName, -3, 3) == '_id') ? substr($propertyName, 0, -3) : $propertyName;
        $propertyName = str_replace(' ', '', ucwords(str_replace('_', ' ', $propertyName)));
        $propertyName = lcfirst($propertyName);
        return $propertyName;
    }
}

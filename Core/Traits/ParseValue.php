<?php

namespace SismaFramework\Core\Traits;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\ProprietaryTypes\SismaDateTime;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\Exceptions\InvalidArgumentException;

trait ParseValue
{

    private function parseValue(\ReflectionNamedType $reflectionNamedType, ?string $value): mixed
    {
        if (($value === null) || ($reflectionNamedType->allowsNull() && ($value === ''))) {
            return null;
        } elseif ($reflectionNamedType->isBuiltin()) {
            settype($value, $reflectionNamedType->getName());
            return $value;
        } elseif (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class)) {
            return $this->parseEntity($reflectionNamedType->getName(), $value);
        } elseif (enum_exists($reflectionNamedType->getName())) {
            $enumerationName = $reflectionNamedType->getName();
            return $enumerationName::from($value);
        } elseif (is_a($reflectionNamedType->getName(), SismaDateTime::class, true)) {
            return new SismaDateTime($value);
        } else {
            throw new InvalidArgumentException();
        }
    }

    private function parseEntity(string $entityName, string $value): BaseEntity
    {
        $modelName = str_replace(\Config\ENTITY_NAMESPACE, \Config\MODEL_NAMESPACE, $entityName) . 'Model';
        $modelInstance = new $modelName();
        return $modelInstance->getEntityById($value);
    }

}

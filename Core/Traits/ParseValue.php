<?php

namespace Sisma\Core\Traits;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\BaseClasses\BaseEnumerator;
use Sisma\Core\ProprietaryTypes\SismaDateTime;
use Sisma\Core\HttpClasses\Request;
use Sisma\Core\Exceptions\InvalidArgumentException;

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
            $entityName = $reflectionNamedType->getName();
            $modelName = str_replace(\Config\ENTITY_NAMESPACE, \Config\MODEL_NAMESPACE, $entityName) . 'Model';
            $modelInstance = new $modelName();
            return $modelInstance->getEntityById($value);
        } elseif (is_subclass_of($reflectionNamedType->getName(), BaseEnumerator::class)) {
            $enumeratorName = $reflectionNamedType->getName();
            return new $enumeratorName($value);
        } elseif (is_a($reflectionNamedType->getName(), SismaDateTime::class, true)) {
            return new SismaDateTime($value);
        }else{
            throw new InvalidArgumentException();
        }
    }

}

<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SismaFramework\Traits;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Core\Exceptions\InvalidArgumentException;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
trait ParseValue
{

    private function parseValue(\ReflectionNamedType $reflectionNamedType, ?string $value, $parseEntity = true): mixed
    {
        if (($value === null) || ($reflectionNamedType->allowsNull() && ($value === ''))) {
            return null;
        } elseif ($reflectionNamedType->isBuiltin()) {
            settype($value, $reflectionNamedType->getName());
            return $value;
        } elseif (is_subclass_of($reflectionNamedType->getName(), BaseEntity::class)) {
            if($parseEntity){
                return $this->parseEntity($reflectionNamedType->getName(), $value);
            }else{
                return intval($value);
            }
        } elseif (enum_exists($reflectionNamedType->getName())) {
            $enumerationName = $reflectionNamedType->getName();
            $enumerationValue = $enumerationName::tryFrom($value);
            if(($enumerationValue === null) && ($reflectionNamedType->allowsNull() === false)){
                throw new InvalidArgumentException();
            }else{
                return $enumerationName::from($value);
            }
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

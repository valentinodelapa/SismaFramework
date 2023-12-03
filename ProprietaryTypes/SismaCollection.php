<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
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

namespace SismaFramework\ProprietaryTypes;

use SismaFramework\Core\Exceptions\InvalidTypeException;

/**
 *
 * @author Valentino de Lapa
 */
class SismaCollection extends \ArrayObject
{
    private string $restrictiveType;

    public function __construct(string $restrictiveType, array|object $array = [], int $flags = 0, string $iteratorClass = \ArrayIterator::class)
    {
        $this->restrictiveType = $restrictiveType;
        return parent::__construct($array, $flags, $iteratorClass);
    }
    
    public function getRestrictiveType():string
    {
        return $this->restrictiveType;
    }

    public function append(mixed $value): void
    {
        if ($value instanceof $this->restrictiveType) {
            parent::append($value);
        } else {
            throw new InvalidTypeException();
        }
    }

    public function exchangeArray(array|object $array): array
    {
        foreach ($array as $entity) {
            if (($entity instanceof $this->restrictiveType) === false) {
                throw new InvalidTypeException();
            }
        }
        return parent::exchangeArray($array);
    }

    public function mergeWith(SismaCollection $sismaCollection): void
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

    public function has($value): bool
    {
        return in_array($value, (array) $this);
    }

    public function slice(int $offset, ?int $length = null): void
    {
        $arrayFromObgect = $this->getArrayCopy();
        $arraySliced = array_slice($arrayFromObgect, $offset, $length);
        $this->exchangeArray($arraySliced);
    }

}

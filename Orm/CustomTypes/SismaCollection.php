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

namespace SismaFramework\Orm\CustomTypes;

use SismaFramework\Core\Exceptions\InvalidTypeException;
use SismaFramework\Orm\BaseClasses\BaseEntity;

/**
 * @author Valentino de Lapa
 */
class SismaCollection extends \ArrayObject
{

    private string $restrictiveType;

    public function __construct(string $restrictiveType, array|object $array = [], int $flags = 0, string $iteratorClass = \ArrayIterator::class)
    {
        $this->restrictiveType = $restrictiveType;
        parent::__construct($array, $flags, $iteratorClass);
    }

    public function getRestrictiveType(): string
    {
        return $this->restrictiveType;
    }

    #[\Override]
    public function append(mixed $value): void
    {
        if ($value instanceof $this->restrictiveType) {
            parent::append($value);
        } else {
            throw new InvalidTypeException();
        }
    }

    #[\Override]
    public function exchangeArray(array|object $array): array
    {
        foreach ($array as $entity) {
            if (($entity instanceof $this->restrictiveType) === false) {
                throw new InvalidTypeException();
            }
        }
        return parent::exchangeArray($array);
    }

    public function mergeWith(SismaCollection $sismaCollection): self
    {
        if ($this->restrictiveType === $sismaCollection->getRestrictiveType()) {
            foreach ($sismaCollection as $object) {
                $this->append($object);
            }
        } else {
            throw new InvalidTypeException();
        }
        return $this;
    }

    public function findEntityFromProperty(string $propertyName, mixed $propertyValue): mixed
    {
        $result = null;
        foreach ($this as $entity) {
            if ($entity->$propertyName === $propertyValue) {
                $result = $entity;
            }
        }
        return $result;
    }

    public function has(BaseEntity $entity): bool
    {
        return in_array($entity, $this->getArrayCopy(), true);
    }

    public function slice(int $offset, ?int $length = null): self
    {
        $arrayFromObgect = $this->getArrayCopy();
        $arraySliced = array_slice($arrayFromObgect, $offset, $length);
        $this->exchangeArray($arraySliced);
        return $this;
    }

    public function isFirst(BaseEntity $entity): bool
    {
        $arrayCopy = $this->getArrayCopy();
        $key = array_search($entity, $arrayCopy, true);
        return $key === array_key_first($arrayCopy);
    }

    public function isLast(BaseEntity $entity): bool
    {
        $arrayCopy = $this->getArrayCopy();
        $key = array_search($entity, $arrayCopy, true);
        return $key === array_key_last($arrayCopy);
    }
}

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

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Core\ExtendedClasses\StandardEntity;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\Parser;

/**
 *
 * @author Valentino de Lapa
 */
abstract class BaseResultSet implements \Iterator
{

    protected string $returnType = StandardEntity::class;
    protected int $currentRecord = 0;
    protected int $maxRecord = -1;

    public function numRows(): int
    {
        return 0;
    }

    public function setReturnType(string $type): void
    {
        $this->returnType = strval($type);
    }

    public function release(): void
    {
        $this->maxRecord = -1;
        $this->currentRecord = 0;
    }

    public function fetchNext(): BaseEntity
    {
        $this->currentRecord++;
        return $this->fetch();
    }

    abstract public function fetch(): StandardEntity|BaseEntity;

    public function seek(int $n): BaseEntity
    {
        $n = intval($n);
        if ($n < 0) {
            $n = 0;
        } elseif ($n > $this->maxRecord) {
            $n = $this->maxRecord;
        }
        $this->currentRecord = $n;
    }

    public function current(): BaseEntity
    {
        return $this->fetch();
    }

    public function next(): void
    {
        $this->currentRecord++;
    }

    public function key(): int
    {
        return $this->currentRecord;
    }

    public function rewind(): void
    {
        $this->currentRecord = 0;
    }

    public function valid(): bool
    {
        if ($this->currentRecord >= 0 && $this->currentRecord <= $this->maxRecord) {
            return true;
        } else {
            return false;
        }
    }

    protected function transformResult(&$result): StandardEntity|BaseEntity
    {
        if (!$result) {
            return null;
        }
        if ($this->returnType == StandardEntity::class) {
            return $this->convertToStandardEntity($result);
        }
        $class = $this->returnType;
        $entity = new $class();
        foreach ($result as $property => $value) {
            $property = $this->buildPropertyName($property);
            if (property_exists($entity, $property)) {
                $reflectionProperty = new \ReflectionProperty($class, $property);
                $reflectionType = $reflectionProperty->getType();
                $initializationVectorColumnName = $this->buildColumnName($entity->getInitializationVectorPropertyName());
                if ($entity->isEncryptedProperty($property) && ($result->$initializationVectorColumnName !== null)) {
                    $value = Encryptor::decryptString($value, $result->$initializationVectorColumnName);
                }
                $entity->$property = Parser::parseValue($reflectionType, $value, false);
            }
        }
        $entity->modified = false;
        return $entity;
    }

    private function convertToStandardEntity($standardClass): StandardEntity
    {
        $StandardEntity = new StandardEntity();
        foreach ($standardClass as $property => $value) {
            $StandardEntity->$property = $value;
        }
        return $StandardEntity;
    }

    private function buildPropertyName(string $columnName): string
    {
        $columnName = (substr($columnName, -3, 3) == '_id') ? substr($columnName, 0, -3) : $columnName;
        $columnName = str_replace(' ', '', ucwords(str_replace('_', ' ', $columnName)));
        $columnName = lcfirst($columnName);
        return $columnName;
    }

    private function buildColumnName(string $propertyName): string
    {
        $propertyName = preg_split('/(?=[A-Z])/', $propertyName);
        array_walk($propertyName, function (&$value) {
            $value = strtolower($value);
        });
        $propertyName = implode('_', $propertyName);
        return $propertyName;
    }
}

?>

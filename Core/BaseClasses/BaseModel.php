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

namespace SismaFramework\Core\BaseClasses;

use SismaFramework\Core\ObjectRelationalMapper\Adapter;
use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\Keyword;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\ComparisonOperator;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\DataType;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseModel
{

    protected ?Adapter $adapter = null;
    protected BaseEntity $entity;

    public function __construct(?Adapter $adapter = null)
    {
        if ($adapter instanceof Adapter) {
            $this->adapter = $adapter;
        } else {
            $this->adapter = Adapter::create(\Config\DATABASE_ADAPTER_TYPE, [
                        'database' => \Config\DATABASE_NAME,
                        'hostname' => \Config\DATABASE_HOST,
                        'password' => \Config\DATABASE_PASSWORD,
                        'port' => \Config\DATABASE_PORT,
                        'username' => \Config\DATABASE_USERNAME,
            ]);
        }
        $this->implementEmbeddedEntity();
    }

    abstract public function implementEmbeddedEntity(): void;

    public function getEmbeddedEntity(): BaseEntity
    {
        return $this->entity;
    }

    public function setEntityByData(array $data): void
    {
        $classProperty = get_class_vars(get_class($this->entity));
        $orderedData = array_intersect_key($data, $classProperty);
        foreach ($orderedData as $key => $value) {
            $this->entity->$key = $value;
        }
    }

    public function countEntityCollection(): int
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->close();
        return $class::getCount($query);
    }

    public function getEntityCollection(?array $order = null): SismaCollection
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->close();
        $result = $class::find($query);
        $collection = new SismaCollection;
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

    public function getEntityById(int $id): ?BaseEntity
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        return $class::findFirst($query, [
                    $id,
                        ], [
                    DataType::typeInteger,
        ]);
    }

    public function saveEntityByData(array $data): void
    {
        $this->setEntityByData($data);
        if (!$this->entity->save()) {
            Throw new ModelException();
        }
    }

    public function deleteEntityById(int $id): bool
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        return $class::deleteBatch($query, [
                    $id,
                        ], [
                    DataType::typeInteger,
        ]);
    }

    public function __destruct()
    {
        $this->adapter = null;
    }

}

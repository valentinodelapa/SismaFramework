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
 *
 * Estende BaseModel (ispirato a SimpleORM - vedere BaseModel.php per dettagli licenza originale).
 */

namespace SismaFramework\Orm\ExtendedClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\HelperClasses\Query;

/**
 *
 * @author Valentino de Lapa
 */
abstract class DependentModel extends BaseModel
{

    /**
     * @deprecated dalla versione 11.0.0, verrà rimosso. Utilizzare la metaprogrammazione tramite __call()
     */
    public function countEntityCollectionByEntity(array $referencedEntities, ?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $referencedEntities, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    protected function buildPropertiesConditions(Query $query, array $properties, array &$bindValues, array &$bindTypes): void
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if ($propertyValue === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '', $propertyValue instanceof ReferencedEntity);
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Placeholder::placeholder, $propertyValue instanceof ReferencedEntity);
                $reflectionNamedType = new \ReflectionProperty($this->entityName, $propertyName);
                $bindValues[] = $propertyValue;
                $bindTypes[] = DataType::fromReflection($reflectionNamedType->getType(), $propertyValue);
            }
            if ($propertyName !== array_key_last($properties)) {
                $query->appendAnd();
            }
        }
    }

    /**
     * @deprecated dalla versione 11.0.0, verrà rimosso. Utilizzare la metaprogrammazione tramite __call()
     */
    public function getEntityCollectionByEntity(array $referencedEntities, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $referencedEntities, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->setOrderBy($order);
        if ($offset !== null) {
            $query->setOffset($offset);
        }
        if ($limit != null) {
            $query->setLimit($limit);
        }
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    /**
     * @deprecated dalla versione 11.0.0, verrà rimosso. Utilizzare la metaprogrammazione tramite __call()
     */
    public function deleteEntityCollectionByEntity(array $referencedEntities, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $referencedEntities, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->deleteBatch($query, $bindValues, $bindTypes);
    }

    public function getOtherEntityCollectionByEntity(BaseEntity $excludedEntity, string $propertyName, BaseEntity $baseEntity): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::notEqual, Placeholder::placeholder, true);
        $bindValues = [
            $excludedEntity,
        ];
        $bindTypes = [
            DataType::typeEntity,
        ];
        $query->appendAnd();
        if ($baseEntity === null) {
            $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($propertyName, ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues = [$baseEntity];
            $bindTypes = [DataType::typeEntity];
        }
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

}

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

namespace SismaFramework\Core\ExtendedClasses;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\BaseClasses\BaseModel;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\ORM\Enumerations\Keyword;
use SismaFramework\ORM\Enumerations\ComparisonOperator;
use SismaFramework\ORM\Enumerations\DataType;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class ReferencedModel extends BaseModel
{

    public function __call($name, $arguments): SismaCollection|bool
    {
        $nameParts = explode('By', $name);
        $sismaCollectionParts = array_filter(preg_split('/(?=[A-Z])/', $nameParts[0]));
        $action = array_shift($sismaCollectionParts);
        $entityNameParts = array_filter(preg_split('/(?=[A-Z])/', $nameParts[1]));
        $entityName = strtolower(implode('_', $entityNameParts));
        switch ($action) {
            case 'get':
                return $this->getSismaCollectionByEntity($entityName, ...$arguments);
                break;
            case 'delete':
                return $this->deleteSismaCollectionByEntity($entityName, $arguments[0]);
                break;
            default:
                throw new ModelException('Metodo non trovato');
                break;
        }
    }

    public function getSismaCollectionByEntity(string $propertyName, BaseEntity $baseEntity = null, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($baseEntity === null) {
            $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
        } else {
            $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues = [$baseEntity];
            $bindTypes = [DataType::typeEntity];
        }
        if ($searchKey !== null) {
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
        return $this->getMultipleRowResult($query, $bindValues, $bindTypes);
    }

    public function deleteSismaCollectionByEntity(string $propertyName, BaseEntity $baseEntity = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        if ($baseEntity === null) {
            $query->appendCondition($propertyName, ComparisonOperator::isNull, '', true);
            $bindValues = [];
            $bindTypes = [];
        } else {
            $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues = [$baseEntity];
            $bindTypes = [DataType::typeEntity];
        }
        $query->close();
        return $this->entityName::deleteBatch($query, $bindValues, $bindTypes);
    }

    public function getOtherEntityCollectionByEntity(BaseEntity $excludedEntity, string $propertyName, BaseEntity $baseEntity): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::notEqualTwo, Keyword::placeholder, true);
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
            $query->appendCondition($propertyName, ComparisonOperator::equal, Keyword::placeholder, true);
            $bindValues = [$baseEntity];
            $bindTypes = [DataType::typeEntity];
        }
        $query->close();
        return $this->getMultipleRowResult($query, $bindValues, $bindTypes);
    }

}

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

namespace SismaFramework\Odm\BaseClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\Indexing;
use SismaFramework\Odm\Exceptions\DocumentMapperException;
use SismaFramework\Odm\HelperClasses\Cache;
use SismaFramework\Odm\HelperClasses\DocumentMapper;
use SismaFramework\Odm\HelperClasses\DocumentQuery;
use SismaFramework\Orm\CustomTypes\SismaCollection;

/**
 * @author Valentino de Lapa
 */
abstract class BaseModel
{
    protected Config $config;

    public function __construct(
        protected DocumentMapper $documentMapper = new DocumentMapper(),
        ?Config $config = null
    ) {
        $this->config = $config ?? Config::getInstance();
    }

    abstract public function getDocumentName(): string;

    public function getDocumentCollection(
        ?DocumentQuery $query = null,
        ?string $orderField = null,
        Indexing $orderDirection = Indexing::asc,
        ?int $offset = null,
        ?int $limit = null
    ): SismaCollection {
        $query ??= new DocumentQuery();
        if ($orderField !== null) {
            $query->orderBy($orderField, $orderDirection);
        }
        if ($offset !== null) {
            $query->offset($offset);
        }
        if ($limit !== null) {
            $query->limit($limit);
        }
        return $this->documentMapper->find($this->getDocumentName(), $query);
    }

    public function getDocumentById(string $id): ?BaseDocument
    {
        if ($this->config->odmCache && Cache::checkDocumentPresenceInCache($this->getDocumentName(), $id)) {
            return Cache::getDocumentById($this->getDocumentName(), $id);
        }
        $query = (new DocumentQuery())->where('_id', FilterOperator::equal, $id);
        return $this->documentMapper->findFirst($this->getDocumentName(), $query);
    }

    public function countDocumentCollection(?DocumentQuery $query = null): int
    {
        $query ??= new DocumentQuery();
        return $this->documentMapper->getCount($this->getDocumentName(), $query);
    }

    public function save(BaseDocument $document): void
    {
        $this->documentMapper->save($document);
    }

    public function deleteDocumentById(string $id): void
    {
        $document = $this->getDocumentById($id);
        if ($document !== null) {
            $this->documentMapper->delete($document);
        }
    }

    public function __call(string $name, array $arguments): SismaCollection|int
    {
        $nameParts = explode('By', $name, 2);
        if (count($nameParts) !== 2) {
            throw new DocumentMapperException($name);
        }
        $action = lcfirst($nameParts[0]);
        $propertyNames = array_map('lcfirst', explode('And', $nameParts[1]));
        if (count($propertyNames) !== count($arguments)) {
            throw new DocumentMapperException($name);
        }
        $query = $this->buildQueryFromProperties($propertyNames, $arguments);
        return match ($action) {
            'find', 'get' => $this->getDocumentCollection($query),
            'count' => $this->countDocumentCollection($query),
            'delete' => $this->documentMapper->deleteBatch($this->getDocumentName(), $query),
            default => throw new DocumentMapperException($name),
        };
    }

    private function buildQueryFromProperties(array $propertyNames, array $values): DocumentQuery
    {
        $query = new DocumentQuery();
        foreach ($propertyNames as $index => $propertyName) {
            $value = $values[$index];
            $operator = $value === null ? FilterOperator::isNull : FilterOperator::equal;
            if ($index === 0) {
                $query->where($propertyName, $operator, $value);
            } else {
                $query->andWhere($propertyName, $operator, $value);
            }
        }
        return $query;
    }
}

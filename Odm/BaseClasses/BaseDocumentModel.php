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

use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\OdmIndexing;
use SismaFramework\Odm\HelperClasses\DocumentMapper;
use SismaFramework\Odm\HelperClasses\DocumentQuery;
use SismaFramework\Orm\CustomTypes\SismaCollection;

/**
 * @author Valentino de Lapa
 */
abstract class BaseDocumentModel
{
    public function __construct(
        protected DocumentMapper $documentMapper = new DocumentMapper()
    ) {}

    abstract public function getDocumentName(): string;

    public function getDocumentCollection(
        ?DocumentQuery $query = null,
        ?string $orderField = null,
        OdmIndexing $orderDirection = OdmIndexing::asc,
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
}

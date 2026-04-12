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

namespace SismaFramework\Odm\HelperClasses;

use SismaFramework\Odm\BaseClasses\BaseDocument;
use SismaFramework\Odm\BaseClasses\BaseOdmAdapter;
use SismaFramework\Odm\Exceptions\DocumentMapperException;
use SismaFramework\Orm\CustomTypes\SismaCollection;

/**
 * @author Valentino de Lapa
 */
class DocumentMapper
{
    public function __construct(
        protected ?BaseOdmAdapter $adapter = null
    ) {
        $this->adapter ??= BaseOdmAdapter::getDefault();
    }

    public function save(BaseDocument $document): void
    {
        if (!$document->modified) {
            return;
        }

        $this->adapter->ensureConnected();
        $collection = $document->getCollectionName();
        $data = $document->toArray();

        if (isset($data['_id']) && $data['_id'] !== null && $data['_id'] !== '') {
            $id = (string) $data['_id'];
            unset($data['_id']);
            $this->adapter->update($collection, $id, $data);
        } else {
            unset($data['_id']);
            $newId = $this->adapter->insert($collection, $data);
            $document->_id = $newId;
        }

        $document->modified = false;
    }

    public function delete(BaseDocument $document): void
    {
        $this->adapter->ensureConnected();
        $id = $document->_id;
        if ($id === null || $id === '') {
            throw new DocumentMapperException('Cannot delete a document without an ID.');
        }
        $this->adapter->delete($document->getCollectionName(), (string) $id);
        $document->_id = null;
        $document->modified = false;
    }

    public function find(string $documentClass, DocumentQuery $query): SismaCollection
    {
        $this->adapter->ensureConnected();
        $prototype = new $documentClass();
        $resultSet = $this->adapter->find($prototype->getCollectionName(), $query);
        $resultSet->setReturnType($documentClass);

        $collection = new SismaCollection($documentClass);
        foreach ($resultSet as $document) {
            $collection->append($document);
        }
        $resultSet->release();
        return $collection;
    }

    public function findFirst(string $documentClass, DocumentQuery $query): ?BaseDocument
    {
        $query->limit(1);
        $collection = $this->find($documentClass, $query);
        return count($collection) > 0 ? $collection[0] : null;
    }

    public function getCount(string $documentClass, DocumentQuery $query): int
    {
        $this->adapter->ensureConnected();
        $prototype = new $documentClass();
        return $this->adapter->count($prototype->getCollectionName(), $query);
    }
}

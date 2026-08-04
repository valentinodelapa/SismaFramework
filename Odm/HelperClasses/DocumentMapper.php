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

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Odm\BaseClasses\BaseAdapter;
use SismaFramework\Odm\BaseClasses\BaseDocument;
use SismaFramework\Odm\Exceptions\DocumentMapperException;
use SismaFramework\Orm\CustomTypes\SismaCollection;

/**
 * @author Valentino de Lapa
 */
class DocumentMapper
{
    private Config $config;

    public function __construct(
        protected ?BaseAdapter $adapter = null,
        ?Config $config = null
    ) {
        $this->config = $config ?? Config::getInstance();
    }

    private function getAdapter(): BaseAdapter
    {
        return $this->adapter ??= BaseAdapter::getDefault();
    }

    public function save(BaseDocument $document): void
    {
        if (!$document->modified) {
            return;
        }

        $this->getAdapter()->ensureConnected();
        $collection = $document->getCollectionName();
        $data = $this->unparseData($document->toArray());

        if (isset($data['_id']) && $data['_id'] !== null && $data['_id'] !== '') {
            $id = (string) $data['_id'];
            unset($data['_id']);
            $this->getAdapter()->update($collection, $id, $data);
        } else {
            unset($data['_id']);
            $newId = $this->getAdapter()->insert($collection, $data);
            $document->_id = $newId;
        }

        $document->modified = false;
        if ($this->config->odmCache) {
            Cache::setDocument($document);
        }
    }

    private function unparseData(array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = is_array($value) ? $this->unparseData($value) : Parser::unparseValue($value);
        }
        return $data;
    }

    public function delete(BaseDocument $document): void
    {
        $this->getAdapter()->ensureConnected();
        $id = $document->_id;
        if ($id === null || $id === '') {
            throw new DocumentMapperException('Cannot delete a document without an ID.');
        }
        $this->getAdapter()->delete($document->getCollectionName(), (string) $id);
        $document->_id = null;
        $document->modified = false;
        if ($this->config->odmCache) {
            Cache::clearDocumentCache();
        }
    }

    public function deleteBatch(string $documentClass, DocumentQuery $query): int
    {
        $this->getAdapter()->ensureConnected();
        $prototype = new $documentClass();
        $deletedCount = $this->getAdapter()->deleteMany($prototype->getCollectionName(), $query);
        if ($this->config->odmCache) {
            Cache::clearDocumentCache();
        }
        return $deletedCount;
    }

    public function find(string $documentClass, DocumentQuery $query): SismaCollection
    {
        $this->getAdapter()->ensureConnected();
        $prototype = new $documentClass();
        $resultSet = $this->getAdapter()->find($prototype->getCollectionName(), $query);
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
        $this->getAdapter()->ensureConnected();
        $prototype = new $documentClass();
        return $this->getAdapter()->count($prototype->getCollectionName(), $query);
    }
}

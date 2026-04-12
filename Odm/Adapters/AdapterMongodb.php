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

namespace SismaFramework\Odm\Adapters;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Odm\BaseClasses\BaseDocumentResultSet;
use SismaFramework\Odm\BaseClasses\BaseOdmAdapter;
use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\LogicalOperator;
use SismaFramework\Odm\Enumerations\OdmAdapterType;
use SismaFramework\Odm\Enumerations\OdmIndexing;
use SismaFramework\Odm\Exceptions\OdmAdapterException;
use SismaFramework\Odm\HelperClasses\DocumentQuery;
use SismaFramework\Odm\ResultSets\ResultSetMongodb;

/**
 * @author Valentino de Lapa
 */
class AdapterMongodb extends BaseOdmAdapter
{
    private ?Client $client = null;
    private string $databaseName = '';
    private string $lastErrorMsg = '';
    private int|string $lastErrorCode = 0;

    #[\Override]
    protected function connect(): void
    {
        if (!extension_loaded('mongodb')) {
            throw new OdmAdapterException('L\'estensione PHP ext-mongodb è necessaria per usare AdapterMongodb.');
        }

        $config = Config::getInstance();
        $this->databaseName = $config->odmDatabaseName;

        $uri = sprintf(
            'mongodb://%s:%s@%s:%s',
            urlencode($config->odmDatabaseUsername),
            urlencode($config->odmDatabasePassword),
            $config->odmDatabaseHost,
            $config->odmDatabasePort
        );

        try {
            $this->client = new Client($uri);
        } catch (\Exception $e) {
            $this->lastErrorMsg = $e->getMessage();
            $this->lastErrorCode = $e->getCode();
            throw new OdmAdapterException('Connessione MongoDB fallita: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function close(): void
    {
        $this->client = null;
        $this->isConnected = false;
    }

    #[\Override]
    public function getAdapterType(): OdmAdapterType
    {
        return OdmAdapterType::mongodb;
    }

    #[\Override]
    public function compileQuery(DocumentQuery $query): array
    {
        $conditions = $query->getConditions();
        if (empty($conditions)) {
            return [];
        }
        return $this->compileConditions($conditions);
    }

    private function compileConditions(array $conditions): array
    {
        $andGroups = [];
        $currentGroup = [];
        $pendingLogical = null;

        foreach ($conditions as $node) {
            if ($node['type'] === 'logical_separator') {
                if (!empty($currentGroup)) {
                    $andGroups[] = ['logical' => $pendingLogical, 'condition' => $currentGroup];
                    $currentGroup = [];
                }
                $pendingLogical = $node['operator'];
            } else {
                $currentGroup = $node;
            }
        }

        if (!empty($currentGroup)) {
            $andGroups[] = ['logical' => $pendingLogical, 'condition' => $currentGroup];
        }

        if (count($andGroups) === 1) {
            return $this->compileSingleCondition($andGroups[0]['condition']);
        }

        $andClauses = [];
        $orClauses = [];

        foreach ($andGroups as $group) {
            $compiled = $this->compileSingleCondition($group['condition']);
            if ($group['logical'] === LogicalOperator::or) {
                $orClauses[] = $compiled;
            } else {
                $andClauses[] = $compiled;
            }
        }

        $filter = [];
        if (!empty($andClauses)) {
            $filter[LogicalOperator::and->getAdapterVersion(OdmAdapterType::mongodb)] = $andClauses;
        }
        if (!empty($orClauses)) {
            $filter[LogicalOperator::or->getAdapterVersion(OdmAdapterType::mongodb)] = $orClauses;
        }

        return $filter;
    }

    private function compileSingleCondition(array $node): array
    {
        $field    = $node['field'];
        $operator = $node['operator'];
        $value    = $node['value'];
        $mongoOp  = $operator->getAdapterVersion(OdmAdapterType::mongodb);

        return match ($operator) {
            FilterOperator::isNull    => [$field => null],
            FilterOperator::isNotNull => [$field => [$mongoOp => null]],
            FilterOperator::like      => [$field => [$mongoOp => $value, '$options' => 'i']],
            FilterOperator::notLike   => [$field => [$mongoOp => ['$regex' => $value, '$options' => 'i']]],
            default                   => [$field => [$mongoOp => $value]],
        };
    }

    private function compileSortOptions(DocumentQuery $query): array
    {
        $sort = [];
        foreach ($query->getSort() as $field => $direction) {
            $sort[$field] = $direction->value;
        }
        return $sort;
    }

    #[\Override]
    public function find(string $collection, DocumentQuery $query): BaseDocumentResultSet
    {
        $filter  = $this->compileQuery($query);
        $options = ['sort' => $this->compileSortOptions($query)];
        if ($query->getLimit() !== null) {
            $options['limit'] = $query->getLimit();
        }
        if ($query->getOffset() !== null) {
            $options['skip'] = $query->getOffset();
        }

        try {
            $cursor = $this->client
                ->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->find($filter, $options);

            $rows = [];
            foreach ($cursor as $bsonDoc) {
                $rows[] = $this->bsonToArray($bsonDoc);
            }
            return new ResultSetMongodb($rows);
        } catch (\Exception $e) {
            throw new OdmAdapterException('Errore find MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function insert(string $collection, array $data): string
    {
        try {
            $result = $this->client
                ->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->insertOne($data);

            return (string) $result->getInsertedId();
        } catch (\Exception $e) {
            throw new OdmAdapterException('Errore insert MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function update(string $collection, string $id, array $data): void
    {
        try {
            $this->client
                ->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->updateOne(['_id' => new ObjectId($id)], ['$set' => $data]);
        } catch (\Exception $e) {
            throw new OdmAdapterException('Errore update MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function delete(string $collection, string $id): void
    {
        try {
            $this->client
                ->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->deleteOne(['_id' => new ObjectId($id)]);
        } catch (\Exception $e) {
            throw new OdmAdapterException('Errore delete MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function count(string $collection, DocumentQuery $query): int
    {
        $filter = $this->compileQuery($query);
        try {
            return (int) $this->client
                ->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->countDocuments($filter);
        } catch (\Exception $e) {
            throw new OdmAdapterException('Errore count MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function getLastErrorMsg(): string
    {
        return $this->lastErrorMsg;
    }

    #[\Override]
    public function getLastErrorCode(): int|string
    {
        return $this->lastErrorCode;
    }

    private function bsonToArray(mixed $bsonDoc): array
    {
        $data = (array) $bsonDoc;
        if (isset($data['_id']) && $data['_id'] instanceof ObjectId) {
            $data['_id'] = (string) $data['_id'];
        }
        return $data;
    }
}

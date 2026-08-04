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
use MongoDB\BSON\UTCDateTime;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Odm\BaseClasses\BaseAdapter;
use SismaFramework\Odm\BaseClasses\BaseResultSet;
use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\LogicalOperator;
use SismaFramework\Odm\Enumerations\AdapterType;
use SismaFramework\Odm\Enumerations\Indexing;
use SismaFramework\Odm\Exceptions\AdapterException;
use SismaFramework\Odm\HelperClasses\DocumentQuery;
use SismaFramework\Odm\ResultSets\ResultSetMongodb;
use SismaFramework\Orm\CustomTypes\SismaDateTime;

/**
 * @author Valentino de Lapa
 */
class AdapterMongodb extends BaseAdapter
{
    private ?Client $client = null;
    private string $databaseName = '';
    private string $lastErrorMsg = '';
    private int|string $lastErrorCode = 0;

    #[\Override]
    protected function connect(): void
    {
        if (!extension_loaded('mongodb')) {
            throw new AdapterException('L\'estensione PHP ext-mongodb è necessaria per usare AdapterMongodb.');
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
            throw new AdapterException('Connessione MongoDB fallita: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function close(): void
    {
        $this->client = null;
        $this->isConnected = false;
    }

    #[\Override]
    public function getAdapterType(): AdapterType
    {
        return AdapterType::mongodb;
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
        $orGroups = $this->splitByOr($conditions);
        $compiledGroups = array_map(fn(array $group): array => $this->compileAndGroup($group), $orGroups);

        if (count($compiledGroups) === 1) {
            return $compiledGroups[0];
        }

        return [LogicalOperator::or->getAdapterVersion(AdapterType::mongodb) => $compiledGroups];
    }

    private function splitByOr(array $conditions): array
    {
        $groups = [];
        $currentGroup = [];

        foreach ($conditions as $node) {
            if ($node['type'] === 'logical_separator' && $node['operator'] === LogicalOperator::or) {
                $groups[] = $currentGroup;
                $currentGroup = [];
            } elseif ($node['type'] !== 'logical_separator') {
                $currentGroup[] = $node;
            }
        }
        $groups[] = $currentGroup;

        return $groups;
    }

    private function compileAndGroup(array $group): array
    {
        $compiledConditions = array_map(fn(array $node): array => $this->compileSingleCondition($node), $group);

        if (count($compiledConditions) === 1) {
            return $compiledConditions[0];
        }

        return [LogicalOperator::and->getAdapterVersion(AdapterType::mongodb) => $compiledConditions];
    }

    private function compileSingleCondition(array $node): array
    {
        $field    = $node['field'];
        $operator = $node['operator'];
        $this->assertSafeField($field);
        $this->assertSafeValue($operator, $node['value']);
        $value    = $this->convertFieldValue($field, $node['value']);
        $mongoOp  = $operator->getAdapterVersion(AdapterType::mongodb);

        return match ($operator) {
            FilterOperator::isNull    => [$field => null],
            FilterOperator::isNotNull => [$field => [$mongoOp => null]],
            FilterOperator::like      => [$field => [$mongoOp => $value, '$options' => 'i']],
            FilterOperator::notLike   => [$field => [$mongoOp => ['$regex' => $value, '$options' => 'i']]],
            default                   => [$field => [$mongoOp => $value]],
        };
    }

    private function assertSafeField(string $field): void
    {
        if ($field === '' || $field[0] === '$') {
            throw new AdapterException('Nome campo non valido: ' . $field);
        }
    }

    private function assertSafeValue(FilterOperator $operator, mixed $value): void
    {
        if ($operator === FilterOperator::isNull || $operator === FilterOperator::isNotNull) {
            return;
        }
        if ($operator === FilterOperator::in || $operator === FilterOperator::notIn) {
            if (!is_array($value)) {
                throw new AdapterException('Il valore per gli operatori in/notIn deve essere un array.');
            }
            foreach ($value as $item) {
                $this->assertScalarOrNullOrObjectId($item);
            }
            return;
        }
        $this->assertScalarOrNullOrObjectId($value);
    }

    private function assertScalarOrNullOrObjectId(mixed $value): void
    {
        if ($value !== null && !is_scalar($value) && !($value instanceof ObjectId)) {
            throw new AdapterException('Valore filtro non valido: sono ammessi solo scalari, null o ObjectId.');
        }
    }

    private function convertFieldValue(string $field, mixed $value): mixed
    {
        if ($field !== '_id' || $value === null) {
            return $value;
        }
        if (is_array($value)) {
            return array_map(fn(mixed $item): mixed => $this->toObjectId($item), $value);
        }
        return $this->toObjectId($value);
    }

    private function toObjectId(mixed $value): ObjectId
    {
        if ($value instanceof ObjectId) {
            return $value;
        }
        try {
            return new ObjectId((string) $value);
        } catch (\Exception $e) {
            throw new AdapterException('ObjectId non valido: ' . $value, $e->getCode(), $e);
        }
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
    public function find(string $collection, DocumentQuery $query): BaseResultSet
    {
        try {
            $filter  = $this->compileQuery($query);
            $options = [
                'sort' => $this->compileSortOptions($query),
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ];
            if ($query->getLimit() !== null) {
                $options['limit'] = $query->getLimit();
            }
            if ($query->getOffset() !== null) {
                $options['skip'] = $query->getOffset();
            }

            $cursor = $this->client
                ->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->find($filter, $options);

            $rows = [];
            foreach ($cursor as $bsonDoc) {
                $rows[] = $this->bsonToArray($bsonDoc);
            }
            return new ResultSetMongodb($rows);
        } catch (AdapterException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AdapterException('Errore find MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
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
            throw new AdapterException('Errore insert MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
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
            throw new AdapterException('Errore update MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
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
            throw new AdapterException('Errore delete MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function deleteMany(string $collection, DocumentQuery $query): int
    {
        try {
            $filter = $this->compileQuery($query);
            $result = $this->client
                ->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->deleteMany($filter);
            return $result->getDeletedCount();
        } catch (AdapterException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AdapterException('Errore deleteMany MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public function count(string $collection, DocumentQuery $query): int
    {
        try {
            $filter = $this->compileQuery($query);
            return (int) $this->client
                ->selectDatabase($this->databaseName)
                ->selectCollection($collection)
                ->countDocuments($filter);
        } catch (AdapterException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AdapterException('Errore count MongoDB: ' . $e->getMessage(), $e->getCode(), $e);
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

    private function bsonToArray(array $bsonDoc): array
    {
        foreach ($bsonDoc as $key => $value) {
            $bsonDoc[$key] = $this->convertBsonValue($value);
        }
        return $bsonDoc;
    }

    private function convertBsonValue(mixed $value): mixed
    {
        if ($value instanceof ObjectId) {
            return (string) $value;
        }
        if ($value instanceof UTCDateTime) {
            return SismaDateTime::createFromInterface($value->toDateTime());
        }
        if (is_array($value)) {
            return $this->bsonToArray($value);
        }
        return $value;
    }
}

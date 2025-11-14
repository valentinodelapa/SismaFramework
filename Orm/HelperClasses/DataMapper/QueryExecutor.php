<?php

/*
 * Questo file contiene codice estratto dalla classe DataMapper,
 * che a sua volta deriva dalla libreria SimpleORM
 * (https://github.com/davideairaghi/php) rilasciata sotto licenza Apache License 2.0
 * (fare riferimento alla licenza in third-party-licenses/SimpleOrm/LICENSE).
 *
 * Copyright (c) 2015-present Davide Airaghi.
 *
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
 * MODIFICHE APPORTATE AL CODICE ORIGINALE:
 * - Estrazione (2025): Responsabilità di esecuzione query estratta da DataMapper in classe @internal separata
 * - Applicazione tipizzazione forte PHP 8.1+
 * - BaseAdapter reso parametro opzionale del costruttore con utilizzo del singleton come default
 * - Gestione cache tramite parametro esplicito nei metodi find/findFirst per semplicità e chiarezza
 * - Integrazione con Cache::getEntityById() per restituzione dell'ultima versione modificata dell'entità
 */

namespace SismaFramework\Orm\HelperClasses\DataMapper;

use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Exceptions\DataMapperException;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\Query;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class QueryExecutor
{

    private BaseAdapter $adapter;

    public function __construct(?BaseAdapter $adapter = null)
    {
        $this->adapter = $adapter ?? BaseAdapter::getDefault();
    }

    public function setVariable(string $variable, string $bindValue, DataType $bindType, Query $query = new Query()): bool
    {
        $query->setVariable($variable);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::set);
        Parser::unparseValue($bindValue);
        $result = $this->adapter->execute($cmd, [$bindValue], [$bindType]);
        return $result;
    }

    public function find(string $entityName, Query $query, array $bindValues = [], array $bindTypes = [], bool $ormCacheEnabled = true): SismaCollection
    {
        $result = $this->getResultSet($entityName, $query, $bindValues, $bindTypes);
        $collection = new SismaCollection($entityName);
        if ($result instanceof BaseResultSet) {
            foreach ($result as $entity) {
                $collection->append($this->selectLastModifiedEntity($entityName, $entity, $ormCacheEnabled));
            }
        }
        return $collection;
    }

    public function findFirst(string $entityName, Query $query, array $bindValues = [], array $bindTypes = [], bool $ormCacheEnabled = true): ?BaseEntity
    {
        $query->setOffset(0);
        $query->setLimit(1);
        $result = $this->getResultSet($entityName, $query, $bindValues, $bindTypes);
        if ($result === null) {
            return null;
        } else {
            switch ($result->numRows()) {
                case 0:
                    return null;
                case 1:
                    return $this->selectLastModifiedEntity($entityName, $result->fetch(), $ormCacheEnabled);
                default:
                    throw new DataMapperException('findFirst() returned more than one row for entity: ' . $entityName);
            }
        }
    }

    public function getCount(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): int
    {
        $query->setCount('');
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::select);
        Parser::unparseValues($bindValues);
        $result = $this->adapter->select($cmd, $bindValues, $bindTypes);
        if ($result === null) {
            return 0;
        }
        $data = $result->fetch();
        $result->release();
        unset($result);
        if ($data === null) {
            return 0;
        }
        return $data->_numrows;
    }

    private function getResultSet(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): ?BaseResultSet
    {
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::select);
        Parser::unparseValues($bindValues);
        $result = $this->adapter->select($cmd, $bindValues, $bindTypes);
        if (!$result) {
            return null;
        }
        $result->setReturnType($entityName);
        return $result;
    }

    private function selectLastModifiedEntity(string $entityName, BaseEntity $entity, bool $ormCacheEnabled): BaseEntity
    {
        if ($ormCacheEnabled && Cache::checkEntityPresenceInCache($entityName, $entity->id)) {
            return Cache::getEntityById($entityName, $entity->id);
        } elseif ($ormCacheEnabled) {
            Cache::setEntity($entity);
        }
        return $entity;
    }
}

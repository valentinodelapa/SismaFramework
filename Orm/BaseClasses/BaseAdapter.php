<?php

/*
 * Questo file contiene codice derivato dalla libreria SimpleORM
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
 * MODIFICHE APPORTATE A QUESTO FILE RISPETTO AL CODICE ORIGINALE DI SIMPLEORM:
 * - Significativa riorganizzazione e rifattorizzazione per l'applicazione della tipizzazione forte.
 * - Sostituzione delle costanti di classe con enum (PHP 8.1+).
 * - Modifica del namespace per l'integrazione nel SismaFramework.
 * - Miglioramento della nomenclatura di metodi, parametri e variabili per maggiore chiarezza.
 * - Trasformazione della classe in astratta per definire un'interfaccia comune.
 * - Definizione come astratti dei metodi precedentemente abbozzati, la cui implementazione è demandata alle classi derivate.
 */

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Orm\Enumerations\AdapterType;
use SismaFramework\Orm\Enumerations\AggregationFunction;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Condition;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\LogicalOperator;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Enumerations\TextSearchMode;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\HelperClasses\Query;

/**
 *
 * @author Valentino de Lapa
 */
abstract class BaseAdapter
{

    protected AdapterType $adapterType;
    protected static ?BaseAdapter $adapter = null;
    protected static mixed $connection = null;

    public function __construct(array $options = [])
    {
        $this->connect($options);
        $this->adapterType = $this->setAdapterType();
    }

    abstract protected function connect(array $options = []): void;

    abstract protected function setAdapterType(): AdapterType;

    public static function setConnection(mixed $connection): void
    {
        self::$connection = $connection;
    }

    public static function &getDefault(?Config $customConfig = null): ?BaseAdapter
    {
        $config = $customConfig ?? Config::getInstance();
        if (static::$adapter === null) {
            $defaultAdapter = static::create($config->defaultAdapterType->getAdapterClass(), [
                'database' => $config->databaseName,
                'hostname' => $config->databaseHost,
                'password' => $config->databasePassword,
                'port' => $config->databasePort,
                'username' => $config->databaseUsername,
            ]);
            static::setDefault($defaultAdapter);
        }
        return static::$adapter;
    }

    public static function setDefault(BaseAdapter &$adapter): void
    {
        static::$adapter = &$adapter;
    }

    public static function create(string $adapterClass, array $options = []): BaseAdapter
    {
        return new $adapterClass($options);
    }

    abstract public function close(): void;

    public function allColumns(): string
    {
        return '*';
    }

    public function escapeIdentifier(string $name): string
    {
        $parsedName = preg_replace("/([^a-zA-Z_]+)/", "", $name);
        return $parsedName;
    }

    public function escapeTable(string $table, ?string $tableAlias = null): string
    {
        $parsedTable = $this->escapeIdentifier(NotationManager::convertEntityNameToTableName($table));
        if ($tableAlias !== null) {
            $parsedTable .= ' as ' . $this->escapeIdentifier(NotationManager::convertEntityNameToTableName($tableAlias));
        }
        return $parsedTable;
    }

    public function escapeOrderIndexing(null|string|Indexing $order = null): string
    {
        if (is_string($order)) {
            $order = Indexing::tryFrom($order);
        }
        if ($order instanceof Indexing) {
            return $order->getAdapterVersion($this->adapterType);
        } else {
            return '';
        }
    }

    public function escapeColumns(array $columns): array
    {
        $parsedColumns = [];
        foreach ($columns as $column) {
            $parsedColumns[] = $this->escapeColumn($column);
        }
        return $parsedColumns;
    }

    public function escapeColumn(string $name, bool $foreignKey = false): string
    {
        return $this->escapeIdentifier(NotationManager::convertPropertyNameToColumnName($name, $foreignKey));
    }

    public function escapeValue(mixed $value, ?ComparisonOperator $operator = null): string
    {
        switch ($operator) {
            case ComparisonOperator::isNull:
            case ComparisonOperator::isNotNull:
                return '';
            case ComparisonOperator::in:
            case ComparisonOperator::notIn:
                if (is_array($value)) {
                    foreach ($value as &$singleValue) {
                        $singleValue = $this->escapeValue($singleValue);
                    }
                    return $this->openBlock() . implode(',', $value) . $this->closeBlock();
                } else {
                    return $this->openBlock() . $this->escapeValue($value) . $this->closeBlock();
                }
            default:
                if ($value instanceof Placeholder) {
                    return $value->getAdapterVersion($this->adapterType);
                } elseif (is_array($value)) {
                    return Parser::unparseValue(array_shift($value));
                } else {
                    return Parser::unparseValue($value);
                }
        }
    }

    public function getPlaceholder(): string
    {
        return Placeholder::placeholder->getAdapterVersion($this->adapterType);
    }

    public function parseComparisonOperator(ComparisonOperator $comparisonOperator): string
    {
        return $comparisonOperator->getAdapterVersion($this->adapterType);
    }

    public function openBlock(): string
    {
        return Keyword::openBlock->getAdapterVersion($this->adapterType) . ' ';
    }

    public function closeBlock(): string
    {
        return ' ' . Keyword::closeBlock->getAdapterVersion($this->adapterType);
    }

    public function opAND(): string
    {
        return LogicalOperator::and->getAdapterVersion($this->adapterType);
    }

    public function opOR(): string
    {
        return LogicalOperator::or->getAdapterVersion($this->adapterType);
    }

    public function opNOT(): string
    {
        return LogicalOperator::not->getAdapterVersion($this->adapterType);
    }

    public function opCOUNT(string $column, bool $distinct): string
    {
        if ($column === '') {
            $column = $this->allColumns();
        }
        if ($column !== $this->allColumns()) {
            $column = $this->escapeColumn($column);
        }
        return AggregationFunction::count->getAdapterVersion($this->adapterType) . Keyword::openBlock->getAdapterVersion($this->adapterType) . ($distinct ? Keyword::distinct->getAdapterVersion($this->adapterType) . ' ' : '') . $column . Keyword::closeBlock->getAdapterVersion($this->adapterType) . ' as _numrows';
    }

    public function opSubquery(Query $subquery, ?string $columnAlias = null): string
    {
        $column = Keyword::openBlock->getAdapterVersion($this->adapterType) . $subquery->getCommandToExecute() . Keyword::closeBlock->getAdapterVersion($this->adapterType);
        if ($columnAlias !== null) {
            $column .= ' as ' . $this->escapeColumn($columnAlias);
        }
        return $column;
    }

    public function parseSet(string $variable, string $value): string
    {
        return Statement::set->getAdapterVersion($this->adapterType) . ' ' . $variable . ' = ' . $value;
    }

    public function parseSelect(bool $distinct, array $select, string $from, array $where, array $groupby, array $having, array $orderby, int $offset, int $limit): string
    {
        $query = Statement::select->getAdapterVersion($this->adapterType) . ' ' .
                ($distinct ? Keyword::distinct->getAdapterVersion($this->adapterType) . ' ' : '') .
                implode(',', $select) . ' ' .
                Keyword::from->getAdapterVersion($this->adapterType) . ' ' . $from . ' ' .
                ((count($where) > 0) ? Condition::where->getAdapterVersion($this->adapterType) . ' ' . implode(' ', $where) : '' ) . ' ' .
                ($groupby ? Keyword::groupBy->getAdapterVersion($this->adapterType) . ' ' . implode(',', $groupby) . ' ' : '') .
                ($groupby && $having ? Condition::having->getAdapterVersion($this->adapterType) . ' ' . implode(' ', $having) . ' ' : '') .
                (count($orderby) > 0 ? Keyword::orderBy->getAdapterVersion($this->adapterType) . ' ' . implode(',', $orderby) . ' ' : '') .
                ($limit > 0 ? Keyword::limit->getAdapterVersion($this->adapterType) . ' ' . $limit . ' ' : '') .
                ($offset > 0 ? Keyword::offset->getAdapterVersion($this->adapterType) . ' ' . $offset . ' ' : '');
        return $query;
    }

    public function parseInsert(string $table, array $columns = [], array $values = []): string
    {
        $query = Statement::insert->getAdapterVersion($this->adapterType) . ' ' . $table . ' ' .
                Keyword::openBlock->getAdapterVersion($this->adapterType) . implode(',', $columns) . Keyword::closeBlock->getAdapterVersion($this->adapterType) . ' ' . Keyword::insertValue->getAdapterVersion($this->adapterType) . ' ' .
                Keyword::openBlock->getAdapterVersion($this->adapterType) . implode(',', $values) . Keyword::closeBlock->getAdapterVersion($this->adapterType);
        return $query;
    }

    public function parseUpdate(string $table, array $columns = [], array $values = [], array $where = []): string
    {
        $cmd = [];
        foreach ($columns as $k => $col) {
            $cmd[] = $col . ' = ' . $values[$k];
        }
        $query = Statement::update->getAdapterVersion($this->adapterType) . ' ' . $table . ' ' .
                Keyword::set->getAdapterVersion($this->adapterType) . ' ' . implode(',', $cmd) . ' ' .
                ($where ? Condition::where->getAdapterVersion($this->adapterType) . ' ' . implode(' ', $where) : '');
        return $query;
    }

    public function parseDelete(string $from, array $where = []): string
    {
        $query = Statement::delete->getAdapterVersion($this->adapterType) . ' ' . $from . ' ' .
                ($where ? Condition::where->getAdapterVersion($this->adapterType) . ' ' . implode(' ', $where) : '');
        return $query;
    }

    public function select(string $cmd, array $bindValues = [], array $bindTypes = []): ?BaseResultSet
    {
        Debugger::addQueryExecuted($cmd);
        return $this->selectToDelegateAdapter($cmd, $bindValues, $bindTypes);
    }

    abstract protected function selectToDelegateAdapter(string $cmd, array $bindValues = [], array $bindTypes = []): ?BaseResultSet;

    public function execute(string $cmd, array $bindValues = [], array $bindTypes = []): bool
    {
        Debugger::addQueryExecuted($cmd);
        return $this->executeToDelegateAdapter($cmd, $bindValues, $bindTypes);
    }

    abstract protected function executeToDelegateAdapter(string $cmd, array $bindValues = [], array $bindTypes = []): bool;

    abstract public function opFulltextIndex(array $columns, Placeholder|string $value = Placeholder::placeholder, ?string $columnAlias = null): string;

    abstract public function opDecryptFunction(string $column, string $initializationVectorColumn): string;

    abstract public function fulltextConditionSintax(array $columns, Placeholder|string $value = Placeholder::placeholder, TextSearchMode $textSearchMode = TextSearchMode::inNaturaLanguageMode): string;

    abstract public function lastInsertId(): int;

    abstract public function beginTransaction(): bool;

    abstract public function commitTransaction(): bool;

    abstract public function rollbackTransaction(): bool;

    abstract public function getLastErrorMsg(): string;

    abstract public function getLastErrorCode(): string;
}

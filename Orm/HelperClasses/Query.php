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
 * - Modifica del namespace per l'integrazione nel SismaFramework.
 * - Introduzione della gestione della forte tipizzazione per proprietà, parametri e valori.
 * - Sostituzione delle costanti di classe con enum (PHP 8.1+) per rappresentare parole chiave e operatori SQL.
 * - Aggiunta di metodi e proprietà per supportare clausole SQL aggiuntive o specifiche del framework.
 * - Potenziale rimozione o modifica di metodi non ritenuti necessari nel contesto del SismaFramework.
 * - Aggiornamento della logica di costruzione delle query per riflettere le esigenze del DataMapper implementato.
 * - Modifica alla gestione della clausola order by.
 */

namespace SismaFramework\Orm\HelperClasses;

use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Enumerations\Condition;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\TextSearchMode;
use SismaFramework\Orm\Enumerations\ComparisonOperator;

/**
 * @author Valentino de Lapa
 */
class Query
{

    protected string $table = '';
    protected bool $distinct = false;
    protected array $columns = [];
    protected array $values = [];
    protected array $where = [];
    protected int $offset = 0;
    protected int $limit = 0;
    protected array $group = [];
    protected array $having = [];
    protected array $order = [];
    protected string $variable;
    protected string $value;
    protected bool $closed = false;
    protected string $command = '';
    static protected ?Query $instance = null;
    protected BaseAdapter $adapter;
    private ?Condition $currentCondition = null;

    public function __construct(?BaseAdapter &$adapter = null)
    {
        if ($adapter === null) {
            $this->adapter = &BaseAdapter::getDefault();
        } else {
            $this->adapter = &$adapter;
        }
    }

    public function &getAdapter(): BaseAdapter
    {
        return $this->adapter;
    }

    public function &setCount(string $column, bool $distinct = false): self
    {
        $this->columns = array($this->adapter->opCount($column, $distinct));
        return $this;
    }

    public function &setDistinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function &setColumns(?array $list = null): self
    {
        if ($list === null) {
            $this->setColumn();
        } else {
            $this->columns = $this->adapter->escapeColumns($list);
        }
        return $this;
    }

    public function &setColumn(?string $column = null): self
    {
        if ($column !== null) {
            $this->columns = [$this->adapter->escapeColumn($column)];
        }
        return $this;
    }

    public function &setFulltextIndexColumn(array $columns, Placeholder|string $value = Placeholder::placeholder, ?string $columnAlias = null, bool $append = false): self
    {
        if ($append) {
            $this->initializeColumn();
            $this->columns[] = $this->adapter->opFulltextIndex($columns, $value, $columnAlias);
        } else {
            $this->columns = [$this->adapter->opFulltextIndex($columns, $value, $columnAlias)];
        }
        return $this;
    }

    private function initializeColumn()
    {
        if (count($this->columns) === 0) {
            $this->columns = [$this->adapter->allColumns()];
        }
    }

    public function &setSubqueryColumn(Query $subquery, ?string $columnAlias = null, bool $append = false): self
    {
        if ($append) {
            $this->initializeColumn();
            $this->columns[] = $this->adapter->opSubquery($subquery, $columnAlias);
        } else {
            $this->columns = [$this->adapter->opSubquery($subquery, $columnAlias)];
        }
        return $this;
    }

    public function &appendColumnValue(string $column, Placeholder|string $value = Placeholder::placeholder, bool $foreignKey = false): self
    {
        $this->columns[] = $this->adapter->escapeColumn($column, $foreignKey);
        $this->values[] = $this->adapter->escapeValue($value);
        return $this;
    }

    public function hasColumn(string $column, bool $foreignKey = false): bool
    {
        $parsedColumn = $this->adapter->escapeColumn($column, $foreignKey);
        return in_array($parsedColumn, $this->columns);
    }

    public function &setTable(string $table, ?string $tableAlias = null): self
    {
        $this->table = $this->adapter->escapeTable($table, $tableAlias);
        return $this;
    }

    public function &setOffset(int $offset): self
    {
        $this->offset = intval($offset);
        return $this;
    }

    public function &setLimit(int $limit): self
    {
        $this->limit = intval($limit);
        return $this;
    }

    public function &setOrderBy(?array $list = null): self
    {
        if (is_array($list)) {
            foreach ($list as $columnOrQuery => $Indexing) {
                $this->appendOrderByOption($columnOrQuery, $Indexing);
            }
        }
        return $this;
    }

    public function &appendOrderByOption(string $column, null|string|Indexing $Indexing = null): self
    {
        $parsedColumn = $this->adapter->escapeColumn($column);
        $parsedIndexing = $this->adapter->escapeOrderIndexing($Indexing);
        $this->order[] = $parsedColumn . ' ' . $parsedIndexing;
        return $this;
    }

    public function &appendOrderByCondition(string $column, ComparisonOperator $operator, Placeholder|string|array $value = Placeholder::placeholder, $Indexing = null, bool $foreignKey = false): self
    {
        $escapedColumn = $this->adapter->escapeColumn($column, $foreignKey);
        $escapedValue = $this->adapter->escapeValue($value, $operator);
        $parsedCondiotion = $this->adapter->openBlock() . $escapedColumn . ' ' . $this->adapter->parseComparisonOperator($operator) . ' ' . $escapedValue . $this->adapter->closeBlock();
        $parsedIndexing = $this->adapter->escapeOrderIndexing($Indexing);
        $this->order[] = $parsedCondiotion . ' ' . $parsedIndexing;
        return $this;
    }

    public function &appendOrderBySubquery(Query $query, null|string|Indexing $Indexing = null): self
    {
        $parsedQuery = $this->adapter->openBlock() . $query->getCommandToExecute() . $this->adapter->closeBlock();
        $parsedIndexing = $this->adapter->escapeOrderIndexing($Indexing);
        $this->order[] = $parsedQuery . ' ' . $parsedIndexing;
        return $this;
    }

    public function &setGroupBy(?array $list = null): self
    {
        if ($list !== null) {
            $this->group = $this->adapter->escapeColumns($list);
        }
        return $this;
    }

    public function &setHaving(): self
    {
        $this->having = array();
        $this->currentCondition = Condition::having;
        return $this;
    }

    public function &setWhere(string $condition = ''): self
    {
        $this->where = array();
        if ($condition !== '') {
            $this->where[] = $condition;
        }
        $this->currentCondition = Condition::where;
        return $this;
    }

    public function &appendCondition(string $column, ComparisonOperator $operator, Placeholder|string|array $value = Placeholder::placeholder, bool $foreignKey = false): self
    {
        $escapedColumn = $this->adapter->escapeColumn($column, $foreignKey);
        $escapedValue = $this->adapter->escapeValue($value, $operator);
        if ($this->currentCondition == Condition::where) {
            $this->where[] = $escapedColumn . ' ' . $this->adapter->parseComparisonOperator($operator) . ' ' . $escapedValue;
        }
        if ($this->currentCondition == Condition::having) {
            $this->having[] = $escapedColumn . ' ' . $this->adapter->parseComparisonOperator($operator) . ' ' . $escapedValue;
        }
        return $this;
    }

    public function &appendConditionOnEncryptedColumn(string $column, string $initializationVectorColumn, ComparisonOperator $operator, Placeholder|string|array $value = Placeholder::placeholder): self
    {
        $escapedValue = $this->adapter->escapeValue($value, $operator);
        if ($this->currentCondition == Condition::where) {
            $this->where[] = $this->adapter->opDecryptFunction($column, $initializationVectorColumn) . ' ' . $this->adapter->parseComparisonOperator($operator) . ' ' . $escapedValue;
        }
        if ($this->currentCondition == Condition::having) {
            $this->having[] = $this->adapter->opDecryptFunction($column, $initializationVectorColumn) . ' ' . $this->adapter->parseComparisonOperator($operator) . ' ' . $escapedValue;
        }
        return $this;
    }

    public function &appendFulltextCondition(array $columns, Placeholder|string $value = Placeholder::placeholder, TextSearchMode $textSearchMode = TextSearchMode::inNaturaLanguageMode): self
    {
        $this->where[] = $this->adapter->fulltextConditionSintax($columns, $value, $textSearchMode);
        return $this;
    }

    public function &appendSubqueryCondition(Query $subquery, ComparisonOperator $operator, Placeholder|string|array $value = Placeholder::placeholder): self
    {
        $escapedValue = $this->adapter->escapeValue($value, $operator);
        if ($this->currentCondition == Condition::where) {
            $this->where[] = $this->adapter->opSubquery($subquery) . ' ' . $this->adapter->parseComparisonOperator($operator) . ' ' . $escapedValue;
        }
        if ($this->currentCondition == Condition::having) {
            $this->having[] = $this->adapter->opSubquery($subquery) . ' ' . $this->adapter->parseComparisonOperator($operator) . ' ' . $escapedValue;
        }
        return $this;
    }

    public function &appendOpenBlock(): self
    {
        if ($this->currentCondition == Condition::where) {
            $this->where[] = $this->adapter->openBlock();
        }
        if ($this->currentCondition == Condition::having) {
            $this->having[] = $this->adapter->openBlock();
        }
        return $this;
    }

    public function &appendCloseBlock(): self
    {
        if ($this->currentCondition == Condition::where) {
            $this->where[] = $this->adapter->closeBlock();
        }
        if ($this->currentCondition == Condition::having) {
            $this->having[] = $this->adapter->closeBlock();
        }
        return $this;
    }

    public function &appendAnd(): self
    {
        if ($this->currentCondition == Condition::where) {
            $this->where[] = $this->adapter->opAND();
        }
        if ($this->currentCondition == Condition::having) {
            $this->having[] = $this->adapter->opAND();
        }
        return $this;
    }

    public function &appendOr(): self
    {
        if ($this->currentCondition == Condition::where) {
            $this->where[] = $this->adapter->opOR();
        }
        if ($this->currentCondition == Condition::having) {
            $this->having[] = $this->adapter->opOR();
        }
        return $this;
    }

    public function &appendNot(): self
    {
        if ($this->currentCondition == Condition::where) {
            $this->where[] = $this->adapter->opNOT();
        }
        if ($this->currentCondition == Condition::having) {
            $this->having[] = $this->adapter->opNOT();
        }
        return $this;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function setVariable(string $variable, Placeholder|string $value = Placeholder::placeholder): self
    {
        $this->variable = $this->adapter->escapeColumn($variable);
        $this->value = $this->adapter->escapeValue($value);
        return $this;
    }

    public function &setCommand(string $cmd): self
    {
        $this->command = strval($cmd);
        $this->close();
        return $this;
    }

    public function getCommandToExecute(Statement $cmdType = Statement::select): ?string
    {
        if ($this->closed) {
            switch ($cmdType) {
                case Statement::insert:
                    $this->command = $this->adapter->parseInsert($this->table, $this->columns, $this->values);
                    break;
                case Statement::update:
                    $this->command = $this->adapter->parseUpdate($this->table, $this->columns, $this->values, $this->where);
                    break;
                case Statement::delete:
                    $this->command = $this->adapter->parseDelete($this->table, $this->where);
                    break;
                case Statement::set:
                    $this->command = $this->adapter->parseSet($this->variable, $this->value);
                    break;
                case Statement::select:
                default:
                    $this->command = $this->adapter->parseSelect($this->distinct, $this->columns ?: [$this->adapter->allColumns()], $this->table, $this->where, $this->group, $this->having, $this->order, $this->offset, $this->limit);
                    break;
            }
            return $this->command;
        } else {
            return null;
        }
    }
}

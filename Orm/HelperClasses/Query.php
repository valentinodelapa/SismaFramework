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

namespace SismaFramework\Orm\HelperClasses;

use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Enumerations\Condition;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\TextSearchMode;
use SismaFramework\Orm\HelperClasses\Query;

/**
 *
 * @author Valentino de Lapa
 */
class Query
{

    protected bool $distinct = false;
    protected array $columns = [];
    protected array $tables = [];
    protected array $where = [];
    protected int $offset = 0;
    protected int $limit = 0;
    protected array $group = [];
    protected array $having = [];
    protected array $order = [];
    protected bool $closed = false;
    protected string $command = '';
    static protected ?Query $instance = null;
    protected BaseAdapter $adapter;
    private ?Condition $current_conditions = null;

    public function __construct(?BaseAdapter &$adapter = null)
    {
        $this->reset();
        if ($adapter === null) {
            $this->adapter = &BaseAdapter::getDefault();
        } else {
            $this->adapter = &$adapter;
        }
        $this->setColumn($this->adapter->allColumns());
    }

    protected function reset(): void
    {
        $this->columns = array();
        $this->tables = array();
        $this->where = array();
        $this->offset = 0;
        $this->limit = 0;
        $this->group = array();
        $this->having = array();
        $this->order = array();
        $this->current_conditions = null;
        $this->closed = false;
        $this->command = '';
    }

    public static function create(?BaseAdapter &$adapter = null): self
    {
        if (static::$instance === null) {
            static::$instance = new self($adapter);
        }
        $ret = clone static::$instance;
        return $ret;
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
        if ($column === null) {
            $this->columns = [$this->adapter->allColumns()];
        } else {
            $this->columns = [$this->adapter->escapeColumn($column)];
        }
        return $this;
    }

    public function &setFulltextIndexColumn(array $columns, Keyword|string $value = Keyword::placeholder, ?string $columnAlias = null, bool $append = false): self
    {
        if ($append) {
            $this->columns[] = $this->adapter->opFulltextIndex($columns, $value, $columnAlias);
        } else {
            $this->columns = [$this->adapter->opFulltextIndex($columns, $value, $columnAlias)];
        }
        return $this;
    }

    public function &setSubqueryColumn(Query $subquery, ?string $columnAlias = null, bool $append = false): self
    {
        if ($append) {
            $this->columns[] = $this->adapter->opSubquery($subquery, $columnAlias);
        } else {
            $this->columns = [$this->adapter->opSubquery($subquery, $columnAlias)];
        }
        return $this;
    }

    public function &setTables(array $list): self
    {
        $tables = [];
        foreach ($list as $t) {
            $tables[] = $this->adapter->escapeIdentifier($t);
        }
        $this->tables = $tables;
        return $this;
    }

    public function &setTable(string $table): self
    {
        $tables = [];
        $tables[] = $this->adapter->escapeIdentifier($table);
        $this->tables = $tables;
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
                if($columnOrQuery instanceof Query){
                    $this->appendOrderBySubquery($columnOrQuery, $Indexing);
                }else{
                    $this->appendOrderByOption($columnOrQuery, $Indexing);
                }
            }
        }
        return $this;
    }

    public function &appendOrderByOption(string $column, null|string|Indexing $Indexing = null): self
    {
        $parsedColumn = $this->adapter->escapeColumn($column);
        $parsedIndexing = $this->adapter->escapeOrderIndexing($Indexing);
        $this->order[$parsedColumn] = $parsedIndexing;
        return $this;
    }

    public function &appendOrderBySubquery(Query $query, null|string|Indexing $Indexing = null): self
    {
        $parsedQuery = '(' . $query->getCommandToExecute() . ')';
        $parsedIndexing = $this->adapter->escapeOrderIndexing($Indexing);
        $this->order[$parsedQuery] = $parsedIndexing;
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
        $this->current_conditions = Condition::having;
        return $this;
    }

    public function &setWhere(string $condition = ''): self
    {
        $this->where = array();
        if ($condition !== '') {
            $this->where[] = $condition;
        }
        $this->current_conditions = Condition::where;
        return $this;
    }

    public function &appendCondition(string $column, ComparisonOperator $operator, Keyword|string $value = Keyword::placeholder, bool $foreignKey = false): self
    {
        $escapedColumn = $this->adapter->escapeColumn($column, $foreignKey);
        $escapedValue = $this->adapter->escapeValue($value, $operator);
        if ($this->current_conditions == Condition::where) {
            $this->where[] = $escapedColumn . ' ' . $operator->value . ' ' . $escapedValue;
        }
        if ($this->current_conditions == Condition::having) {
            $this->having[] = $escapedColumn . ' ' . $operator->value . ' ' . $escapedValue;
        }
        return $this;
    }

    public function &appendFulltextCondition(array $columns, Keyword|string|null $value = null): self
    {
        $this->where[] = $this->adapter->fulltextConditionSintax($columns, $value);
        return $this;
    }

    public function &appendSubqueryCondition(Query $subquery, ComparisonOperator $operator, Keyword|string|null $value = null): self
    {
        $escapedValue = $this->adapter->escapeValue($value, $operator);
        if ($this->current_conditions == Condition::where) {
            $this->where[] = $this->adapter->opSubquery($subquery) . ' ' . $operator->value . ' ' . $escapedValue;
        }
        if ($this->current_conditions == Condition::having) {
            $this->having[] = $this->adapter->opSubquery($subquery) . ' ' . $operator->value . ' ' . $escapedValue;
        }
        return $this;
    }

    public function &appendOpenBlock(): self
    {
        if ($this->current_conditions == Condition::where) {
            $this->where[] = $this->adapter->openBlock();
        }
        if ($this->current_conditions == Condition::having) {
            $this->having[] = $this->adapter->openBlock();
        }
        return $this;
    }

    public function &appendCloseBlock(): self
    {
        if ($this->current_conditions == Condition::where) {
            $this->where[] = $this->adapter->closeBlock();
        }
        if ($this->current_conditions == Condition::having) {
            $this->having[] = $this->adapter->closeBlock();
        }
        return $this;
    }

    public function &appendAnd(): self
    {
        if ($this->current_conditions == Condition::where) {
            $this->where[] = $this->adapter->opAND();
        }
        if ($this->current_conditions == Condition::having) {
            $this->having[] = $this->adapter->opAND();
        }
        return $this;
    }

    public function &appendOr(): self
    {
        if ($this->current_conditions == Condition::where) {
            $this->where[] = $this->adapter->opOR();
        }
        if ($this->current_conditions == Condition::having) {
            $this->having[] = $this->adapter->opOR();
        }
        return $this;
    }

    public function &appendNot(): self
    {
        if ($this->current_conditions == Condition::where) {
            $this->where[] = $this->adapter->opNOT();
        }
        if ($this->current_conditions == Condition::having) {
            $this->having[] = $this->adapter->opNOT();
        }
        return $this;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function &setCommand(string $cmd): self
    {
        $this->command = strval($cmd);
        $this->close();
        return $this;
    }

    public function getCommandToExecute(Statement $cmdType = Statement::select, array $extra = []): ?string
    {
        if (!$this->closed) {
            return null;
        }
        switch ($cmdType) {
            case Statement::insert:
                $this->command = $this->adapter->parseInsert($this->tables, $extra['columns'], $extra['values']);
                break;
            case Statement::update:
                $this->command = $this->adapter->parseUpdate($this->tables, $extra['columns'], $extra['values'], $this->where);
                break;
            case Statement::delete:
                $this->command = $this->adapter->parseDelete($this->tables, $this->where);
                break;
            case Statement::select:
            default:
                $this->command = $this->adapter->parseSelect($this->distinct, $this->columns, $this->tables, $this->where, $this->group, $this->having, $this->order, $this->offset, $this->limit);
                break;
        }
        return $this->command;
    }
}

?>

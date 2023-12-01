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

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Orm\Enumerations\AggregationFunction;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Condition;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\LogicalOperator;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\HelperClasses\Query;

/**
 *
 * @author Valentino de Lapa
 */
abstract class BaseAdapter
{

    protected static ?BaseAdapter $adapter = null;
    protected static mixed $connection = null;

    public function __construct(array $options = [])
    {
        $this->connect($options);
    }

    abstract protected function connect(array $options = []): void;

    public static function &getDefault(): ?BaseAdapter
    {
        if (static::$adapter === null) {
            $defaultAdapter = static::create(\Config\DEFAULT_ADAPTER, [
                        'database' => \Config\DATABASE_NAME,
                        'hostname' => \Config\DATABASE_HOST,
                        'password' => \Config\DATABASE_PASSWORD,
                        'port' => \Config\DATABASE_PORT,
                        'username' => \Config\DATABASE_USERNAME,
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

    public function escapeOrderIndexing(null|string|Indexing $order = null): string
    {
        if (is_string($order)) {
            $order = Indexing::tryFrom($order);
        }
        if ($order instanceof Indexing) {
            return $order->value;
        } else {
            return '';
        }
    }

    public function escapeColumns(array $cols): array
    {
        $ret = [];
        foreach ($cols as $col) {
            $ret[] = $this->escapeColumn($col);
        }
        return $ret;
    }

    public function escapeColumn(string $name, bool $foreignKey = false): string
    {
        $arrayName = preg_split('/(?=[A-Z])/', $this->escapeIdentifier(($foreignKey) ? $name . 'Id' : $name));
        array_walk($arrayName, function (&$value) {
            $value = strtolower($value);
        });
        $implodedName = implode('_', $arrayName);
        return $implodedName;
    }

    public function escapeValue(mixed $value, ?ComparisonOperator $operator = null): string
    {
        if ($operator === ComparisonOperator::isNull || $operator === ComparisonOperator::isNotNull) {
            return '';
        } elseif ($operator === ComparisonOperator::in || $operator === ComparisonOperator::notIn) {
            if (!is_array($value)) {
                $value = [$value];
            }
            $vals = [];
            foreach ($value as $v) {
                $vals[] = $this->escapeValue($v);
            }
            return $this->openBlock . implode(',', $vals) . $this->closeBlock;
        } else {
            if (is_array($value)) {
                $val = array_shift($value);
                $value = $val;
            }
            if ($value instanceof Keyword) {
                $stringValue = $value->value;
            } else {
                $stringValue = strval($value);
            }
        }
        return $stringValue;
    }

    public function openBlock(): string
    {
        return Keyword::openBlock->value . ' ';
    }

    public function closeBlock(): string
    {
        return ' ' . Keyword::closeBlock->value;
    }

    public function opAND(): string
    {
        return LogicalOperator::and->value;
    }

    public function opOR(): string
    {
        return LogicalOperator::or->value;
    }

    public function opNOT(): string
    {
        return LogicalOperator::not->value;
    }

    public function opCOUNT(string $column, bool $distinct): string
    {
        if ($column === '') {
            $column = $this->allColumns();
        }
        if ($column !== $this->allColumns()) {
            //$column = $this->escapeIdentifier($column);
            $column = $this->escapeColumn($column);
        }
        return AggregationFunction::count->value . Keyword::openBlock->value . ($distinct ? Keyword::distinct->value . ' ' : '') . $column . Keyword::closeBlock->value . ' as _numrows';
    }

    public function opSubquery(Query $subquery, ?string $columnAlias = null): string
    {
        $column = Keyword::openBlock->value . $subquery->getCommandToExecute() . Keyword::closeBlock->value;
        if ($columnAlias !== null) {
            $column .= ' as ' . $this->escapeColumn($columnAlias);
        }
        return $column;
    }

    public function parseSelect(bool $distinct, array $select, array $from, array $where, array $groupby, array $having, array $orderby, int $offset, int $limit): string
    {
        foreach ($orderby as $k => $v) {
            $orderby[$k] = $k . ' ' . $v;
        }
        $query = Statement::select->value . ' ' .
                ($distinct ? ' ' . Keyword::distinct->value . ' ' : '') .
                implode(',', $select) . ' ' .
                Keyword::from->value . ' ' . implode(',', $from) . ' ' .
                ((count($where) > 0) ? Condition::where->value . ' ' . implode(' ', $where) : '' ) . ' ' .
                ($groupby ? ' ' . Keyword::groupBy->value . ' ' . implode(',', $groupby) . ' ' : '') .
                ($groupby && $having ? Condition::having->value . ' ' . implode(' ', $having) . ' ' : '') .
                (count($orderby) > 0 ? ' ' . Keyword::orderBy->value . ' ' . implode(',', $orderby) . ' ' : '') .
                ($limit > 0 ? ' ' . Keyword::limit->value . ' ' . $limit . ' ' : '') .
                ($offset > 0 ? ' ' . Keyword::offset->value . ' ' . $offset . ' ' : '');
        return $query;
    }

    public function parseInsert(array $table, array $columns = [], array $values = []): string
    {
        $query = Statement::insert->value . ' ' . implode(',', $table) . ' ' .
                Keyword::openBlock->value . implode(',', $columns) . Keyword::closeBlock->value . ' ' . Keyword::insertValue->value . ' ' .
                Keyword::openBlock->value . implode(',', $values) . Keyword::closeBlock->value;
        return $query;
    }

    public function parseUpdate(array $table, array $columns = [], array $values = [], array $where = []): string
    {
        $cmd = [];
        foreach ($columns as $k => $col) {
            $cmd[] = $col . ' = ' . $values[$k];
        }
        $query = Statement::update->value . ' ' . implode(',', $table) . ' ' .
                Keyword::set->value . ' ' . implode(',', $cmd) . ' ' .
                ($where ? ' ' . Condition::where->value . ' ' . implode(' ', $where) : '');
        return $query;
    }

    public function parseDelete(array $from, array $where = []): string
    {
        $query = Statement::delete->value . ' ' . implode(',', $from) . ' ' .
                ($where ? ' ' . Condition::where->value . ' ' . implode(' ', $where) : '');
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

    abstract public function opFulltextIndex(array $columns, Keyword|string|null $value = null, ?string $columnAlias): string;

    abstract public function fulltextConditionSintax(array $columns, Keyword|string|null $value = null): string;

    abstract public function lastInsertId(): int;

    abstract public function beginTransaction(): bool;

    abstract public function commitTransaction(): bool;

    abstract public function rollbackTransaction(): bool;

    abstract public function getLastErrorMsg(): string;

    abstract public function getLastErrorCode(): string;
}

?>

<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Core\ObjectRelationalMapper;

use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmFunction;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmKeyword;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmOperator;
use SismaFramework\Core\ObjectRelationalMapper\ResultSet;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class Adapter
{

    protected static ?Adapter $adapter = null;
    protected static $connection = null;

    public function __construct(array $options = [])
    {
        $this->connect($options);
    }
    
    abstract protected function connect(array $options = []):void;

    public static function &getDefault(): ?Adapter
    {
        return static::$adapter;
    }

    public static function setDefault(Adapter &$adapter): void
    {
        static::$adapter = &$adapter;
    }

    public static function create(string $type, array $options = []): Adapter
    {
        $parsedType = ucwords(strtolower($type));
        $class = \Config\ADAPTER_NAMESPACE . 'Adapter' . $parsedType;
        return new $class($options);
    }

    abstract public function close(): void;

    public function allColumns(): string
    {
        return '*';
    }

    public function escapeIdentifier(string $name): string
    {
        $parsedName = str_replace(array(chr(0), "\n", "\r", "\t", "'", "\""), "", $name);
        return $parsedName;
    }

    public function escapeOrderDirection(?OrmKeyword $order = null): string
    {
        $ok = array(null, OrmKeyword::asc, OrmKeyword::desc);
        if (in_array($order, $ok)) {
            $parsedOrder = $order->value;
        }else{
            $parsedOrder = '';
        }
        return $parsedOrder;
    }

    public function escapeColumns(array $cols): array
    {
        $ret = [];
        if (!is_array($cols)) {
            $cols = array(strval($cols));
        }
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

    public function escapeValue(mixed $value, OrmOperator $operator): string
    {
        //$ret = '';
        if ($operator == OrmOperator::isNull || $operator == OrmOperator::isNotNull) {
            return '';
        }
        if ($operator == OrmOperator::in || $operator == OrmOperator::notIn) {
            if (!is_array($value)) {
                $value = [$value];
            }
            $vals = [];
            foreach ($value as $v) {
                $vals[] = $this->escapeValue($v, '');
            }
            return $this->openBlock . implode(',', $vals) . $this->closeBlock;
        }
        if (is_array($value)) {
            $val = array_shift($value);
            $value = $val;
        }
        if($value instanceof OrmKeyword){
            $stringValue = $value->value;
        }else{
            $stringValue = strval($value);
        }
        return $stringValue;
    }

    public function openBlock(): string
    {
        return OrmKeyword::openBlock->value . ' ';
    }

    public function closeBlock(): string
    {
        return ' ' . OrmKeyword::closeBlock->value;
    }

    public function opAND(): string
    {
        return OrmOperator::and->value;
    }

    public function opOR(): string
    {
        return OrmOperator::or->value;
    }

    public function opNOT(): string
    {
        return OrmOperator::not->value;
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
        return OrmFunction::count->value . OrmKeyword::openBlock->value . ($distinct ? OrmKeyword::distinct->value . ' ' : '') . $column . OrmKeyword::closeBlock->value . ' as _numrows';
    }

    public function parseSelect(bool $distinct, array $select, array $from, array $where, array $groupby, array $having, array $orderby, int $offset, int $limit): string
    {
        foreach ($orderby as $k => $v) {
            $orderby[$k] = $k . ' ' . $v;
        }
        $query = OrmKeyword::select->value . ' ' .
                ($distinct ? ' ' . OrmKeyword::distinct->value . ' ' : '') .
                implode(',', $select) . ' ' .
                OrmKeyword::from->value . ' ' . implode(',', $from) . ' ' .
                ((count($where) > 0) ? OrmKeyword::where->value . ' ' . implode(' ', $where) : '' ) . ' ' .
                ($groupby ? ' ' . OrmKeyword::groupBy->value . ' ' . implode(',', $groupby) . ' ' : '') .
                ($groupby && $having ? OrmKeyword::having->value . ' ' . implode(' ', $having) . ' ' : '') .
                (count($orderby) > 0 ? ' ' . OrmKeyword::orderBy->value . ' ' . implode(',', $orderby) . ' ' : '') .
                ($limit > 0 ? ' ' . OrmKeyword::limit->value . ' ' . $limit . ' ' : '') .
                ($offset > 0 ? ' ' . OrmKeyword::offset->value . ' ' . $offset . ' ' : '');
        return $query;
    }

    public function parseInsert(array $table, array $columns = [], array $values = []): string
    {
        $query = OrmKeyword::insertInto->value . ' ' . implode(',', $table) . ' ' .
                OrmKeyword::openBlock->value . implode(',', $columns) . OrmKeyword::closeBlock->value . ' ' . OrmKeyword::insertValue->value . ' ' .
                OrmKeyword::openBlock->value . implode(',', $values) . OrmKeyword::closeBlock->value;
        return $query;
    }

    public function parseUpdate(array $table, array $columns = [], array $values = [], array $where = []): string
    {
        $cmd = [];
        foreach ($columns as $k => $col) {
            $cmd[] = $col . ' = ' . $values[$k];
        }
        $query = OrmKeyword::update->value . ' ' . implode(',', $table) . ' ' .
                OrmKeyword::set->value . ' ' . implode(',', $cmd) . ' ' .
                ($where ? ' ' . OrmKeyword::where->value . ' ' . implode(' ', $where) : '');
        return $query;
    }

    public function parseDelete(array $from, array $where = []): string
    {
        $query = OrmKeyword::deleteFrom->value . ' ' . implode(',', $from) . ' ' .
                ($where ? ' ' . OrmKeyword::where->value . ' ' . implode(' ', $where) : '');
        return $query;
    }

    public function select(string $cmd, array $bindValues = [], array $bindTypes = []): ?ResultSet
    {
        Debugger::addQueryExecuted($cmd);
        return $this->selectToDelegateAdapter($cmd, $bindValues, $bindTypes);
    }

    abstract protected function selectToDelegateAdapter(string $cmd, array $bindValues = [], array $bindTypes = []): ?ResultSet;

    public function execute(string $cmd, array $bindValues = [], array $bindTypes = []): bool
    {
        Debugger::addQueryExecuted($cmd);
        return $this->executeToDelegateAdapter($cmd, $bindValues, $bindTypes);
    }

    abstract protected function executeToDelegateAdapter(string $cmd, array $bindValues = [], array $bindTypes = []): bool;

    abstract public function lastInsertId(): int;

    abstract public function beginTransaction(): bool;

    abstract public function commitTransaction(): bool;

    abstract public function rollbackTransaction(): bool;

    abstract public function getLastErrorMsg(): string;

    abstract public function getLastErrorCode(): string;

}

?>

<?php

namespace Sisma\Core\ObjectRelationalMapper;

use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmFunction;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmKeyword;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmOperator;
use Sisma\Core\ObjectRelationalMapper\ResultSet;

class Adapter
{
    protected static ?Adapter $adapter = null;
    protected static $connection = null;

    public function __construct(array $options = [])
    {
        if (!Adapter::getDefault()) {
            Adapter::setDefault($this);
        }
    }

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
        $class = \Sisma\Core\ADAPTER_NAMESPACE.'Adapter' . $parsedType;
        return new $class($options);
    }

    public function close(): void
    {
        
    }

    public function allColumns(): string
    {
        return '*';
    }

    public function escapeIdentifier(string $name): string
    {
        $parsedName = str_replace(array(chr(0), "\n", "\r", "\t", "'", "\""), "", $name);
        return $parsedName;
    }

    public function escapeOrderDirection(string $name): string
    {
        $parsedName = strtoupper($name);
        $ok = array('', OrmKeyword::ASC(), OrmKeyword::DESC());
        if (!in_array($parsedName, $ok)) {
            $parsedName = '';
        }
        return $parsedName;
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
        array_walk($arrayName, function(&$value) {
            $value = strtolower($value);
        });
        $implodedName = implode('_', $arrayName);
        return $implodedName;
    }

    public function escapeOperator(string $operator): string
    {
        $uppedOperator = strtoupper($operator);
        if (!in_array($uppedOperator, OrmOperator::toArray())) {
            return ' ' . OrmOperator::EQUAL() . ' ';
        }
        return ' ' . $uppedOperator . ' ';
    }

    /*
     * escape e couple operator+value and return a string representation of the value to use in the query
     * @param array $value
     * @param string $operator
     * @return string
     */

    public function escapeValue($value, string $operator): string
    {
        //$ret = '';
        $escapedOperator = $this->escapeOperator($operator);
        if ($escapedOperator == OrmOperator::IS_NULL() || $escapedOperator == OrmOperator::IS_NOT_NULL()) {
            return '';
        }
        if ($escapedOperator == OrmOperator::IN() || $escapedOperator == OrmOperator::NOT_IN()) {
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
        $stringValue = strval($value);
        return $stringValue;
    }

    public function openBlock(): string
    {
        return OrmKeyword::OPEN_BLOCK().' ';
    }

    public function closeBlock(): string
    {
        return ' '.OrmKeyword::CLOSE_BLOCK();
    }

    public function opAND(): string
    {
        return OrmOperator::AND();
    }

    public function opOR(): string
    {
        return OrmOperator::OR();
    }

    public function opNOT(): string
    {
        return OrmOperator::NOT();
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
        return OrmFunction::COUNT().OrmKeyword::OPEN_BLOCK() . ($distinct ? OrmKeyword::DISTINCT().' ' : '') . $column . OrmKeyword::CLOSE_BLOCK().' as _numrows';
    }

    public function parseSelect(bool $distinct, array $select, array $from, array $where, array $groupby, array $having, array $orderby, int $offset, int $limit): string
    {
        foreach ($orderby as $k => $v) {
            $orderby[$k] = $k . ' ' . $v;
        }
        $query = OrmKeyword::SELECT().' ' .
                ($distinct ? ' '.OrmKeyword::DISTINCT().' ' : '') .
                implode(',', $select) . ' ' .
                OrmKeyword::FROM().' ' . implode(',', $from) . ' ' .
                ((count($where) > 0) ? OrmKeyword::WHERE().' ' . implode(' ', $where) : '' ). ' ' .
                ($groupby ? ' '.OrmKeyword::GROUP_BY().' ' . implode(',', $groupby) . ' ' : '') .
                ($groupby && $having ? OrmKeyword::Having().' ' . implode(' ', $having) . ' ' : '') .
                (count($orderby) > 0 ? ' '.OrmKeyword::ORDER_BY().' ' . implode(',', $orderby) . ' ' : '') .
                ($limit > 0 ? ' '.OrmKeyword::LIMIT().' ' . $limit . ' ' : '') .
                ($offset > 0 ? ' '.OrmKeyword::OFFSET().' ' . $offset . ' ' : '');
        return $query;
    }

    public function parseInsert(array $table, array $columns = [], array $values = []): string
    {
        $query = OrmKeyword::INSERT_INTO().' ' . implode(',', $table) . ' ' .
                OrmKeyword::OPEN_BLOCK() . implode(',', $columns) . OrmKeyword::CLOSE_BLOCK().' '.OrmKeyword::INSERT_VALUES().' ' .
                OrmKeyword::OPEN_BLOCK() . implode(',', $values) . OrmKeyword::CLOSE_BLOCK();
        return $query;
    }

    public function parseUpdate(array $table, array $columns = [], array $values = [], array $where = []): string
    {
        $cmd = [];
        foreach ($columns as $k => $col) {
            $cmd[] = $col . ' = ' . $values[$k];
        }
        $query = OrmKeyword::UPDATE().' ' . implode(',', $table) . ' ' .
                OrmKeyword::SET().' ' . implode(',', $cmd) . ' ' .
                ($where ? ' '.OrmKeyword::WHERE().' ' . implode(' ', $where) : '');
        return $query;
    }

    public function parseDelete(array $from, array $where = []): string
    {
        $query = OrmKeyword::DELETE_FROM().' ' . implode(',', $from) . ' ' .
                ($where ? ' '.OrmKeyword::WHERE().' ' . implode(' ', $where) : '');
        return $query;
    }

    public function select(string $cmd, array $bindValues = [], array $bindTypes = []): ?ResultSet
    {
        return null;
    }

    public function execute(string $cmd, array $bindValues = [], array $bindTypes = []): bool
    {
        return false;
    }

    public function lastInsertId(): int
    {
        return 0;
    }

    public function beginTransaction(): bool
    {
        return true;
    }

    public function commitTransaction(): bool
    {
        return true;
    }

    public function rollbackTransaction(): bool
    {
        return true;
    }

    public function getLastErrorMsg(): string
    {
        return '';
    }

    public function getLastErrorCode(): string
    {
        return -1;
    }

}

?>
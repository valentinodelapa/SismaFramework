<?php

namespace SismaFramework\Core\ObjectRelationalMapper;

use SismaFramework\Core\ObjectRelationalMapper\Adapter;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmKeyword;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmOperator;
use SismaFramework\Core\ObjectRelationalMapper\Query;

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
    protected Adapter $adapter;
    private string $current_conditions = '';

    public function __construct(?Adapter &$adapter = null)
    {
        $this->reset();
        if ($adapter === null) {
            $adapter = Adapter::getDefault();
        }
        $this->adapter = &$adapter;
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
        $this->order = array();
        $this->current_conditions = '';
        $this->closed = false;
        $this->command = '';
    }

    public static function create(?Adapter &$adapter = null): Query
    {
        if (static::$instance === null) {
            static::$instance = new Query($adapter);
        }
        $ret = clone static::$instance;
        return $ret;
    }

    public function &getAdapter(): Adapter
    {
        return $this->adapter;
    }

    public function &setCount(string $column, bool $distinct = false): Query
    {
        $this->columns = array($this->adapter->opCount($column, $distinct));
        return $this;
    }

    public function &setDistinct(bool $distinct = true): Query
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function &setColumns(?array $list = null): Query
    {
        if ($list === null) {
            $this->setColumn();
        } else {
            $this->columns = $this->adapter->escapeColumns($list);
        }
        return $this;
    }

    public function &setColumn(?string $column = null): Query
    {
        if ($column === null) {
            $this->columns = [$this->adapter->allColumns()];
        } else {
            $this->columns = [$this->adapter->escapeColumn($column)];
        }
        return $this;
    }

    /* public function &setTables($list): Query
      {
      if (!is_array($list)) {
      $list = array(strval($list));
      }
      $tables = array();
      foreach ($list as $t) {
      $tables[] = $this->adapter->escapeIdentifier($t);
      }
      $this->tables = $tables;
      return $this;
      } */

    public function &setTable(string $table): Query
    {
        $tables = [];
        $tables[] = $this->adapter->escapeIdentifier($table);
        $this->tables = $tables;
        return $this;
    }

    public function &setOffset(int $offset): Query
    {
        $this->offset = intval($offset);
        return $this;
    }

    public function &setLimit(int $limit): Query
    {
        $this->limit = intval($limit);
        return $this;
    }

    public function &setOrderBy(?array $list = null): Query
    {
        $orders = array();
        if ($list !== null) {
            if (!is_array($list)) {
                $list = [strval($list) => ''];
            }
            foreach ($list as $column => $mode) {
                $orders[$this->adapter->escapeIdentifier($column)] = $this->adapter->escapeOrderDirection($mode);
            }
            $this->order = $orders;
        }
        return $this;
    }

    public function &setGroupBy(?array $list = null): Query
    {
        if ($list !== null) {
            $this->group = $this->adapter->escapeColumns($list);
        }
        return $this;
    }

    public function &setHaving(): Query
    {
        $this->having = array();
        $this->current_conditions = 'having';
        return $this;
    }

    public function &setWhere(string $condition = ''): Query
    {
        $this->where = array();
        if ($condition !== '') {
            $this->where[] = $condition;
        }
        $this->current_conditions = 'where';
        return $this;
    }

    public function &appendCondition(string $column, OrmOperator $operator, OrmKeyword|string|null $value = null, bool $foreignKey = false): Query
    {
        $escapedColumn = $this->adapter->escapeColumn($column, $foreignKey);
        $escapedValue = $this->adapter->escapeValue($value, $operator);
        if ($this->current_conditions == 'where') {
            $this->where[] = $escapedColumn .' '. $operator->value .' '. $escapedValue;
        }
        if ($this->current_conditions == 'having') {
            $this->having[] = $escapedColumn .' '. $operator->value .' '. $escapedValue;
        }
        return $this;
    }

    public function &appendOpenBlock(): Query
    {
        if ($this->current_conditions == 'where') {
            $this->where[] = $this->adapter->openBlock();
        }
        if ($this->current_conditions == 'having') {
            $this->having[] = $this->adapter->openBlock();
        }
        return $this;
    }

    public function &appendCloseBlock(): Query
    {
        if ($this->current_conditions == 'where') {
            $this->where[] = $this->adapter->closeBlock();
        }
        if ($this->current_conditions == 'having') {
            $this->having[] = $this->adapter->closeBlock();
        }
        return $this;
    }

    public function &appendAnd(): Query
    {
        if ($this->current_conditions == 'where') {
            $this->where[] = $this->adapter->opAND();
        }
        if ($this->current_conditions == 'having') {
            $this->having[] = $this->adapter->opAND();
        }
        return $this;
    }

    public function &appendOr(): Query
    {
        if ($this->current_conditions == 'where') {
            $this->where[] = $this->adapter->opOR();
        }
        if ($this->current_conditions == 'having') {
            $this->having[] = $this->adapter->opOR();
        }
        return $this;
    }

    public function &appendNot(): Query
    {
        if ($this->current_conditions == 'where') {
            $this->where[] = $this->adapter->opNOT();
        }
        if ($this->current_conditions == 'having') {
            $this->having[] = $this->adapter->opNOT();
        }
        return $this;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function &setCommand(string $cmd): Query
    {
        $this->command = strval($cmd);
        $this->close();
        return $this;
    }

    public function getCommandToExecute(string $cmdType = 'select', array $extra = []): string
    {
        if (!$this->closed) {
            return null;
        }
        switch ($cmdType) {
            case 'insert':
                $this->command = $this->adapter->parseInsert($this->tables, $extra['columns'], $extra['values']);
                break;
            case 'update':
                $this->command = $this->adapter->parseUpdate($this->tables, $extra['columns'], $extra['values'], $this->where);
                break;
            case 'delete':
                $this->command = $this->adapter->parseDelete($this->tables, $this->where);
                break;
            case 'select':
            default:
                $this->command = $this->adapter->parseSelect($this->distinct, $this->columns, $this->tables, $this->where, $this->group, $this->having, $this->order, $this->offset, $this->limit);
                break;
        }
        return $this->command;
    }

}

?>
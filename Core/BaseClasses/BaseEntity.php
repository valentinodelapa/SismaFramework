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

namespace SismaFramework\Core\BaseClasses;

use SismaFramework\Core\BaseClasses\BaseEnumerator;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\ProprietaryTypes\SismaDateTime;
use SismaFramework\Core\ObjectRelationalMapper\Adapter;
use SismaFramework\Core\ObjectRelationalMapper\Query;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmKeyword;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmOperator;
use SismaFramework\Core\ObjectRelationalMapper\ResultSet;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseEntity
{

    use \SismaFramework\Core\Traits\UnparseValue;

    protected string $tableName = '';
    protected string $primaryKey = 'id';
    protected static ?BaseEntity $instance = null;
    protected static bool $isFirstExecutedEntity = true;
    protected bool $isActiveTransaction = false;
    protected ?Adapter $adapter = null;
    protected array $collectionPropertiesName = [];

    public function __construct(?Adapter &$adapter = null)
    {
        if ($this->tableName === '') {
            $this->tableName = $this->buildTableName();
        }
        if ($adapter === null) {
            $adapter = Adapter::getDefault();
        }
        $this->adapter = &$adapter;
        $this->setPropertyDefaultValue();
    }
    
    abstract protected function setPropertyDefaultValue():void;

    public function isPrimaryKey(string $propertyName): bool
    {
        return ($propertyName === $this->primaryKey);
    }

    protected static function getTableName(): string
    {
        $obj = static::create();
        $name = $obj->buildTableName();
        unset($obj);
        return $name;
    }

    static public function create(): BaseEntity
    {
        $class = get_called_class();
        if ((static::$instance === null) || ((static::$instance instanceof $class) === false)) {
            static::$instance = new $class();
        }
        $ret = clone static::$instance;
        return $ret;
    }

    public function getField(string $name)
    {
        $prop = new \ReflectionProperty(get_class($this), $name);
        if (!$prop->isPublic()) {
            return null;
        }
        unset($prop);
        return $this->$name;
    }

    public function buildTableName(): string
    {
        $class = get_class($this);
        $poppedParts = explode('\\', $class);
        $parts = preg_split('/(?=[A-Z])/', array_pop($poppedParts));
        $tmp = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p != '') {
                $tmp[] = $p;
            }
        }
        return strtolower(implode('_', $tmp));
    }

    static public function initQuery(?Adapter &$adapter = null): Query
    {
        if ($adapter === null) {
            $adapter = Adapter::getDefault();
        }
        $class = get_called_class();
        $name = $class::getTableName();
        $qry = new Query($adapter);
        $qry->setTable($name);
        return $qry;
    }

    public function &getAdapter(): Adapter
    {
        return $this->adapter;
    }
    
    public function save(): bool
    {
        if (($this->primaryKey == '')  || empty($this->{$this->primaryKey})) {
            return $this->insert();
        }else{
            return $this->update();
        }
    }
    
    public function update()
    {
        $cols = $vals = $markers = [];
        $this->parseValues($cols, $vals, $markers);
        $query = new Query($this->adapter);
        $query->setTable($this->tableName);
        $query->setWhere();
        $query->appendCondition($this->primaryKey, OrmOperator::equal, OrmKeyword::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute('update', array('columns' => $cols, 'values' => $markers));
        $vals[] = $this->{$this->primaryKey};
        $this->checkStartTransaction();
        $ok = $this->adapter->execute($cmd, $vals);
        if ($ok){
            $this->saveSismaCollection();
        }
        $this->checkEndTransaction();
        return $ok;
    }

    public function parseValues(array &$cols, array &$vals, array &$markers): void
    {
        foreach ($this as $p_name => $p_val) {
            $prop = new \ReflectionProperty(get_class($this), $p_name);
            if ($prop->isPublic() && $p_name != $this->primaryKey) {
                $markers[] = '?';
                $this->switchValueType($p_name, $prop->getType(), $p_val, $cols, $vals);
            }elseif ($prop->getType()->getName() === SismaCollection::class){
                array_push($this->collectionPropertiesName, $p_name);
            }
        }
    }

    public function switchValueType(string $p_name, \ReflectionType $reflectionType, mixed $p_val, array &$cols, array &$vals): void
    {
        $cols[] = $this->adapter->escapeColumn($p_name, is_subclass_of($reflectionType->getName(), BaseEntity::class));
        if (is_a($p_val, BaseEntity::class)) {
            if (!isset($p_val->id)){
                $p_val->insert();
            }
            $vals[] = $p_val->id;
        } elseif (is_subclass_of ($p_val, \UnitEnum::class)) {
            $vals[] = $p_val->value;
        } elseif ($p_val instanceof SismaDateTime) {
            $vals[] = $p_val->format("Y-m-d H:i:s");
        } else {
            $vals[] = $p_val;
        }
    }
    
    protected function saveSismaCollection():void
    {
        
    }

    public function insert(): bool
    {
        $cols = $vals = $markers = [];
        $this->parseValues($cols, $vals, $markers);
        $query = new Query($this->adapter);
        $query->setTable($this->tableName);
        $query->close();
        $cmd = $query->getCommandToExecute('insert', array('columns' => $cols, 'values' => $markers));
        $this->checkStartTransaction();
        $ok = $this->adapter->execute($cmd, $vals);
        if ($ok) {
            $this->{$this->primaryKey} = $this->adapter->lastInsertId();
            $this->saveSismaCollection();
        }
        $this->checkEndTransaction();
        return $ok;
    }

    private function checkStartTransaction()
    {
        if (self::$isFirstExecutedEntity) {
            $adapterToCall = $this->adapter;
            $adapterToCall->beginTransaction();
            self::$isFirstExecutedEntity = false;
            $this->isActiveTransaction = true;
        }
    }

    private function checkEndTransaction()
    {
        if ($this->isActiveTransaction) {
            $adapterToCall = $this->adapter;
            $adapterToCall->commitTransaction();
            self::$isFirstExecutedEntity = true;
            $this->isActiveTransaction = false;
        }
    }

    public function delete(): bool
    {
        if ($this->primaryKey == '') {
            return false;
        }
        $query = new Query($this->adapter);
        $query->setTable($this->tableName);
        $query->setWhere();
        $query->appendCondition($this->primaryKey, OrmOperator::equal, OrmKeyword::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute('delete');
        $adapterToCall = $query->getAdapter();
        $bindValues = array($this->{$this->primaryKey});
        $ok = $adapterToCall->execute($cmd, $bindValues);
        if ($ok) {
            unset($this->{$this->primaryKey});
        }
        return $ok;
    }

    static public function deleteBatch(?Query $query = null, array $bindValues = [], array $bindTypes = [], ?Adapter &$adapter = null): bool
    {
        if ($query === null) {
            $query = static::initQuery($adapter);
        }
        $query->close();
        $cmd = $query->getCommandToExecute('delete');
        $adapterToCall = $query->getAdapter();
        self::unparseValue($bindValues);
        $ok = $adapterToCall->execute($cmd, $bindValues, $bindTypes);
        return $ok;
    }

    static public function find(?Query $query = null, array $bindValues = [], array $bindTypes = [], ?Adapter &$adapter = null): ResultSet
    {
        if ($query === null) {
            $query = static::initQuery($adapter);
        }
        $query->close();
        $cmd = $query->getCommandToExecute('select');
        $adapterToCall = $query->getAdapter();
        self::unparseValue($bindValues);
        $result = $adapterToCall->select($cmd, $bindValues, $bindTypes);
        if (!$result) {
            return null;
        }
        $result->setReturnType(get_called_class());
        return $result;
    }

    static public function getCount(?Query $query = null, array $bindValues = [], array $bindTypes = [], ?Adapter &$adapter = null): int
    {
        if ($query === null) {
            $query = $query = static::initQuery($adapter);
        } else {
            $query = clone $query;
        }
        $query->setCount('');
        $query->close();
        $cmd = $query->getCommandToExecute('select');
        $adapterToCall = $query->getAdapter();
        self::unparseValue($bindValues);
        $result = $adapterToCall->select($cmd, $bindValues, $bindTypes);
        if (!$result) {
            return 0;
        }
        $data = $result->fetch();
        $result->release();
        unset($result);
        if (!$data) {
            return 0;
        }
        return $data->_numrows;
    }

    static public function findFirst(?Query $query = null, array $bindValues = [], array $bindTypes = [], ?Adapter &$adapter = null): ?BaseEntity
    {
        if ($query === null) {
            $query = $query = static::initQuery($adapter);
        }
        $query->setOffset(0);
        $query->setLimit(1);
        $list = static::find($query, $bindValues, $bindTypes, $adapter);
        if (!$list) {
            return null;
        }
        $ret = null;
        foreach ($list as $x) {
            $ret = $x;
            break;
        }
        return $ret;
    }

}

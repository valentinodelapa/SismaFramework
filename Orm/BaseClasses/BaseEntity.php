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

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\HelperClasses\Cache;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseEntity
{

    use \SismaFramework\Traits\ParseValue;
    use \SismaFramework\Traits\UnparseValue;

    protected string $tableName = '';
    protected string $primaryKey = 'id';
    protected static ?BaseEntity $instance = null;
    protected static bool $isFirstExecutedEntity = true;
    protected bool $isActiveTransaction = false;
    protected ?BaseAdapter $adapter = null;
    protected array $collectionPropertiesName = [];
    protected array $foreignKeyIndexes = [];

    public function __construct(?BaseAdapter &$adapter = null)
    {
        if ($this->tableName === '') {
            $this->tableName = $this->buildTableName();
        }
        if ($adapter === null) {
            $adapter = BaseAdapter::getDefault();
        }
        $this->adapter = &$adapter;
        $this->setPropertyDefaultValue();
    }

    public function __get($name)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->forceForeignKeyPropertySet($name);
            return $this->$name;
        } else {
            throw new InvalidArgumentException($name);
        }
    }

    protected function checkFinalClassProperty($propertyName): bool
    {
        $reflectionProperty = new \ReflectionProperty($this, $propertyName);
        return $reflectionProperty->class === get_class($this);
    }

    protected function forceForeignKeyPropertySet($propertyName): void
    {
        $reflectionProperty = new \ReflectionProperty($this, $propertyName);
        $reflectionTypeName = $reflectionProperty->getType()->getName();
        if (($reflectionProperty->class === get_class($this)) && (isset($this->$propertyName) === false) && isset($this->foreignKeyIndexes[$propertyName]) && is_subclass_of($reflectionTypeName, BaseEntity::class)) {
            $this->$propertyName = $this->parseEntity($reflectionTypeName, $this->foreignKeyIndexes[$propertyName]);
            unset($this->foreignKeyIndexes[$propertyName]);
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name) && $this->checkFinalClassProperty($name)) {
            $this->switchSettingType($name, $value);
        } else {
            throw new InvalidArgumentException();
        }
    }

    private function switchSettingType($name, $value): void
    {
        $reflectionProperty = new \ReflectionProperty($this, $name);
        if (is_subclass_of($reflectionProperty->getType()->getName(), BaseEntity::class) && is_int($value)) {
            $this->foreignKeyIndexes[$name] = $value;
        } else {
            $this->$name = $value;
        }
    }

    public function __isset($name)
    {
        if (property_exists($this, $name)) {
            $this->forceForeignKeyPropertySet($name);
        }
        return isset($this->$name);
    }

    abstract protected function setPropertyDefaultValue(): void;

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
        $prop = new \ReflectionProperty(get_called_class(), $name);
        if ($prop->class !== get_called_class()) {
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

    static public function initQuery(?BaseAdapter &$adapter = null): Query
    {
        if ($adapter === null) {
            $adapter = BaseAdapter::getDefault();
        }
        $class = get_called_class();
        $name = $class::getTableName();
        $qry = new Query($adapter);
        $qry->setTable($name);
        return $qry;
    }

    public function &getAdapter(): BaseAdapter
    {
        return $this->adapter;
    }

    public function save(): bool
    {
        if (($this->primaryKey == '') || empty($this->{$this->primaryKey})) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }

    public function update()
    {
        $cols = $vals = $markers = [];
        $this->parseValues($cols, $vals, $markers);
        $this->parseForeignKeyIndexes($cols, $vals, $markers);
        $query = new Query($this->adapter);
        $query->setTable($this->tableName);
        $query->setWhere();
        $query->appendCondition($this->primaryKey, ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::update, array('columns' => $cols, 'values' => $markers));
        $vals[] = $this->{$this->primaryKey};
        $this->checkStartTransaction();
        $ok = $this->adapter->execute($cmd, $vals);
        if ($ok) {
            $this->saveEntityCollection();
        }
        $this->checkEndTransaction();
        if (\Config\ORM_CACHE) {
            Cache::setEntity($this);
        }
        return $ok;
    }

    public function parseValues(array &$cols, array &$vals, array &$markers): void
    {
        foreach ($this as $p_name => $p_val) {
            $prop = new \ReflectionProperty(get_class($this), $p_name);
            if ($prop->getType()->getName() === SismaCollection::class) {
                array_push($this->collectionPropertiesName, $p_name);
            } elseif (($prop->class === get_called_class()) && ($p_name != $this->primaryKey)) {
                $markers[] = '?';
                $this->switchValueType($p_name, $prop->getType(), $p_val, $cols, $vals);
            }
        }
    }

    public function switchValueType(string $p_name, \ReflectionType $reflectionType, mixed $p_val, array &$cols, array &$vals): void
    {
        $cols[] = $this->adapter->escapeColumn($p_name, is_subclass_of($reflectionType->getName(), BaseEntity::class));
        if (is_a($p_val, BaseEntity::class)) {
            if (!isset($p_val->id)) {
                $p_val->insert();
            }
            $vals[] = $p_val->id;
        } elseif (is_subclass_of($p_val, \UnitEnum::class)) {
            $vals[] = $p_val->value;
        } elseif ($p_val instanceof SismaDateTime) {
            $vals[] = $p_val->format("Y-m-d H:i:s");
        } else {
            $vals[] = $p_val;
        }
    }

    public function parseForeignKeyIndexes(array &$cols, array &$vals, array &$markers): void
    {
        foreach ($this->foreignKeyIndexes as $p_name => $p_val) {
            $markers[] = '?';
            $cols[] = $this->adapter->escapeColumn($p_name, true);
            $vals[] = $p_val;
        }
    }

    protected function saveEntityCollection(): void
    {
        
    }

    public function insert(): bool
    {
        $cols = $vals = $markers = [];
        $this->parseValues($cols, $vals, $markers);
        $this->parseForeignKeyIndexes($cols, $vals, $markers);
        $query = new Query($this->adapter);
        $query->setTable($this->tableName);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::insert, array('columns' => $cols, 'values' => $markers));
        $this->checkStartTransaction();
        $ok = $this->adapter->execute($cmd, $vals);
        if ($ok) {
            $this->{$this->primaryKey} = $this->adapter->lastInsertId();
            $this->saveEntityCollection();
        }
        $this->checkEndTransaction();
        if (\Config\ORM_CACHE) {
            Cache::setEntity($this);
        }
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
        $query->appendCondition($this->primaryKey, ComparisonOperator::equal, Keyword::placeholder);
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::delete);
        $adapterToCall = $query->getAdapter();
        $bindValues = array($this->{$this->primaryKey});
        $ok = $adapterToCall->execute($cmd, $bindValues);
        if ($ok) {
            unset($this->{$this->primaryKey});
        }
        return $ok;
    }

    static public function deleteBatch(?Query $query = null, array $bindValues = [], array $bindTypes = [], ?BaseAdapter &$adapter = null): bool
    {
        if ($query === null) {
            $query = static::initQuery($adapter);
        }
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::delete);
        $adapterToCall = $query->getAdapter();
        self::unparseValue($bindValues);
        $ok = $adapterToCall->execute($cmd, $bindValues, $bindTypes);
        return $ok;
    }

    static public function find(?Query $query = null, array $bindValues = [], array $bindTypes = [], ?BaseAdapter &$adapter = null): BaseResultSet
    {
        if ($query === null) {
            $query = static::initQuery($adapter);
        }
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::select);
        $adapterToCall = $query->getAdapter();
        self::unparseValue($bindValues);
        $result = $adapterToCall->select($cmd, $bindValues, $bindTypes);
        if (!$result) {
            return null;
        }
        $result->setReturnType(get_called_class());
        return $result;
    }

    static public function getCount(?Query $query = null, array $bindValues = [], array $bindTypes = [], ?BaseAdapter &$adapter = null): int
    {
        if ($query === null) {
            $query = $query = static::initQuery($adapter);
        } else {
            $query = clone $query;
        }
        $query->setCount('');
        $query->close();
        $cmd = $query->getCommandToExecute(Statement::select);
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

    static public function findFirst(?Query $query = null, array $bindValues = [], array $bindTypes = [], ?BaseAdapter &$adapter = null): ?BaseEntity
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

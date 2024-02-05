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

namespace SismaFramework\Orm\Adapters;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\Exceptions\AdapterException;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Enumerations\AggregationFunction;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\TextSearchMode;
use SismaFramework\Orm\ResultSets\ResultSetMysql;

/**
 *
 * @author Valentino de Lapa
 */
class AdapterMysql extends BaseAdapter
{

    protected string $backtick = "`";

    public function connect(array $options = []): void
    {
        if (self::$connection === null) {
            $hostname = $options['hostname'] ?? 'localhost';
            $port = $options['port'] ?? null;
            $username = $options['username'] ?? 'root';
            $password = $options['password'] ?? '';
            $database = $options['database'] ?? '';
            $charset = $options['charset'] ?? 'utf8';

            $dsn = 'mysql:' . 'host=' . $hostname . ';' . ($port !== null ? 'port=' . $port . ';' : '') . 'dbname=' . $database . ';' . 'charset=' . $charset;
            self::$connection = new \PDO($dsn, $username, $password, $options);
            if (!self::$connection) {
                self::$connection = null;
                throw new AdapterException('DB: unable to connect');
            }
            self::$connection->exec('SET names ' . $charset);
            if (BaseAdapter::getDefault() === null) {
                BaseAdapter::setDefault($this);
            }
        }
    }

    public function close(): void
    {
        if (self::$connection) {
            unset(self::$connection);
            self::$connection = null;
        }
    }

    protected function translateDataType(DataType $ormType): int|false
    {

        switch ($ormType) {
            case DataType::typeBoolean:
                return \PDO::PARAM_BOOL;
            case DataType::typeNull:
                return \PDO::PARAM_NULL;
            case DataType::typeInteger:
            case DataType::typeEntity:
                return \PDO::PARAM_INT;
            case DataType::typeEnumeration:
            case DataType::typeString:
            case DataType::typeDecimal:
            case DataType::typeDate:
                return \PDO::PARAM_STR;
            case DataType::typeBinary:
                return \PDO::PARAM_LOB;
            case DataType::typeStatement:
                return \PDO::PARAM_STMT;
            case DataType::typeGeneric:
            default:
                return false;
        }
    }

    protected function parseBind(array &$bindValues = [], array &$bindTypes = []): void
    {
        foreach ($bindValues as $key => $value) {
            if (!isset($bindTypes[$key])) {
                $bindTypes[$key] = DataType::typeGeneric;
            }
            if ($bindTypes[$key] === DataType::typeGeneric) {
                $bindTypes[$key] = $this->parseGenericBindType($value);
            }
            $bindTypes[$key] = $this->translateDataType($bindTypes[$key]);
        }
        if (array_key_exists(0, $bindValues)) {
            $this->incrementIndexedArrayKey($bindValues, $bindTypes);
        }
    }

    private function parseGenericBindType(mixed $value): DataType
    {
        if (is_integer($value)) {
            return DataType::typeInteger;
        } elseif (is_float($value)) {
            return DataType::typeDecimal;
        } elseif (is_string($value)) {
            return DataType::typeString;
        } elseif (is_bool($value)) {
            return DataType::typeBoolean;
        } elseif ($value instanceof BaseEntity) {
            return DataType::typeEntity;
        } elseif (is_subclass_of($value, \UnitEnum::class)) {
            return DataType::typeEnumeration;
        } elseif ($value instanceof SismaDateTime) {
            return DataType::typeDate;
        } elseif ($value === null) {
            return DataType::typeNull;
        } else {
            return DataType::typeGeneric;
        }
    }

    private function incrementIndexedArrayKey(array &$bindValues = [], array &$bindTypes = []): void
    {
        $temporanyValues = $temporanyTypes = [];
        foreach ($bindValues as $key => $value) {
            if (is_int($key)) {
                $temporanyValues[$key + 1] = $value;
                $temporanyTypes[$key + 1] = $bindTypes[$key];
            } else {
                $temporanyValues[$key] = $value;
                $temporanyTypes[$key] = $bindTypes[$key];
            }
        }
        $bindValues = $temporanyValues;
        $bindTypes = $temporanyTypes;
    }

    protected function selectToDelegateAdapter(string $cmd, array $bindValues = [], array $bindTypes = []): ?ResultSetMysql
    {
        if (!self::$connection) {
            return null;
        }
        $statement = self::$connection->prepare($cmd, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL));
        $this->parseBind($bindValues, $bindTypes);
        foreach ($bindValues as $key => &$value) {
            if ($bindTypes[$key] !== false) {
                $statement->bindParam($key, $value, $bindTypes[$key]);
            } else {
                $statement->bindParam($key, $value);
            }
        }
        $statement->execute();
        return new ResultSetMysql($statement);
    }

    protected function executeToDelegateAdapter(string $cmd, array $bindValues = [], array $bindTypes = []): bool
    {
        if (!self::$connection) {
            return false;
        }
        $statement = self::$connection->prepare($cmd);
        $this->parseBind($bindValues, $bindTypes);
        foreach ($bindValues as $key => &$value) {
            if ($bindTypes[$key] !== false) {
                $statement->bindParam($key, $value, $bindTypes[$key]);
            } else {
                $statement->bindParam($key, $value);
            }
        }
        if ($statement->execute()) {
            return true;
        } else {
            $errorInfo = $statement->errorInfo();
            Throw new AdapterException($errorInfo[0] . ' - ' . $errorInfo[2] . ' - ' . $cmd, intval($errorInfo[1]));
        }
    }

    public function escapeIdentifier(string $name): string
    {
        if ($name == '*' || preg_match('#^([0-9]+)$#', $name) || preg_match('#^([0-9]+)\.([0-9]+)$#', $name)) {
            return $name;
        }
        $parts = explode('.', $name);
        foreach ($parts as $key => $value) {
            $parts[$key] = $this->backtick . str_replace($this->backtick, "", parent::escapeIdentifier($value)) . $this->backtick;
        }
        $parsedName = implode('.', $parts);
        return $parsedName;
    }

    public function escapeValue(mixed $value, ?ComparisonOperator $operator = null): string
    {
        $value = parent::escapeValue($value, $operator);
        if (!in_array($operator, [ComparisonOperator::in, ComparisonOperator::notIn, ComparisonOperator::isNull, ComparisonOperator::isNotNull])) {
            $placeholder = ($value === Keyword::placeholder->value || preg_match('#^([\?\:])([0-9a-zA-Z]+)$#', $value) || preg_match('#^([\:])([0-9a-zA-Z]+)([\:])$#', $value));
            if ($placeholder) {
                return $value;
            }
            $value = str_replace(array(chr(0)), "", $value);
        }
        return $value;
    }

    public function lastInsertId(): int
    {
        if (!self::$connection) {
            return -1;
        }
        return self::$connection->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        if (!self::$connection) {
            return false;
        }
        return self::$connection->beginTransaction();
    }

    public function commitTransaction(): bool
    {
        if (!self::$connection) {
            return false;
        }
        return self::$connection->commit();
    }

    public function rollbackTransaction(): bool
    {
        if (!self::$connection) {
            return false;
        }
        return self::$connection->rollBack();
    }

    public function getLastErrorMsg(): string
    {
        if (!self::$connection) {
            return '';
        }
        return implode('; ', self::$connection->errorInfo());
    }

    public function getLastErrorCode(): string
    {
        if (!self::$connection) {
            return -1;
        }
        return self::$connection->errorCode();
    }

    public function opFulltextIndex(array $columns, Keyword|string $value = Keyword::placeholder, ?string $columnAlias = null): string
    {
        return $this->fulltextConditionSintax($columns, $value) . ' as ' . ($columnAlias ?? '_relevance');
    }

    public function fulltextConditionSintax(array $columns, Keyword|string $value = Keyword::placeholder): string
    {
        foreach ($columns as &$column) {
            $column = $this->escapeColumn($column);
        }
        $escapedValue = $this->escapeValue($value, ComparisonOperator::against);
        $condition = Keyword::match->value . ' ' . Keyword::openBlock->value . implode(',', $columns) . Keyword::closeBlock->value . ' ' . ComparisonOperator::against->value . ' ' . Keyword::openBlock->value . $escapedValue . ' ' . TextSearchMode::inNaturaLanguageMode->value . Keyword::closeBlock->value;
        return $condition;
    }

    public function opDecryptFunction(string $column, string $initializationVectorColumn): string
    {
        return 'AES_DECRYPT' . $this->openBlock() . $this->opBase64DecodeFunction($column) . ', ' . Keyword::placeholder->value . ', ' . $this->opConvertBlobToHex($initializationVectorColumn) . $this->closeBlock();
    }

    private function opBase64DecodeFunction(string $column): string
    {
        $escapedColumn = $this->escapeColumn($column);
        return 'FROM_BASE64' . $this->openBlock() . $escapedColumn . $this->closeBlock();
    }

    private function opConvertBlobToHex(string $column): string
    {
        $escapedColumn = $this->escapeColumn($column);
        return 'UNHEX' . $this->openBlock() . 'HEX' . $this->openBlock() . $escapedColumn . $this->closeBlock() . $this->closeBlock();
    }
}

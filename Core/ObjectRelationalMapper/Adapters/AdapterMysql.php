<?php

namespace SismaFramework\Core\ObjectRelationalMapper\Adapters;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\ProprietaryTypes\SismaDateTime;
use SismaFramework\Core\Exceptions\AdapterException;
use SismaFramework\Core\ObjectRelationalMapper\Adapter;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmOperator;
use SismaFramework\Core\ObjectRelationalMapper\Enumerations\OrmType;
use SismaFramework\Core\ObjectRelationalMapper\ResultSets\ResultSetMysql;

class AdapterMysql extends Adapter
{

    protected string $backtick = "`";

    public function connect(array $options = []): void
    {
        if (self::$connection === null) {
            $hostname = isset($options['hostname']) ? $options['hostname'] : 'localhost';
            $port = isset($options['port']) ? $options['port'] : null;
            $username = isset($options['username']) ? $options['username'] : 'root';
            $password = isset($options['password']) ? $options['password'] : '';
            $database = isset($options['database']) ? $options['database'] : '';
            $charset = isset($options['charset']) ? $options['charset'] : 'utf8';

            $dsn = 'mysql:' .
                    'host=' . $hostname . ';' .
                    ($port !== null ? 'port=' . $port . ';' : '') .
                    'dbname=' . $database . ';' .
                    'charset=' . $charset;
            unset($options['hostname']);
            unset($options['port']);
            unset($options['username']);
            unset($options['password']);
            unset($options['database']);
            unset($options['charset']);
            self::$connection = new \PDO($dsn, $username, $password, $options);
            if (!self::$connection) {
                self::$connection = null;
                throw new \Exception('DB: unable to connect');
            }
            self::$connection->exec('SET names ' . $charset);
            if (!Adapter::getDefault()) {
                Adapter::setDefault($this);
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

    protected function translateDataType(OrmType $ormType): int
    {

        switch ($ormType) {
            case OrmType::typeBoolean:
                return \PDO::PARAM_BOOL;
            case OrmType::typeNull:
                return \PDO::PARAM_NULL;
            case OrmType::typeInteger:
            case OrmType::typeEntity:
                return \PDO::PARAM_INT;
            case OrmType::typeEnumeration:
            case OrmType::typeString:
            case OrmType::typeDecimal:
            case OrmType::typeDate:
                return \PDO::PARAM_STR;
            case OrmType::typeBinary:
                return \PDO::PARAM_LOB;
            case OrmType::typeStatement:
                return \PDO::PARAM_STMT;
            case OrmType::typeGeneric:
            default:
                return false;
        }
    }

    protected function parseBind(array &$bindValues = [], array &$bindTypes = []): void
    {
        if ($bindValues) {
            $zero = false;
            foreach ($bindValues as $k => $v) {
                if ($k === 0) {
                    $zero = true;
                }
                if (!isset($bindTypes[$k])) {
                    $bindTypes[$k] = OrmType::typeGeneric;
                }
                if ($bindTypes[$k] == OrmType::typeGeneric) {
                    if (is_integer($v)) {
                        $bindTypes[$k] = OrmType::typeInteger;
                    } elseif (is_float($v)) {
                        $bindTypes[$k] = OrmType::typeDecimal;
                    } elseif (is_string($v)) {
                        $bindTypes[$k] = OrmType::typeString;
                    } elseif (is_bool($v)) {
                        $bindTypes[$k] = OrmType::typeBoolean;
                    } elseif ($v instanceof BaseEntity) {
                        $bindTypes[$k] = OrmType::typeEntity;
                    } elseif (is_subclass_of($v, \UnitEnum::class)) {
                        $bindTypes[$k] = OrmType::typeEnumeration;
                    } elseif ($v instanceof SismaDateTime) {
                        $bindTypes[$k] = OrmType::typeDate;
                    } else {
                        $bindTypes[$k] = OrmType::typeGeneric;
                    }
                }
                $bindTypes[$k] = $this->translateDataType($bindTypes[$k]);
            }
            if ($zero) {
                $tmpV = array();
                $tmpK = array();
                foreach ($bindValues as $k => $v) {
                    if (is_int($k)) {
                        $tmpV[$k + 1] = $v;
                        $tmpK[$k + 1] = $bindTypes[$k];
                    } else {
                        $tmpV[$k] = $v;
                        $tmpK[$k] = $bindTypes[$k];
                    }
                }
                $bindValues = $tmpV;
                $bindTypes = $tmpK;
            }
        } else {
            $bindTypes = array();
            $bindValues = array();
        }
    }

    protected function selectToDelegateAdapter(string $cmd, array $bindValues = [], array $bindTypes = []): ?ResultSetMysql
    {
        if (!self::$connection) {
            return null;
        }
        $st = self::$connection->prepare($cmd, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL));
        $this->parseBind($bindValues, $bindTypes);
        foreach ($bindValues as $key => $val) {
            if ($bindTypes[$key] !== false) {
                $st->bindValue($key, $val, $bindTypes[$key]);
                $st->bindParam($key, $bindValues[$key], $bindTypes[$key]);
            } else {
                $st->bindValue($key, $val, $bindTypes[$key]);
                $st->bindParam($key, $bindValues[$key]);
            }
        }
        $st->execute();
        return new ResultSetMysql($st);
    }

    protected function executeToDelegateAdapter(string $cmd, array $bindValues = [], array $bindTypes = []): bool
    {
        if (!self::$connection) {
            return false;
        }
        $st = self::$connection->prepare($cmd);
        $this->parseBind($bindValues, $bindTypes);
        foreach ($bindValues as $key => $val) {
            if ($bindTypes[$key] !== false) {
                $st->bindValue($key, $val, $bindTypes[$key]);
                $st->bindParam($key, $bindValues[$key], $bindTypes[$key]);
            } else {
                $st->bindValue($key, $val, $bindTypes[$key]);
                $st->bindParam($key, $bindValues[$key]);
            }
        }
        if ($st->execute()) {
            return true;
        } else {
            $errorInfo = $st->errorInfo();
            Throw new AdapterException($errorInfo[0] . ' - ' . $errorInfo[2] . ' - ' . $cmd, $errorInfo[1]);
        }
    }

    public function escapeIdentifier(string $name): string
    {
        if ($name == '*' || preg_match('#^([0-9]+)$#', $name) || preg_match('#^([0-9]+)\.([0-9]+)$#', $name)) {
            return $name;
        }
        $parts = explode('.', $name);
        foreach ($parts as $k => $v) {
            //$name = parent::escapeIdentifier($v);
            //$name = str_replace($this->backtick, "", $name);
            //$name = $this->backtick . $name . $this->backtick;
            $parts[$k] = $this->backtick . str_replace($this->backtick, "", parent::escapeIdentifier($v)) . $this->backtick;
        }
        $parsedName = implode('.', $parts);
        return $parsedName;
    }

    public function escapeValue(mixed $value, OrmOperator $operator): string
    {
        $value = parent::escapeValue($value, $operator);
        if (!in_array($operator, [OrmOperator::in, OrmOperator::notIn, OrmOperator::isNull, OrmOperator::isNotNull])) {
            $placeholder = ($value == '?' || preg_match('#^([\?\:])([0-9a-zA-Z]+)$#', $value) || preg_match('#^([\:])([0-9a-zA-Z]+)([\:])$#', $value));
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

}

?>
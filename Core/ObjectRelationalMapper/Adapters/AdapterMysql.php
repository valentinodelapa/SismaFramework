<?php

namespace Sisma\Core\ObjectRelationalMapper\Adapters;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\BaseClasses\BaseEnumerator;
use Sisma\Core\ProprietaryTypes\SismaDateTime;
use Sisma\Core\Exceptions\AdapterException;
use Sisma\Core\ObjectRelationalMapper\Adapter;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmOperator;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmType;
use Sisma\Core\ObjectRelationalMapper\ResultSets\ResultSetMysql;

class AdapterMysql extends Adapter
{

    protected string $backtick = "`";

    public function __construct(array $options = [])
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

    protected function translateDataType(string $type): int
    {

        switch ($type) {
            case OrmType::BOOLEAN():
                return \PDO::PARAM_BOOL;
            case OrmType::NULL():
                return \PDO::PARAM_NULL;
            case OrmType::INTEGER():
            case OrmType::ENTITY():
                return \PDO::PARAM_INT;
            case OrmType::ENUMERATOR():
            case OrmType::STRING():
            case OrmType::DECIMAL():
            case OrmType::DATE():
                return \PDO::PARAM_STR;
            case OrmType::BINARY():
                return \PDO::PARAM_LOB;
            case OrmType::STMT():
                return \PDO::PARAM_STMT;
            case OrmType::GENERIC():
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
                    $bindTypes[$k] = OrmType::GENERIC();
                }
                if ($bindTypes[$k] == OrmType::GENERIC()) {
                    if (is_integer($v)) {
                        $bindTypes[$k] = OrmType::INTEGER();
                    } elseif (is_float($v)) {
                        $bindTypes[$k] = OrmType::DECIMAL();
                    } elseif (is_string($v)) {
                        $bindTypes[$k] = OrmType::STRING();
                    } elseif (is_bool($v)) {
                        $bindTypes[$k] = OrmType::BOOLEAN();
                    } elseif ($v instanceof BaseEntity) {
                        $bindTypes[$k] = OrmType::ENTITY();
                    } elseif ($v instanceof BaseEnumerator) {
                        $bindTypes[$k] = OrmType::ENUMERATOR();
                    } elseif ($v instanceof SismaDateTime) {
                        $bindTypes[$k] = OrmType::DATE();
                    } else {
                        $bindTypes[$k] = OrmType::GENERIC();
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

    public function select(string $cmd, array $bindValues = [], array $bindTypes = []): ?ResultSetMysql
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

    public function execute(string $cmd, array $bindValues = [], array $bindTypes = []): bool
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

    /*
     * escape a couple operator+value and return a string representation of the value to use in the query
     * @param array $value
     * @param string $operator
     * @return string
     */

    public function escapeValue($value, $operator): string
    {
        $operator = $this->escapeOperator($operator);
        $value = parent::escapeValue($value, $operator);
        if (!in_array($operator, array(OrmOperator::IN(), OrmOperator::NOT_IN(), OrmOperator::IS_NULL(), OrmOperator::IS_NOT_NULL()))) {
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
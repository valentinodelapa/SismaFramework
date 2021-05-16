<?php

namespace Sisma\Core\ObjectRelationalMapper;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\BaseClasses\BaseEnumerator;

class ResultSet implements \Iterator
{
    use \Sisma\Core\Traits\BuildPropertyName;
    use \Sisma\Core\Traits\ParseValue;

    protected string $returnType = \stdClass::class;
    protected int $currentRecord = 0;
    protected int $maxRecord = -1;
    //protected $result = null;
/*
    public function __construct(&$result)
    {
        $this->result = &$result;
        $this->currentRecord = 0;
        $this->maxRecord = -1;
    }
*/
    public function numRows(): int
    {
        return 0;
    }

    public function setReturnType(string $type): void
    {
        $this->returnType = strval($type);
    }

    public function release(): void
    {
        $this->maxRecord = -1;
        $this->currentRecord = 0;
    }

    public function fetchNext(): BaseEntity
    {
        $this->currentRecord++;
        return $this->fetch();
    }

    public function fetch(): \stdClass|BaseEntity
    {
        
    }

    public function seek(int $n): BaseEntity
    {
        $n = intval($n);
        if ($n < 0) {
            $n = 0;
        } elseif ($n > $this->maxRecord) {
            $n = $this->maxRecord;
        }
        $this->currentRecord = $n;
    }

    public function current(): BaseEntity
    {
        return $this->fetch();
    }

    public function next(): void
    {
        $this->currentRecord++;
    }

    public function key(): int
    {
        return $this->currentRecord;
    }

    public function rewind(): void
    {
        $this->currentRecord = 0;
    }

    public function valid(): bool
    {
        if ($this->currentRecord >= 0 && $this->currentRecord <= $this->maxRecord) {
            return true;
        } else {
            return false;
        }
    }

    protected function transformResult(&$result): \stdClass|BaseEntity
    {
        if (!$result) {
            return null;
        }
        if ($this->returnType == \stdClass::class) {
            return $result;
        }
        $class = $this->returnType;
        $obj = new $class();
        foreach ($result as $property => $value) {
            $property = static::buildPropertyName($property);
            if (property_exists($obj, $property)) {
                $reflectionProperty = new \ReflectionProperty($class, $property);
                $reflectionType = $reflectionProperty->getType();
                $obj->$property = $this->parseValue($reflectionType, $value);
            }
        }
        return $obj;
    }

}

?>
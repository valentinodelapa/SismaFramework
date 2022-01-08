<?php

namespace SismaFramework\Core\ObjectRelationalMapper\ResultSets;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\ObjectRelationalMapper\Adapter;
use SismaFramework\Core\ObjectRelationalMapper\ResultSet;

class ResultSetMysql extends ResultSet
{
    /*
     * result to use
     * @var \PDOStatement
     */

    protected ?\PDOStatement $result = null;

    public function __construct(\PDOStatement &$result)
    {
        $this->result = &$result;
        $this->maxRecord = $this->result->rowCount() - 1;
        $this->currentRecord = 0;
    }

    public function release(): void
    {
        if ($this->result === null) {
            return;
        }
        parent::release();
        $this->result->closeCursor();
        unset($this->result);
        $this->result = null;
    }

    public function fetch(): \stdClass|BaseEntity
    {
        if (($this->result === null) || ($this->currentRecord > $this->maxRecord)) {
            return null;
        }
        $dbdata = $this->result->fetch(\PDO::FETCH_OBJ, \PDO::FETCH_ORI_ABS, $this->currentRecord);
        return $this->transformResult($dbdata);
    }

}

?>
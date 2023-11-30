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

namespace SismaFramework\Orm\ResultSets;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Core\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\BaseClasses\BaseResultSet;

/**
 *
 * @author Valentino de Lapa
 */
class ResultSetMysql extends BaseResultSet
{

    protected ?\PDOStatement $result = null;

    public function __construct(\PDOStatement &$result)
    {
        $this->result = &$result;
        parent::__construct();
    }

    public function numRows(): int
    {
        return $this->result->rowCount();
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

    public function fetch(): StandardEntity|BaseEntity
    {
        if (($this->result === null) || ($this->currentRecord > $this->maxRecord)) {
            return null;
        }
        $dbdata = $this->result->fetch(\PDO::FETCH_OBJ, \PDO::FETCH_ORI_ABS, $this->currentRecord);
        return $this->transformResult($dbdata);
    }
}

?>

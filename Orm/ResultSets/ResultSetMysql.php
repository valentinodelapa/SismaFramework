<?php

/*
 * Questo file contiene codice derivato dalla libreria SimpleORM
 * (https://github.com/davideairaghi/php) rilasciata sotto licenza Apache License 2.0
 * (fare riferimento alla licenza in third-party-licenses/SimpleOrm/LICENSE).
 *
 * Copyright (c) 2015-present Davide Airaghi.
 *
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
 * 
 * MODIFICHE APPORTATE A QUESTO FILE RISPETTO AL CODICE ORIGINALE DI SIMPLEORM\RESULTSETS\RESULTSETMYSQL:
 * - Modifica del namespace per l'integrazione nel SismaFramework.
 * - Estensione della classe astratta `BaseResultSet` implementata nel framework.
 * - Implementazione dei metodi astratti `next()`, `key()`, `valid()` e `rewind()` definiti in `BaseResultSet` per la gestione specifica dei risultati MySQL.
 * - Potenziale adattamento o estensione della logica di idratazione per gestire specificità di MySQL o delle tue Entities.
 */

namespace SismaFramework\Orm\ResultSets;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
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

    public function fetch(bool $autoNext = true): null|StandardEntity|BaseEntity
    {
        if (($this->result instanceof \PDOStatement) && ($this->currentRecord <= $this->maxRecord)) {
            $dbdata = $this->result->fetch(\PDO::FETCH_OBJ, \PDO::FETCH_ORI_ABS, $this->currentRecord);
            if ($dbdata instanceof \stdClass) {
                if ($autoNext) {
                    $this->next();
                }
                return $this->hydrate($dbdata);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}

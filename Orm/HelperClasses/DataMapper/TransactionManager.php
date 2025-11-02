<?php

/*
 * Questo file contiene codice estratto dalla classe DataMapper,
 * che a sua volta deriva dalla libreria SimpleORM
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
 * MODIFICHE APPORTATE AL CODICE ORIGINALE:
 * - Estrazione (2025): Responsabilità di gestione transazioni estratta da DataMapper in classe @internal separata
 * - Applicazione tipizzazione forte PHP 8.1+
 * - BaseAdapter e ProcessedEntitiesCollection resi parametri opzionali del costruttore con utilizzo dei singleton come default
 * - Gestione stato transazione tramite proprietà statica per controllo annidamento
 * - Integrazione con ProcessedEntitiesCollection per pulizia entità processate al commit
 */

namespace SismaFramework\Orm\HelperClasses\DataMapper;

use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class TransactionManager
{

    private static bool $isActiveTransaction = false;
    private BaseAdapter $adapter;
    private ProcessedEntitiesCollection $processedEntitiesCollection;

    public function __construct(?BaseAdapter $adapter = null, ?ProcessedEntitiesCollection $processedEntitiesCollection = null)
    {
        $this->adapter = $adapter ?? BaseAdapter::getDefault();
        $this->processedEntitiesCollection = $processedEntitiesCollection ?? ProcessedEntitiesCollection::getInstance();
    }

    public function start(): bool
    {
        if (self::$isActiveTransaction === false) {
            if ($this->adapter->beginTransaction()) {
                self::$isActiveTransaction = true;
                return true;
            }
        }
        return false;
    }

    public function commit(bool $checkAnnidation = true): void
    {
        if (self::$isActiveTransaction && $checkAnnidation) {
            if ($this->adapter->commitTransaction()) {
                self::$isActiveTransaction = false;
                $this->processedEntitiesCollection->clear();
            }
        }
    }

    public function rollback(): void
    {
        $this->adapter->rollbackTransaction();
    }
}

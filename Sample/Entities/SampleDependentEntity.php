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

namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Sample\Enumerations\ArticleStatus;

/**
 * Esempio di Entity con Relazioni - Articolo con Autore
 *
 * Questa entity mostra:
 * - Relazione Many-to-One: più articoli possono avere lo stesso autore
 * - Lazy Loading: l'autore viene caricato solo quando accedi a $article->getSampleReferencedEntity()
 * - Come l'ORM gestisce automaticamente le foreign key
 *
 * Naming Convention:
 * - La proprietà 'sampleReferencedEntity' si mappa automaticamente alla colonna 'sample_referenced_entity_id'
 * - L'ORM riconosce il tipo (SampleReferencedEntity) e carica l'entità correlata
 *
 * @author Valentino de Lapa
 */
class SampleDependentEntity extends BaseEntity
{
    protected int $id;

    /** Titolo dell'articolo */
    protected string $title;

    /** Contenuto dell'articolo */
    protected string $content;

    /** Data di creazione */
    protected SismaDateTime $createdAt;

    /** Stato dell'articolo */
    protected ArticleStatus $status;

    /** Numero di visualizzazioni */
    protected int $views = 0;

    /**
     * Relazione Many-to-One con l'Autore
     *
     * Questa proprietà rappresenta una foreign key.
     * L'ORM caricherà automaticamente l'oggetto SampleReferencedEntity quando necessario (Lazy Loading).
     *
     * Nel database, questa proprietà si mappa alla colonna 'sample_referenced_entity_id'
     */
    protected SampleReferencedEntity $sampleReferencedEntity;

    #[\Override]
    protected function setEncryptedProperties(): void
    {
        // Nessuna proprietà crittografata in questo esempio
    }

    #[\Override]
    protected function setPropertyDefaultValue(): void
    {
        // Imposta valori di default
        $this->status = ArticleStatus::DRAFT;
        $this->createdAt = new SismaDateTime();
        $this->views = 0;
    }

    /**
     * Esempio di utilizzo del Lazy Loading:
     *
     * $article = $articleModel->getEntityById(1);
     * // A questo punto l'autore NON è ancora caricato dal database
     *
     * $authorName = $article->getSampleReferencedEntity()->getFullName();
     * // SOLO ORA l'ORM esegue la query per caricare l'autore
     */
}

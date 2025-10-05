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
 * Esempio di Entity base con diverse tipologie di proprietà
 *
 * Questa entity mostra:
 * - Tipi nativi (int, string, float, bool)
 * - Custom types (SismaDateTime)
 * - BackedEnum (ArticleStatus)
 * - Proprietà nullable
 * - Proprietà con valori default
 * - Crittografia di proprietà sensibili
 *
 * @author Valentino de Lapa
 */
class SampleBaseEntity extends BaseEntity
{
    protected int $id;

    /** Titolo dell'articolo */
    protected string $title;

    /** Contenuto dell'articolo (nullable) */
    protected ?string $content = null;

    /** Punteggio/rating dell'articolo (esempio di float) */
    protected float $rating = 0.0;

    /** Flag per articolo in evidenza */
    protected bool $featured = false;

    /** Data di pubblicazione (custom type) */
    protected SismaDateTime $publishedAt;

    /** Stato dell'articolo (BackedEnum) */
    protected ArticleStatus $status;

    /** Note interne sensibili (verranno crittografate) */
    protected ?string $internalNotes = null;

    /**
     * Definisce quali proprietà devono essere crittografate nel database
     * Le proprietà crittografate sono protette con AES-256
     */
    #[\Override]
    protected function setEncryptedProperties(): void
    {
        // Le note interne contengono informazioni sensibili, quindi le crittografiamo
        $this->addEncryptedProperty('internalNotes');
    }

    /**
     * Definisce i valori di default per le proprietà
     * Questi valori vengono impostati quando si crea una nuova entity
     */
    #[\Override]
    protected function setPropertyDefaultValue(): void
    {
        // Imposta lo stato di default a DRAFT per i nuovi articoli
        $this->status = ArticleStatus::DRAFT;

        // Imposta la data di pubblicazione a "ora"
        $this->publishedAt = new SismaDateTime();
    }
}

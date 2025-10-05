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

use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;

/**
 * Esempio di ReferencedEntity - Autore
 *
 * Una ReferencedEntity è un'entità che può essere referenziata da altre entità.
 * In questo caso, l'Autore può essere referenziato da più articoli.
 *
 * Benefici di ReferencedEntity:
 * - Cache automatica delle entità referenziate per ottimizzare le performance
 * - Accesso alle collezioni inverse (es. $author->sampleDependentEntityCollection per ottenere tutti gli articoli)
 * - Metodi magici per gestire le relazioni inverse
 *
 * @author Valentino de Lapa
 */
class SampleReferencedEntity extends ReferencedEntity
{
    protected int $id;

    /** Nome completo dell'autore */
    protected string $fullName;

    /** Email dell'autore (crittografata per privacy) */
    protected string $email;

    /** Biografia dell'autore */
    protected ?string $bio = null;

    /** Flag per autore verificato */
    protected bool $verified = false;

    /**
     * Definisce le proprietà da crittografare
     */
    #[\Override]
    protected function setEncryptedProperties(): void
    {
        // L'email è un dato personale sensibile, quindi la crittografiamo
        $this->addEncryptedProperty('email');
    }

    /**
     * Definisce i valori di default
     */
    #[\Override]
    protected function setPropertyDefaultValue(): void
    {
        // I nuovi autori non sono verificati di default
        $this->verified = false;
    }

    /**
     * Esempio di property magica per accedere agli articoli di questo autore
     *
     * Grazie a ReferencedEntity, puoi accedere a:
     * - $author->sampleDependentEntityCollection : array di tutti gli articoli
     * - $author->countSampleDependentEntityCollection() : conteggio articoli
     * - $author->addSampleDependentEntity($article) : aggiungi articolo
     */
}

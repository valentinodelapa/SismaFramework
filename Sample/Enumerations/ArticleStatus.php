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

namespace SismaFramework\Sample\Enumerations;

/**
 * Enumeration che rappresenta lo stato di un articolo
 *
 * Questo è un esempio di BackedEnum che può essere usato nelle entità.
 * L'ORM salverà automaticamente il valore (D, P, A) nel database.
 *
 * @author Valentino de Lapa
 */
enum ArticleStatus: string
{
    use \SismaFramework\Core\Traits\SelectableEnumeration;

    case DRAFT = 'D';
    case PUBLISHED = 'P';
    case ARCHIVED = 'A';

    /**
     * Restituisce una label leggibile per l'enum
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Bozza',
            self::PUBLISHED => 'Pubblicato',
            self::ARCHIVED => 'Archiviato',
        };
    }

    /**
     * Verifica se l'articolo è visibile pubblicamente
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLISHED;
    }
}

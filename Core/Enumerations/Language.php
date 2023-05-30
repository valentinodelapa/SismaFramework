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

namespace SismaFramework\Core\Enumerations;

/**
 *
 * @author Valentino de Lapa
 */
enum Language: string
{

    use \SismaFramework\Core\Traits\SelectableEnumeration;

    case italian = 'it_IT';
    case americanEnglish = 'en_US';
    case spanish = 'es_ES';
    case german = 'de_DE';

    public function getFriendlyLabel(self $language): string
    {
        return match ($this) {
            self::italian => match ($language) {
                Language::italian => "Italiano",
                Language::americanEnglish => "Italian",
                Language::spanish => "Italiano",
                Language::german => "Italienisch",
            },
            self::americanEnglish => match ($language) {
                Language::italian => "Inglese americano",
                Language::americanEnglish => "American english",
                Language::spanish => "Inglés americano",
                Language::german => "Amerikanisches englisch",
            },
            self::spanish => match ($language) {
                Language::italian => "Spagnolo",
                Language::americanEnglish => "Spanish",
                Language::spanish => "Español",
                Language::german => "Spanisch",
            },
            self::german => match ($language) {
                Language::italian => "Tedesco",
                Language::americanEnglish => "German",
                Language::spanish => "Alemán",
                Language::german => "Deutsch",
            },
        };
    }

}

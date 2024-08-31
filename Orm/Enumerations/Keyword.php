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

namespace SismaFramework\Orm\Enumerations;

/**
 *
 * @author Valentino de Lapa
 */
enum Keyword
{
    use \SismaFramework\Orm\Traits\OrmKeyword;
    
    case as;
    case insertValue;
    case set;
    case from;
    case distinct;
    case join;
    case match;
    case orderBy;
    case groupBy;
    case limit;
    case offset;
    case placeholder;
    case openBlock;
    case closeBlock;

    #[\Override]
    public function getAdapterVersion(AdapterType $adapterType): string
    {
        return match ($this) {
            self::as => match ($adapterType) {
                AdapterType::mysql => 'AS',
            },
            self::insertValue => match ($adapterType) {
                AdapterType::mysql => 'VALUES',
            },
            self::set => match ($adapterType) {
                AdapterType::mysql => 'SET',
            },
            self::from => match ($adapterType) {
                AdapterType::mysql => 'FROM',
            },
            self::distinct => match ($adapterType) {
                AdapterType::mysql => 'DISTINCT',
            },
            self::join => match ($adapterType) {
                AdapterType::mysql => 'JOIN',
            },
            self::match => match ($adapterType) {
                AdapterType::mysql => 'MATCH',
            },
            self::orderBy => match ($adapterType) {
                AdapterType::mysql => 'ORDER BY',
            },
            self::groupBy => match ($adapterType) {
                AdapterType::mysql => 'GROUP_BY',
            },
            self::limit => match ($adapterType) {
                AdapterType::mysql => 'LIMIT',
            },
            self::offset => match ($adapterType) {
                AdapterType::mysql => 'OFFSET',
            },
            self::placeholder => match ($adapterType) {
                AdapterType::mysql => '?',
            },
            self::openBlock => match ($adapterType) {
                AdapterType::mysql => '(',
            },
            self::closeBlock => match ($adapterType) {
                AdapterType::mysql => ')',
            },
        };
    }

}

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
enum ComparisonOperator
{

    use \SismaFramework\Orm\Traits\OrmKeyword;

    case against;
    case equal;
    case notEqual;
    case greater;
    case less;
    case greaterOrEqual;
    case lessOrEqual;
    case in;
    case notIn;
    case like;
    case notLike;
    case isNull;
    case isNotNull;

    #[\Override]
    public function getAdapterVersion(AdapterType $adapterType): string
    {
        return match ($this) {
            self::against => match ($adapterType) {
                AdapterType::mysql => 'AGAINST',
            },
            self::equal => match ($adapterType) {
                AdapterType::mysql => '=',
            },
            self::notEqual => match ($adapterType) {
                AdapterType::mysql => '<>',
            },
            self::greater => match ($adapterType) {
                AdapterType::mysql => '>',
            },
            self::less => match ($adapterType) {
                AdapterType::mysql => '<',
            },
            self::greaterOrEqual => match ($adapterType) {
                AdapterType::mysql => '>=',
            },
            self::lessOrEqual => match ($adapterType) {
                AdapterType::mysql => '<=',
            },
            self::in => match ($adapterType) {
                AdapterType::mysql => 'IN',
            },
            self::notIn => match ($adapterType) {
                AdapterType::mysql => 'NOT IN',
            },
            self::like => match ($adapterType) {
                AdapterType::mysql => 'LIKE',
            },
            self::notLike => match ($adapterType) {
                AdapterType::mysql => 'NOT LIKE',
            },
            self::isNull => match ($adapterType) {
                AdapterType::mysql => 'IS NULL',
            },
            self::isNotNull => match ($adapterType) {
                AdapterType::mysql => 'IS NOT NULL',
            },
        };
    }
}

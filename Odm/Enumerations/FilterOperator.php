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

namespace SismaFramework\Odm\Enumerations;

/**
 * @author Valentino de Lapa
 */
enum FilterOperator
{
    use \SismaFramework\Odm\Traits\OdmKeyword;

    case equal;
    case notEqual;
    case greater;
    case greaterOrEqual;
    case less;
    case lessOrEqual;
    case in;
    case notIn;
    case like;
    case notLike;
    case isNull;
    case isNotNull;

    #[\Override]
    public function getAdapterVersion(OdmAdapterType $adapterType): string
    {
        return match ($this) {
            self::equal => match ($adapterType) {
                OdmAdapterType::mongodb => '$eq',
            },
            self::notEqual => match ($adapterType) {
                OdmAdapterType::mongodb => '$ne',
            },
            self::greater => match ($adapterType) {
                OdmAdapterType::mongodb => '$gt',
            },
            self::greaterOrEqual => match ($adapterType) {
                OdmAdapterType::mongodb => '$gte',
            },
            self::less => match ($adapterType) {
                OdmAdapterType::mongodb => '$lt',
            },
            self::lessOrEqual => match ($adapterType) {
                OdmAdapterType::mongodb => '$lte',
            },
            self::in => match ($adapterType) {
                OdmAdapterType::mongodb => '$in',
            },
            self::notIn => match ($adapterType) {
                OdmAdapterType::mongodb => '$nin',
            },
            self::like => match ($adapterType) {
                OdmAdapterType::mongodb => '$regex',
            },
            self::notLike => match ($adapterType) {
                OdmAdapterType::mongodb => '$not',
            },
            self::isNull => match ($adapterType) {
                OdmAdapterType::mongodb => '$eq',
            },
            self::isNotNull => match ($adapterType) {
                OdmAdapterType::mongodb => '$ne',
            },
        };
    }
}

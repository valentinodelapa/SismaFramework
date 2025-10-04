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

namespace SismaFramework\Orm\CustomTypes;

use SismaFramework\Orm\Interfaces\CustomDateTimeInterface;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SismaTime extends \DateTimeImmutable implements CustomDateTimeInterface
{
    private const REFERENCE_DATE = '1970-01-01';

    public static function createFromStandardTimeFormat(string $time): self
    {
        return new self(self::REFERENCE_DATE . ' ' . $time);
    }

    public function formatToStandardTimeFormat(): string
    {
        return $this->format('H:i:s');
    }

    public function equals(CustomDateTimeInterface $other): bool
    {
        return $this->format('H:i:s') === $other->format('H:i:s');
    }
}

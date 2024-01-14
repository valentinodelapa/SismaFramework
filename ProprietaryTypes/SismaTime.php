<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\ProprietaryTypes;

use SismaFramework\ProprietaryTypes\Interfaces\ProprietaryTypeInterface;
use SismaFramework\ProprietaryTypes\Interfaces\ProprietaryDateTimeEcosystem;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SismaTime extends \DateInterval implements ProprietaryTypeInterface, ProprietaryDateTimeEcosystem
{

    public static function createFromStandardTimeFormat(string $time): self
    {
        $timeParts = explode(':', $time);
        return new SismaTime('PT' . intval($timeParts[0]) . 'H' . intval($timeParts[1]) . 'M' . intval($timeParts[2]) . 'S');
    }

    public function formatToStandardTimeFormat(): string
    {
        return $this->format('%H:%I:%S');
    }

    public function equals(ProprietaryDateTimeEcosystem $other):bool
    {
        return $this->y === $other->y &&
                $this->m === $other->m &&
                $this->d === $other->d &&
                $this->h === $other->h &&
                $this->i === $other->i &&
                $this->s === $other->s &&
                $this->f === $other->f &&
                $this->invert === $other->invert &&
                $this->days === $other->days;
    }
}

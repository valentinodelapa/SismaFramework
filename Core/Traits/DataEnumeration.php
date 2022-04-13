<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Core\Traits;

use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\Exceptions\EnumerationException;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
trait DataEnumeration
{

    abstract private function setAdditionalData(): null|int|string|array|\UnitEnum;
    
    abstract private function setFunctionalData() :null|int|string|array|\UnitEnum;

    public function getAdditionalDataField(int|string|\UnitEnum ...$offsets): null|int|string|array|\UnitEnum
    {
        $field = $this->setAdditionalData();
        foreach ($offsets as $offset) {
            if (isset($field[$offset])) {
                $field = $field[$offset];
            } else {
                throw new EnumerationException();
            }
        }
        return $field;
    }

    public function getFunctionalDataField(int|string|\UnitEnum ...$offsets): null|int|string|array|\UnitEnum
    {
        $field = $this->setFunctionalData();
        foreach ($offsets as $offset) {
            if (isset($field[$offset])) {
                $field = $field[$offset];
            } else {
                throw new EnumerationException();
            }
        }
        return $field;
    }
    
    public static function getChoiceFromEnumerations(mixed $labelOffset = null): array
    {
        $choice = [];
        foreach (self::cases() as $value) {
            $choiceKey = ($labelOffset === null) ? $value->getAdditionalDataField() : $value->getAdditionalDataField($labelOffset);
            $choice[$choiceKey] = $value->value;
        }
        return $choice;
    }

}

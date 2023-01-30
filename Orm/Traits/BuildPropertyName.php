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

namespace SismaFramework\Orm\Traits;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
trait BuildPropertyName
{

    protected static function buildPropertyName(string $columnName): string
    {
        $columnName = (substr($columnName, -3, 3) == '_id') ? substr($columnName, 0, -3) : $columnName;
        $columnName = str_replace(' ', '', ucwords(str_replace('_', ' ', $columnName)));
        $columnName = lcfirst($columnName);
        return $columnName;
    }

    public static function buildColumnName(string $propertyName): string
    {
        $propertyName = preg_split('/(?=[A-Z])/', $propertyName);
        array_walk($propertyName, function (&$value) {
            $value = strtolower($value);
        });
        $propertyName = implode('_', $propertyName);
        return $propertyName;
    }

}

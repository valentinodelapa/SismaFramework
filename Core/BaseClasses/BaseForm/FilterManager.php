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

namespace SismaFramework\Core\BaseClasses\BaseForm;

use SismaFramework\Core\Enumerations\FilterType;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class FilterManager
{

    private array $filterFieldsMode = [];

    public function addFilterFieldMode(string $propertyName, FilterType $filterType, array $parameters = [], bool $allowNull = false): void
    {
        $this->filterFieldsMode[$propertyName] = [
            'filterType' => $filterType,
            'parameters' => $parameters,
            'allowNull' => $allowNull,
        ];
    }

    public function hasFilter(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->filterFieldsMode);
    }

    public function getFilterConfig(string $propertyName): array
    {
        return $this->filterFieldsMode[$propertyName] ?? [];
    }

    public function applyFilter(string $propertyName, mixed $value): bool
    {
        if (!$this->hasFilter($propertyName)) {
            return true;
        }

        $config = $this->filterFieldsMode[$propertyName];
        return $config['filterType']->applyFilter($value, $config['parameters']);
    }

    public function isNullable(string $propertyName): bool
    {
        if (!$this->hasFilter($propertyName)) {
            return false;
        }

        return $this->filterFieldsMode[$propertyName]['allowNull'];
    }

    public function getAllFilteredPropertyNames(): array
    {
        return array_keys($this->filterFieldsMode);
    }
}

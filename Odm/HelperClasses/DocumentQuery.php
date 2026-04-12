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

namespace SismaFramework\Odm\HelperClasses;

use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\LogicalOperator;
use SismaFramework\Odm\Enumerations\OdmIndexing;

/**
 * @author Valentino de Lapa
 */
class DocumentQuery
{
    private array $conditions = [];
    private array $sort = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    public function where(string $field, FilterOperator $operator, mixed $value = null): static
    {
        $this->conditions = [];
        $this->conditions[] = $this->buildConditionNode($field, $operator, $value);
        return $this;
    }

    public function andWhere(string $field, FilterOperator $operator, mixed $value = null): static
    {
        $this->conditions[] = ['type' => 'logical_separator', 'operator' => LogicalOperator::and];
        $this->conditions[] = $this->buildConditionNode($field, $operator, $value);
        return $this;
    }

    public function orWhere(string $field, FilterOperator $operator, mixed $value = null): static
    {
        $this->conditions[] = ['type' => 'logical_separator', 'operator' => LogicalOperator::or];
        $this->conditions[] = $this->buildConditionNode($field, $operator, $value);
        return $this;
    }

    public function orderBy(string $field, OdmIndexing $direction = OdmIndexing::asc): static
    {
        $this->sort[$field] = $direction;
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;
        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getSort(): array
    {
        return $this->sort;
    }

    public function getLimit(): ?int
    {
        return $this->limitValue;
    }

    public function getOffset(): ?int
    {
        return $this->offsetValue;
    }

    private function buildConditionNode(string $field, FilterOperator $operator, mixed $value): array
    {
        return [
            'type'     => 'condition',
            'field'    => $field,
            'operator' => $operator,
            'value'    => $value,
        ];
    }
}

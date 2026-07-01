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

namespace SismaFramework\Odm\ResultSets;

use SismaFramework\Odm\BaseClasses\BaseResultSet;

/**
 * @author Valentino de Lapa
 */
class ResultSetMongodb extends BaseResultSet
{
    private array $rows = [];
    private int $pointer = 0;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    #[\Override]
    protected function rewindCursor(): void
    {
        $this->pointer = 0;
    }

    #[\Override]
    protected function fetchNextRaw(): ?array
    {
        if ($this->pointer >= count($this->rows)) {
            return null;
        }
        return $this->rows[$this->pointer++];
    }

    #[\Override]
    public function numRows(): int
    {
        return count($this->rows);
    }

    #[\Override]
    public function release(): void
    {
        $this->rows = [];
        $this->pointer = 0;
    }
}

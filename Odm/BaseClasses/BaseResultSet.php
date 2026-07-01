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

namespace SismaFramework\Odm\BaseClasses;

/**
 * @author Valentino de Lapa
 */
abstract class BaseResultSet implements \Iterator
{
    private int $position = 0;
    private ?BaseDocument $current = null;
    private bool $valid = true;
    private string $returnType;

    public function setReturnType(string $documentClass): void
    {
        $this->returnType = $documentClass;
    }

    public function rewind(): void
    {
        $this->position = 0;
        $this->rewindCursor();
        $this->advance();
    }

    public function current(): ?BaseDocument
    {
        return $this->current;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
        $this->advance();
    }

    public function valid(): bool
    {
        return $this->valid;
    }

    private function advance(): void
    {
        $raw = $this->fetchNextRaw();
        if ($raw === null) {
            $this->valid = false;
            $this->current = null;
            return;
        }
        $document = new $this->returnType();
        $document->hydrate($raw);
        $this->current = $document;
    }

    abstract protected function rewindCursor(): void;

    abstract protected function fetchNextRaw(): ?array;

    abstract public function numRows(): int;

    abstract public function release(): void;
}

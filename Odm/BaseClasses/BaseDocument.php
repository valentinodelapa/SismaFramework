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
abstract class BaseDocument
{
    public bool $modified = false;
    private array $data = [];

    public function __construct()
    {
        $this->setPropertyDefaultValue();
    }

    public function hydrate(array $data): void
    {
        $this->data = $data;
        $this->modified = false;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        if (!array_key_exists($name, $this->data) || $this->data[$name] !== $value) {
            $this->modified = true;
        }
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __unset(string $name): void
    {
        if (array_key_exists($name, $this->data)) {
            $this->modified = true;
            unset($this->data[$name]);
        }
    }

    public function toArray(): array
    {
        return $this->data;
    }

    abstract public function getCollectionName(): string;

    abstract protected function setPropertyDefaultValue(): void;
}

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

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Odm\Enumerations\AdapterType;
use SismaFramework\Odm\Exceptions\AdapterException;
use SismaFramework\Odm\HelperClasses\DocumentQuery;

/**
 * @author Valentino de Lapa
 */
abstract class BaseAdapter
{
    protected static ?BaseAdapter $adapter = null;
    protected static mixed $connection = null;
    protected bool $isConnected = false;

    public static function setDefault(BaseAdapter $adapter): void
    {
        static::$adapter = $adapter;
    }

    public static function getDefault(?Config $config = null): static
    {
        if (static::$adapter === null) {
            $config ??= Config::getInstance();
            $adapterClass = $config->defaultOdmAdapterType->getAdapterClass();
            static::$adapter = new $adapterClass();
        }
        return static::$adapter;
    }

    final public function ensureConnected(): void
    {
        if (!$this->isConnected) {
            $this->connect();
            $this->isConnected = true;
        }
    }

    abstract protected function connect(): void;

    abstract public function close(): void;

    abstract public function getAdapterType(): AdapterType;

    abstract public function compileQuery(DocumentQuery $query): mixed;

    abstract public function find(string $collection, DocumentQuery $query): BaseResultSet;

    abstract public function insert(string $collection, array $data): string;

    abstract public function update(string $collection, string $id, array $data): void;

    abstract public function delete(string $collection, string $id): void;

    abstract public function count(string $collection, DocumentQuery $query): int;

    abstract public function getLastErrorMsg(): string;

    abstract public function getLastErrorCode(): int|string;
}

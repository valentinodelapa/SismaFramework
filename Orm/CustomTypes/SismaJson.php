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

use SismaFramework\Orm\Interfaces\CustomTypeInterface;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SismaJson implements CustomTypeInterface, \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{

    private const ENCODE_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    public function __construct(private readonly array $data = [])
    {
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($data)) {
            return new self($data);
        } else {
            throw new \JsonException('JSON value is not an object or an array: ' . $json);
        }
    }

    public function toJson(): string
    {
        return json_encode($this->data, self::ENCODE_FLAGS);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function has(string|int $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string|int $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    public function with(string|int $key, mixed $value): self
    {
        $data = $this->data;
        $data[$key] = $value;
        return new self($data);
    }

    public function without(string|int $key): self
    {
        $data = $this->data;
        unset($data[$key]);
        return new self($data);
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException(self::class . ' is immutable: use with()');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException(self::class . ' is immutable: use without()');
    }

    #[\Override]
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }

    #[\Override]
    public function count(): int
    {
        return count($this->data);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    #[\Override]
    public function equals(CustomTypeInterface $other): bool
    {
        return ($other instanceof self) && (self::normalize($this->data) === self::normalize($other->toArray()));
    }

    private static function normalize(array $data): array
    {
        if (array_is_list($data) === false) {
            ksort($data, SORT_STRING);
        }
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = self::normalize($value);
            }
        }
        return $data;
    }
}

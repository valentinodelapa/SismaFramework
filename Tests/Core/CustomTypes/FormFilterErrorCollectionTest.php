<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\CustomTypes;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\CustomTypes\FormFilterError;
use SismaFramework\Core\CustomTypes\FormFilterErrorCollection;

/**
 * @author Valentino de Lapa
 */
class FormFilterErrorCollectionTest extends TestCase
{

    public function testExtendsArrayObject()
    {
        $collection = new FormFilterErrorCollection();
        $this->assertInstanceOf(\ArrayObject::class, $collection);
    }

    public function testOffsetGetWithExistingKey()
    {
        $collection = new FormFilterErrorCollection();
        $error = new FormFilterError();
        $collection['test'] = $error;

        $result = $collection['test'];
        $this->assertSame($error, $result);
    }

    public function testOffsetGetWithNonExistingKey()
    {
        $collection = new FormFilterErrorCollection();

        $result = $collection['nonexistent'];
        $this->assertInstanceOf(FormFilterError::class, $result);
    }

    public function testOffsetGetCreatesNewFormFilterError()
    {
        $collection = new FormFilterErrorCollection();

        $result1 = $collection['key1'];
        $result2 = $collection['key2'];

        $this->assertInstanceOf(FormFilterError::class, $result1);
        $this->assertInstanceOf(FormFilterError::class, $result2);
        $this->assertNotSame($result1, $result2);
    }

    public function testMultipleCallsToSameKeyCreateMultipleInstances()
    {
        $collection = new FormFilterErrorCollection();

        $result1 = $collection['same_key'];
        $result2 = $collection['same_key'];

        // Each call to a non-existing key creates a new FormFilterError
        $this->assertInstanceOf(FormFilterError::class, $result1);
        $this->assertInstanceOf(FormFilterError::class, $result2);
        $this->assertNotSame($result1, $result2);
    }

    public function testOffsetSetAndGet()
    {
        $collection = new FormFilterErrorCollection();
        $error = new FormFilterError();

        $collection['field'] = $error;
        $this->assertTrue($collection->offsetExists('field'));
        $this->assertSame($error, $collection['field']);
    }

    public function testArrayAccessInterface()
    {
        $collection = new FormFilterErrorCollection();
        $error1 = new FormFilterError();
        $error2 = new FormFilterError();

        // Test setting multiple errors
        $collection['field1'] = $error1;
        $collection['field2'] = $error2;

        $this->assertSame($error1, $collection['field1']);
        $this->assertSame($error2, $collection['field2']);
        $this->assertCount(2, $collection);
    }

    public function testUnsetBehavior()
    {
        $collection = new FormFilterErrorCollection();
        $error = new FormFilterError();

        $collection['field'] = $error;
        $this->assertTrue($collection->offsetExists('field'));

        unset($collection['field']);
        $this->assertFalse($collection->offsetExists('field'));

        // After unset, accessing should create a new FormFilterError
        $newError = $collection['field'];
        $this->assertInstanceOf(FormFilterError::class, $newError);
        $this->assertNotSame($error, $newError);
    }

    public function testIteratorFunctionality()
    {
        $collection = new FormFilterErrorCollection();
        $error1 = new FormFilterError();
        $error2 = new FormFilterError();

        $collection['field1'] = $error1;
        $collection['field2'] = $error2;

        $keys = [];
        $values = [];

        foreach ($collection as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $this->assertEquals(['field1', 'field2'], $keys);
        $this->assertEquals([$error1, $error2], $values);
    }

    public function testCountFunctionality()
    {
        $collection = new FormFilterErrorCollection();

        $this->assertCount(0, $collection);

        $collection['field1'] = new FormFilterError();
        $this->assertCount(1, $collection);

        $collection['field2'] = new FormFilterError();
        $this->assertCount(2, $collection);

        unset($collection['field1']);
        $this->assertCount(1, $collection);
    }
}
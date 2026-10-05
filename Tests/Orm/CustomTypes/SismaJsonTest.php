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

namespace SismaFramework\Tests\Orm\CustomTypes;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\CustomTypes\SismaJson;
use SismaFramework\Orm\Interfaces\CustomTypeInterface;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SismaJsonTest extends TestCase
{

    public function testFromJsonAndToJson()
    {
        $sismaJson = SismaJson::fromJson('{"name":"città","price":10.0,"path":"a/b","tags":["one","two"]}');
        $this->assertInstanceOf(CustomTypeInterface::class, $sismaJson);
        $this->assertEquals(['name' => 'città', 'price' => 10.0, 'path' => 'a/b', 'tags' => ['one', 'two']], $sismaJson->toArray());
        $this->assertEquals('{"name":"città","price":10.0,"path":"a/b","tags":["one","two"]}', $sismaJson->toJson());
    }

    public function testEmptyJson()
    {
        $sismaJson = new SismaJson();
        $this->assertEquals([], $sismaJson->toArray());
        $this->assertEquals('[]', $sismaJson->toJson());
        $this->assertCount(0, $sismaJson);
    }

    public function testFromJsonWithInvalidJson()
    {
        $this->expectException(\JsonException::class);
        SismaJson::fromJson('{invalid');
    }

    public function testFromJsonWithScalarJson()
    {
        $this->expectException(\JsonException::class);
        SismaJson::fromJson('"string"');
    }

    public function testGetAndHas()
    {
        $sismaJson = new SismaJson(['key' => 'value', 'nullKey' => null]);
        $this->assertTrue($sismaJson->has('key'));
        $this->assertTrue($sismaJson->has('nullKey'));
        $this->assertFalse($sismaJson->has('missingKey'));
        $this->assertEquals('value', $sismaJson->get('key'));
        $this->assertNull($sismaJson->get('nullKey', 'default'));
        $this->assertEquals('default', $sismaJson->get('missingKey', 'default'));
    }

    public function testWithAndWithoutAreImmutable()
    {
        $sismaJson = new SismaJson(['key' => 'value']);
        $sismaJsonWith = $sismaJson->with('otherKey', 'other value');
        $sismaJsonWithout = $sismaJsonWith->without('key');
        $this->assertEquals(['key' => 'value'], $sismaJson->toArray());
        $this->assertEquals(['key' => 'value', 'otherKey' => 'other value'], $sismaJsonWith->toArray());
        $this->assertEquals(['otherKey' => 'other value'], $sismaJsonWithout->toArray());
    }

    public function testArrayAccess()
    {
        $sismaJson = new SismaJson(['key' => 'value']);
        $this->assertTrue(isset($sismaJson['key']));
        $this->assertFalse(isset($sismaJson['missingKey']));
        $this->assertEquals('value', $sismaJson['key']);
        $this->assertNull($sismaJson['missingKey']);
    }

    public function testOffsetSetThrowsException()
    {
        $this->expectException(\LogicException::class);
        $sismaJson = new SismaJson(['key' => 'value']);
        $sismaJson['key'] = 'other value';
    }

    public function testOffsetUnsetThrowsException()
    {
        $this->expectException(\LogicException::class);
        $sismaJson = new SismaJson(['key' => 'value']);
        unset($sismaJson['key']);
    }

    public function testIteratorAndCount()
    {
        $data = ['one' => 1, 'two' => 2];
        $sismaJson = new SismaJson($data);
        $this->assertCount(2, $sismaJson);
        $this->assertEquals($data, iterator_to_array($sismaJson));
    }

    public function testJsonSerialize()
    {
        $sismaJson = new SismaJson(['key' => 'value']);
        $this->assertEquals('{"json":{"key":"value"}}', json_encode(['json' => $sismaJson]));
    }

    public function testEquals()
    {
        $sismaJsonOne = SismaJson::fromJson('{"a":1,"b":{"c":2,"d":3},"e":[1,2]}');
        $sismaJsonTwo = SismaJson::fromJson('{"e":[1,2],"b":{"d":3,"c":2},"a":1}');
        $sismaJsonThree = SismaJson::fromJson('{"a":1,"b":{"c":2,"d":3},"e":[2,1]}');
        $sismaJsonFour = SismaJson::fromJson('{"a":"1","b":{"c":2,"d":3},"e":[1,2]}');
        $this->assertTrue($sismaJsonOne->equals($sismaJsonTwo));
        $this->assertFalse($sismaJsonOne->equals($sismaJsonThree));
        $this->assertFalse($sismaJsonOne->equals($sismaJsonFour));
    }

    public function testEqualsWithOtherCustomType()
    {
        $sismaJson = new SismaJson();
        $otherCustomType = new class implements CustomTypeInterface {

            #[\Override]
            public function equals(CustomTypeInterface $other): bool
            {
                return false;
            }
        };
        $this->assertFalse($sismaJson->equals($otherCustomType));
    }

    public function testToJsonWithNestedSerializableObject()
    {
        $sismaJson = new SismaJson(['nested' => new SismaJson(['key' => 'value'])]);
        $this->assertEquals('{"nested":{"key":"value"}}', $sismaJson->toJson());
    }
}

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

namespace SismaFramework\Tests\Odm\Adapters;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use SismaFramework\Odm\Adapters\AdapterMongodb;
use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Exceptions\AdapterException;
use SismaFramework\Odm\HelperClasses\DocumentQuery;
use SismaFramework\Orm\CustomTypes\SismaDateTime;

/**
 * @author Valentino de Lapa
 */
class AdapterMongodbTest extends TestCase
{
    private AdapterMongodb $adapter;

    #[\Override]
    public function setUp(): void
    {
        $this->adapter = new AdapterMongodb();
    }

    public function testCompileQueryConvertsIdEqualToObjectId(): void
    {
        $id = '507f1f77bcf86cd799439011';
        $query = (new DocumentQuery())->where('_id', FilterOperator::equal, $id);

        $filter = $this->adapter->compileQuery($query);

        $this->assertInstanceOf(ObjectId::class, $filter['_id']['$eq']);
        $this->assertEquals($id, (string) $filter['_id']['$eq']);
    }

    public function testCompileQueryConvertsIdInArrayToObjectIds(): void
    {
        $ids = ['507f1f77bcf86cd799439011', '507f191e810c19729de860ea'];
        $query = (new DocumentQuery())->where('_id', FilterOperator::in, $ids);

        $filter = $this->adapter->compileQuery($query);

        $this->assertCount(2, $filter['_id']['$in']);
        foreach ($filter['_id']['$in'] as $index => $objectId) {
            $this->assertInstanceOf(ObjectId::class, $objectId);
            $this->assertEquals($ids[$index], (string) $objectId);
        }
    }

    public function testCompileQueryLeavesExistingObjectIdUnchanged(): void
    {
        $objectId = new ObjectId();
        $query = (new DocumentQuery())->where('_id', FilterOperator::equal, $objectId);

        $filter = $this->adapter->compileQuery($query);

        $this->assertSame($objectId, $filter['_id']['$eq']);
    }

    public function testCompileQueryDoesNotConvertNonIdFields(): void
    {
        $query = (new DocumentQuery())->where('status', FilterOperator::equal, 'published');

        $filter = $this->adapter->compileQuery($query);

        $this->assertSame('published', $filter['status']['$eq']);
    }

    public function testCompileQueryThrowsOnInvalidIdFormat(): void
    {
        $this->expectException(AdapterException::class);
        $query = (new DocumentQuery())->where('_id', FilterOperator::equal, 'not-a-valid-object-id');
        $this->adapter->compileQuery($query);
    }

    public function testCompileQueryDoesNotConvertNullIdValue(): void
    {
        $query = (new DocumentQuery())->where('_id', FilterOperator::isNull);

        $filter = $this->adapter->compileQuery($query);

        $this->assertNull($filter['_id']);
    }

    public function testCompileQuerySingleConditionIsNotWrapped(): void
    {
        $query = (new DocumentQuery())->where('status', FilterOperator::equal, 'published');

        $filter = $this->adapter->compileQuery($query);

        $this->assertSame(['status' => ['$eq' => 'published']], $filter);
    }

    public function testCompileQueryAllAndIsWrappedInAnd(): void
    {
        $query = (new DocumentQuery())
            ->where('a', FilterOperator::equal, 1)
            ->andWhere('b', FilterOperator::equal, 2)
            ->andWhere('c', FilterOperator::equal, 3);

        $filter = $this->adapter->compileQuery($query);

        $this->assertSame([
            '$and' => [
                ['a' => ['$eq' => 1]],
                ['b' => ['$eq' => 2]],
                ['c' => ['$eq' => 3]],
            ],
        ], $filter);
    }

    public function testCompileQueryAllOrIsWrappedInOr(): void
    {
        $query = (new DocumentQuery())
            ->where('a', FilterOperator::equal, 1)
            ->orWhere('b', FilterOperator::equal, 2)
            ->orWhere('c', FilterOperator::equal, 3);

        $filter = $this->adapter->compileQuery($query);

        $this->assertSame([
            '$or' => [
                ['a' => ['$eq' => 1]],
                ['b' => ['$eq' => 2]],
                ['c' => ['$eq' => 3]],
            ],
        ], $filter);
    }

    public function testCompileQueryAndBindsTighterThanOr(): void
    {
        $query = (new DocumentQuery())
            ->where('a', FilterOperator::equal, 1)
            ->andWhere('b', FilterOperator::equal, 2)
            ->orWhere('c', FilterOperator::equal, 3);

        $filter = $this->adapter->compileQuery($query);

        $this->assertSame([
            '$or' => [
                ['$and' => [
                    ['a' => ['$eq' => 1]],
                    ['b' => ['$eq' => 2]],
                ]],
                ['c' => ['$eq' => 3]],
            ],
        ], $filter);
    }

    public function testCompileQueryMultipleOrGroupsWithAnd(): void
    {
        $query = (new DocumentQuery())
            ->where('a', FilterOperator::equal, 1)
            ->andWhere('b', FilterOperator::equal, 2)
            ->orWhere('c', FilterOperator::equal, 3)
            ->andWhere('d', FilterOperator::equal, 4);

        $filter = $this->adapter->compileQuery($query);

        $this->assertSame([
            '$or' => [
                ['$and' => [
                    ['a' => ['$eq' => 1]],
                    ['b' => ['$eq' => 2]],
                ]],
                ['$and' => [
                    ['c' => ['$eq' => 3]],
                    ['d' => ['$eq' => 4]],
                ]],
            ],
        ], $filter);
    }

    public function testCompileQueryRejectsFieldStartingWithDollar(): void
    {
        $this->expectException(AdapterException::class);
        $query = (new DocumentQuery())->where('$where', FilterOperator::equal, '1==1');
        $this->adapter->compileQuery($query);
    }

    public function testCompileQueryRejectsArrayValueForEqual(): void
    {
        $this->expectException(AdapterException::class);
        $query = (new DocumentQuery())->where('status', FilterOperator::equal, ['$ne' => null]);
        $this->adapter->compileQuery($query);
    }

    public function testCompileQueryAllowsScalarArrayForIn(): void
    {
        $query = (new DocumentQuery())->where('status', FilterOperator::in, ['draft', 'published']);

        $filter = $this->adapter->compileQuery($query);

        $this->assertSame(['status' => ['$in' => ['draft', 'published']]], $filter);
    }

    public function testCompileQueryRejectsNonScalarInArrayForIn(): void
    {
        $this->expectException(AdapterException::class);
        $query = (new DocumentQuery())->where('status', FilterOperator::in, [['$gt' => '']]);
        $this->adapter->compileQuery($query);
    }

    public function testBsonToArrayConvertsObjectIdToString(): void
    {
        $id = new ObjectId();

        $result = $this->invokeBsonToArray(['_id' => $id, 'title' => 'Test']);

        $this->assertSame((string) $id, $result['_id']);
    }

    public function testBsonToArrayConvertsUtcDateTimeToSismaDateTime(): void
    {
        $utcDateTime = new UTCDateTime(new \DateTimeImmutable('2024-03-15 10:30:00', new \DateTimeZone('UTC')));

        $result = $this->invokeBsonToArray(['publishedAt' => $utcDateTime]);

        $this->assertInstanceOf(SismaDateTime::class, $result['publishedAt']);
        $this->assertEquals('2024-03-15 10:30:00', $result['publishedAt']->format('Y-m-d H:i:s'));
    }

    public function testBsonToArrayConvertsNestedArraysRecursively(): void
    {
        $id = new ObjectId();

        $result = $this->invokeBsonToArray(['author' => ['ref' => $id, 'name' => 'Valentino']]);

        $this->assertSame((string) $id, $result['author']['ref']);
        $this->assertSame('Valentino', $result['author']['name']);
    }

    public function testBsonToArrayLeavesPlainScalarsUnchanged(): void
    {
        $result = $this->invokeBsonToArray(['title' => 'Hello', 'count' => 5]);

        $this->assertSame(['title' => 'Hello', 'count' => 5], $result);
    }

    private function invokeBsonToArray(array $raw): array
    {
        $method = new \ReflectionMethod(AdapterMongodb::class, 'bsonToArray');
        return $method->invoke($this->adapter, $raw);
    }
}

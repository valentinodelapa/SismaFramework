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

namespace SismaFramework\Tests\Odm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\LogicalOperator;
use SismaFramework\Odm\Enumerations\Indexing;
use SismaFramework\Odm\HelperClasses\DocumentQuery;

/**
 * @author Valentino de Lapa
 */
class DocumentQueryTest extends TestCase
{
    public function testNewQueryHasNoConditions(): void
    {
        $this->assertEmpty((new DocumentQuery())->getConditions());
    }

    public function testNewQueryHasNoSort(): void
    {
        $this->assertEmpty((new DocumentQuery())->getSort());
    }

    public function testNewQueryHasNullLimit(): void
    {
        $this->assertNull((new DocumentQuery())->getLimit());
    }

    public function testNewQueryHasNullOffset(): void
    {
        $this->assertNull((new DocumentQuery())->getOffset());
    }

    public function testWhereAddsConditionNode(): void
    {
        $query = (new DocumentQuery())->where('status', FilterOperator::equal, 'active');
        $conditions = $query->getConditions();
        $this->assertCount(1, $conditions);
        $this->assertEquals('condition', $conditions[0]['type']);
        $this->assertEquals('status', $conditions[0]['field']);
        $this->assertSame(FilterOperator::equal, $conditions[0]['operator']);
        $this->assertEquals('active', $conditions[0]['value']);
    }

    public function testWhereReplacesExistingConditions(): void
    {
        $query = (new DocumentQuery())
            ->where('status', FilterOperator::equal, 'active')
            ->where('count', FilterOperator::greater, 5);
        $conditions = $query->getConditions();
        $this->assertCount(1, $conditions);
        $this->assertEquals('count', $conditions[0]['field']);
    }

    public function testAndWhereAddsLogicalSeparatorAndCondition(): void
    {
        $query = (new DocumentQuery())
            ->where('status', FilterOperator::equal, 'active')
            ->andWhere('count', FilterOperator::greater, 0);

        $conditions = $query->getConditions();
        $this->assertCount(3, $conditions);
        $this->assertEquals('condition', $conditions[0]['type']);
        $this->assertEquals('logical_separator', $conditions[1]['type']);
        $this->assertSame(LogicalOperator::and, $conditions[1]['operator']);
        $this->assertEquals('condition', $conditions[2]['type']);
    }

    public function testOrWhereAddsOrSeparator(): void
    {
        $query = (new DocumentQuery())
            ->where('status', FilterOperator::equal, 'active')
            ->orWhere('status', FilterOperator::equal, 'draft');

        $conditions = $query->getConditions();
        $this->assertCount(3, $conditions);
        $this->assertSame(LogicalOperator::or, $conditions[1]['operator']);
    }

    public function testWhereStoresOperatorEnum(): void
    {
        foreach (FilterOperator::cases() as $operator) {
            $query = (new DocumentQuery())->where('field', $operator, 'value');
            $this->assertSame($operator, $query->getConditions()[0]['operator']);
        }
    }

    public function testOrderByStoresField(): void
    {
        $query = (new DocumentQuery())->orderBy('createdAt', Indexing::desc);
        $sort = $query->getSort();
        $this->assertArrayHasKey('createdAt', $sort);
        $this->assertSame(Indexing::desc, $sort['createdAt']);
    }

    public function testOrderByDefaultIsAsc(): void
    {
        $this->assertSame(Indexing::asc, (new DocumentQuery())->orderBy('title')->getSort()['title']);
    }

    public function testMultipleOrderByFields(): void
    {
        $sort = (new DocumentQuery())
            ->orderBy('createdAt', Indexing::desc)
            ->orderBy('title', Indexing::asc)
            ->getSort();
        $this->assertCount(2, $sort);
        $this->assertArrayHasKey('createdAt', $sort);
        $this->assertArrayHasKey('title', $sort);
    }

    public function testLimitIsSet(): void
    {
        $this->assertEquals(10, (new DocumentQuery())->limit(10)->getLimit());
    }

    public function testOffsetIsSet(): void
    {
        $this->assertEquals(20, (new DocumentQuery())->offset(20)->getOffset());
    }

    public function testMethodChainingReturnsQueryInstance(): void
    {
        $query = new DocumentQuery();
        $result = $query
            ->where('status', FilterOperator::equal, 'active')
            ->andWhere('count', FilterOperator::greater, 0)
            ->orderBy('title')
            ->limit(5)
            ->offset(10);
        $this->assertInstanceOf(DocumentQuery::class, $result);
        $this->assertSame($query, $result);
    }
}

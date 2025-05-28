<?php

/*
 * The MIT License
 *
 * Copyright (c) 2023-present Valentino de Lapa.
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

namespace SismaFramework\Tests\Orm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\Enumerations\AdapterType;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Placeholder;

/**
 * Description of QueryTest
 */
class QueryTest extends TestCase
{

    public function testGetAdapter()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $query = new Query($baseAdapterMock);
        $this->assertEquals($baseAdapterMock, $query->getAdapter());
    }

    public function testSelectDistinct()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(true, ['*'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setDistinct()
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectCount()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('opCOUNT')
                ->with('id')
                ->willReturn('COUNT(id)');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['COUNT(id)'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setCount('id')
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectAllColumns()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(3))
                ->method('allColumns')
                ->willReturn('*');
        $baseAdapterMock->expects($this->exactly(3))
                ->method('parseSelect')
                ->with(false, ['*'], '', [], [], [], [], 0, 0);
        $queryOne = new Query($baseAdapterMock);
        $queryOne->close();
        $this->assertEquals('', $queryOne->getCommandToExecute());
        $queryTwo = new Query($baseAdapterMock);
        $queryTwo->setColumns()
                ->close();
        $this->assertEquals('', $queryTwo->getCommandToExecute());
        $queryTree = new Query($baseAdapterMock);
        $queryTree->setColumn()
                ->close();
        $this->assertEquals('', $queryTree->getCommandToExecute());
    }

    public function testSelectColumns()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->once();
        $baseAdapterMock->expects($matcher)
                ->method('escapeColumns')
                ->willReturnCallback(function ($list) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(['columnOne', 'columnTwo'], $list);
                            return ['column_one', 'column_two'];
                    }
                });
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['column_one', 'column_two'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setColumns(['columnOne', 'columnTwo'])
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectColumn()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->once();
        $baseAdapterMock->expects($matcher)
                ->method('escapeColumn')
                ->willReturnCallback(function ($name, $foreignKey) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('columnOne', $name);
                            $this->assertFalse($foreignKey);
                            return 'column_one';
                    }
                });
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['column_one'], '', [], [], [], [], 0, 0);
        $queryOne = new Query($baseAdapterMock);
        $queryOne->setColumn('columnOne')
                ->close();
        $this->assertEquals('', $queryOne->getCommandToExecute());
    }

    public function testSelectSetFullIndexColumnAndCondition()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(1))
                ->method('allColumns')
                ->willReturn('*');
        $matcherOne = $this->exactly(2);
        $baseAdapterMock->expects($matcherOne)
                ->method('opFulltextIndex')
                ->willReturnCallback(function ($columns, $value, $columnAlias) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(['fulltextColumn'], $columns);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertNull($columnAlias);
                            return 'MATCH (filltext_column) AGAINST ?';
                        case 2:
                            $this->assertEquals(['fulltextColumn'], $columns);
                            $this->assertEquals('value', $value);
                            $this->assertEquals('columnAlias', $columnAlias);
                            return 'MATCH (filltext_column) AGAINST value as column_alias';
                    }
                });
        $matcherTwo = $this->exactly(2);
        $baseAdapterMock->expects($matcherTwo)
                ->method('fulltextConditionSintax')
                ->willReturnCallback(function ($columns, $value) use ($matcherTwo) {
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(['fulltextColumn'], $columns);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            return 'MATCH (filltext_column) AGAINST ?';
                        case 2:
                            $this->assertEquals(['fulltextColumn'], $columns);
                            $this->assertEquals('value', $value);
                            return 'MATCH (filltext_column) AGAINST value';
                    }
                });
        $matcherTree = $this->exactly(2);
        $baseAdapterMock->expects($matcherTree)
                ->method('parseSelect')
                ->willReturnCallback(function ($distinct, $select, $from, $where, $groupby, $having, $orderby, $offset, $limit) use ($matcherTree) {
                    switch ($matcherTree->numberOfInvocations()) {
                        case 1:
                            $this->assertFalse($distinct);
                            $this->assertEquals(['MATCH (filltext_column) AGAINST ?'], $select);
                            $this->assertEquals('', $from);
                            $this->assertEquals(['MATCH (filltext_column) AGAINST ?'], $where);
                            $this->assertEquals([], $groupby);
                            $this->assertEquals([], $having);
                            $this->assertEquals([], $orderby);
                            $this->assertEquals(0, $offset);
                            $this->assertEquals(0, $limit);
                            break;
                        case 2:
                            $this->assertFalse($distinct);
                            $this->assertEquals(['*', 'MATCH (filltext_column) AGAINST value as column_alias'], $select);
                            $this->assertEquals('', $from);
                            $this->assertEquals(['MATCH (filltext_column) AGAINST value'], $where);
                            $this->assertEquals([], $groupby);
                            $this->assertEquals([], $having);
                            $this->assertEquals([], $orderby);
                            $this->assertEquals(0, $offset);
                            $this->assertEquals(0, $limit);
                            break;
                    }
                    return '';
                });
        $queryOne = new Query($baseAdapterMock);
        $queryOne->setWhere()
                ->setFulltextIndexColumn(['fulltextColumn'])
                ->appendFulltextCondition(['fulltextColumn'])
                ->close();
        $this->assertEquals('', $queryOne->getCommandToExecute());
        $queryTwo = new Query($baseAdapterMock);
        $queryTwo->setWhere()
                ->setFulltextIndexColumn(['fulltextColumn'], 'value', 'columnAlias', true)
                ->appendFulltextCondition(['fulltextColumn'], 'value')
                ->close();
        $this->assertEquals('', $queryTwo->getCommandToExecute());
    }

    public function testSelectSetSubqueryColumn()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(2))
                ->method('allColumns')
                ->willReturn('*');
        $subquery = new Query($baseAdapterMock);
        $subquery->close();
        $matcherOne = $this->exactly(2);
        $baseAdapterMock->expects($matcherOne)
                ->method('opSubquery')
                ->willReturnCallback(function ($columns, $columnAlias) use ($matcherOne, $subquery) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals($subquery, $columns);
                            $this->assertNull($columnAlias);
                            return 'subquery';
                        case 2:
                            $this->assertEquals($subquery, $columns);
                            $this->assertEquals('columnAlias', $columnAlias);
                            return 'subquery';
                    }
                });
        $matcherTwo = $this->exactly(2);
        $baseAdapterMock->expects($matcherTwo)
                ->method('parseSelect')
                ->willReturnCallback(function ($distinct, $select, $from, $where, $groupby, $having, $orderby, $offset, $limit) use ($matcherTwo) {
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertFalse($distinct);
                            $this->assertEquals(['subquery'], $select);
                            $this->assertEquals('', $from);
                            $this->assertEquals([], $where);
                            $this->assertEquals([], $groupby);
                            $this->assertEquals([], $having);
                            $this->assertEquals([], $orderby);
                            $this->assertEquals(0, $offset);
                            $this->assertEquals(0, $limit);
                            break;
                        case 2:
                            $this->assertFalse($distinct);
                            $this->assertEquals(['*', 'subquery'], $select);
                            $this->assertEquals('', $from);
                            $this->assertEquals([], $where);
                            $this->assertEquals([], $groupby);
                            $this->assertEquals([], $having);
                            $this->assertEquals([], $orderby);
                            $this->assertEquals(0, $offset);
                            $this->assertEquals(0, $limit);
                            break;
                    }
                    return '';
                });
        $queryOne = new Query($baseAdapterMock);
        $queryOne->setSubqueryColumn($subquery)
                ->close();
        $this->assertEquals('', $queryOne->getCommandToExecute());
        $queryTwo = new Query($baseAdapterMock);
        $queryTwo->setSubqueryColumn($subquery, 'columnAlias', true)
                ->close();
        $this->assertEquals('', $queryTwo->getCommandToExecute());
    }

    public function testSelectWhere()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $matcherOne = $this->exactly(4);
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeColumn')
                ->willReturnCallback(function ($column, $foreignKey) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('columnOne', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_one';
                        case 2:
                            $this->assertEquals('columnTwo', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_two';
                        case 3:
                            $this->assertEquals('columnTree', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_tree';
                        case 4:
                            $this->assertEquals('columnFour', $column);
                            $this->assertTrue($foreignKey);
                            return 'column_four_id';
                    }
                });
        $matcherTwo = $this->exactly(4);
        $baseAdapterMock->expects($matcherTwo)
                ->method('escapeValue')
                ->willReturnCallback(function ($value, $operator) use ($matcherTwo) {
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            return $value->getAdapterVersion(AdapterType::mysql);
                        case 2:
                            $this->assertEquals(1, $value);
                            $this->assertEquals(ComparisonOperator::greater, $operator);
                            return $value;
                        case 3:
                            $this->assertEquals('value', $value);
                            $this->assertEquals(ComparisonOperator::lessOrEqual, $operator);
                            return $value;
                        case 4:
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(ComparisonOperator::isNull, $operator);
                            return '';
                    }
                });
        $matcherThree = $this->exactly(4);
        $baseAdapterMock->expects($matcherThree)
                ->method('parseComparisonOperator')
                ->willReturnCallback(function ($operator) use ($matcherThree) {
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            return $operator->getAdapterVersion(AdapterType::mysql);
                        case 2:
                            $this->assertEquals(ComparisonOperator::greater, $operator);
                            return $operator->getAdapterVersion(AdapterType::mysql);
                        case 3:
                            $this->assertEquals(ComparisonOperator::lessOrEqual, $operator);
                            return $operator->getAdapterVersion(AdapterType::mysql);
                        case 4:
                            $this->assertEquals(ComparisonOperator::isNull, $operator);
                            return $operator->getAdapterVersion(AdapterType::mysql);
                    }
                });
        $baseAdapterMock->expects($this->once())
                ->method('openBlock')
                ->willReturn('(');
        $baseAdapterMock->expects($this->once())
                ->method('closeBlock')
                ->willReturn(')');
        $baseAdapterMock->expects($this->once())
                ->method('opAND')
                ->willReturn('AND');
        $baseAdapterMock->expects($this->once())
                ->method('opOR')
                ->willReturn('OR');
        $baseAdapterMock->expects($this->once())
                ->method('opNOT')
                ->willReturn('NOT');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], '', [
                    'column_one = ?',
                    'AND',
                    '(',
                    'column_two > 1',
                    'OR',
                    'column_tree <= value',
                    ')',
                    'NOT',
                    'column_four_id IS NULL ',
                        ], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setWhere()
                ->appendCondition('columnOne', ComparisonOperator::equal)
                ->appendAnd()
                ->appendOpenBlock()
                ->appendCondition('columnTwo', ComparisonOperator::greater, 1)
                ->appendOr()
                ->appendCondition('columnTree', ComparisonOperator::lessOrEqual, 'value')
                ->appendCloseBlock()
                ->appendNot()
                ->appendCondition('columnFour', ComparisonOperator::isNull, Placeholder::placeholder, true)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectHaving()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $matcherOne = $this->exactly(4);
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeColumn')
                ->willReturnCallback(function ($column, $foreignKey) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('columnOne', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_one';
                        case 2:
                            $this->assertEquals('columnTwo', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_two';
                        case 3:
                            $this->assertEquals('columnTree', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_tree';
                        case 4:
                            $this->assertEquals('columnFour', $column);
                            $this->assertTrue($foreignKey);
                            return 'column_four_id';
                    }
                });
        $matcherTwo = $this->exactly(4);
        $baseAdapterMock->expects($matcherTwo)
                ->method('escapeValue')
                ->willReturnCallback(function ($value, $operator) use ($matcherTwo) {
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            return $value->getAdapterVersion(AdapterType::mysql);
                        case 2:
                            $this->assertEquals(1, $value);
                            $this->assertEquals(ComparisonOperator::greater, $operator);
                            return $value;
                        case 3:
                            $this->assertEquals('value', $value);
                            $this->assertEquals(ComparisonOperator::lessOrEqual, $operator);
                            return $value;
                        case 4:
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(ComparisonOperator::isNull, $operator);
                            return '';
                    }
                });
        $matcherThree = $this->exactly(4);
        $baseAdapterMock->expects($matcherThree)
                ->method('parseComparisonOperator')
                ->willReturnCallback(function ($operator) use ($matcherThree) {
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            return $operator->getAdapterVersion(AdapterType::mysql);
                        case 2:
                            $this->assertEquals(ComparisonOperator::greater, $operator);
                            return $operator->getAdapterVersion(AdapterType::mysql);
                        case 3:
                            $this->assertEquals(ComparisonOperator::lessOrEqual, $operator);
                            return $operator->getAdapterVersion(AdapterType::mysql);
                        case 4:
                            $this->assertEquals(ComparisonOperator::isNull, $operator);
                            return $operator->getAdapterVersion(AdapterType::mysql);
                    }
                });
        $baseAdapterMock->expects($this->once())
                ->method('openBlock')
                ->willReturn('(');
        $baseAdapterMock->expects($this->once())
                ->method('closeBlock')
                ->willReturn(')');
        $baseAdapterMock->expects($this->once())
                ->method('opAND')
                ->willReturn('AND');
        $baseAdapterMock->expects($this->once())
                ->method('opOR')
                ->willReturn('OR');
        $baseAdapterMock->expects($this->once())
                ->method('opNOT')
                ->willReturn('NOT');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], '', [], [], [
                    'column_one = ?',
                    'AND',
                    '(',
                    'column_two > 1',
                    'OR',
                    'column_tree <= value',
                    ')',
                    'NOT',
                    'column_four_id IS NULL ',
                        ], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setHaving()
                ->appendCondition('columnOne', ComparisonOperator::equal)
                ->appendAnd()
                ->appendOpenBlock()
                ->appendCondition('columnTwo', ComparisonOperator::greater, 1)
                ->appendOr()
                ->appendCondition('columnTree', ComparisonOperator::lessOrEqual, 'value')
                ->appendCloseBlock()
                ->appendNot()
                ->appendCondition('columnFour', ComparisonOperator::isNull, Placeholder::placeholder, true)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSetTable()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $baseAdapterMock->expects($this->once())
                ->method('escapeTable')
                ->with('tableName')
                ->willReturn('table_name');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], 'table_name', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setTable('tableName')
                ->setWhere()
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSetTableWithAlias()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $baseAdapterMock->expects($this->once())
                ->method('escapeTable')
                ->with('tableName', 'A')
                ->willReturn('table_name as A');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], 'table_name as A', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setTable('tableName', 'A')
                ->setWhere()
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testOffsetLimit()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], '', [], [], [], [], 5, 10);
        $query = new Query($baseAdapterMock);
        $query->setWhere()
                ->setOffset(5)
                ->setLimit(10)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testOrderBy()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $matcherOne = $this->once();
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeColumn')
                ->willReturnCallback(function ($column, $foreignKey) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('id', $column);
                            $this->assertFalse($foreignKey);
                            return 'id';
                    }
                });
        $baseAdapterMock->expects($this->exactly(2))
                ->method('escapeOrderIndexing')
                ->with(Indexing::asc)
                ->willReturn('ASC');
        $subqueryMock = $this->createMock(Query::class);
        $baseAdapterMock->expects($this->once())
                ->method('openBlock')
                ->willReturn('(');
        $subqueryMock->expects($this->once())
                ->method('getCommandToExecute')
                ->willReturn('subquery');
        $baseAdapterMock->expects($this->once())
                ->method('closeBlock')
                ->willReturn(')');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], '', [], [], [], ['id ASC', '(subquery) ASC'], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setWhere()
                ->setOrderBy(['id' => Indexing::asc])
                ->appendOrderBySubquery($subqueryMock, Indexing::asc)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testGroupBy()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $baseAdapterMock->expects($this->once())
                ->method('escapeColumns')
                ->with(['columnNameOne', 'columnNameTwo'])
                ->willReturn(['column_name_one', 'column_name_two']);
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], '', [], ['column_name_one', 'column_name_two'], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setWhere()
                ->setGroupBy(['columnNameOne', 'columnNameTwo'])
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }
}

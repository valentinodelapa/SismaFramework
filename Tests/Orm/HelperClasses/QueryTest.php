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
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Enumerations\TextSearchMode;

/**
 * Description of QueryTest
 */
class QueryTest extends TestCase
{

    public function testGetAdapter()
    {
        $baseAdapterMock = $this->createStub(BaseAdapter::class);
        $query = new Query($baseAdapterMock);
        $this->assertEquals($baseAdapterMock, $query->getAdapter());
    }

    public function testSelectDistinct()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
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

    public function testSelectAVG()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('opAggregationFunction')
                ->with($this->callback(function ($aggregationFunction) {
                            return $aggregationFunction->name === 'avg';
                        }), 'price', 'avg_price', false)
                ->willReturn('AVG(price) as avg_price');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['AVG(price) as avg_price'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setAVG('price', 'avg_price')
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectAVGWithDistinct()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('opAggregationFunction')
                ->with($this->callback(function ($aggregationFunction) {
                            return $aggregationFunction->name === 'avg';
                        }), 'price', null, true)
                ->willReturn('AVG(DISTINCT price)');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['AVG(DISTINCT price)'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setAVG('price', null, true)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectAVGWithAppend()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('escapeTable', null)
                ->with('tableName')
                ->willReturn('table_name');
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $baseAdapterMock->expects($this->once())
                ->method('opAggregationFunction')
                ->with($this->callback(function ($aggregationFunction) {
                            return $aggregationFunction->name === 'avg';
                        }), 'price', 'avg_price', false)
                ->willReturn('AVG(price) as avg_price');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['table_name.*', 'AVG(price) as avg_price'], 'table_name', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setTable('tableName')
                ->setAVG('price', 'avg_price', false, true)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectSUM()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('opAggregationFunction')
                ->with($this->callback(function ($aggregationFunction) {
                            return $aggregationFunction->name === 'sum';
                        }), 'amount', 'total', false)
                ->willReturn('SUM(amount) as total');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['SUM(amount) as total'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setSum('amount', 'total')
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectSUMWithDistinct()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('opAggregationFunction')
                ->with($this->callback(function ($aggregationFunction) {
                            return $aggregationFunction->name === 'sum';
                        }), 'amount', null, true)
                ->willReturn('SUM(DISTINCT amount)');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['SUM(DISTINCT amount)'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setSum('amount', null, true)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectMAX()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('opAggregationFunction')
                ->with($this->callback(function ($aggregationFunction) {
                            return $aggregationFunction->name === 'max';
                        }), 'score', 'max_score', false)
                ->willReturn('MAX(score) as max_score');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['MAX(score) as max_score'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setMax('score', 'max_score')
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectMIN()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('opAggregationFunction')
                ->with($this->callback(function ($aggregationFunction) {
                            return $aggregationFunction->name === 'min';
                        }), 'score', 'min_score', false)
                ->willReturn('MIN(score) as min_score');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['MIN(score) as min_score'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setMin('score', 'min_score')
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectAggregationFunctionWithSubquery()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $subquery = new Query($baseAdapterMock);
        $subquery->close();
        $baseAdapterMock->expects($this->once())
                ->method('opAggregationFunction')
                ->with($this->callback(function ($aggregationFunction) {
                            return $aggregationFunction->name === 'avg';
                        }), $subquery, 'avg_sub', false)
                ->willReturn('AVG((subquery)) as avg_sub');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['AVG((subquery)) as avg_sub'], '', [], [], [], [], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setAVG($subquery, 'avg_sub')
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectAllColumns()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(3))
                ->method('allColumns')
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
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
        $baseAdapterMock->expects($this->exactly(2))
                ->method('escapeTable', null)
                ->with('tableName')
                ->willReturn('table_name');
        $baseAdapterMock->expects($this->exactly(1))
                ->method('allColumns')
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $matcherOne = $this->exactly(2);
        $baseAdapterMock->expects($matcherOne)
                ->method('opFulltextIndex')
                ->willReturnCallback(function ($columns, $value, $textSearchMode, $columnAlias) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(['fulltextColumn'], $columns);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(TextSearchMode::inNaturaLanguageMode, $textSearchMode);
                            $this->assertNull($columnAlias);
                            return 'MATCH (filltext_column) AGAINST ?';
                        case 2:
                            $this->assertEquals(['fulltextColumn'], $columns);
                            $this->assertEquals('value', $value);
                            $this->assertEquals(TextSearchMode::inNaturaLanguageMode, $textSearchMode);
                            $this->assertEquals('columnAlias', $columnAlias);
                            return 'MATCH (filltext_column) AGAINST value as column_alias';
                    }
                });
        $matcherTwo = $this->exactly(2);
        $baseAdapterMock->expects($matcherTwo)
                ->method('fulltextConditionSintax')
                ->willReturnCallback(function ($columns, $value, $textSearchMode) use ($matcherTwo) {
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(['fulltextColumn'], $columns);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(TextSearchMode::inNaturaLanguageMode, $textSearchMode);
                            return 'MATCH (filltext_column) AGAINST ?';
                        case 2:
                            $this->assertEquals(['fulltextColumn'], $columns);
                            $this->assertEquals('value', $value);
                            $this->assertEquals(TextSearchMode::inNaturaLanguageMode, $textSearchMode);
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
                            $this->assertEquals('table_name', $from);
                            $this->assertEquals(['MATCH (filltext_column) AGAINST ?'], $where);
                            $this->assertEquals([], $groupby);
                            $this->assertEquals([], $having);
                            $this->assertEquals([], $orderby);
                            $this->assertEquals(0, $offset);
                            $this->assertEquals(0, $limit);
                            break;
                        case 2:
                            $this->assertFalse($distinct);
                            $this->assertEquals(['table_name.*', 'MATCH (filltext_column) AGAINST value as column_alias'], $select);
                            $this->assertEquals('table_name', $from);
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
        $queryOne->setTable('tableName')
                ->setWhere()
                ->setFulltextIndexColumn(['fulltextColumn'])
                ->appendFulltextCondition(['fulltextColumn'])
                ->close();
        $this->assertEquals('', $queryOne->getCommandToExecute());
        $queryTwo = new Query($baseAdapterMock);
        $queryTwo->setTable('tableName')
                ->setWhere()
                ->setFulltextIndexColumn(['fulltextColumn'], 'value', 'columnAlias', true)
                ->appendFulltextCondition(['fulltextColumn'], 'value')
                ->close();
        $this->assertEquals('', $queryTwo->getCommandToExecute());
    }

    public function testSelectSetFullIndexColumnWithBooleanMode()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(1))
                ->method('escapeTable', null)
                ->with('tableName')
                ->willReturn('table_name');
        $baseAdapterMock->expects($this->once())
                ->method('opFulltextIndex')
                ->willReturnCallback(function ($columns, $value, $textSearchMode, $columnAlias) {
                    $this->assertEquals(['fulltextColumn'], $columns);
                    $this->assertEquals(Placeholder::placeholder, $value);
                    $this->assertEquals(TextSearchMode::inBooleanMode, $textSearchMode);
                    $this->assertNull($columnAlias);
                    return 'MATCH (filltext_column) AGAINST ? IN BOOLEAN MODE';
                });
        $baseAdapterMock->expects($this->once())
                ->method('fulltextConditionSintax')
                ->willReturnCallback(function ($columns, $value, $textSearchMode) {
                    $this->assertEquals(['fulltextColumn'], $columns);
                    $this->assertEquals(Placeholder::placeholder, $value);
                    $this->assertEquals(TextSearchMode::inBooleanMode, $textSearchMode);
                    return 'MATCH (filltext_column) AGAINST ? IN BOOLEAN MODE';
                });
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->willReturnCallback(function ($distinct, $select, $from, $where, $groupby, $having, $orderby, $offset, $limit) {
                    $this->assertEquals(['MATCH (filltext_column) AGAINST ? IN BOOLEAN MODE'], $select);
                    $this->assertEquals(['MATCH (filltext_column) AGAINST ? IN BOOLEAN MODE'], $where);
                    return '';
                });
        $query = new Query($baseAdapterMock);
        $query->setTable('tableName')
                ->setWhere()
                ->setFulltextIndexColumn(['fulltextColumn'], textSearchMode: TextSearchMode::inBooleanMode)
                ->appendFulltextCondition(['fulltextColumn'], textSearchMode: TextSearchMode::inBooleanMode)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectSetFullIndexColumnWithQueryExpansionMode()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(1))
                ->method('escapeTable', null)
                ->with('tableName')
                ->willReturn('table_name');
        $baseAdapterMock->expects($this->once())
                ->method('opFulltextIndex')
                ->willReturnCallback(function ($columns, $value, $textSearchMode, $columnAlias) {
                    $this->assertEquals(['fulltextColumn'], $columns);
                    $this->assertEquals(Placeholder::placeholder, $value);
                    $this->assertEquals(TextSearchMode::withQueryExpansion, $textSearchMode);
                    $this->assertNull($columnAlias);
                    return 'MATCH (filltext_column) AGAINST ? WITH QUERY EXPANSION';
                });
        $baseAdapterMock->expects($this->once())
                ->method('fulltextConditionSintax')
                ->willReturnCallback(function ($columns, $value, $textSearchMode) {
                    $this->assertEquals(['fulltextColumn'], $columns);
                    $this->assertEquals(Placeholder::placeholder, $value);
                    $this->assertEquals(TextSearchMode::withQueryExpansion, $textSearchMode);
                    return 'MATCH (filltext_column) AGAINST ? WITH QUERY EXPANSION';
                });
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->willReturnCallback(function ($distinct, $select, $from, $where, $groupby, $having, $orderby, $offset, $limit) {
                    $this->assertEquals(['MATCH (filltext_column) AGAINST ? WITH QUERY EXPANSION'], $select);
                    $this->assertEquals(['MATCH (filltext_column) AGAINST ? WITH QUERY EXPANSION'], $where);
                    return '';
                });
        $query = new Query($baseAdapterMock);
        $query->setTable('tableName')
                ->setWhere()
                ->setFulltextIndexColumn(['fulltextColumn'], textSearchMode: TextSearchMode::withQueryExpansion)
                ->appendFulltextCondition(['fulltextColumn'], textSearchMode: TextSearchMode::withQueryExpansion)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testSelectSetSubqueryColumn()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcherOne = $this->exactly(3);
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeTable')
                ->willReturnCallback(function ($tableName) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('subqueryTableName', $tableName);
                            return 'subquery_table_name';
                        case 2:
                            $this->assertEquals('tableNameOne', $tableName);
                            return 'table_name_one';
                        case 3:
                            $this->assertEquals('tableNameTwo', $tableName);
                            return 'table_name_two';
                    }
                });
        $baseAdapterMock->expects($this->exactly(1))
                ->method('allColumns')
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $subquery = new Query($baseAdapterMock);
        $subquery->setTable('subqueryTableName')->close();
        $matcherTwo = $this->exactly(2);
        $baseAdapterMock->expects($matcherTwo)
                ->method('opSubquery')
                ->willReturnCallback(function ($columns, $columnAlias) use ($matcherTwo, $subquery) {
                    switch ($matcherTwo->numberOfInvocations()) {
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
        $matcherThree = $this->exactly(2);
        $baseAdapterMock->expects($matcherThree)
                ->method('parseSelect')
                ->willReturnCallback(function ($distinct, $select, $from, $where, $groupby, $having, $orderby, $offset, $limit) use ($matcherThree) {
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            $this->assertFalse($distinct);
                            $this->assertEquals(['subquery'], $select);
                            $this->assertEquals('table_name_one', $from);
                            $this->assertEquals([], $where);
                            $this->assertEquals([], $groupby);
                            $this->assertEquals([], $having);
                            $this->assertEquals([], $orderby);
                            $this->assertEquals(0, $offset);
                            $this->assertEquals(0, $limit);
                            break;
                        case 2:
                            $this->assertFalse($distinct);
                            $this->assertEquals(['table_name_two.*', 'subquery'], $select);
                            $this->assertEquals('table_name_two', $from);
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
        $queryOne->setTable('tableNameOne')
                ->setSubqueryColumn($subquery)
                ->close();
        $this->assertEquals('', $queryOne->getCommandToExecute());
        $queryTwo = new Query($baseAdapterMock);
        $queryTwo->setTable('tableNameTwo')
                ->setSubqueryColumn($subquery, 'columnAlias', true)
                ->close();
        $this->assertEquals('', $queryTwo->getCommandToExecute());
    }

    public function testSelectWhere()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('escapeTable', null)
                ->with('tableName')
                ->willReturn('table_name');
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
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
                ->with(false, ['table_name.*'], 'table_name', [
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
        $query->setTable('tableName')
                ->setWhere()
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
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
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
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $baseAdapterMock->expects($this->once())
                ->method('escapeTable')
                ->with('tableName')
                ->willReturn('table_name');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['table_name.*'], 'table_name', [], [], [], [], 0, 0);
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
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $matcherOne = $this->exactly(2);
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeTable')
                ->willReturnCallback(function ($tableName) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('tableName', $tableName);
                            return 'table_name';
                        case 2:
                            $this->assertEquals('A', $tableName);
                            return 'a';
                    }
                });
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['table_name.*'], 'table_name as a', [], [], [], [], 0, 0);
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
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
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
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $matcherOne = $this->exactly(3);
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeColumn')
                ->willReturnCallback(function ($column, $foreignKey) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('id', $column);
                            $this->assertFalse($foreignKey);
                            return 'id';
                        case 2:
                            $this->assertEquals('column', $column);
                            $this->assertFalse($foreignKey);
                            return 'column';
                        case 3:
                            $this->assertEquals('foreignKey', $column);
                            $this->assertTrue($foreignKey);
                            return 'foreign_key_id';
                    }
                });
        $baseAdapterMock->expects($this->exactly(2))
                ->method('parseComparisonOperator')
                ->with(ComparisonOperator::equal)
                ->willReturn('=');
        $baseAdapterMock->expects($this->exactly(2))
                ->method('escapeValue')
                ->with(Placeholder::placeholder)
                ->willReturn('?');
        $matcherTwo = $this->exactly(4);
        $baseAdapterMock->expects($matcherTwo)
                ->method('escapeOrderIndexing')
                ->willReturnCallback(function ($order) use ($matcherTwo) {
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                        case 3:
                            $this->assertEquals(Indexing::asc, $order);
                            return 'ASC';
                        case 2:
                        case 4:
                            $this->assertEquals(Indexing::desc, $order);
                            return 'DESC';
                    }
                });
        $baseAdapterMock->expects($this->exactly(3))
                ->method('openBlock')
                ->willReturn('(');
        $baseAdapterMock->expects($this->exactly(3))
                ->method('closeBlock')
                ->willReturn(')');
        $subqueryMock = $this->createMock(Query::class);
        $subqueryMock->expects($this->once())
                ->method('getCommandToExecute')
                ->willReturn('subquery');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], '', [], [], [], ['id ASC', '(column = ?) DESC', '(foreign_key_id = ?) ASC', '(subquery) DESC'], 0, 0);
        $query = new Query($baseAdapterMock);
        $query->setWhere()
                ->setOrderBy(['id' => Indexing::asc])
                ->appendOrderByCondition('column', ComparisonOperator::equal, Placeholder::placeholder, Indexing::desc)
                ->appendOrderByCondition('foreignKey', ComparisonOperator::equal, Placeholder::placeholder, Indexing::asc, true)
                ->appendOrderBySubquery($subqueryMock, Indexing::desc)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }

    public function testGroupBy()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
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

    public function testInsertIntoEntityWithOnlyId()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->never())
                ->method('allColumns');
        $baseAdapterMock->expects($this->never())
                ->method('escapeColumns');
        $baseAdapterMock->expects($this->once())
                ->method('parseInsert')
                ->with('', [], []);
        $query = new Query($baseAdapterMock);
        $query->close();
        $this->assertEquals('', $query->getCommandToExecute(Statement::insert));
    }
}

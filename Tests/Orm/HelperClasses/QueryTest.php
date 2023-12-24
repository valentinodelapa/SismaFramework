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
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Keyword;

/**
 * Description of QueryTest
 */
class QueryTest extends TestCase
{
    public function testSelectAllColumns()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');
        $baseAdapterMock->expects($this->once())
                ->method('escapeIdentifier')
                ->with('tableName')
                ->willReturn('table_name');
        $matcherOne = $this->exactly(5);
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeColumn')
                ->willReturnCallback(function ($column, $foreignKey) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('columnNameOne', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_name_one';
                        case 2:
                            $this->assertEquals('columnNameTwo', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_name_two';
                        case 3:
                            $this->assertEquals('columnNameTree', $column);
                            $this->assertFalse($foreignKey);
                            return 'column_name_tree';
                        case 4:
                            $this->assertEquals('columnNameFour', $column);
                            $this->assertTrue($foreignKey);
                            return 'column_name_four_id';
                        case 5:
                            $this->assertEquals('id', $column);
                            $this->assertFalse($foreignKey);
                            return 'id';
                    }
                });
        $matcherTwo = $this->exactly(4);
        $baseAdapterMock->expects($matcherTwo)
                ->method('escapeValue')
                ->willReturnCallback(function ($value, $operator) use ($matcherTwo) {
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(Keyword::placeholder, $value);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            return $value->value;
                        case 2:
                            $this->assertEquals(1, $value);
                            $this->assertEquals(ComparisonOperator::greater, $operator);
                            return $value;
                        case 3:
                            $this->assertEquals('value', $value);
                            $this->assertEquals(ComparisonOperator::lessOrEqual, $operator);
                            return $value;
                        case 4:
                            $this->assertEquals(Keyword::placeholder, $value);
                            $this->assertEquals(ComparisonOperator::isNull, $operator);
                            return '';
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
                ->method('escapeColumns')
                ->with(['columnNameOne', 'columnNameTwo'])
                ->willReturn(['column_name_one', 'column_name_two']);
        $baseAdapterMock->expects($this->once())
                ->method('escapeOrderIndexing')
                ->with(Indexing::asc)
                ->willReturn('ASC');
        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(false, ['*'], ['table_name'], [
                    'column_name_one = ?',
                    'AND',
                    '(',
                    'column_name_two > 1',
                    'OR',
                    'column_name_tree <= value',
                    ')',
                    'NOT',
                    'column_name_four_id IS NULL ',
                ], ['column_name_one', 'column_name_two'], [], ['id' => 'ASC'], 5, 10);
        $query = new Query($baseAdapterMock);
        $query->setTable('tableName')
                ->setWhere()
                ->appendCondition('columnNameOne', ComparisonOperator::equal)
                ->appendAnd()
                ->appendOpenBlock()
                ->appendCondition('columnNameTwo', ComparisonOperator::greater, 1)
                ->appendOr()
                ->appendCondition('columnNameTree', ComparisonOperator::lessOrEqual, 'value')
                ->appendCloseBlock()
                ->appendNot()
                ->appendCondition('columnNameFour', ComparisonOperator::isNull, Keyword::placeholder, true)
                ->setGroupBy(['columnNameOne', 'columnNameTwo'])
                ->setOrderBy(['id' => Indexing::asc])
                ->setOffset(5)
                ->setLimit(10)
                ->close();
        $this->assertEquals('', $query->getCommandToExecute());
    }
}

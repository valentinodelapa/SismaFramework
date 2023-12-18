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

namespace SismaFramework\Tests\Orm\Adapters;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\Adapters\AdapterMysql;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Exceptions\AdapterException;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\ResultSets\ResultSetMysql;
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Sample\Entities\BaseSample;

/**
 * @author Valentino de Lapa
 */
class AdapterMysqlTest extends TestCase
{
    private \PDO $connectionMock;
    
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->connectionMock = $this->createMock(\PDO::class);
        AdapterMysql::setConnection($this->connectionMock);
    }

    public function testAllColumns()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('*', $adapterMysql->allColumns());
    }

    public function testEscapeIdentifier()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('*', $adapterMysql->escapeIdentifier("*"));
        $this->assertEquals('1', $adapterMysql->escapeIdentifier("1"));
        $this->assertEquals('1.1', $adapterMysql->escapeIdentifier("1.1"));
        $this->assertEquals('`Table`.`column_name`', $adapterMysql->escapeIdentifier("T-a#b@l4e.c#o8l(umn_name"));
    }
    
    public function testEscapeOrderIndexing()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals("ASC", $adapterMysql->escapeOrderIndexing(Indexing::asc));
        $this->assertEquals("DESC", $adapterMysql->escapeOrderIndexing(Indexing::desc));
        $this->assertEquals("ASC", $adapterMysql->escapeOrderIndexing("ASC"));
        $this->assertEquals("DESC", $adapterMysql->escapeOrderIndexing("DESC"));
        $this->assertEmpty($adapterMysql->escapeOrderIndexing("B"));
        $this->assertEmpty($adapterMysql->escapeOrderIndexing());
    }
    
    public function testEscapeColumns()
    {
        $adapterMysql = new AdapterMysql();
        $escapedColumns = $adapterMysql->escapeColumns(['columnName', 'tableName.column#Name']);
        $this->assertIsArray($escapedColumns);
        $this->assertEquals('`column_name`', $escapedColumns[0]);
        $this->assertEquals('`table_name`.`column_name`', $escapedColumns[1]);
    }
    
    public function testEscapeColumn()
    {
        
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('`column_name_id`', $adapterMysql->escapeColumn('columnName', true));
        $this->assertEquals('`column_name`', $adapterMysql->escapeColumn('columnName'));
        $this->assertEquals('`table_name`.`column_name_id`', $adapterMysql->escapeColumn('tableName.column#Name', true));
        $this->assertEquals('`table_name`.`column_name`', $adapterMysql->escapeColumn('tableName.column#Name'));
    }
    
    public function testEscapeValue()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEmpty($adapterMysql->escapeValue(null, ComparisonOperator::isNull));
        $this->assertEmpty($adapterMysql->escapeValue(null, ComparisonOperator::isNotNull));
        $this->assertEmpty($adapterMysql->escapeValue(1, ComparisonOperator::isNull));
        $this->assertEmpty($adapterMysql->escapeValue(1, ComparisonOperator::isNotNull));
        $this->assertEmpty($adapterMysql->escapeValue('sample', ComparisonOperator::isNull));
        $this->assertEmpty($adapterMysql->escapeValue('sample', ComparisonOperator::isNotNull));
        $this->assertEmpty($adapterMysql->escapeValue(Keyword::placeholder, ComparisonOperator::isNull));
        $this->assertEmpty($adapterMysql->escapeValue(Keyword::placeholder, ComparisonOperator::isNotNull));
        
        $this->assertEquals('1,sample,?',$adapterMysql->escapeValue([1, 'sample', Keyword::placeholder], ComparisonOperator::in));
        $this->assertEquals('1,sample,?',$adapterMysql->escapeValue([1, 'sample', Keyword::placeholder], ComparisonOperator::notIn));
        $this->assertEquals('sample',$adapterMysql->escapeValue('sample', ComparisonOperator::in));
        $this->assertEquals('sample',$adapterMysql->escapeValue('sample', ComparisonOperator::notIn));
        
        $this->assertEquals('1', $adapterMysql->escapeValue(1));
        $this->assertEquals('sample', $adapterMysql->escapeValue('sample'));
        $this->assertEquals(Keyword::placeholder->value, $adapterMysql->escapeValue(Keyword::placeholder));
        $sismaDateTime = new SismaDateTime();
        $this->assertEquals($sismaDateTime->format('Y-m-d H:i:s'), $adapterMysql->escapeValue($sismaDateTime));
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        $this->assertEquals('1', $adapterMysql->escapeValue($baseSample));
        $this->assertEquals('1', $adapterMysql->escapeValue([1, 'sample', Keyword::placeholder]));
    }
    
    public function testOpenBlock()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('( ', $adapterMysql->openBlock());
    }
    
    public function testCloseBlock()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals(' )', $adapterMysql->closeBlock());
    }
    
    public function testOpAnd()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('AND', $adapterMysql->opAND());
    }
    
    public function testOpOr()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('OR', $adapterMysql->opOR());
    }
    
    public function testOpNot()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('NOT', $adapterMysql->opNOT());
    }
    
    public function testOpCount()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('COUNT(*) as _numrows', $adapterMysql->opCOUNT('', false));
        $this->assertEquals('COUNT(*) as _numrows', $adapterMysql->opCOUNT('*', false));
        $this->assertEquals('COUNT(`column_name`) as _numrows', $adapterMysql->opCOUNT('column#Name', false));
        $this->assertEquals('COUNT(`table_name`.`column_name`) as _numrows', $adapterMysql->opCOUNT('table-Name.column#Name', false));
        $this->assertEquals('COUNT(DISTINCT `column_name`) as _numrows', $adapterMysql->opCOUNT('column#Name', true));
        $this->assertEquals('COUNT(DISTINCT `table_name`.`column_name`) as _numrows', $adapterMysql->opCOUNT('table-Name.column#Name', true));
    }
    
    public function testOpSubquery()
    {
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->any())
                ->method('getCommandToExecute')
                ->willReturn('subquery');
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('(subquery)', $adapterMysql->opSubquery($queryMock));
        $this->assertEquals('(subquery) as `alias`', $adapterMysql->opSubquery($queryMock, 'alias'));
    }
    
    public function testParseSelect()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('SELECT * FROM `table_name` WHERE `table_name`.`id` = 1 ', $adapterMysql->parseSelect(false, ['*'], ['`table_name`'], ['`table_name`.`id` = 1'], [], [], [], 0, 0));
        $this->assertEquals('SELECT DISTINCT `table_name`.`column_name_one`,`table_name`.`column_name_two` FROM `table_name` WHERE `table_name`.`id` = 1 GROUP_BY `table_name`.`column_name_one` HAVING `value` ORDER BY `table_name`.`id` ASC LIMIT 10 OFFSET 1 ',
                $adapterMysql->parseSelect(true, ['`table_name`.`column_name_one`', '`table_name`.`column_name_two`'], ['`table_name`'], ['`table_name`.`id` = 1'], ['`table_name`.`column_name_one`'], ['`value`'], ['`table_name`.`id`' => 'ASC'], 1, 10));
    }
    
    public function testParseInsert()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('INSERT INTO `table_name` (`column_name_one`,`column_name_two`) VALUES (`valueOne`,`valueTwo`)', $adapterMysql->parseInsert(['`table_name`'], ['`column_name_one`','`column_name_two`'], ['`valueOne`', '`valueTwo`']));
    }
    
    public function testParseUpdate()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('UPDATE `table_name` SET `column_name_one` = `valueOne`,`column_name_two` = `valueTwo` WHERE `table_name`.`id` = 1', $adapterMysql->parseUpdate(['`table_name`'], ['`column_name_one`','`column_name_two`'], ['`valueOne`', '`valueTwo`'], ['`table_name`.`id` = 1']));
    }
    
    public function testParseDelete()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('DELETE FROM `table_name` WHERE `table_name`.`id` = 1', $adapterMysql->parseDelete(['`table_name`'], ['`table_name`.`id` = 1']));
    }
    
    public function testSelect()
    {
        $pdoStatementMock = $this->createMock(\PDOStatement::class);
        $pdoStatementMock->expects($this->any())
                ->method('execute');
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->any())
                ->method('prepare')
                ->willReturn($pdoStatementMock);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $this->assertInstanceOf(ResultSetMysql::class, $adapterMysql->select(''));
    }
    
    public function testExecuteTrue()
    {
        $pdoStatementMock = $this->createMock(\PDOStatement::class);
        $pdoStatementMock->expects($this->any())
                ->method('execute')
                ->willReturn(true);
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->any())
                ->method('prepare')
                ->willReturn($pdoStatementMock);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $this->assertTrue($adapterMysql->execute(''));
    }
    
    public function testExecute()
    {
        $this->expectException(AdapterException::class);
        $pdoStatementMock = $this->createMock(\PDOStatement::class);
        $pdoStatementMock->expects($this->any())
                ->method('execute')
                ->willReturn(false);
        $pdoStatementMock->expects($this->any())
                ->method('errorInfo')
                ->willReturn(['error', '000', 'message']);
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->any())
                ->method('prepare')
                ->willReturn($pdoStatementMock);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $adapterMysql->execute('');
    }
}

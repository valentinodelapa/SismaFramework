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
use SismaFramework\Orm\Enumerations\Indexing;

/**
 * @author Valentino de Lapa
 */
class AdapterMysqlTest extends TestCase
{
    public function __construct(string $name)
    {
        parent::__construct($name);
        $connectionMock = $this->createMock(\PDOStatement::class);
        AdapterMysql::setConnection($connectionMock);
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
        $this->assertEquals("ASC", $adapterMysql->escapeOrderIndexing("ASC"));
        $this->assertEquals("DESC", $adapterMysql->escapeOrderIndexing(Indexing::desc));
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
}

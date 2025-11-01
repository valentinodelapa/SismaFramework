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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Orm\Adapters\AdapterMysql;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Enumerations\AdapterType;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Exceptions\AdapterException;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\DataMapper\TransactionManager;
use SismaFramework\Orm\HelperClasses\DataMapper\QueryExecutor;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\ResultSets\ResultSetMysql;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */
class AdapterMysqlTest extends TestCase
{

    private Config $configMock;
    private DataMapper $dataMapperMock;

    #[\Override]
    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['defaultAdapterType', AdapterType::mysql],
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['developmentEnvironment', false],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
                    ['ormCache', true],
        ]);
        Config::setInstance($this->configMock);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $processedEntitesCollectionMock = $this->createMock(ProcessedEntitiesCollection::class);

        $transactionManager = new TransactionManager($baseAdapterMock, $processedEntitesCollectionMock);
        $queryExecutor = new QueryExecutor($baseAdapterMock, fn() => $this->configMock->ormCache);

        $this->dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->setConstructorArgs([
                    $baseAdapterMock,
                    $processedEntitesCollectionMock,
                    $this->configMock,
                    $transactionManager,
                    $queryExecutor
                ])
                ->getMock();
        $connectionMock = $this->createMock(\PDO::class);
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

    public function testEscapeTable()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('`table_name`', $adapterMysql->escapeTable("tableName"));
        $this->assertEquals('`table_name`', $adapterMysql->escapeTable("table#Name"));
        $this->assertEquals('`table_name` as `alias`', $adapterMysql->escapeTable("TableName", "Alias"));
        $this->assertEquals('`table_name` as `alias`', $adapterMysql->escapeTable("Table#Name", "Ali@as"));
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
        $this->assertEmpty($adapterMysql->escapeValue(Placeholder::placeholder, ComparisonOperator::isNull));
        $this->assertEmpty($adapterMysql->escapeValue(Placeholder::placeholder, ComparisonOperator::isNotNull));

        $this->assertEquals('( 1,sample,? )', $adapterMysql->escapeValue([1, 'sample', Placeholder::placeholder], ComparisonOperator::in));
        $this->assertEquals('( 1,sample,? )', $adapterMysql->escapeValue([1, 'sample', Placeholder::placeholder], ComparisonOperator::notIn));
        $this->assertEquals('( sample )', $adapterMysql->escapeValue('sample', ComparisonOperator::in));
        $this->assertEquals('( sample )', $adapterMysql->escapeValue('sample', ComparisonOperator::notIn));

        $this->assertEquals('1', $adapterMysql->escapeValue(1));
        $this->assertEquals('sample', $adapterMysql->escapeValue('sample'));
        $this->assertEquals(Placeholder::placeholder->getAdapterVersion(AdapterType::mysql), $adapterMysql->escapeValue(Placeholder::placeholder));
        $sismaDateTime = new SismaDateTime();
        $this->assertEquals($sismaDateTime->format('Y-m-d H:i:s'), $adapterMysql->escapeValue($sismaDateTime));
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        $this->assertEquals('1', $adapterMysql->escapeValue($baseSample));
        $this->assertEquals('1', $adapterMysql->escapeValue([1, 'sample', Placeholder::placeholder]));
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
        $this->assertEquals('SELECT * FROM `table_name` WHERE `table_name`.`id` = 1 ', $adapterMysql->parseSelect(false, ['*'], '`table_name`', ['`table_name`.`id` = 1'], [], [], [], 0, 0));
        $this->assertEquals('SELECT DISTINCT `table_name`.`column_name_one`,`table_name`.`column_name_two` FROM `table_name` WHERE `table_name`.`id` = 1 GROUP_BY `table_name`.`column_name_one` HAVING `value` ORDER BY `table_name`.`id` ASC LIMIT 10 OFFSET 1 ',
                $adapterMysql->parseSelect(true, ['`table_name`.`column_name_one`', '`table_name`.`column_name_two`'], '`table_name`', ['`table_name`.`id` = 1'], ['`table_name`.`column_name_one`'], ['`value`'], ['`table_name`.`id` ASC'], 1, 10));
    }

    public function testParseInsert()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('INSERT INTO `table_name` (`column_name_one`,`column_name_two`) VALUES (`valueOne`,`valueTwo`)', $adapterMysql->parseInsert('`table_name`', ['`column_name_one`', '`column_name_two`'], ['`valueOne`', '`valueTwo`']));
    }

    public function testParseUpdate()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('UPDATE `table_name` SET `column_name_one` = `valueOne`,`column_name_two` = `valueTwo` WHERE `table_name`.`id` = 1', $adapterMysql->parseUpdate('`table_name`', ['`column_name_one`', '`column_name_two`'], ['`valueOne`', '`valueTwo`'], ['`table_name`.`id` = 1']));
    }

    public function testParseDelete()
    {
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('DELETE FROM `table_name` WHERE `table_name`.`id` = 1', $adapterMysql->parseDelete('`table_name`', ['`table_name`.`id` = 1']));
    }

    public function testSelect()
    {
        $sismaDateTime = new SismaDateTime();
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        $standardClass = new \stdClass();
        $pdoStatementMock = $this->createMock(\PDOStatement::class);
        $pdoStatementMock->expects($this->any())
                ->method('execute');
        $matcher = $this->exactly(18);
        $pdoStatementMock->expects($matcher)
                ->method('bindParam')
                ->willReturnCallback(function ($key, $value, $type) use ($matcher, $sismaDateTime, $baseSample, $standardClass) {
                    switch ($matcher->numberOfInvocations()) {
                        case 18:
                            $this->assertEquals($key, '*');
                            break;
                        default :
                            $this->assertEquals($key, $matcher->numberOfInvocations());
                            break;
                    }
                    switch ($matcher->numberOfInvocations()) {
                        case 1 :
                        case 9:
                            $this->assertEquals($value, 1);
                            $this->assertEquals($type, \PDO::PARAM_INT);
                            break;
                        case 2 :
                        case 10:
                            $this->assertEquals($value, 1.1);
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 3 :
                        case 11:
                            $this->assertEquals($value, true);
                            $this->assertEquals($type, \PDO::PARAM_BOOL);
                            break;
                        case 4 :
                        case 12:
                            $this->assertEquals($value, 'string');
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 5 :
                        case 13:
                            $this->assertEquals($value, $sismaDateTime);
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 6 :
                        case 14:
                            $this->assertEquals($value, SampleType::one);
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 7 :
                        case 15:
                            $this->assertEquals($value, $baseSample);
                            $this->assertEquals($type, \PDO::PARAM_INT);
                            break;
                        case 8 :
                        case 16:
                            $this->assertEquals($value, null);
                            $this->assertEquals($type, \PDO::PARAM_NULL);
                            break;
                        case 17:
                            $this->assertEquals($value, $standardClass);
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 18:
                            $this->assertEquals($value, 'associativeIndexString');
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                    }
                    return true;
                });
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->any())
                ->method('prepare')
                ->willReturn($pdoStatementMock);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $bindValues = [
            0 => 1,
            1 => 1.1,
            2 => true,
            3 => 'string',
            4 => $sismaDateTime,
            5 => SampleType::one,
            6 => $baseSample,
            7 => null,
            8 => 1,
            9 => 1.1,
            10 => true,
            11 => 'string',
            12 => $sismaDateTime,
            13 => SampleType::one,
            14 => $baseSample,
            15 => null,
            16 => $standardClass,
            '*' => 'associativeIndexString',
        ];
        $bindTypes = [
            0 => DataType::typeInteger,
            1 => DataType::typeDecimal,
            2 => DataType::typeBoolean,
            3 => DataType::typeString,
            4 => DataType::typeDate,
            5 => DataType::typeEnumeration,
            6 => DataType::typeEntity,
            7 => DataType::typeNull,
        ];
        $this->assertInstanceOf(ResultSetMysql::class, $adapterMysql->select('', $bindValues, $bindTypes));
    }

    public function testExecuteTrue()
    {
        $sismaDateTime = new SismaDateTime();
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        $standardClass = new \stdClass();
        $pdoStatementMock = $this->createMock(\PDOStatement::class);
        $pdoStatementMock->expects($this->any())
                ->method('execute')
                ->willReturn(true);
        $matcher = $this->exactly(18);
        $pdoStatementMock->expects($matcher)
                ->method('bindParam')
                ->willReturnCallback(function ($key, $value, $type) use ($matcher, $sismaDateTime, $baseSample, $standardClass) {
                    switch ($matcher->numberOfInvocations()) {
                        case 18:
                            $this->assertEquals($key, '*');
                            break;
                        default :
                            $this->assertEquals($key, $matcher->numberOfInvocations());
                            break;
                    }
                    switch ($matcher->numberOfInvocations()) {
                        case 1 :
                        case 9:
                            $this->assertEquals($value, 1);
                            $this->assertEquals($type, \PDO::PARAM_INT);
                            break;
                        case 2 :
                        case 10:
                            $this->assertEquals($value, 1.1);
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 3 :
                        case 11:
                            $this->assertEquals($value, true);
                            $this->assertEquals($type, \PDO::PARAM_BOOL);
                            break;
                        case 4 :
                        case 12:
                            $this->assertEquals($value, 'string');
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 5 :
                        case 13:
                            $this->assertEquals($value, $sismaDateTime);
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 6 :
                        case 14:
                            $this->assertEquals($value, SampleType::one);
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 7 :
                        case 15:
                            $this->assertEquals($value, $baseSample);
                            $this->assertEquals($type, \PDO::PARAM_INT);
                            break;
                        case 8 :
                        case 16:
                            $this->assertEquals($value, null);
                            $this->assertEquals($type, \PDO::PARAM_NULL);
                            break;
                        case 17:
                            $this->assertEquals($value, $standardClass);
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                        case 18:
                            $this->assertEquals($value, 'associativeIndexString');
                            $this->assertEquals($type, \PDO::PARAM_STR);
                            break;
                    }
                    return true;
                });
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->any())
                ->method('prepare')
                ->willReturn($pdoStatementMock);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $bindValues = [
            0 => 1,
            1 => 1.1,
            2 => true,
            3 => 'string',
            4 => $sismaDateTime,
            5 => SampleType::one,
            6 => $baseSample,
            7 => null,
            8 => 1,
            9 => 1.1,
            10 => true,
            11 => 'string',
            12 => $sismaDateTime,
            13 => SampleType::one,
            14 => $baseSample,
            15 => null,
            16 => $standardClass,
            '*' => 'associativeIndexString',
        ];
        $bindTypes = [
            0 => DataType::typeInteger,
            1 => DataType::typeDecimal,
            2 => DataType::typeBoolean,
            3 => DataType::typeString,
            4 => DataType::typeDate,
            5 => DataType::typeEnumeration,
            6 => DataType::typeEntity,
            7 => DataType::typeNull,
        ];
        $this->assertTrue($adapterMysql->execute('', $bindValues, $bindTypes));
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

    public function testLastInsertId()
    {
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->once())
                ->method('lastInsertId')
                ->willReturn('1');
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('1', $adapterMysql->lastInsertId(''));
        AdapterMysql::setConnection(null);
        $this->assertEquals(-1, $adapterMysql->lastInsertId(''));
    }

    public function testBeginTransaction()
    {
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturn(true);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $this->assertTrue($adapterMysql->beginTransaction());
        AdapterMysql::setConnection(null);
        $this->assertfalse($adapterMysql->beginTransaction());
    }

    public function testCommittTransaction()
    {
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->once())
                ->method('commit')
                ->willReturn(true);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $this->assertTrue($adapterMysql->commitTransaction());
        AdapterMysql::setConnection(null);
        $this->assertFalse($adapterMysql->commitTransaction());
    }

    public function testRollbackTransaction()
    {
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->once())
                ->method('rollBack')
                ->willReturn(true);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $this->assertTrue($adapterMysql->rollbackTransaction());
        AdapterMysql::setConnection(null);
        $this->assertFalse($adapterMysql->rollbackTransaction());
    }

    public function testGetLastErrorMsg()
    {
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->once())
                ->method('errorInfo')
                ->willReturn(['error message']);
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('error message', $adapterMysql->getLastErrorMsg());
        AdapterMysql::setConnection(null);
        $this->assertEquals('', $adapterMysql->getLastErrorMsg());
    }

    public function testGetLastErrorCode()
    {
        $connectionMock = $this->createMock(\PDO::class);
        $connectionMock->expects($this->once())
                ->method('errorCode')
                ->willReturn('code');
        AdapterMysql::setConnection($connectionMock);
        $adapterMysql = new AdapterMysql();
        $this->assertEquals('code', $adapterMysql->getLastErrorCode());
        AdapterMysql::setConnection(null);
        $this->assertEquals(-1, $adapterMysql->getLastErrorCode());
    }
}

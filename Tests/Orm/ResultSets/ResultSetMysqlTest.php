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

namespace SismaFramework\Tests\Orm\ResultSets;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\ResultSets\ResultSetMysql;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\EntityWithEncryptedPropertyOne;

/**
 * @author Valentino de Lapa
 */
class ResultSetMysqlTest extends TestCase
{

    private Config $configStub;
    private \PDOStatement $PDOStatementMock;

    public function setUp(): void
    {
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['developmentEnvironment', true],
                    ['encryptionPassphrase', 'encryption-key'],
                    ['encryptionAlgorithm', 'AES-256-CBC'],
                    ['initializationVectorBytes', 16],
                    ['ormCache', true],
        ]);
        $this->configStub->method('__isset')
                ->willReturnMap([
                    ['encryptionPassphrase', true],
        ]);
        Config::setInstance($this->configStub);
        $this->PDOStatementMock = $this->createStub(\PDOStatement::class);
    }

    public function testNumRows()
    {
        $this->PDOStatementMock->method('rowCount')
                ->willReturn(10);
        $resultSetMysql = new ResultSetMysql($this->PDOStatementMock);
        $this->assertEquals(10, $resultSetMysql->numRows());
    }

    public function testFetchWithNoRows()
    {
        $this->PDOStatementMock->method('rowCount')
                ->willReturn(0);
        $resultSetMysql = new ResultSetMysql($this->PDOStatementMock);
        $this->assertNull($resultSetMysql->fetch());
    }

    public function testFetchWithRelease()
    {
        $this->PDOStatementMock->method('rowCount')
                ->willReturn(0);
        $resultSetMysql = new ResultSetMysql($this->PDOStatementMock);
        $resultSetMysql->release();
        $this->assertNull($resultSetMysql->fetch());
    }

    public function testFetchWithPDOFetchFalse()
    {
        $this->PDOStatementMock->method('rowCount')
                ->willReturn(1);
        $this->PDOStatementMock->method('fetch')
                ->willReturn(false);
        $resultSetMysql = new ResultSetMysql($this->PDOStatementMock);
        $this->assertNull($resultSetMysql->fetch());
    }

    public function testFetchWithStandardEntity()
    {
        $this->PDOStatementMock->method('rowCount')
                ->willReturnCallback(function () {
                    $rowsNum = 1;
                    $actualRowsNum = $rowsNum;
                    $rowsNum--;
                    return $actualRowsNum;
                });
        $result = new \stdClass();
        $result->id = 1;
        $result->name = 'name';
        $this->PDOStatementMock->method('fetch')
                ->willReturn($result);
        $resultSetMysql = new ResultSetMysql($this->PDOStatementMock);
        $this->assertTrue($resultSetMysql->valid());
        $this->assertEquals(0, $resultSetMysql->key());
        $this->assertInstanceOf(StandardEntity::class, $resultSetMysql->fetch());
        $this->assertFalse($resultSetMysql->valid());
        $this->assertEquals(1, $resultSetMysql->key());
        $this->assertNull($resultSetMysql->fetch());
    }

    public function testFetchWithBaseEntity()
    {
        $PDOStatementMock = $this->createMock(\PDOStatement::class);
        $PDOStatementMock->expects($this->any())
                ->method('rowCount')
                ->willReturnCallback(function () {
                    $rowsNum = 1;
                    $actualRowsNum = $rowsNum;
                    $rowsNum--;
                    return $actualRowsNum;
                });
        $result = new \stdClass();
        $result->id = 1;
        $result->stringWithoutInitialization = 'name';
        $PDOStatementMock->expects($this->once())
                ->method('fetch')
                ->willReturn($result);
        $resultSetMysql = new ResultSetMysql($PDOStatementMock);
        $resultSetMysql->setReturnType(BaseSample::class);
        $this->assertTrue($resultSetMysql->valid());
        $this->assertEquals(0, $resultSetMysql->key());
        $this->assertInstanceOf(BaseSample::class, $resultSetMysql->fetch());
        $this->assertFalse($resultSetMysql->valid());
        $this->assertEquals(1, $resultSetMysql->key());
        $this->assertNull($resultSetMysql->fetch());
    }

    public function testFetchWithEntityWithEncryptedProperty()
    {
        $PDOStatementMock = $this->createMock(\PDOStatement::class);
        $PDOStatementMock->expects($this->any())
                ->method('rowCount')
                ->willReturnCallback(function () {
                    $rowsNum = 1;
                    $actualRowsNum = $rowsNum;
                    $rowsNum--;
                    return $actualRowsNum;
                });
        $propertyValueOne = 'test-value-one';
        $propertyValueTwo = 'test-value-two';
        $initializationVector = Encryptor::createInitializationVector($this->configStub);
        $result = new \stdClass();
        $result->id = 1;
        $result->encrypted_property_one = Encryptor::encryptString($propertyValueOne, $initializationVector, $this->configStub);
        $result->encrypted_property_two = Encryptor::encryptString($propertyValueTwo, $initializationVector, $this->configStub);
        $result->initialization_vector = $initializationVector;
        $PDOStatementMock->expects($this->once())
                ->method('fetch')
                ->willReturn($result);
        $resultSetMysql = new ResultSetMysql($PDOStatementMock);
        $resultSetMysql->setReturnType(EntityWithEncryptedPropertyOne::class);
        $this->assertTrue($resultSetMysql->valid());
        $this->assertEquals(0, $resultSetMysql->key());
        $entityWithEncryptedProperty = $resultSetMysql->fetch();
        $this->assertInstanceOf(EntityWithEncryptedPropertyOne::class, $entityWithEncryptedProperty);
        $this->assertEquals($propertyValueOne, $entityWithEncryptedProperty->encryptedPropertyOne);
        $this->assertEquals($propertyValueTwo, $entityWithEncryptedProperty->encryptedPropertyTwo);
        $this->assertEquals($initializationVector, $entityWithEncryptedProperty->initializationVector);
        $this->assertFalse($resultSetMysql->valid());
        $this->assertEquals(1, $resultSetMysql->key());
        $this->assertNull($resultSetMysql->fetch());
    }

    public function testFetchWithMultipleBaseEntity()
    {
        $PDOStatementMock = $this->createMock(\PDOStatement::class);
        $PDOStatementMock->expects($this->any())
                ->method('rowCount')
                ->willReturnCallback(function () {
                    $rowsNum = 2;
                    $actualRowsNum = $rowsNum;
                    $rowsNum--;
                    return $actualRowsNum;
                });
        $matcher = $this->exactly(4);
        $PDOStatementMock->expects($matcher)
                ->method('fetch')
                ->willReturnCallback(function () use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                        case 2:
                            $result = new \stdClass();
                            $result->id = 1;
                            $result->stringWithoutInitialization = 'name';
                            return $result;
                        case 3:
                        case 4:
                            $result = new \stdClass();
                            $result->id = 2;
                            $result->stringWithoutInitialization = 'name';
                            return $result;
                    }
                });
        $resultSetMysql = new ResultSetMysql($PDOStatementMock);
        $resultSetMysql->setReturnType(BaseSample::class);
        $this->assertTrue($resultSetMysql->valid());
        $this->assertEquals(0, $resultSetMysql->key());
        $this->assertInstanceOf(BaseSample::class, $resultSetMysql->current());
        $this->assertTrue($resultSetMysql->valid());
        $this->assertEquals(0, $resultSetMysql->key());
        $this->assertInstanceOf(BaseSample::class, $resultSetMysql->fetch());
        $this->assertTrue($resultSetMysql->valid());
        $this->assertEquals(1, $resultSetMysql->key());
        $this->assertInstanceOf(BaseSample::class, $resultSetMysql->current());
        $this->assertTrue($resultSetMysql->valid());
        $this->assertEquals(1, $resultSetMysql->key());
        $this->assertInstanceOf(BaseSample::class, $resultSetMysql->fetch());
        $this->assertFalse($resultSetMysql->valid());
        $this->assertEquals(2, $resultSetMysql->key());
        $this->assertNull($resultSetMysql->fetch());
    }

    public function testIndexNavigation()
    {
        $this->PDOStatementMock->method('rowCount')
                ->willReturn(10);
        $resultSetMysql = new ResultSetMysql($this->PDOStatementMock);
        $this->assertEquals(10, $resultSetMysql->numRows());
        $this->assertEquals(0, $resultSetMysql->key());
        $resultSetMysql->next();
        $this->assertEquals(1, $resultSetMysql->key());
        $resultSetMysql->seek(-1);
        $this->assertEquals(0, $resultSetMysql->key());
        $resultSetMysql->seek(15);
        $this->assertEquals(9, $resultSetMysql->key());
        $resultSetMysql->seek(5);
        $this->assertEquals(5, $resultSetMysql->key());
        $resultSetMysql->rewind();
        $this->assertEquals(0, $resultSetMysql->key());
    }

    public function testWithForeach()
    {
        $PDOStatementMock = $this->createMock(\PDOStatement::class);
        $PDOStatementMock->expects($this->any())
                ->method('rowCount')
                ->willReturn(10);
        $result = new \stdClass();
        $result->id = 1;
        $result->stringWithoutInitialization = 'name';
        $PDOStatementMock->expects($this->exactly(10))
                ->method('fetch')
                ->willReturn($result);
        $resultSetMysql = new ResultSetMysql($PDOStatementMock);
        $resultSetMysql->setReturnType(BaseSample::class);
        $current = 0;
        foreach ($resultSetMysql as $baseSample) {
            $this->assertEquals($current, $resultSetMysql->key());
            $this->assertInstanceOf(BaseSample::class, $baseSample);
            $current++;
        }
    }
}

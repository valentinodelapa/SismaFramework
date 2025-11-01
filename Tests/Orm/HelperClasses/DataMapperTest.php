<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\Exceptions\InvalidTypeException;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\Statement;
use SismaFramework\Orm\Exceptions\DataMapperException;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\DataMapper\TransactionManager;
use SismaFramework\Orm\HelperClasses\DataMapper\QueryExecutor;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaTime;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;
use SismaFramework\TestsApplication\Entities\OtherReferencedSample;
use SismaFramework\TestsApplication\Entities\DependentEntityOne;
use SismaFramework\TestsApplication\Entities\DependentEntityTwo;
use SismaFramework\TestsApplication\Entities\EntityWithEncryptedPropertyOne;
use SismaFramework\TestsApplication\Entities\EntityWithEncryptedPropertyTwo;
use SismaFramework\TestsApplication\Entities\EntityWithTwoCollection;
use SismaFramework\TestsApplication\Entities\SimpleEntity;
use SismaFramework\TestsApplication\Entities\SubdependentEntity;
use SismaFramework\TestsApplication\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */
class DataMapperTest extends TestCase
{

    private BaseAdapter $baseAdapterMock;
    private Config $configMock;
    private Query $queryMock;

    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $referenceCacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('cache_', true) . DIRECTORY_SEPARATOR;
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['developmentEnvironment', false],
                    ['encryptionPassphrase', 'encryption-key'],
                    ['encryptionAlgorithm', 'AES-256-CBC'],
                    ['entityNamespace', 'TestsApplication\\Entities\\'],
                    ['entityPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR],
                    ['foreignKeySuffix', 'Collection'],
                    ['initializationVectorBytes', 16],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
                    ['moduleFolders', ['SismaFramework']],
                    ['ormCache', true],
                    ['parentPrefixPropertyName', 'parent'],
                    ['referenceCacheDirectory', $referenceCacheDirectory],
                    ['referenceCachePath', $referenceCacheDirectory . 'referenceCache.json'],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
                    ['sonCollectionPropertyName', 'sonCollection'],
        ]);
        $this->configMock->expects($this->any())
                ->method('__isset')
                ->willReturnMap([
                    ['encryptionPassphrase', true],
        ]);
        Config::setInstance($this->configMock);
        $this->baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($this->baseAdapterMock);
        $this->queryMock = $this->createMock(Query::class);
    }

    /**
     * Helper method to create a DataMapper with mocked dependencies
     */
    private function createDataMapperWithMockedAdapter(?ProcessedEntitiesCollection $processedEntitiesCollection = null): DataMapper
    {
        $transactionManager = new TransactionManager($this->baseAdapterMock, $processedEntitiesCollection);
        $queryExecutor = new QueryExecutor($this->baseAdapterMock);

        return new DataMapper(
            $this->baseAdapterMock,
            $processedEntitiesCollection,
            $this->configMock,
            $transactionManager,
            $queryExecutor
        );
    }

    public function testSaveNewBaseEntityWithInsertInsertInsert()
    {
        $matcher = $this->exactly(3);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample"], $param2);
                            $this->assertEquals([DataType::typeString], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
                            $this->assertEquals([
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeBoolean,
                                    ], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('parseInsert');
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('lastInsertId')
                ->willReturn(1);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertUpdateInsert()
    {
        $matcher = $this->exactly(3);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
                            $this->assertEquals([
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeBoolean,
                                    ], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('parseInsert');
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('lastInsertId')
                ->willReturn(1);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithNothingUpdateInsert()
    {
        $matcher = $this->exactly(2);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 3], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([2, 2, null, 3, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
                            $this->assertEquals([
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeBoolean,
                                    ], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('parseInsert');
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('lastInsertId')
                ->willReturn(1);
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 2;
        $referencedSample->text = 'referenced sample';
        $referencedSample->modified = false;
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 3;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertInsertUpdate()
    {
        $matcher = $this->exactly(3);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample"], $param2);
                            $this->assertEquals([DataType::typeString], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 2], $param2);
                            $this->assertEquals([
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeBoolean,
                                DataType::typeInteger,
                                    ], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('parseInsert');
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('lastInsertId')
                ->willReturn(1);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 2;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertUpdateUpdate()
    {
        $matcher = $this->exactly(3);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 3], $param2);
                            $this->assertEquals([
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeBoolean,
                                DataType::typeInteger,
                                    ], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('parseInsert');
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('parseUpdate');
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('lastInsertId')
                ->willReturn(1);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 3;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithNothingUpdateUpdate()
    {
        $matcher = $this->exactly(2);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 3], $param2);
                            $this->assertEquals([
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeBoolean,
                                DataType::typeInteger,
                                    ], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('parseUpdate');
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 1;
        $referencedSample->text = 'referenced sample';
        $referencedSample->modified = false;
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 3;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertInsertNothing()
    {
        $matcher = $this->exactly(2);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample"], $param2);
                            $this->assertEquals([DataType::typeString], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('parseInsert');
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('lastInsertId')
                ->willReturn(1);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 2;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $baseSample->modified = false;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertUpdateNothing()
    {
        $matcher = $this->exactly(2);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('parseInsert');
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('lastInsertId')
                ->willReturn(1);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 3;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $baseSample->modified = false;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithNothingUpdateNothing()
    {
        $this->baseAdapterMock->expects($this->once())
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) {
                    $this->assertEquals('', $param1);
                    $this->assertEquals(["other referenced sample", 2], $param2);
                    $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 1;
        $referencedSample->text = 'referenced sample';
        $referencedSample->modified = false;
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 3;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $baseSample->modified = false;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewEntityWithEncryptedPropertyOne()
    {
        $initializationVector = null;
        $propertyValueOne = 'test-value-one';
        $propertyValueTwo = 'test-value-two';
        $propertyValueThree = 'test-value-three';
        $matcherOne = $this->exactly(2);
        $this->baseAdapterMock->expects($matcherOne)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($propertyValueOne, $propertyValueTwo, $propertyValueThree, $matcherOne, &$initializationVector) {
                    $this->assertEquals('query', $param1);
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertCount(3, $param2);
                            $this->assertEquals([DataType::typeBinary, DataType::typeString, DataType::typeString], $param3);
                            $initializationVector = $param2[0];
                            $this->assertNotNull($initializationVector);
                            $encryptedPropertyValueOne = Encryptor::encryptString($propertyValueOne, $initializationVector);
                            $encryptedPropertyValueTwo = Encryptor::encryptString($propertyValueTwo, $initializationVector);
                            $this->assertEquals($encryptedPropertyValueOne, $param2[1]);
                            $this->assertEquals($encryptedPropertyValueTwo, $param2[2]);
                            break;
                        case 2:
                            $this->assertCount(4, $param2);
                            $this->assertEquals([DataType::typeBinary, DataType::typeString, DataType::typeString, DataType::typeInteger], $param3);
                            $this->assertEquals($initializationVector, $param2[0]);
                            $encryptedPropertyValueOne = Encryptor::encryptString($propertyValueOne, $initializationVector);
                            $encryptedPropertyValueThree = Encryptor::encryptString($propertyValueThree, $initializationVector);
                            $this->assertEquals($encryptedPropertyValueOne, $param2[1]);
                            $this->assertEquals($encryptedPropertyValueThree, $param2[2]);
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->once())
                ->method('lastInsertId')
                ->willReturn(1);
        $matcherTwo = $this->exactly(4);
        $this->queryMock->expects($matcherTwo)
                ->method('hasColumn')
                ->willReturnCallback(function ($column, $foreignKey) use ($matcherTwo) {
                    $this->assertFalse($foreignKey);
                    $this->assertEquals('initializationVector', $column);
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                        case 3:
                            return false;
                        case 2:
                        case 4:
                            return true;
                    }
                });
        $matcherThree = $this->exactly(6);
        $this->queryMock->expects($matcherThree)
                ->method('appendColumnValue')
                ->willReturnCallback(function ($column, $value, $foreignKey) use ($matcherThree) {
                    $this->assertEquals(Placeholder::placeholder, $value);
                    $this->assertFalse($foreignKey);
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('initializationVector', $column);
                            break;
                        case 2:
                            $this->assertEquals('encryptedPropertyOne', $column);
                            break;
                        case 3:
                            $this->assertEquals('encryptedPropertyTwo', $column);
                            break;
                        case 4:
                            $this->assertEquals('initializationVector', $column);
                            break;
                        case 5:
                            $this->assertEquals('encryptedPropertyOne', $column);
                            break;
                        case 6:
                            $this->assertEquals('encryptedPropertyTwo', $column);
                            break;
                    }
                    return $this->queryMock;
                });
        $matcherFour = $this->exactly(2);
        $this->queryMock->expects($matcherFour)
                ->method('getCommandToExecute')
                ->willReturnCallback(function ($cmdType) use ($matcherFour) {
                    switch ($matcherFour->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(Statement::insert, $cmdType);
                            break;
                        case 2:
                            $this->assertEquals(Statement::update, $cmdType);
                            break;
                    }
                    return 'query';
                });
        $entityWithEncryptedPropertyOne = new EntityWithEncryptedPropertyOne();
        $entityWithEncryptedPropertyOne->encryptedPropertyOne = $propertyValueOne;
        $entityWithEncryptedPropertyOne->encryptedPropertyTwo = $propertyValueTwo;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($entityWithEncryptedPropertyOne, $this->queryMock);
        $entityWithEncryptedPropertyOne->encryptedPropertyTwo = $propertyValueThree;
        $dataMapper->save($entityWithEncryptedPropertyOne, $this->queryMock);
    }

    public function testSaveNewEntityWithEncryptedPropertyTwo()
    {
        $initializationVector = null;
        $propertyValueOne = 'test-value-one';
        $propertyValueTwo = 'test-value-two';
        $propertyValueThree = 'test-value-three';
        $matcherOne = $this->exactly(2);
        $this->baseAdapterMock->expects($matcherOne)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($propertyValueOne, $propertyValueTwo, $propertyValueThree, $matcherOne, &$initializationVector) {
                    $this->assertEquals('query', $param1);
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertCount(3, $param2);
                            $this->assertEquals([DataType::typeBinary, DataType::typeString, DataType::typeString], $param3);
                            $initializationVector = $param2[0];
                            $this->assertNotNull($initializationVector);
                            $encryptedPropertyValueOne = Encryptor::encryptString($propertyValueOne, $initializationVector);
                            $encryptedPropertyValueTwo = Encryptor::encryptString($propertyValueTwo, $initializationVector);
                            $this->assertEquals($encryptedPropertyValueOne, $param2[1]);
                            $this->assertEquals($encryptedPropertyValueTwo, $param2[2]);
                            break;
                        case 2:
                            $this->assertCount(4, $param2);
                            $this->assertEquals([DataType::typeBinary, DataType::typeString, DataType::typeString, DataType::typeInteger], $param3);
                            $this->assertEquals($initializationVector, $param2[0]);
                            $encryptedPropertyValueOne = Encryptor::encryptString($propertyValueOne, $initializationVector);
                            $encryptedPropertyValueThree = Encryptor::encryptString($propertyValueThree, $initializationVector);
                            $this->assertEquals($encryptedPropertyValueOne, $param2[1]);
                            $this->assertEquals($encryptedPropertyValueThree, $param2[2]);
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->once())
                ->method('lastInsertId')
                ->willReturn(1);
        $matcherTwo = $this->exactly(4);
        $this->queryMock->expects($matcherTwo)
                ->method('hasColumn')
                ->willReturnCallback(function ($column, $foreignKey) use ($matcherTwo) {
                    $this->assertFalse($foreignKey);
                    $this->assertEquals('initializationVector', $column);
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                        case 3:
                            return false;
                        case 2:
                        case 4:
                            return true;
                    }
                });
        $matcherThree = $this->exactly(6);
        $this->queryMock->expects($matcherThree)
                ->method('appendColumnValue')
                ->willReturnCallback(function ($column, $value, $foreignKey) use ($matcherThree) {
                    $this->assertEquals(Placeholder::placeholder, $value);
                    $this->assertFalse($foreignKey);
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('initializationVector', $column);
                            break;
                        case 2:
                            $this->assertEquals('encryptedPropertyOne', $column);
                            break;
                        case 3:
                            $this->assertEquals('encryptedPropertyTwo', $column);
                            break;
                        case 4:
                            $this->assertEquals('initializationVector', $column);
                            break;
                        case 5:
                            $this->assertEquals('encryptedPropertyOne', $column);
                            break;
                        case 6:
                            $this->assertEquals('encryptedPropertyTwo', $column);
                            break;
                    }
                    return $this->queryMock;
                });
        $matcherFour = $this->exactly(2);
        $this->queryMock->expects($matcherFour)
                ->method('getCommandToExecute')
                ->willReturnCallback(function ($cmdType) use ($matcherFour) {
                    switch ($matcherFour->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals(Statement::insert, $cmdType);
                            break;
                        case 2:
                            $this->assertEquals(Statement::update, $cmdType);
                            break;
                    }
                    return 'query';
                });
        $entityWithEncryptedPropertyTwo = new EntityWithEncryptedPropertyTwo();
        $entityWithEncryptedPropertyTwo->encryptedPropertyOne = $propertyValueOne;
        $entityWithEncryptedPropertyTwo->encryptedPropertyTwo = $propertyValueTwo;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($entityWithEncryptedPropertyTwo, $this->queryMock);
        $entityWithEncryptedPropertyTwo->encryptedPropertyTwo = $propertyValueThree;
        $dataMapper->save($entityWithEncryptedPropertyTwo, $this->queryMock);
    }

    public function testSaveNewDependentEntityWithForeignKeyIndex()
    {
        $this->baseAdapterMock->expects($this->once())
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) {
                    $this->assertEquals('', $param1);
                    $this->assertCount(1, $param2);
                    $this->assertEquals(2, $param2[0]);
                    $this->assertEquals([DataType::typeInteger], $param3);
                    return true;
                });
        $dependentEntityOne = new DependentEntityOne();
        $dependentEntityOne->entityWithTwoCollection = 2;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($dependentEntityOne);
    }

    public function testNewReferencedEntityWithInsertInsertInsert()
    {
        $matcher = $this->exactly(3);
        $this->baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([DataType::typeString, DataType::typeInteger], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample"], $param2);
                            $this->assertEquals([DataType::typeString], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
                            $this->assertEquals([
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeEntity,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeDate,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeEnumeration,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeString,
                                DataType::typeBoolean,
                                    ], $param3);
                            break;
                    }
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('parseInsert');
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('lastInsertId')
                ->willReturn(1);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $baseSampleCollection = new SismaCollection(BaseSample::class);
        $baseSampleCollection->append($baseSample);
        $referencedSample->setBaseSampleCollectionReferencedEntityWithoutInitialization($baseSampleCollection);
        $referencedSample->setBaseSampleCollectionReferencedEntityWithInitialization($baseSampleCollection);
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($referencedSample);
    }

    public function testInitQuery()
    {
        $this->queryMock->expects($this->any())
                ->method('setTable')
                ->with('entity_name');
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $this->assertInstanceOf(Query::class, $dataMapper->initQuery(BaseSample::class));
    }

    public function testInsertAutomaticStartTransaction()
    {
        $this->baseAdapterMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturn(true);
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('execute')
                ->willReturn(true);
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('lastInsertId')
                ->willReturn(1);
        $this->baseAdapterMock->expects($this->once())
                ->method('commitTransaction')
                ->willReturn(true);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testInsertManualStartTransaction()
    {
        $adapterMethodCallOrder = [];
        $this->baseAdapterMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'beginTransaction';
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('execute')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'execute';
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('lastInsertId')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'lastInsertId';
                    return 1;
                });
        $this->baseAdapterMock->expects($this->once())
                ->method('commitTransaction')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'commitTransaction';
                    return true;
                });
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->startTransaction();
        $dataMapper->save($baseSample);
        $dataMapper->commitTransaction();
        $this->assertEquals(['beginTransaction', 'execute', 'lastInsertId', 'execute', 'lastInsertId', 'execute', 'lastInsertId', 'commitTransaction'], $adapterMethodCallOrder);
    }

    public function testNotDuplicateSavingCollection()
    {
        $adapterMethodCallOrder = [];
        $this->baseAdapterMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'beginTransaction';
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('execute')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'execute';
                    return true;
                });
        $matcher = $this->exactly(3);
        $this->baseAdapterMock->expects($matcher)
                ->method('lastInsertId')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder, $matcher) {
                    $adapterMethodCallOrder[] = 'lastInsertId';
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            return 1;
                        case 2:
                            return 2;
                        case 3:
                            return 3;
                    }
                });
        $this->baseAdapterMock->expects($this->once())
                ->method('commitTransaction')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'commitTransaction';
                    return true;
                });
        $entityWithTwoCollection = new EntityWithTwoCollection();
        $entityWithTwoCollection->addDependentEntityOne(new DependentEntityOne());
        $entityWithTwoCollection->addDependentEntityTwo(new DependentEntityTwo());
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($entityWithTwoCollection);
        $this->assertEquals(['beginTransaction', 'execute', 'lastInsertId', 'execute', 'lastInsertId', 'execute', 'lastInsertId', 'commitTransaction'], $adapterMethodCallOrder);
    }

    public function testDoubleSaveAfterModification()
    {
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('execute')
                ->willReturn(true);
        $processedEntitiesCollectionMock = $this->createMock(ProcessedEntitiesCollection::class);
        $dataMapper = $this->createDataMapperWithMockedAdapter($processedEntitiesCollectionMock);
        $simpleEntity = new SimpleEntity($dataMapper, $processedEntitiesCollectionMock);
        $processedEntitiesCollectionMock->expects($this->exactly(2))
                ->method('append')
                ->with($simpleEntity);
        $processedEntitiesCollectionMock->expects($this->exactly(2))
                ->method('remove')
                ->with($simpleEntity);
        $simpleEntity->string = 'test';
        $dataMapper->save($simpleEntity);
        $simpleEntity->string = 'test-modified';
        $dataMapper->save($simpleEntity);
    }

    public function testSaveModificationOnSubnidificateCollection()
    {
        $adapterMethodCallOrder = [];
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('beginTransaction')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'beginTransaction';
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(5))
                ->method('execute')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'execute';
                    return true;
                });
        $matcherOne = $this->exactly(5);
        $this->baseAdapterMock->expects($matcherOne)
                ->method('escapeTable')
                ->willReturnCallback(function ($entity) use (&$matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals("entity_with_two_collection", $entity);
                            break;
                        case 2:
                            $this->assertEquals("dependent_entity_one", $entity);
                            break;
                        case 3:
                            $this->assertEquals("dependent_entity_two", $entity);
                            break;
                        case 4:
                            $this->assertEquals("subdependent_entity", $entity);
                            break;
                        case 5:
                            $this->assertEquals("subdependent_entity", $entity);
                            break;
                    }
                    return $entity;
                });
        $matcherTwo = $this->exactly(4);
        $this->baseAdapterMock->expects($matcherTwo)
                ->method('parseInsert')
                ->willReturnCallback(function ($query) use (&$adapterMethodCallOrder, $matcherTwo) {
                    $adapterMethodCallOrder[] = 'parseInsert';
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals("entity_with_two_collection", $query);
                            break;
                        case 2:
                            $this->assertEquals("dependent_entity_one", $query);
                            break;
                        case 3:
                            $this->assertEquals("dependent_entity_two", $query);
                            break;
                        case 4:
                            $this->assertEquals("subdependent_entity", $query);
                            break;
                    }
                    return $query;
                });
        $this->baseAdapterMock->expects($this->once())
                ->method('parseUpdate')
                ->willReturnCallback(function ($query) use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'parseUpdate';
                    $this->assertEquals('subdependent_entity', $query);
                    return $query;
                });
        $matcherThree = $this->exactly(4);
        $this->baseAdapterMock->expects($matcherThree)
                ->method('lastInsertId')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder, $matcherThree) {
                    $adapterMethodCallOrder[] = 'lastInsertId';
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            return 1;
                        case 2:
                            return 2;
                        case 3:
                            return 3;
                        case 4:
                            return 4;
                    }
                });
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('commitTransaction')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'commitTransaction';
                    return true;
                });
        $entityWithTwoCollection = new EntityWithTwoCollection();
        $entityWithTwoCollection->addDependentEntityOne(new DependentEntityOne());
        $dependentEntityTwo = new DependentEntityTwo();
        $subdependentEntity = new SubdependentEntity();
        $subdependentEntity->string = 'test';
        $dependentEntityTwo->addSubdependentEntity($subdependentEntity);
        $entityWithTwoCollection->addDependentEntityTwo($dependentEntityTwo);
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($entityWithTwoCollection);
        $subdependentEntity->string = 'testTwo';
        $dataMapper->save($entityWithTwoCollection);
        $this->assertEquals([
            'beginTransaction',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'commitTransaction',
            'beginTransaction',
            'parseUpdate',
            'execute',
            'commitTransaction'
                ], $adapterMethodCallOrder);
    }

    public function testSaveModificationOnSubnidificateEntity()
    {
        $adapterMethodCallOrder = [];
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('beginTransaction')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'beginTransaction';
                    return true;
                });
        $this->baseAdapterMock->expects($this->exactly(5))
                ->method('execute')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'execute';
                    return true;
                });
        $matcherOne = $this->exactly(5);
        $this->baseAdapterMock->expects($matcherOne)
                ->method('escapeTable')
                ->willReturnCallback(function ($entity) use (&$matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals("dependent_entity_one", $entity);
                            break;
                        case 2:
                            $this->assertEquals("entity_with_two_collection", $entity);
                            break;
                        case 3:
                            $this->assertEquals("dependent_entity_two", $entity);
                            break;
                        case 4:
                            $this->assertEquals("subdependent_entity", $entity);
                            break;
                        case 5:
                            $this->assertEquals("subdependent_entity", $entity);
                            break;
                    }
                    return $entity;
                });
        $matcherTwo = $this->exactly(4);
        $this->baseAdapterMock->expects($matcherTwo)
                ->method('parseInsert')
                ->willReturnCallback(function ($query) use (&$adapterMethodCallOrder, $matcherTwo) {
                    $adapterMethodCallOrder[] = 'parseInsert';
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals("entity_with_two_collection", $query);
                            break;
                        case 2:
                            $this->assertEquals("dependent_entity_two", $query);
                            break;
                        case 3:
                            $this->assertEquals("subdependent_entity", $query);
                            break;
                        case 4:
                            $this->assertEquals("dependent_entity_one", $query);
                            break;
                    }
                    return $query;
                });
        $this->baseAdapterMock->expects($this->once())
                ->method('parseUpdate')
                ->willReturnCallback(function ($query) use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'parseUpdate';
                    $this->assertEquals('subdependent_entity', $query);
                    return $query;
                });
        $matcherThree = $this->exactly(4);
        $this->baseAdapterMock->expects($matcherThree)
                ->method('lastInsertId')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder, $matcherThree) {
                    $adapterMethodCallOrder[] = 'lastInsertId';
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            return 1;
                        case 2:
                            return 2;
                        case 3:
                            return 3;
                        case 4:
                            return 4;
                    }
                });
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('commitTransaction')
                ->willReturnCallback(function () use (&$adapterMethodCallOrder) {
                    $adapterMethodCallOrder[] = 'commitTransaction';
                    return true;
                });
        $dependentEntityOne = new DependentEntityOne();
        $dependentEntityOne->entityWithTwoCollection = new EntityWithTwoCollection();
        $dependentEntityTwo = new DependentEntityTwo();
        $subdependentEntity = new SubdependentEntity();
        $subdependentEntity->string = 'test';
        $dependentEntityTwo->addSubdependentEntity($subdependentEntity);
        $dependentEntityOne->entityWithTwoCollection->addDependentEntityTwo($dependentEntityTwo);
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($dependentEntityOne);
        $subdependentEntity->string = 'testTwo';
        $dataMapper->save($dependentEntityOne);
        $this->assertEquals([
            'beginTransaction',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'commitTransaction',
            'beginTransaction',
            'parseUpdate',
            'execute',
            'commitTransaction'
                ], $adapterMethodCallOrder);
    }

    public function testUpdateAutomaticStartTransaction()
    {
        $this->baseAdapterMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturn(true);
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('execute')
                ->willReturn(true);
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('lastInsertId')
                ->willReturn(1);
        $this->baseAdapterMock->expects($this->once())
                ->method('commitTransaction')
                ->willReturn(true);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $dataMapper->save($baseSample);
    }

    public function testDelete()
    {
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('execute')
                ->willReturnOnConsecutiveCalls(false, true);
        $this->queryMock->expects($this->exactly(2))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $baseSampleOne = new BaseSample();
        $baseSampleOne->id = 1;
        $baseSampleOne->setPrimaryKeyPropertyName('');
        $this->assertFalse($dataMapper->delete($baseSampleOne, $this->queryMock));
        $baseSampleTwo = new BaseSample();
        $baseSampleTwo->id = 1;
        $this->assertFalse($dataMapper->delete($baseSampleTwo, $this->queryMock));
        $baseSampleTree = new BaseSample();
        $baseSampleTree->id = 1;
        Cache::setEntity($baseSampleTwo);
        $this->assertTrue(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
        $this->assertTrue($dataMapper->delete($baseSampleTree, $this->queryMock));
        $this->assertFalse(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
    }

    public function testDeleteBatch()
    {
        $this->baseAdapterMock->expects($this->exactly(2))
                ->method('execute')
                ->willReturnOnConsecutiveCalls(false, true);
        $this->queryMock->expects($this->exactly(2))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        Cache::setEntity($baseSample);
        $this->assertTrue(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
        $this->assertFalse($dataMapper->deleteBatch($this->queryMock));
        $this->assertTrue(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
        $this->assertTrue($dataMapper->deleteBatch($this->queryMock));
        $this->assertFalse(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
    }

    public function testFind()
    {
        $firstInitializedBaseSample = new BaseSample();
        $firstInitializedBaseSample->id = 1;
        $secondInitializedBaseSample = new BaseSample();
        $secondInitializedBaseSample->id = 2;
        $thirdInitializedBaseSample = new BaseSample();
        $thirdInitializedBaseSample->id = 2;
        $firstBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $firstBaseResultSetMock->expects($this->exactly(2))
                ->method('current')
                ->willReturnOnConsecutiveCalls($firstInitializedBaseSample, $secondInitializedBaseSample);
        $firstBaseResultSetMock->expects($this->any())
                ->method('valid')
                ->willReturnOnConsecutiveCalls(true, true, false);
        $firstBaseResultSetMock->expects($this->any())
                ->method('key')
                ->willReturnOnConsecutiveCalls(0, 1);
        $firstBaseResultSetMock->expects($this->any())
                ->method('next')
                ->willReturnSelf();
        $firstBaseResultSetMock->expects($this->any())
                ->method('rewind')
                ->willReturnSelf();
        $secondBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $secondBaseResultSetMock->expects($this->exactly(1))
                ->method('current')
                ->willReturnOnConsecutiveCalls($thirdInitializedBaseSample);
        $secondBaseResultSetMock->expects($this->any())
                ->method('valid')
                ->willReturnOnConsecutiveCalls(true, false);
        $secondBaseResultSetMock->expects($this->any())
                ->method('key')
                ->willReturnOnConsecutiveCalls(0);
        $secondBaseResultSetMock->expects($this->any())
                ->method('next')
                ->willReturnSelf();
        $secondBaseResultSetMock->expects($this->any())
                ->method('rewind')
                ->willReturnSelf();
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('select')
                ->willReturnOnConsecutiveCalls(null, $firstBaseResultSetMock, $secondBaseResultSetMock);
        $this->queryMock->expects($this->exactly(3))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $firstEntityCollection = $dataMapper->find(BaseSample::class, $this->queryMock);
        $this->assertInstanceOf(SismaCollection::class, $firstEntityCollection);
        $this->assertCount(0, $firstEntityCollection);
        $secondEntityCollection = $dataMapper->find(BaseSample::class, $this->queryMock);
        $this->assertInstanceOf(SismaCollection::class, $secondEntityCollection);
        $this->assertCount(2, $secondEntityCollection);
        $this->expectException(InvalidTypeException::class);
        $dataMapper->find(ReferencedSample::class, $this->queryMock);
    }

    public function testGetCount()
    {
        $standardEntity = new StandardEntity();
        $standardEntity->_numrows = 5;
        $baseResultSetMock = $this->createMock(BaseResultSet::class);
        $baseResultSetMock->expects($this->exactly(2))
                ->method('fetch')
                ->willReturnOnConsecutiveCalls(null, $standardEntity);
        $this->baseAdapterMock->expects($this->exactly(3))
                ->method('select')
                ->willReturnOnConsecutiveCalls(null, $baseResultSetMock, $baseResultSetMock);
        $this->queryMock->expects($this->exactly(3))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $this->assertEquals(0, $dataMapper->getCount($this->queryMock));
        $this->assertEquals(0, $dataMapper->getCount($this->queryMock));
        $this->assertEquals(5, $dataMapper->getCount($this->queryMock));
    }

    public function testFindFirst()
    {
        $initializedBaseSample = new BaseSample();
        $initializedBaseSample->id = 1;
        $firstBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $firstBaseResultSetMock->expects($this->any())
                ->method('numRows')
                ->willReturn(0);
        $secondBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $secondBaseResultSetMock->expects($this->exactly(1))
                ->method('fetch')
                ->willReturn($initializedBaseSample);
        $secondBaseResultSetMock->expects($this->any())
                ->method('numRows')
                ->willReturn(1);
        $thidBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $thidBaseResultSetMock->expects($this->any())
                ->method('numRows')
                ->willReturn(2);
        $this->baseAdapterMock->expects($this->exactly(4))
                ->method('select')
                ->willReturnOnConsecutiveCalls(null, $firstBaseResultSetMock, $secondBaseResultSetMock, $thidBaseResultSetMock);
        $this->queryMock->expects($this->exactly(4))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = $this->createDataMapperWithMockedAdapter();
        $this->assertNull($dataMapper->findFirst(BaseSample::class, $this->queryMock));
        $this->assertNull($dataMapper->findFirst(BaseSample::class, $this->queryMock));
        $this->assertInstanceOf(BaseSample::class, $dataMapper->findFirst(BaseSample::class, $this->queryMock));
        $this->expectException(DataMapperException::class);
        $dataMapper->findFirst(BaseSample::class, $this->queryMock);
    }
}

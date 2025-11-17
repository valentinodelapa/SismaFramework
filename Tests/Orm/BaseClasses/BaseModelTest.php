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

namespace SismaFramework\Tests\Orm\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaTime;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\TestsApplication\Models\NotDependentEntityModel;
use SismaFramework\TestsApplication\Entities\NotDependentEntity;
use SismaFramework\TestsApplication\Enumerations\SampleType;

/**
 * Test for BaseModel class
 * @author Valentino de Lapa
 */
class BaseModelTest extends TestCase
{

    private Config $configMock;
    private DataMapper $dataMapperMock;
    private Query $queryMock;
    private ProcessedEntitiesCollection $processedEntitiesCollectionMock;
    private NotDependentEntityModel $model;

    protected function setUp(): void
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
                ->willReturn(true);

        Config::setInstance($this->configMock);

        $this->dataMapperMock = $this->createMock(DataMapper::class);
        $this->queryMock = $this->createMock(Query::class);
        $this->processedEntitiesCollectionMock = $this->createMock(ProcessedEntitiesCollection::class);

        $this->model = new NotDependentEntityModel($this->dataMapperMock, $this->configMock);
    }

    public function testConstructorWithValidEntity()
    {
        $model = new NotDependentEntityModel($this->dataMapperMock, $this->configMock);
        $this->assertInstanceOf(BaseModel::class, $model);
    }

    public function testConstructorThrowsExceptionForInvalidEntity()
    {
        $this->expectException(ModelException::class);

        // Create a mock model that returns a non-existent entity name
        $mockModel = new class($this->dataMapperMock, $this->configMock) extends BaseModel
        {

            protected function getEntityName(): string
            {
                return 'NonExistentClass'; // This should cause ModelException
            }

            protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
            {
                // Empty implementation for testing
            }
        };
    }

    public function testCountEntityCollectionWithoutSearch()
    {
        $expectedCount = 5;

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->with(NotDependentEntity::class)
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('getCount')
                ->with($this->queryMock, [], [])
                ->willReturn($expectedCount);

        $result = $this->model->countEntityCollection();
        $this->assertEquals($expectedCount, $result);
    }

    public function testCountEntityCollectionWithSearch()
    {
        $searchKey = 'test search';
        $expectedCount = 3;

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->with(NotDependentEntity::class)
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setWhere');

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('getCount')
                ->willReturn($expectedCount);

        $result = $this->model->countEntityCollection($searchKey);
        $this->assertEquals($expectedCount, $result);
    }

    public function testGetEntityCollectionWithoutParameters()
    {
        $expectedCollection = new SismaCollection(NotDependentEntity::class);

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->with(NotDependentEntity::class)
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setOrderBy')
                ->with(null);

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('find')
                ->with(NotDependentEntity::class, $this->queryMock, [], [])
                ->willReturn($expectedCollection);

        $result = $this->model->getEntityCollection();
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testGetEntityCollectionWithAllParameters()
    {
        $searchKey = 'test';
        $order = ['name' => 'ASC'];
        $offset = 10;
        $limit = 5;
        $expectedCollection = new SismaCollection(NotDependentEntity::class);

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->with(NotDependentEntity::class)
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setWhere');

        $this->queryMock->expects($this->once())
                ->method('setOrderBy')
                ->with($order);

        $this->queryMock->expects($this->once())
                ->method('setOffset')
                ->with($offset);

        $this->queryMock->expects($this->once())
                ->method('setLimit')
                ->with($limit);

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('find')
                ->willReturn($expectedCollection);

        $result = $this->model->getEntityCollection($searchKey, $order, $offset, $limit);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testGetOtherEntityCollection()
    {
        $excludedEntity = $this->createMock(NotDependentEntity::class);
        $expectedCollection = new SismaCollection(NotDependentEntity::class);

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->with(NotDependentEntity::class)
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setWhere');

        $this->queryMock->expects($this->once())
                ->method('appendCondition');

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('find')
                ->willReturn($expectedCollection);

        $result = $this->model->getOtherEntityCollection($excludedEntity);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testConvertArrayIntoEntityCollection()
    {
        $entitiesId = [1, 2, 3];
        $mockEntity = $this->createMock(NotDependentEntity::class);

        $this->dataMapperMock->expects($this->exactly(3))
                ->method('initQuery')
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->exactly(3))
                ->method('setWhere');

        $this->queryMock->expects($this->exactly(3))
                ->method('appendCondition');

        $this->queryMock->expects($this->exactly(3))
                ->method('close');

        $this->dataMapperMock->expects($this->exactly(3))
                ->method('findFirst')
                ->willReturn($mockEntity);

        $result = $this->model->convertArrayIntoEntityCollection($entitiesId);
        $this->assertInstanceOf(SismaCollection::class, $result);
        $this->assertEquals(3, $result->count());
    }

    public function testGetEntityByIdReturnsNull()
    {
        $id = 999;

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setWhere');

        $this->queryMock->expects($this->once())
                ->method('appendCondition');

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('findFirst')
                ->willReturn(null);

        $result = $this->model->getEntityById($id);
        $this->assertNull($result);
    }

    public function testDeleteEntityById()
    {
        $id = 1;

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->with(NotDependentEntity::class)
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setWhere');

        $this->queryMock->expects($this->once())
                ->method('appendCondition');

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('deleteBatch')
                ->willReturn(true);

        $result = $this->model->deleteEntityById($id);
        $this->assertTrue($result);
    }

    public function testFindSingleColumn()
    {
        $entityName = NotDependentEntity::class;
        $columnName = 'name';
        $isForeignKey = false;
        $mockEntity = $this->createMock(NotDependentEntity::class);

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setColumn')
                ->with($columnName)
                ->willReturnSelf();

        $this->queryMock->expects($this->once())
                ->method('setLimit')
                ->with(1)
                ->willReturnSelf();

        $this->dataMapperMock->expects($this->exactly(2))
                ->method('setOrmCacheStatus');

        $this->dataMapperMock->expects($this->once())
                ->method('findFirst')
                ->with($entityName, $this->queryMock)
                ->willReturn($mockEntity);

        $result = $this->model->findSingleColumn($entityName, $columnName, $isForeignKey);
        $this->assertInstanceOf(NotDependentEntity::class, $result);
    }

    public function testFindSingleColumnWithForeignKey()
    {
        $entityName = NotDependentEntity::class;
        $columnName = 'referenced';
        $isForeignKey = true;

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setColumn')
                ->with($columnName . 'Id')
                ->willReturnSelf();

        $this->queryMock->expects($this->once())
                ->method('setLimit')
                ->with(1)
                ->willReturnSelf();

        $this->dataMapperMock->expects($this->exactly(2))
                ->method('setOrmCacheStatus');

        $this->dataMapperMock->expects($this->once())
                ->method('findFirst')
                ->willReturn(null);

        $result = $this->model->findSingleColumn($entityName, $columnName, $isForeignKey);
        $this->assertNull($result);
    }

    public function testMagicMethodWithInvalidAction()
    {
        $this->expectException(ModelException::class);
        $this->model->invalidActionByInt(1);
    }

    public function testMagicMethodWithInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->model->getByString(5);
    }

    public function testMagicMethodCountByBuiltinProperty()
    {
        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->willReturn($this->queryMock);
        $this->queryMock->expects($this->once())
                ->method('setWhere');
        $invokedCount = $this->exactly(4);
        $this->queryMock->expects($invokedCount)
                ->method('appendCondition')
                ->willReturnCallback(function ($column, $operator, $value, $foreignKey = false) use ($invokedCount) {
                    switch ($invokedCount->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('string', $column);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(false, $foreignKey);
                            break;
                        case 2:
                            $this->assertEquals('int', $column);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(false, $foreignKey);
                            break;
                        case 3:
                            $this->assertEquals('float', $column);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(false, $foreignKey);
                            break;
                        case 4:
                            $this->assertEquals('bool', $column);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(false, $foreignKey);
                            break;
                    }
                    return $this->queryMock;
                });
        $this->queryMock->expects($this->once())
                ->method('close');
        $this->dataMapperMock->expects($this->once())
                ->method('getCount')
                ->willReturn(2);
        $result = $this->model->countByStringAndIntAndFloatAndBool('sample string', 5, 1.2, false);
        $this->assertEquals(2, $result);
    }

    public function testMagicMethodGetByEnumerationProperty()
    {
        $sismaCollection = new SismaCollection(NotDependentEntity::class);
        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->willReturn($this->queryMock);
        $this->queryMock->expects($this->once())
                ->method('setWhere');
        $this->queryMock->expects($this->once())
                ->method('appendCondition')
                ->with('sampleType', ComparisonOperator::equal, Placeholder::placeholder, false);
        $this->queryMock->expects($this->once())
                ->method('close');
        $this->dataMapperMock->expects($this->once())
                ->method('find')
                ->willReturn($sismaCollection);
        $result = $this->model->getBySampleType(SampleType::one);
        $this->assertEquals($sismaCollection, $result);
    }

    public function testMagicMethodDeleteBySismaDateProperty()
    {
        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->willReturn($this->queryMock);
        $this->queryMock->expects($this->once())
                ->method('setWhere');
        $invokedCount = $this->exactly(3);
        $this->queryMock->expects($invokedCount)
                ->method('appendCondition')
                ->willReturnCallback(function ($column, $operator, $value, $foreignKey = false) use ($invokedCount) {
                    switch ($invokedCount->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('date', $column);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(false, $foreignKey);
                            break;
                        case 2:
                            $this->assertEquals('datetime', $column);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(false, $foreignKey);
                            break;
                        case 3:
                            $this->assertEquals('time', $column);
                            $this->assertEquals(ComparisonOperator::equal, $operator);
                            $this->assertEquals(Placeholder::placeholder, $value);
                            $this->assertEquals(false, $foreignKey);
                            break;
                    }
                    return $this->queryMock;
                });
        $this->queryMock->expects($this->once())
                ->method('close');
        $this->dataMapperMock->expects($this->once())
                ->method('deleteBatch')
                ->willReturn(true);
        $date = new SismaDate();
        $datetime = new SismaDateTime();
        $time = new SismaTime();
        $result = $this->model->deleteByDateAndDatetimeAndTime($date, $datetime, $time);
        $this->assertTrue($result);
    }
}

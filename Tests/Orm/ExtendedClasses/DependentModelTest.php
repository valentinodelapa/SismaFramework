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

namespace SismaFramework\Tests\Orm\ExtendedClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\TestsApplication\Models\BaseSampleModel;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;

/**
 * Test for DependentModel class
 * @author Valentino de Lapa
 */
class DependentModelTest extends TestCase
{
    private Config $configMock;
    private DataMapper $dataMapperMock;
    private Query $queryMock;
    private ProcessedEntitiesCollection $processedEntitiesCollectionMock;
    private BaseSampleModel $model;

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

        $this->model = new BaseSampleModel($this->dataMapperMock, $this->configMock);
    }

    public function testMagicMethodGetByEntity()
    {
        $referencedEntity = $this->createMock(ReferencedSample::class);
        $expectedCollection = new SismaCollection(BaseSample::class);

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->once())
            ->method('appendCondition');

        $this->queryMock->expects($this->once())
            ->method('setOrderBy');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('find')
            ->willReturn($expectedCollection);

        $result = $this->model->getByReferencedEntityWithInitialization($referencedEntity);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testMagicMethodCountByEntity()
    {
        $referencedEntity = $this->createMock(ReferencedSample::class);
        $expectedCount = 5;

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
            ->method('getCount')
            ->willReturn($expectedCount);

        $result = $this->model->countByReferencedEntityWithInitialization($referencedEntity);
        $this->assertEquals($expectedCount, $result);
    }

    public function testMagicMethodDeleteByEntity()
    {
        $referencedEntity = $this->createMock(ReferencedSample::class);

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
            ->method('deleteBatch')
            ->willReturn(true);

        $result = $this->model->deleteByReferencedEntityWithInitialization($referencedEntity);
        $this->assertTrue($result);
    }

    public function testMagicMethodWithInvalidAction()
    {
        $this->expectException(ModelException::class);

        $referencedEntity = $this->createMock(ReferencedSample::class);
        $this->model->invalidActionByReferencedEntityWithInitialization($referencedEntity);
    }

    public function testMagicMethodWithInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        // Pass wrong entity type
        $wrongEntity = $this->createMock(BaseSample::class);
        $this->model->getByReferencedEntityWithInitialization($wrongEntity);
    }

    public function testCountEntityCollectionByEntityWithNullEntity()
    {
        $referencedEntities = ['referenced_entity_with_initialization' => null];
        $expectedCount = 3;

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
            ->method('getCount')
            ->willReturn($expectedCount);

        $result = $this->model->countEntityCollectionByEntity($referencedEntities);
        $this->assertEquals($expectedCount, $result);
    }

    public function testCountEntityCollectionByEntityWithSearchKey()
    {
        $referencedEntity = $this->createMock(ReferencedSample::class);
        $referencedEntities = ['referenced_entity_with_initialization' => $referencedEntity];
        $searchKey = 'test search';
        $expectedCount = 2;

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->once())
            ->method('appendCondition');

        $this->queryMock->expects($this->once())
            ->method('appendAnd');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('getCount')
            ->willReturn($expectedCount);

        $result = $this->model->countEntityCollectionByEntity($referencedEntities, $searchKey);
        $this->assertEquals($expectedCount, $result);
    }

    public function testGetEntityCollectionByEntityWithAllParameters()
    {
        $referencedEntity = $this->createMock(ReferencedSample::class);
        $referencedEntities = ['referenced_entity_with_initialization' => $referencedEntity];
        $searchKey = 'test';
        $order = ['name' => 'ASC'];
        $offset = 10;
        $limit = 5;
        $expectedCollection = new SismaCollection(BaseSample::class);

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->once())
            ->method('appendCondition');

        $this->queryMock->expects($this->once())
            ->method('appendAnd');

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

        $result = $this->model->getEntityCollectionByEntity($referencedEntities, $searchKey, $order, $offset, $limit);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testDeleteEntityCollectionByEntity()
    {
        $referencedEntity = $this->createMock(ReferencedSample::class);
        $referencedEntities = ['referenced_entity_with_initialization' => $referencedEntity];

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
            ->method('deleteBatch')
            ->willReturn(true);

        $result = $this->model->deleteEntityCollectionByEntity($referencedEntities);
        $this->assertTrue($result);
    }

    public function testGetOtherEntityCollectionByEntity()
    {
        $excludedEntity = $this->createMock(BaseSample::class);
        $referencedEntity = $this->createMock(ReferencedSample::class);
        $propertyName = 'referenced_entity_with_initialization';
        $expectedCollection = new SismaCollection(BaseSample::class);

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendCondition');

        $this->queryMock->expects($this->once())
            ->method('appendAnd');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('find')
            ->willReturn($expectedCollection);

        $result = $this->model->getOtherEntityCollectionByEntity($excludedEntity, $propertyName, $referencedEntity);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testGetOtherEntityCollectionByEntityWithNullEntity()
    {
        $excludedEntity = $this->createMock(BaseSample::class);
        $propertyName = 'referenced_entity_with_initialization';
        $expectedCollection = new SismaCollection(BaseSample::class);

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendCondition');

        $this->queryMock->expects($this->once())
            ->method('appendAnd');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('find')
            ->willReturn($expectedCollection);

        $referencedEntity = $this->createMock(ReferencedSample::class);
        $result = $this->model->getOtherEntityCollectionByEntity($excludedEntity, $propertyName, $referencedEntity);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }
}
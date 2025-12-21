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
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\ExtendedClasses\SelfReferencedModel;
use SismaFramework\TestsApplication\Models\SelfReferencedSampleModel;
use SismaFramework\TestsApplication\Entities\SelfReferencedSample;

/**
 * Test for SelfReferencedModel class
 * @author Valentino de Lapa
 */
class SelfReferencedModelTest extends TestCase
{
    private Config $configStub;
    private DataMapper $dataMapperMock;
    private Query $queryMock;
    private ProcessedEntitiesCollection $processedEntitiesCollectionMock;
    private SelfReferencedSampleModel $model;

    protected function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $referenceCacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('cache_', true) . DIRECTORY_SEPARATOR;

        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')
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
                    ['sonCollectionGetterMethod', 'getSonCollection'],
        ]);
        $this->configStub->method('__isset')
                ->willReturn(true);

        Config::setInstance($this->configStub);
        $this->processedEntitiesCollectionMock = $this->createStub(ProcessedEntitiesCollection::class);
    }
    
    private function initializeMock()
    {
        $this->dataMapperMock = $this->createMock(DataMapper::class);
        $this->queryMock = $this->createMock(Query::class);
        $this->model = new SelfReferencedSampleModel($this->dataMapperMock, $this->configStub);
    }
    
    private function initializePartialMock()
    {
        $this->dataMapperMock = $this->createMock(DataMapper::class);
        $this->queryMock = $this->createStub(Query::class);
        $this->model = new SelfReferencedSampleModel($this->dataMapperMock, $this->configStub);
    }
    
    private function initializeStub()
    {
        $this->dataMapperMock = $this->createStub(DataMapper::class);
        $this->queryMock = $this->createStub(Query::class);
        $this->model = new SelfReferencedSampleModel($this->dataMapperMock, $this->configStub);
    }

    public function testConstructorSetParentForeignKey()
    {
        $this->initializeStub();
        $this->assertInstanceOf(SelfReferencedModel::class, $this->model);
    }

    public function testMagicMethodCallWithParentAndEntity()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $referencedEntity = $this->createStub(SelfReferencedSample::class);
        $expectedCollection = new SismaCollection(SelfReferencedSample::class);

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
            ->method('setOrderBy');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('find')
            ->willReturn($expectedCollection);

        // This tests magic method handling for "getByParentAndParentSelfReferencedSample"
        $result = $this->model->getByParentAndParentSelfReferencedSample($parentEntity, $referencedEntity);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testMagicMethodCallWithInvalidAction()
    {
        $this->initializeStub();
        $this->expectException(ModelException::class);

        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $referencedEntity = $this->createStub(SelfReferencedSample::class);

        $this->model->invalidActionByParentAndParentSelfReferencedSample($parentEntity, $referencedEntity);
    }

    public function testMagicMethodCallFallbackToParent()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(SelfReferencedSample::class);
        $expectedCollection = new SismaCollection(SelfReferencedSample::class);

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

        // This should fallback to parent DependentModel magic method
        $result = $this->model->getByParentSelfReferencedSample($referencedEntity);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testCountEntityCollectionByParentWithNullParent()
    {
        $this->initializeMock();
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

        $result = $this->model->countEntityCollectionByParent(null);
        $this->assertEquals($expectedCount, $result);
    }

    public function testCountEntityCollectionByParentWithSearchKey()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
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

        $result = $this->model->countEntityCollectionByParent($parentEntity, $searchKey);
        $this->assertEquals($expectedCount, $result);
    }

    public function testCountEntityCollectionByParentAndEntity()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $referencedEntities = ['parentSelfReferencedSample' => $this->createStub(SelfReferencedSample::class)];
        $expectedCount = 1;

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
            ->method('getCount')
            ->willReturn($expectedCount);

        $result = $this->model->countEntityCollectionByParentAndEntity($referencedEntities, $parentEntity);
        $this->assertEquals($expectedCount, $result);
    }

    public function testGetEntityCollectionByParentWithAllParameters()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $searchKey = 'test';
        $order = ['name' => 'ASC'];
        $offset = 5;
        $limit = 10;
        $expectedCollection = new SismaCollection(SelfReferencedSample::class);

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

        $result = $this->model->getEntityCollectionByParent($parentEntity, $searchKey, $order, $offset, $limit);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testGetEntityCollectionByParentAndEntity()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $referencedEntities = ['parentSelfReferencedSample' => $this->createStub(SelfReferencedSample::class)];
        $expectedCollection = new SismaCollection(SelfReferencedSample::class);

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
            ->method('setOrderBy');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('find')
            ->willReturn($expectedCollection);

        $result = $this->model->getEntityCollectionByParentAndEntity($referencedEntities, $parentEntity);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testGetOtherEntityCollectionByParent()
    {
        $this->initializeMock();
        $excludedEntity = $this->createStub(SelfReferencedSample::class);
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $order = ['name' => 'DESC'];
        $expectedCollection = new SismaCollection(SelfReferencedSample::class);

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
            ->method('setOrderBy')
            ->with($order);

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('find')
            ->willReturn($expectedCollection);

        $result = $this->model->getOtherEntityCollectionByParent($excludedEntity, $parentEntity, $order);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testGetEntityTree()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $order = ['name' => 'ASC'];

        // Create mock entities with children
        $childEntity = $this->createMock(SelfReferencedSample::class);
        $childEntity->expects($this->once())
            ->method('setEntityCollection');

        $parentCollection = new SismaCollection(SelfReferencedSample::class);
        $parentCollection->append($childEntity);

        $childCollection = new SismaCollection(SelfReferencedSample::class);

        $this->dataMapperMock->expects($this->exactly(2))
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->exactly(2))
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendCondition');

        $this->queryMock->expects($this->exactly(2))
            ->method('setOrderBy');

        $this->queryMock->expects($this->exactly(2))
            ->method('close');

        $this->dataMapperMock->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($parentCollection, $childCollection);

        $result = $this->model->getEntityTree($parentEntity, $order);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testDeleteEntityCollectionByParent()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);

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

        $result = $this->model->deleteEntityCollectionByParent($parentEntity);
        $this->assertTrue($result);
    }

    public function testDeleteEntityCollectionByParentAndEntity()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $referencedEntities = ['parentSelfReferencedSample' => $this->createStub(SelfReferencedSample::class)];

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
            ->method('deleteBatch')
            ->willReturn(true);

        $result = $this->model->deleteEntityCollectionByParentAndEntity($referencedEntities, $parentEntity);
        $this->assertTrue($result);
    }

    public function testDeleteEntityTree()
    {
        $this->initializePartialMock();
        // Crea mock che simula il comportamento di __call per getSonCollection
        $childEntity = $this->createStub(SelfReferencedSample::class);
        $childEntity->method('__call')
            ->with('getSonCollection', [])
            ->willReturn(new SismaCollection(SelfReferencedSample::class));

        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $childCollection = new SismaCollection(SelfReferencedSample::class);
        $childCollection->append($childEntity);

        $parentEntity->method('__call')
            ->with('getSonCollection', [])
            ->willReturn($childCollection);

        $this->dataMapperMock->expects($this->exactly(2))
            ->method('delete');

        $this->model->deleteEntityTree($parentEntity);

        // Verifica che delete sia stato chiamato per entrambe le entità
        $this->assertTrue(true); // Test passa se non vengono lanciate eccezioni
    }

    public function testCountEntityCollectionByParentAndEntityWithBuiltinProperty()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $baseSampleEntity = $this->createStub(\SismaFramework\TestsApplication\Entities\BaseSample::class);
        $referencedEntities = [
            'baseSample' => $baseSampleEntity,
            'text' => 'test text'
        ];
        $expectedCount = 2;

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(3))
            ->method('appendCondition');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendAnd');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('getCount')
            ->willReturn($expectedCount);

        $result = $this->model->countEntityCollectionByParentAndEntity($referencedEntities, $parentEntity);
        $this->assertEquals($expectedCount, $result);
    }

    public function testGetEntityCollectionByParentAndEntityWithBuiltinProperty()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $baseSampleEntity = $this->createStub(\SismaFramework\TestsApplication\Entities\BaseSample::class);
        $referencedEntities = [
            'baseSample' => $baseSampleEntity,
            'text' => 'sample text'
        ];
        $searchKey = 'search';
        $order = ['text' => 'ASC'];
        $offset = 0;
        $limit = 10;
        $expectedCollection = new SismaCollection(SelfReferencedSample::class);

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(3))
            ->method('appendCondition');

        $this->queryMock->expects($this->exactly(3))
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

        $result = $this->model->getEntityCollectionByParentAndEntity($referencedEntities, $parentEntity, $searchKey, $order, $offset, $limit);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testDeleteEntityCollectionByParentAndEntityWithBuiltinProperty()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $referencedEntities = [
            'baseSample' => null,
            'text' => 'delete this'
        ];

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(3))
            ->method('appendCondition');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendAnd');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('deleteBatch')
            ->willReturn(true);

        $result = $this->model->deleteEntityCollectionByParentAndEntity($referencedEntities, $parentEntity);
        $this->assertTrue($result);
    }

    public function testMagicMethodCallCountByParentAndBuiltinProperty()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $expectedCount = 3;

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
            ->method('getCount')
            ->willReturn($expectedCount);

        // Test magic method: countByParentAndText
        $result = $this->model->countByParentAndText($parentEntity, 'specific text');
        $this->assertEquals($expectedCount, $result);
    }

    public function testMagicMethodCallGetByParentAndBuiltinProperty()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $expectedCollection = new SismaCollection(SelfReferencedSample::class);

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
            ->method('setOrderBy');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('find')
            ->willReturn($expectedCollection);

        // Test magic method: getByParentAndText
        $result = $this->model->getByParentAndText($parentEntity, 'test value');
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testMagicMethodCallDeleteByParentAndBuiltinProperty()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);

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
            ->method('deleteBatch')
            ->willReturn(true);

        // Test magic method: deleteByParentAndText
        $result = $this->model->deleteByParentAndText($parentEntity, 'text to delete');
        $this->assertTrue($result);
    }

    public function testMagicMethodCallCountByParentAndBuiltinPropertyWithSearchKey()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendCondition');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendAnd');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('getCount')
            ->willReturn(7);

        $result = $this->model->countByParentAndText($parentEntity, 'test text', 'searchKey');
        $this->assertEquals(7, $result);
    }

    public function testMagicMethodCallGetByParentAndBuiltinPropertyWithSearchKeyAndPagination()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $expectedCollection = new SismaCollection(SelfReferencedSample::class);

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendCondition');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendAnd');

        $this->queryMock->expects($this->once())
            ->method('setOrderBy')
            ->with(['text' => 'ASC']);

        $this->queryMock->expects($this->once())
            ->method('setOffset')
            ->with(0);

        $this->queryMock->expects($this->once())
            ->method('setLimit')
            ->with(50);

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('find')
            ->willReturn($expectedCollection);

        $result = $this->model->getByParentAndText($parentEntity, 'test text', 'searchKey', ['text' => 'ASC'], 0, 50);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testMagicMethodCallDeleteByParentAndBuiltinPropertyWithSearchKey()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);

        $this->dataMapperMock->expects($this->once())
            ->method('initQuery')
            ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
            ->method('setWhere');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendCondition');

        $this->queryMock->expects($this->exactly(2))
            ->method('appendAnd');

        $this->queryMock->expects($this->once())
            ->method('close');

        $this->dataMapperMock->expects($this->once())
            ->method('deleteBatch')
            ->willReturn(true);

        $result = $this->model->deleteByParentAndText($parentEntity, 'test text', 'searchKey');
        $this->assertTrue($result);
    }

    public function testMagicMethodCallCountByParentAndMultiplePropertiesWithNull()
    {
        $this->initializeMock();
        $parentEntity = $this->createStub(SelfReferencedSample::class);

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
            ->method('getCount')
            ->willReturn(3);

        // Test with null value for nullable text property
        $result = $this->model->countByParentAndNullableText($parentEntity, null);
        $this->assertEquals(3, $result);
    }

    public function testMagicMethodCallThrowsExceptionForNullOnNonNullableProperty()
    {
        $this->initializeStub();
        $parentEntity = $this->createStub(SelfReferencedSample::class);
        $this->expectException(\SismaFramework\Core\Exceptions\InvalidArgumentException::class);
        $this->model->countByParentAndText($parentEntity, null);
    }
}
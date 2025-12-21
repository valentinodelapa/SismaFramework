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
use SismaFramework\Orm\Enumerations\DataType;

/**
 * Test for DependentModel class
 * @author Valentino de Lapa
 */
class DependentModelTest extends TestCase
{

    private Config $configStub;
    private DataMapper $dataMapperMock;
    private Query $queryMock;
    private ProcessedEntitiesCollection $processedEntitiesCollectionMock;
    private BaseSampleModel $model;

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
        $this->model = new BaseSampleModel($this->dataMapperMock, $this->configStub);
    }

    private function initializeStub()
    {
        $this->dataMapperMock = $this->createStub(DataMapper::class);
        $this->model = new BaseSampleModel($this->dataMapperMock, $this->configStub);
    }

    public function testMagicMethodGetByEntity()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
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
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
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
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);

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
        $this->initializeStub();
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $this->model->invalidActionByReferencedEntityWithInitialization($referencedEntity);
    }

    public function testMagicMethodWithInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->initializeStub();
        // Pass wrong entity type
        $wrongEntity = $this->createStub(BaseSample::class);
        $this->model->getByReferencedEntityWithInitialization($wrongEntity);
    }

    public function testCountEntityCollectionByEntityWithNullEntity()
    {
        $this->initializeMock();
        $referencedEntities = ['referencedEntityWithInitialization' => null];
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
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $referencedEntities = ['referencedEntityWithInitialization' => $referencedEntity];
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
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $referencedEntities = ['referencedEntityWithInitialization' => $referencedEntity];
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
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $referencedEntities = ['referencedEntityWithInitialization' => $referencedEntity];

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

    public function testGetEntityCollectionByEntityAndBuiltinProperty()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $referencedEntities = [
            'referencedEntityWithInitialization' => $referencedEntity,
            'boolean' => true,
            'stringWithInizialization' => 'test string'
        ];
        $expectedCollection = new SismaCollection(BaseSample::class);

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
                ->method('setOrderBy');

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('find')
                ->willReturn($expectedCollection);

        $result = $this->model->getEntityCollectionByEntity($referencedEntities);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testCountEntityCollectionByEntityAndBuiltinPropertyWithNull()
    {
        $this->initializeMock();
        $referencedEntities = [
            'referencedEntityWithInitialization' => null,
            'nullableStringWithInizialization' => null
        ];
        $expectedCount = 4;

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

        $result = $this->model->countEntityCollectionByEntity($referencedEntities);
        $this->assertEquals($expectedCount, $result);
    }

    public function testDeleteEntityCollectionByEntityAndBuiltinProperty()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $referencedEntities = [
            'referencedEntityWithInitialization' => $referencedEntity,
            'boolean' => false
        ];

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

        $result = $this->model->deleteEntityCollectionByEntity($referencedEntities);
        $this->assertTrue($result);
    }

    public function testGetOtherEntityCollectionByEntity()
    {
        $this->initializeMock();
        $excludedEntity = $this->createStub(BaseSample::class);
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $propertyName = 'referencedEntityWithInitialization';
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
        $this->initializeMock();
        $excludedEntity = $this->createStub(BaseSample::class);
        $propertyName = 'referencedEntityWithInitialization';
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

        $referencedEntity = $this->createStub(ReferencedSample::class);
        $result = $this->model->getOtherEntityCollectionByEntity($excludedEntity, $propertyName, $referencedEntity);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testMagicMethodCountByEntityWithSearchKey()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);

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
                ->willReturn(5);

        $result = $this->model->countByReferencedEntityWithInitialization($referencedEntity, 'searchKey');
        $this->assertEquals(5, $result);
    }

    public function testMagicMethodGetByEntityWithSearchKeyAndPagination()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
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
                ->with(['id' => 'DESC']);

        $this->queryMock->expects($this->once())
                ->method('setOffset')
                ->with(5);

        $this->queryMock->expects($this->once())
                ->method('setLimit')
                ->with(10);

        $this->queryMock->expects($this->once())
                ->method('close');

        $this->dataMapperMock->expects($this->once())
                ->method('find')
                ->willReturn($expectedCollection);

        $result = $this->model->getByReferencedEntityWithInitialization($referencedEntity, 'searchKey', ['id' => 'DESC'], 5, 10);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    public function testMagicMethodDeleteByEntityWithSearchKey()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);

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
                ->method('deleteBatch')
                ->willReturn(true);

        $result = $this->model->deleteByReferencedEntityWithInitialization($referencedEntity, 'searchKey');
        $this->assertTrue($result);
    }

    /**
     * Test che verifica che il quarto parametro di appendCondition sia corretto
     * per proprietà miste (entity e builtin).
     * 
     * Questo test avrebbe catturato il bug della versione 10.1.0 dove il metodo
     * buildPropertyConditions (typo) non passava correttamente il quarto parametro
     * e non faceva override del metodo di BaseModel.
     */
    public function testBuildPropertiesConditionsPassesCorrectFourthParameterToAppendCondition()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $referencedEntities = [
            'referencedEntityWithInitialization' => $referencedEntity,  // ReferencedEntity
            'boolean' => true,                                              // builtin
            'stringWithInizialization' => 'test'                         // builtin
        ];

        $this->dataMapperMock->expects($this->once())
                ->method('initQuery')
                ->willReturn($this->queryMock);

        $this->queryMock->expects($this->once())
                ->method('setWhere');

        // Verifica che appendCondition venga chiamato con il quarto parametro corretto
        $invokedCount = $this->exactly(3);
        $this->queryMock->expects($invokedCount)
                ->method('appendCondition')
                ->willReturnCallback(function ($column, $operator, $value, $isForeignKey = false) use ($invokedCount) {
                    switch ($invokedCount->numberOfInvocations()) {
                        case 1:
                            // Prima proprietà: referenced_entity_with_initialization (ReferencedEntity)
                            $this->assertEquals('referencedEntityWithInitialization', $column);
                            $this->assertTrue($isForeignKey, 'Il quarto parametro deve essere TRUE per ReferencedEntity');
                            break;
                        case 2:
                            // Seconda proprietà: boolean (builtin)
                            $this->assertEquals('boolean', $column);
                            $this->assertFalse($isForeignKey, 'Il quarto parametro deve essere FALSE per proprietà builtin');
                            break;
                        case 3:
                            // Terza proprietà: string_with_inizialization (builtin)
                            $this->assertEquals('stringWithInizialization', $column);
                            $this->assertFalse($isForeignKey, 'Il quarto parametro deve essere FALSE per proprietà builtin');
                            break;
                    }
                    return $this->queryMock;
                });

        $this->queryMock->expects($this->exactly(2))
                ->method('appendAnd');

        $this->queryMock->expects($this->once())
                ->method('setOrderBy');

        $this->queryMock->expects($this->once())
                ->method('close');

        $expectedCollection = new SismaCollection(BaseSample::class);
        $this->dataMapperMock->expects($this->once())
                ->method('find')
                ->willReturn($expectedCollection);

        $result = $this->model->getEntityCollectionByEntity($referencedEntities);
        $this->assertInstanceOf(SismaCollection::class, $result);
    }

    /**
     * Test che verifica che i bind types siano determinati correttamente
     * per proprietà miste (entity e builtin).
     * 
     * Questo test avrebbe catturato il bug della versione 10.1.0 dove
     * buildPropertyConditions hardcodava DataType::typeEntity per tutte
     * le proprietà invece di usare DataType::fromReflection().
     */
    public function testBuildPropertiesConditionsGeneratesCorrectBindTypesForMixedProperties()
    {
        $this->initializeMock();
        $referencedEntity = $this->createStub(ReferencedSample::class);
        $referencedEntities = [
            'referencedEntityWithInitialization' => $referencedEntity,
            'boolean' => true,
            'stringWithInizialization' => 'test string'
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

        // Spy per catturare i bind types passati a getCount
        $capturedBindTypes = null;
        $this->dataMapperMock->expects($this->once())
                ->method('getCount')
                ->willReturnCallback(function ($query, $bindValues, $bindTypes) use (&$capturedBindTypes) {
                    $capturedBindTypes = $bindTypes;
                    return 5;
                });

        $result = $this->model->countEntityCollectionByEntity($referencedEntities);

        // Verifica che i bind types siano corretti
        $this->assertIsArray($capturedBindTypes);
        $this->assertCount(3, $capturedBindTypes);
        
        // Prima proprietà: ReferencedEntity -> typeEntity
        $this->assertEquals(DataType::typeEntity, $capturedBindTypes[0], 
            'Il bind type per ReferencedEntity deve essere typeEntity');
        
        // Seconda proprietà: boolean -> typeBoolean
        $this->assertEquals(DataType::typeBoolean, $capturedBindTypes[1], 
            'Il bind type per boolean deve essere typeBoolean, NON typeEntity');
        
        // Terza proprietà: string -> typeString
        $this->assertEquals(DataType::typeString, $capturedBindTypes[2], 
            'Il bind type per string deve essere typeString, NON typeEntity');
        
        $this->assertEquals(5, $result);
    }
}

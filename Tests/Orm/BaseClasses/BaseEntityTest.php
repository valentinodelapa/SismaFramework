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
use SismaFramework\Orm\Exceptions\InvalidPropertyException;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaTime;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\DependentEntityThree;
use SismaFramework\TestsApplication\Entities\EntityWithOneCollectionOne;
use SismaFramework\TestsApplication\Entities\ReferencedSample;
use SismaFramework\TestsApplication\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */
class BaseEntityTest extends TestCase
{

    private Config $configStub;
    private DataMapper $dataMapperMock;
    private ProcessedEntitiesCollection $processedEntitiesCollectionMock;

    #[\Override]
    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['developmentEnvironment', false],
                    ['entityNamespace', 'TestsApplication\\Entities\\'],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
                    ['modelNamespace', 'TestsApplication\\Models\\'],
                    ['ormCache', true],
        ]);
        Config::setInstance($this->configStub);
        $this->dataMapperMock = $this->createStub(DataMapper::class);
        $this->processedEntitiesCollectionMock = $this->createStub(ProcessedEntitiesCollection::class);
    }

    public function testUnsetPrimaryKey()
    {
        $baseSample = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSample->id = 1;
        $baseSample->unsetPrimaryKey();
        $this->assertFalse(isset($baseSample->id));
    }

    public function testGetInvalidProperty()
    {
        $this->expectException(InvalidPropertyException::class);
        $baseSample = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSample->inexistentProperty;
    }

    public function testSetInvalidProperty()
    {
        $this->expectException(InvalidPropertyException::class);
        $baseSample = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSample->inexistentProperty = 'value';
    }

    public function testForceForeignKey()
    {
        $referencedSample = new ReferencedSample($this->dataMapperMock);
        $referencedSample->id = 10;
        $this->dataMapperMock->method('findFirst')
                ->willReturn($referencedSample);
        $baseSample = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSample->referencedEntityWithInitialization = 10;
        $this->assertEquals($referencedSample, $baseSample->referencedEntityWithInitialization);
    }

    public function testEntityWithEntityNotConvertedProperty()
    {
        $baseSampleOne = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->referencedEntityWithoutInitialization = 1;
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->referencedEntityWithoutInitialization = 1;
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->referencedEntityWithoutInitialization = 2;
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSampleTwo->referencedEntityWithInitialization->id = 1;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedEntityWithInitialization = 1;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedEntityWithInitialization = 2;
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableReferencedEntityWithInitialization = 1;
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleThree->nullableReferencedEntityWithInitialization = 1;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableReferencedEntityWithInitialization = 2;
        $this->assertTrue($baseSampleThree->modified);
    }

    public function testEntityWithEntityConvertedPropertyModifiedOne()
    {
        $referencedSampleOne = new ReferencedSample($this->dataMapperMock);
        $referencedSampleOne->id = 1;
        $baseSampleOne = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->referencedEntityWithoutInitialization = $referencedSampleOne;
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->referencedEntityWithoutInitialization = $referencedSampleOne;
        $this->assertFalse($baseSampleOne->modified);
        $referencedSampleTwo = new ReferencedSample($this->dataMapperMock);
        $referencedSampleTwo->id = 2;
        $baseSampleOne->referencedEntityWithoutInitialization = $referencedSampleTwo;
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->referencedEntityWithoutInitialization = new ReferencedSample($this->dataMapperMock);
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSampleTwo->referencedEntityWithInitialization->id = 1;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedEntityWithInitialization = $baseSampleTwo->referencedEntityWithInitialization;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedEntityWithInitialization = new ReferencedSample($this->dataMapperMock);
        $this->assertTrue($baseSampleTwo->modified);

        $referencedSampleFour = new ReferencedSample($this->dataMapperMock);
        $referencedSampleFour->id = 1;
        $baseSampleThree = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableReferencedEntityWithInitialization = null;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableReferencedEntityWithInitialization = $referencedSampleFour;
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleThree->nullableReferencedEntityWithInitialization = $referencedSampleFour;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableReferencedEntityWithInitialization = null;
        $this->assertTrue($baseSampleThree->modified);
    }

    public function testEntityWithBuiltInProperty()
    {
        $baseSampleOne = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->stringWithoutInitialization = 'base sample';
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->stringWithoutInitialization = 'base sample';
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->stringWithoutInitialization = 'base sample modified';
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSampleTwo->stringWithInitialization = 'base sample';
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->stringWithInitialization = 'base sample modified';
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSampleThree->nullableStringWithInitialization = null;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableStringWithInitialization = 'nullable string';
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleThree->nullableStringWithInitialization = 'nullable string';
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableStringWithInitialization = 'nullable modified string';
        $this->assertTrue($baseSampleThree->modified);
    }

    public function testEntityWithEnumProperty()
    {
        $baseSampleOne = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->enumWithoutInitialization = SampleType::one;
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->enumWithoutInitialization = SampleType::one;
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->enumWithoutInitialization = SampleType::two;
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->enumWithInitialization = SampleType::one;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->enumWithInitialization = SampleType::two;
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSampleThree->enumNullableWithInitialization = null;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->enumNullableWithInitialization = SampleType::one;
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleThree->enumNullableWithInitialization = SampleType::one;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->enumNullableWithInitialization = SampleType::two;
        $this->assertTrue($baseSampleThree->modified);
    }

    public function testEntityWithSismaDateTimeProperty()
    {
        $baseSampleOne = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->datetimeWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->datetimeWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSampleThree->datetimeNullableWithInitialization = null;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->datetimeNullableWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleThree->datetimeNullableWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->datetimeNullableWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $this->assertTrue($baseSampleThree->modified);
    }

    public function testEntityWithSismaDateProperty()
    {
        $baseSampleOne = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-01');
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-01');
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->dateWithInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-01');
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->dateWithInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSampleThree->dateNullableWithInitialization = null;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->dateNullableWithInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-01');
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleThree->dateNullableWithInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-01');
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->dateNullableWithInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $this->assertTrue($baseSampleThree->modified);
    }

    public function testEntityWithSismaTimeProperty()
    {
        $baseSampleOne = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:31:25');
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:31:25');
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->timeWithInitialization = SismaTime::createFromStandardTimeFormat('10:31:25');
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->timeWithInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample($this->dataMapperMock, $this->processedEntitiesCollectionMock, $this->configStub);
        $baseSampleThree->timeNullableWithInitialization = null;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->timeNullableWithInitialization = SismaTime::createFromStandardTimeFormat('10:31:25');
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleThree->timeNullableWithInitialization = SismaTime::createFromStandardTimeFormat('10:31:25');
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->timeNullableWithInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $this->assertTrue($baseSampleThree->modified);
    }

    public function testToArrayGood()
    {
        $dependentEntityThree = new DependentEntityThree($this->dataMapperMock);
        $dependentEntityThree->id = 1;
        $dependentEntityThree->string = 'sample-string';
        $entityWithOneCollectionOne = new EntityWithOneCollectionOne();
        $entityWithOneCollectionOne->id = 2;
        $entityWithOneCollectionOne->string = 'other-sample-string';
        $dependentEntityThree->entityWithOneCollectionOne = $entityWithOneCollectionOne;
        $dependentEntityThree->entityWithOneCollectionTwo = 3;
        $entityToArray = $dependentEntityThree->toArray();
        $this->assertIsArray($entityToArray);
        $this->assertEquals([
            'id' => 1,
            'string' => 'sample-string',
            'entityWithOneCollectionOne' => [
                'id' => 2,
                'string' => 'other-sample-string',
            ],
            'entityWithOneCollectionTwo' => 3,
                ], $entityToArray);
    }

    public function testToArrayWithExceptionOne()
    {
        $this->expectException(InvalidPropertyException::class);
        $dependentEntityThree = new DependentEntityThree($this->dataMapperMock);
        $dependentEntityThree->id = 1;
        $dependentEntityThree->string = 'sample-string';
        $entityWithOneCollectionOne = new EntityWithOneCollectionOne();
        $entityWithOneCollectionOne->id = 2;
        $entityWithOneCollectionOne->string = 'other-sample-string';
        $dependentEntityThree->entityWithOneCollectionOne = $entityWithOneCollectionOne;
        $dependentEntityThree->toArray();
    }

    public function testToArrayWithExceptionTwo()
    {
        $this->expectException(InvalidPropertyException::class);
        $dependentEntityThree = new DependentEntityThree($this->dataMapperMock);
        $dependentEntityThree->id = 1;
        $entityWithOneCollectionOne = new EntityWithOneCollectionOne();
        $entityWithOneCollectionOne->id = 2;
        $entityWithOneCollectionOne->string = 'other-sample-string';
        $dependentEntityThree->entityWithOneCollectionOne = $entityWithOneCollectionOne;
        $dependentEntityThree->entityWithOneCollectionTwo = 3;
        $dependentEntityThree->toArray();
    }
}

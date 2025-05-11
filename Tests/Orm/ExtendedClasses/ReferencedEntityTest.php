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
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Exceptions\InvalidPropertyException;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;
use SismaFramework\TestsApplication\Entities\OtherReferencedSample;

/**
 * @author Valentino de Lapa
 */
class ReferencedEntityTest extends TestCase
{

    private DataMapper $dataMapperMock;

    #[\Override]
    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $referenceCacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('cache_', true) . DIRECTORY_SEPARATOR;
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['entityNamespace', 'TestsApplication\\Entities\\'],
                    ['entityPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
                    ['moduleFolders', ['SismaFramework']],
                    ['ormCache', true],
                    ['referenceCacheDirectory', $referenceCacheDirectory],
                    ['referenceCachePath', $referenceCacheDirectory . 'referenceCache.json'],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
        ]);
        Config::setInstance($configMock);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }

    public function testGetCollectionNames()
    {
        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $this->assertIsArray($otherReferencedSample->getCollectionNames());
        $this->assertContains('baseSampleCollectionOtherReferencedSample', $otherReferencedSample->getCollectionNames());
    }

    public function testGetInvalidProperty()
    {
        $this->expectException(InvalidPropertyException::class);
        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $otherReferencedSample->inexistentProperty;
    }

    public function testSetCollectionProperty()
    {
        $baseSampleCollection = new SismaCollection(BaseSample::class);
        $referencedSample = new ReferencedSample($this->dataMapperMock);
        $referencedSample->baseSampleCollectionReferencedEntityWithoutInitialization = $baseSampleCollection;
        $this->assertEquals($baseSampleCollection, $referencedSample->baseSampleCollectionReferencedEntityWithoutInitialization);
        $referencedSample->baseSampleCollectionReferencedEntityWithInitialization = $baseSampleCollection;
        $this->assertEquals($baseSampleCollection, $referencedSample->baseSampleCollectionReferencedEntityWithInitialization);
        $referencedSample->baseSampleCollectionNullableReferencedEntityWithInitialization = $baseSampleCollection;
        $this->assertEquals($baseSampleCollection, $referencedSample->baseSampleCollectionNullableReferencedEntityWithInitialization);

        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $otherReferencedSample->baseSampleCollection = $baseSampleCollection;
        $this->assertEquals($baseSampleCollection, $otherReferencedSample->baseSampleCollection);
    }

    public function testSetInconsistentEntityInCollection()
    {
        $this->expectException(InvalidArgumentException::class);
        $referencedSampleCollection = new SismaCollection(ReferencedSample::class);
        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $otherReferencedSample->baseSampleCollection = $referencedSampleCollection;
    }

    public function testSetInvalidProperty()
    {
        $this->expectException(InvalidPropertyException::class);
        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $otherReferencedSample->inexistentProperty = 'value';
    }

    public function testCheckCollectionExsists()
    {
        $referencedSample = new ReferencedSample($this->dataMapperMock);
        $this->assertTrue($referencedSample->checkCollectionExists('baseSampleCollectionReferencedEntityWithoutInitialization'));
        $this->assertTrue($referencedSample->checkCollectionExists('baseSampleCollectionReferencedEntityWithInitialization'));
        $this->assertTrue($referencedSample->checkCollectionExists('baseSampleCollectionNullableReferencedEntityWithInitialization'));
        $this->assertFalse($referencedSample->checkCollectionExists('baseSampleCollection'));

        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $this->assertTrue($otherReferencedSample->checkCollectionExists('baseSampleCollection'));
    }

    public function testCheckIssetAndCountCollectionProperty()
    {
        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $this->dataMapperMock->expects($this->exactly(2))
                ->method('getCount')
                ->willReturnOnConsecutiveCalls(0, 1);
        $this->assertTrue(isset($otherReferencedSample->baseSampleCollection));
        $this->assertInstanceOf(SismaCollection::class, $otherReferencedSample->baseSampleCollection);
        $this->assertEquals(0, $otherReferencedSample->countEntityCollection('baseSampleCollection'));
        $this->assertTrue(isset($otherReferencedSample->baseSampleCollection));
        $this->assertEquals(1, $otherReferencedSample->countEntityCollection('baseSampleCollection'));
    }
}

<?php

/*
 * The MIT License
 *
 * Copyright 2025 Valentino de Lapa <valentino.delapa@gmail.com>.
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
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Entities\SelfReferencedSample;

/**
 * Description of SelfReferencedEntityTest
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SelfReferencedEntityTest extends TestCase
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
                    ['foreignKeySuffix', 'Collection'],
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
        Config::setInstance($configMock);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }

    public function testForceCollectionPropertyWithId()
    {
        $selfReferencedSampleOne = new SelfReferencedSample($this->dataMapperMock);
        $sismaCollectionOne = new SismaCollection(SelfReferencedSample::class);
        $sismaCollectionOne->append($selfReferencedSampleOne);
        $this->dataMapperMock->expects($this->once())
                ->method('find')
                ->willReturn($sismaCollectionOne);
        $selfReferencedSampleTwo = new SelfReferencedSample($this->dataMapperMock);
        $selfReferencedSampleTwo->id = 1;
        $this->assertEquals($sismaCollectionOne, $selfReferencedSampleTwo->sonCollection);
        $this->assertEquals($selfReferencedSampleOne, $selfReferencedSampleTwo->sonCollection[0]);
    }

    public function testNotForceCollectionPropertyWithoutId()
    {
        $this->dataMapperMock->expects($this->never())
                ->method('find');
        $selfReferencedSampleOne = new SelfReferencedSample($this->dataMapperMock);
        $this->assertInstanceOf(SismaCollection::class, $selfReferencedSampleOne->sonCollection);
    }

    public function testNotForceCollectionProperty()
    {
        $this->dataMapperMock->expects($this->never())
                ->method('find');
        $selfReferencedSampleOne = new SelfReferencedSample();
        $selfReferencedSampleTwo = new SelfReferencedSample($this->dataMapperMock);
        $selfReferencedSampleTwo->id = 1;
        $selfReferencedSampleTwo->addSelfReferencedSample($selfReferencedSampleOne);
        $this->assertInstanceOf(SismaCollection::class, $selfReferencedSampleTwo->sonCollection);
        $this->assertEquals($selfReferencedSampleOne, $selfReferencedSampleTwo->sonCollection[0]);
        $selfReferencedSampleThree = new SelfReferencedSample($this->dataMapperMock);
        $selfReferencedSampleThree->id = 2;
        $selfReferencedSampleThree->addEntityToEntityCollection('sonCollection', $selfReferencedSampleOne);
        $this->assertInstanceOf(SismaCollection::class, $selfReferencedSampleThree->sonCollection);
        $this->assertEquals($selfReferencedSampleOne, $selfReferencedSampleThree->sonCollection[0]);
        $sismaCollectionOne = new SismaCollection(SelfReferencedSample::class);
        $selfReferencedSampleFour = new SelfReferencedSample($this->dataMapperMock);
        $selfReferencedSampleFour->id = 3;
        $selfReferencedSampleFour->setSelfReferencedSampleCollection($sismaCollectionOne);
        $this->assertInstanceOf(SismaCollection::class, $selfReferencedSampleFour->sonCollection);
        $this->assertEquals($sismaCollectionOne, $selfReferencedSampleFour->sonCollection);
        $selfReferencedSampleFive = new SelfReferencedSample($this->dataMapperMock);
        $selfReferencedSampleFive->id = 4;
        $selfReferencedSampleFive->setEntityCollection('sonCollection', $sismaCollectionOne);
        $this->assertInstanceOf(SismaCollection::class, $selfReferencedSampleFive->sonCollection);
        $this->assertEquals($sismaCollectionOne, $selfReferencedSampleFive->sonCollection);
    }

    public function testCollectionPropertyIsSettedWithSetCollection()
    {
        $this->dataMapperMock->expects($this->never())
                ->method('find');
        $selfReferencedSampleOne = new SelfReferencedSample($this->dataMapperMock);
        $this->assertFalse($selfReferencedSampleOne->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleOne->sonCollection = new SismaCollection(SelfReferencedSample::class);
        $this->assertTrue($selfReferencedSampleOne->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleTwo = new SelfReferencedSample($this->dataMapperMock);
        $this->assertFalse($selfReferencedSampleTwo->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleTwo->setSelfReferencedSampleCollection(new SismaCollection(SelfReferencedSample::class));
        $this->assertTrue($selfReferencedSampleTwo->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleThree = new SelfReferencedSample($this->dataMapperMock);
        $this->assertFalse($selfReferencedSampleThree->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleThree->setEntityCollection('sonCollection', new SismaCollection(SelfReferencedSample::class));
        $this->assertTrue($selfReferencedSampleThree->collectionPropertyIsSetted('sonCollection'));
    }

    public function testCollectionPropertyIsSettedWithAddEntityOnCollection()
    {
        $this->dataMapperMock->expects($this->never())
                ->method('find');
        $selfReferencedSampleOne = new SelfReferencedSample($this->dataMapperMock);
        $this->assertFalse($selfReferencedSampleOne->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleOne->sonCollection->append(new SelfReferencedSample());
        $this->assertTrue($selfReferencedSampleOne->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleTwo = new SelfReferencedSample($this->dataMapperMock);
        $this->assertFalse($selfReferencedSampleTwo->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleTwo->addSelfReferencedSample(new SelfReferencedSample());
        $this->assertTrue($selfReferencedSampleTwo->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleThree = new SelfReferencedSample($this->dataMapperMock);
        $this->assertFalse($selfReferencedSampleThree->collectionPropertyIsSetted('sonCollection'));
        $selfReferencedSampleThree->addEntityToEntityCollection('sonCollection', new SelfReferencedSample());
        $this->assertTrue($selfReferencedSampleThree->collectionPropertyIsSetted('sonCollection'));
    }
}

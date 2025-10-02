<?php

/*
 * The MIT License
 *
 * Copyright 2024 Valentino de Lapa.
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

namespace SismaFramework\Tests\Orm\CustomTypes;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\InvalidTypeException;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;

/**
 * @author Valentino de Lapa
 */
class SismaCollectionTest extends TestCase
{

    private DataMapper $dataMapperMock;

    #[\Override]
    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['developmentEnvironment', false],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
                    ['ormCache', true],
        ]);
        Config::setInstance($configMock);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }

    public function testRestrictiveType()
    {
        $sismaCollection = new SismaCollection(BaseSample::class);
        $this->assertEquals(BaseSample::class, $sismaCollection->getRestrictiveType());
    }

    public function testAppend()
    {
        $this->expectException(InvalidTypeException::class);
        $sismaCollection = new SismaCollection(BaseSample::class);
        $this->assertCount(0, $sismaCollection);
        $sismaCollection->append(new BaseSample($this->dataMapperMock));
        $this->assertCount(1, $sismaCollection);
        $sismaCollection->append(new ReferencedSample($this->dataMapperMock));
    }

    public function testExchangeArray()
    {
        $this->expectException(InvalidTypeException::class);
        $baseSampleOne = new BaseSample($this->dataMapperMock);
        $baseSampleTwo = new BaseSample($this->dataMapperMock);
        $sismaCollection = new SismaCollection(BaseSample::class);
        $sismaCollection->append($baseSampleOne);
        $this->assertEquals($baseSampleOne, $sismaCollection[0]);
        $sismaCollection->exchangeArray([$baseSampleTwo]);
        $this->assertEquals($baseSampleTwo, $sismaCollection[0]);
        $sismaCollection->exchangeArray([new ReferencedSample($this->dataMapperMock)]);
    }

    public function testMergeWith()
    {
        $this->expectException(InvalidTypeException::class);
        $sismaCollectionOne = new SismaCollection(BaseSample::class);
        $sismaCollectionOne->append(new BaseSample($this->dataMapperMock));
        $this->assertCount(1, $sismaCollectionOne);
        $sismaCollectionTwo = new SismaCollection(BaseSample::class);
        $sismaCollectionTwo->append(new BaseSample($this->dataMapperMock));
        $this->assertCount(1, $sismaCollectionTwo);
        $sismaCollectionThree = $sismaCollectionOne->mergeWith($sismaCollectionTwo);
        $this->assertEquals($sismaCollectionThree, $sismaCollectionOne);
        $this->assertCount(2, $sismaCollectionThree);
        $sismaCollectionFour = new SismaCollection(ReferencedSample::class);
        $sismaCollectionFour->append(new ReferencedSample($this->dataMapperMock));
        $this->assertCount(1, $sismaCollectionFour);
        $sismaCollectionOne->mergeWith($sismaCollectionFour);
    }
    
    public function testFindEntityFromProperty()
    {
        $sismaCollection = new SismaCollection(BaseSample::class);
        $baseSampleOne = new BaseSample($this->dataMapperMock);
        $baseSampleOne->stringWithoutInizialization = "search key one";
        $sismaCollection->append($baseSampleOne);
        $baseSampleTwo = new BaseSample($this->dataMapperMock);
        $baseSampleTwo->stringWithoutInizialization = "search key two";
        $sismaCollection->append($baseSampleTwo);
        $this->assertEquals($baseSampleOne, $sismaCollection->findEntityFromProperty('stringWithoutInizialization', "search key one"));
        $this->assertEquals($baseSampleTwo, $sismaCollection->findEntityFromProperty('stringWithoutInizialization', "search key two"));
    }
    
    public function testHas()
    {
        $sismaCollection = new SismaCollection(BaseSample::class);
        $baseSampleOne = new BaseSample($this->dataMapperMock);
        $sismaCollection->append($baseSampleOne);
        $baseSampleTwo = new BaseSample($this->dataMapperMock);
        $sismaCollection->append($baseSampleTwo);
        $baseSampleThree = new BaseSample($this->dataMapperMock);
        $this->assertTrue($sismaCollection->has($baseSampleOne));
        $this->assertTrue($sismaCollection->has($baseSampleTwo));
        $this->assertFalse($sismaCollection->has($baseSampleThree));
    }
    
    public function testSlice()
    {
        $sismaCollectionOne = new SismaCollection(BaseSample::class);
        $baseSampleOne = new BaseSample($this->dataMapperMock);
        $sismaCollectionOne->append($baseSampleOne);
        $baseSampleTwo = new BaseSample($this->dataMapperMock);
        $sismaCollectionOne->append($baseSampleTwo);
        $this->assertCount(2, $sismaCollectionOne);
        $this->assertEquals($baseSampleOne, $sismaCollectionOne[0]);
        $this->assertEquals($baseSampleTwo, $sismaCollectionOne[1]);
        $sismaCollectionTwo = $sismaCollectionOne->slice(1);
        $this->assertEquals($sismaCollectionTwo, $sismaCollectionOne);
        $this->assertCount(1, $sismaCollectionTwo);
        $this->assertEquals($baseSampleTwo, $sismaCollectionOne[0]);
        $baseSampleThree = new BaseSample($this->dataMapperMock);
        $sismaCollectionOne->append($baseSampleThree);
        $baseSampleFour = new BaseSample($this->dataMapperMock);
        $sismaCollectionOne->append($baseSampleFour);
        $this->assertCount(3, $sismaCollectionOne);
        $sismaCollectionOne->slice(1, 1);
        $this->assertCount(1, $sismaCollectionOne);
        $this->assertEquals($baseSampleThree, $sismaCollectionOne[0]);   
    }
    
    public function testIsFirst()
    {
        $sismaCollection = new SismaCollection(BaseSample::class);
        $baseSampleOne = new BaseSample($this->dataMapperMock);
        $sismaCollection->append($baseSampleOne);
        $baseSampleTwo = new BaseSample($this->dataMapperMock);
        $sismaCollection->append($baseSampleTwo);
        $baseSampleThree = new BaseSample($this->dataMapperMock);
        $sismaCollection->append($baseSampleThree);
        $this->assertTrue($sismaCollection->isFirst($baseSampleOne));
        $this->assertFalse($sismaCollection->isFirst($baseSampleTwo));
        $this->assertFalse($sismaCollection->isFirst($baseSampleThree));
    }
    
    public function testIsLast()
    {
        $sismaCollection = new SismaCollection(BaseSample::class);
        $baseSampleOne = new BaseSample($this->dataMapperMock);
        $sismaCollection->append($baseSampleOne);
        $baseSampleTwo = new BaseSample($this->dataMapperMock);
        $sismaCollection->append($baseSampleTwo);
        $baseSampleThree = new BaseSample($this->dataMapperMock);
        $sismaCollection->append($baseSampleThree);
        $this->assertFalse($sismaCollection->isLast($baseSampleOne));
        $this->assertFalse($sismaCollection->isLast($baseSampleTwo));
        $this->assertTrue($sismaCollection->isLast($baseSampleThree));
    }
}

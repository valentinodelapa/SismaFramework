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

namespace SismaFramework\Tests\Orm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\TestsApplication\Entities\SimpleEntity;

/**
 * Description of ParsedEntitiesCollectionTest
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ProcessedEntitiesCollectionTest extends TestCase
{
    
    private DataMapper $dataMapperMock;

    #[\Override]
    public function setUp(): void
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }
    
    public function testHas()
    {
        $simpleEntity = new SimpleEntity($this->dataMapperMock);
        $processedEntitesCollection = ProcessedEntitiesCollection::getInstance();
        $this->assertFalse($processedEntitesCollection->has($simpleEntity));
        $processedEntitesCollection->append($simpleEntity);
        $this->assertTrue($processedEntitesCollection->has($simpleEntity));
    }
    
    public function testRemove()
    {
        $simpleEntity = new SimpleEntity($this->dataMapperMock);
        $processedEntitesCollection = ProcessedEntitiesCollection::getInstance();
        $this->assertFalse($processedEntitesCollection->has($simpleEntity));
        $processedEntitesCollection->append($simpleEntity);
        $this->assertTrue($processedEntitesCollection->has($simpleEntity));
        $processedEntitesCollection->remove($simpleEntity);
        $this->assertFalse($processedEntitesCollection->has($simpleEntity));
    }
    
    public function testClear()
    {
        $simpleEntity = new SimpleEntity($this->dataMapperMock);
        $processedEntitesCollection = ProcessedEntitiesCollection::getInstance();
        $this->assertFalse($processedEntitesCollection->has($simpleEntity));
        $processedEntitesCollection->append($simpleEntity);
        $this->assertTrue($processedEntitesCollection->has($simpleEntity));
        $processedEntitesCollection->clear();
        $this->assertFalse($processedEntitesCollection->has($simpleEntity));
        $this->assertCount(0, $processedEntitesCollection);
    }
}

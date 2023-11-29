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
use SismaFramework\Orm\Exceptions\InvalidPropertyException;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Entities\OtherReferencedSample;

/**
 * @author Valentino de Lapa
 */
class ReferencedEntityTest extends TestCase
{
    
    public function testGetInvalidProperty()
    {
        $this->expectException(InvalidPropertyException::class);
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->inexistentProperty;
    }
    
    public function testSetInvalidProperty()
    {
        $this->expectException(InvalidPropertyException::class);
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->inexistentProperty = 'value';
    }

    public function testModifyCollectionNestedChanges()
    {
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->addBaseSample(new BaseSample());
        $this->assertTrue($otherReferencedSample->collectionNestedChanges);
        $otherReferencedSample->collectionNestedChanges = false;
        $otherReferencedSample->baseSampleCollection[0]->stringWithoutInizialization = 'base sample';
        $this->assertTrue($otherReferencedSample->collectionNestedChanges);
        $otherReferencedSample->collectionNestedChanges = false;
        $this->assertFalse($otherReferencedSample->collectionNestedChanges);
        $otherReferencedSample->baseSampleCollection[0]->stringWithoutInizialization = 'base sample modified';
        $this->assertTrue($otherReferencedSample->collectionNestedChanges);
    }

    public function testSetCollectionNestedChange()
    {
        $otherReferencedSampleOne = new OtherReferencedSample();
        $baseSampleOne = new BaseSample();
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleCollectionOne = new SismaCollection(BaseSample::class);
        $baseSampleCollectionOne->append($baseSampleOne);
        $otherReferencedSampleOne->setBaseSampleCollection($baseSampleCollectionOne);
        $this->assertTrue($otherReferencedSampleOne->collectionNestedChanges);
        
        $otherReferencedSampleTwo = new OtherReferencedSample();
        $baseSampleTwo = new BaseSample();
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->id = 1;
        $this->assertTrue($baseSampleTwo->modified);
        $baseSampleTwo->modified = false;
        $baseSampleTwo->stringWithoutInizialization = 'base sample';
        $this->assertTrue($baseSampleTwo->modified);
        $baseSampleCollectionTwo = new SismaCollection(BaseSample::class);
        $baseSampleCollectionTwo->append($baseSampleTwo);
        $otherReferencedSampleTwo->setBaseSampleCollection($baseSampleCollectionTwo);
        $this->assertTrue($otherReferencedSampleTwo->collectionNestedChanges);
        
        $otherReferencedSampleThree = new OtherReferencedSample();
        $otherReferencedSampleThree->id = 1;
        $baseSampleThree = new BaseSample();
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->id = 1;
        $baseSampleThree->otherReferencedSample = $otherReferencedSampleThree;
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleCollectionThree = new SismaCollection(BaseSample::class);
        $baseSampleCollectionThree->append($baseSampleThree);
        $otherReferencedSampleThree->setBaseSampleCollection($baseSampleCollectionThree);
        $this->assertFalse($otherReferencedSampleThree->collectionNestedChanges);
    }

    public function testAddCollectionNestedChange()
    {
        $otherReferencedSampleOne = new OtherReferencedSample();
        $baseSampleOne = new BaseSample();
        $this->assertFalse($baseSampleOne->modified);
        $otherReferencedSampleOne->addBaseSample($baseSampleOne);
        $this->assertTrue($otherReferencedSampleOne->collectionNestedChanges);
        
        $otherReferencedSampleTwo = new OtherReferencedSample();
        $baseSampleTwo = new BaseSample();
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->id = 1;
        $this->assertTrue($baseSampleTwo->modified);
        $baseSampleTwo->modified = false;
        $baseSampleTwo->stringWithoutInizialization = 'base sample';
        $this->assertTrue($baseSampleTwo->modified);
        $otherReferencedSampleTwo->addBaseSample($baseSampleTwo);
        $this->assertTrue($otherReferencedSampleTwo->collectionNestedChanges);
        
        $otherReferencedSampleThree = new OtherReferencedSample();
        $otherReferencedSampleThree->id = 1;
        $baseSampleThree = new BaseSample();
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->id = 1;
        $baseSampleThree->otherReferencedSample = $otherReferencedSampleThree;
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $otherReferencedSampleThree->addBaseSample($baseSampleThree);
        $this->assertFalse($otherReferencedSampleThree->collectionNestedChanges);
    }
}

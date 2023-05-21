<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa <valentino.delapa@gmail.com>.
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
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Entities\ReferencedSample;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class BaseEntityTest extends TestCase
{
    
    public function testEntityWithEntityNotConvertedPropertyNotModified()
    {
        $baseSample = new BaseSample();
        $baseSample->text = 'base sample';
        $baseSample->boolean = true;
        $baseSample->referencedSample = 1;
        $this->assertFalse($baseSample->modified);
    }
    
    public function testEntityWithEntityNotConvertedPropertyModified()
    {
        $baseSampleOne = new BaseSample();
        $baseSampleOne->referencedSample = 1;
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->referencedSample = 2;
        $this->assertTrue($baseSampleOne->modified);
        
        $baseSampleTwo = new BaseSample();
        $baseSampleTwo->referencedSample = new ReferencedSample();
        $baseSampleTwo->referencedSample = 1;
        $this->assertTrue($baseSampleTwo->modified);
        
        $baseSampleThree = new BaseSample();
        $baseSampleThree->referencedSample = new ReferencedSample();
        $baseSampleThree->referencedSample->id = 2;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->referencedSample = 3;
        $this->assertTrue($baseSampleThree->modified);
    }
    
    public function testEntityWithEntityConvertedPropertyNotModified()
    {
        $baseSample = new BaseSample();
        $baseSample->text = 'base sample';
        $baseSample->boolean = true;
        $referencedSample = new ReferencedSample();
        $baseSample->referencedSample = $referencedSample;
        $this->assertFalse($baseSample->modified);
    }
    
    public function testEntityWithEntityConvertedPropertyModifiedOne()
    {
        $baseSampleOne = new BaseSample();
        $baseSampleOne->referencedSample = new ReferencedSample();
        $baseSampleOne->referencedSample->id = 1;
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->referencedSample = new ReferencedSample();
        $this->assertTrue($baseSampleOne->modified);
        
        $baseSampleTwo = new BaseSample();
        $baseSampleTwo->referencedSample = 1;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedSample = new ReferencedSample();
        $this->assertTrue($baseSampleTwo->modified);
        
        $baseSampleThree = new BaseSample();
        $baseSampleThree->referencedSample = 1;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->referencedSample = new ReferencedSample();
        $baseSampleThree->referencedSample->id = 2;
        $this->assertTrue($baseSampleThree->modified);
        
        $baseSampleFour = new BaseSample();
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 1;
        $referencedSample->text = 'referenced sample';
        $baseSampleFour->referencedSample = $referencedSample;
        $this->assertFalse($baseSampleFour->modified);
        $referencedSample->text = 'referenced sample modified';
        $baseSampleFour->referencedSample = $referencedSample;
        $this->assertFalse($baseSampleFour->modified);
    }
    
    public function testEntityWithBuiltInPropertyNotModified()
    {
        $baseSample = new BaseSample();
        $baseSample->text = 'base sample';
        $this->assertFalse($baseSample->modified);
    }
    
    public function testEntityWithBuiltInPropertyModified()
    {
        $baseSample = new BaseSample();
        $baseSample->text = 'base sample';
        $this->assertFalse($baseSample->modified);
        $baseSample->text = 'base sample';
        $this->assertFalse($baseSample->modified);
        $baseSample->text = 'base sample modified';
        $this->assertTrue($baseSample->modified);
    }
    
    public function testForeignKeyNestedChanges()
    {
        $baseSampleOne = new BaseSample();
        $baseSampleOne->referencedSample = new ReferencedSample();
        $baseSampleOne->referencedSample->text = 'referenced sample';
        $this->assertFalse($baseSampleOne->nestedChanges);
        $baseSampleOne->referencedSample->text = 'referenced sample modified';
        $this->assertTrue($baseSampleOne->nestedChanges);
        
        $baseSampleTwo = new BaseSample();
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 1;
        $referencedSample->text = 'referenced sample';
        Cache::setEntity($referencedSample);
        $baseSampleTwo->referencedSample = 1;
        $this->assertFalse($baseSampleTwo->nestedChanges);
        $baseSampleTwo->referencedSample->text = 'referenced sample modified';
        $this->assertTrue($baseSampleTwo->nestedChanges);
    }
}

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
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Entities\ReferencedSample;
use SismaFramework\Sample\Enumerations\SampleType;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class BaseEntityTest extends TestCase
{

    public function testEntityWithEntityNotConvertedProperty()
    {
        $baseSampleOne = new BaseSample();
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->referencedEntityWithoutInitialization = 1;
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->referencedEntityWithoutInitialization = 1;
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->referencedEntityWithoutInitialization = 2;
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample();
        $baseSampleTwo->referencedEntityWithInitialization->id = 1;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedEntityWithInitialization = 1;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedEntityWithInitialization = 2;
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample();
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
        $referencedSampleOne = new ReferencedSample();
        $referencedSampleOne->id = 1;
        $baseSampleOne = new BaseSample();
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->referencedEntityWithoutInitialization = $referencedSampleOne;
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->referencedEntityWithoutInitialization = $referencedSampleOne;
        $this->assertFalse($baseSampleOne->modified);
        $referencedSampleTwo = new ReferencedSample();
        $referencedSampleTwo->id = 2;
        $baseSampleOne->referencedEntityWithoutInitialization = $referencedSampleTwo;
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->referencedEntityWithoutInitialization = new ReferencedSample();
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample();
        $baseSampleTwo->referencedEntityWithInitialization->id = 1;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedEntityWithInitialization = $baseSampleTwo->referencedEntityWithInitialization;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->referencedEntityWithInitialization = new ReferencedSample();
        $this->assertTrue($baseSampleTwo->modified);

        $referencedSampleFour = new ReferencedSample();
        $referencedSampleFour->id = 1;
        $baseSampleThree = new BaseSample();
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
        $baseSampleOne = new BaseSample();
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->stringWithoutInizialization = 'base sample';
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->stringWithoutInizialization = 'base sample';
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->stringWithoutInizialization = 'base sample modified';
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample();
        $baseSampleTwo->stringWithInizialization = 'base sample';
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->stringWithInizialization = 'base sample modified';
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample();
        $baseSampleThree->nullableStringWithInizialization = null;
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableStringWithInizialization = 'nullable string';
        $this->assertTrue($baseSampleThree->modified);
        $baseSampleThree->modified = false;
        $baseSampleThree->nullableStringWithInizialization = 'nullable string';
        $this->assertFalse($baseSampleThree->modified);
        $baseSampleThree->nullableStringWithInizialization = 'nullable modified string';
        $this->assertTrue($baseSampleThree->modified);
    }

    public function testEntityWithEnumProperty()
    {
        $baseSampleOne = new BaseSample();
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->enumWithoutInitialization = SampleType::one;
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->enumWithoutInitialization = SampleType::one;
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->enumWithoutInitialization = SampleType::two;
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample();
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->enumWithInitialization = SampleType::one;
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->enumWithInitialization = SampleType::two;
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample();
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
        $baseSampleOne = new BaseSample();
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->assertTrue($baseSampleOne->modified);
        $baseSampleOne->modified = false;
        $baseSampleOne->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->assertFalse($baseSampleOne->modified);
        $baseSampleOne->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $this->assertTrue($baseSampleOne->modified);

        $baseSampleTwo = new BaseSample();
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->datetimeWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->assertFalse($baseSampleTwo->modified);
        $baseSampleTwo->datetimeWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $this->assertTrue($baseSampleTwo->modified);

        $baseSampleThree = new BaseSample();
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

    public function testForeignKeyNestedChanges()
    {
        $baseSampleOne = new BaseSample();
        $this->assertFalse($baseSampleOne->nestedChanges);
        $baseSampleOne->referencedEntityWithoutInitialization = new ReferencedSample();
        $baseSampleOne->referencedEntityWithoutInitialization->text = 'referenced sample';
        $this->assertTrue($baseSampleOne->nestedChanges);
        $baseSampleOne->nestedChanges = false;
        $baseSampleOne->referencedEntityWithoutInitialization->text = 'referenced sample';
        $this->assertFalse($baseSampleOne->nestedChanges);
        $baseSampleOne->referencedEntityWithoutInitialization->text = 'referenced sample modified';
        $this->assertTrue($baseSampleOne->nestedChanges);

        $baseSampleTwo = new BaseSample();
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 1;
        $referencedSample->text = 'referenced sample';
        Cache::setEntity($referencedSample);
        $baseSampleTwo->referencedEntityWithoutInitialization = 1;
        $this->assertFalse($baseSampleTwo->nestedChanges);
        $baseSampleOne->referencedEntityWithoutInitialization->text = 'referenced sample';
        $this->assertFalse($baseSampleTwo->nestedChanges);
        $baseSampleTwo->referencedEntityWithoutInitialization->text = 'referenced sample modified';
        $this->assertTrue($baseSampleTwo->nestedChanges);
    }
}

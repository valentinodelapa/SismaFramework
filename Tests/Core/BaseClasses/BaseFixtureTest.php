<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Tests\Core\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Exceptions\FixtureException;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Entities\FakeReferencedSample;
use SismaFramework\Sample\Entities\OtherReferencedSample;
use SismaFramework\Sample\Entities\ReferencedSample;
use SismaFramework\Sample\FakeFixtures\FakeBaseSampleFixture;
use SismaFramework\Sample\FakeFixtures\FakeReferencedSampleFixture;
use SismaFramework\Sample\Fixtures\BaseSampleFixture;
use SismaFramework\Sample\Fixtures\OtherReferencedSampleFixture;
use SismaFramework\Sample\Fixtures\ReferencedSampleFixture;

/**
 * Description of BaseFixtureTest
 *
 * Copyright 2022 Valentino de Lapa <valentino.delapa@gmail.com>.
 */
class BaseFixtureTest extends TestCase
{

    public function testBaseSampleFixture()
    {
        $dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['save'])
                ->getMock();
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample text';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample text';
        $entitesArray = [
            ReferencedSampleFixture::class => $referencedSample,
            OtherReferencedSampleFixture::class => $otherReferencedSample,
        ];
        $baseSampleFixture = new BaseSampleFixture($dataMapperMock);
        $baseSample = $baseSampleFixture->execute($entitesArray);
        $this->assertInstanceOf(BaseSample::class, $baseSample);
        $this->assertInstanceOf(ReferencedSample::class, $baseSample->referencedEntityWithoutInitialization);
        $this->assertInstanceOf(ReferencedSample::class, $baseSample->referencedEntityWithInitialization);
        $this->assertInstanceOf(OtherReferencedSample::class, $baseSample->otherReferencedSample);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFakeBaseSampleFixtureWithException()
    {
        $this->expectException(FixtureException::class);
        $dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['save'])
                ->getMock();
        $entitesArray = [
            FakeReferencedSampleFixture::class => new FakeReferencedSample(),
        ];
        $fakeBaseSampleFixture = new FakeBaseSampleFixture($dataMapperMock);
        $fakeBaseSampleFixture->execute($entitesArray);
    }

}

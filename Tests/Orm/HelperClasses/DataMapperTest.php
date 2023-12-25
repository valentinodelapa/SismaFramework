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

namespace SismaFramework\Tests\Orm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Entities\ReferencedSample;
use SismaFramework\Sample\Entities\OtherReferencedSample;
use SismaFramework\Sample\Enumerations\SampleType;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\ProprietaryTypes\SismaDateTime;

/**
 * @author Valentino de Lapa
 */
class DataMapperTest extends TestCase
{

    public function testSaveNewBaseEntityWithInsertInsertInsert()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(3);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample"], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-01 00:00:00', null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(3))
                ->method('parseInsert');
        $baseAdapterMock->expects($this->exactly(3))
                ->method('lastInsertId')
                ->willReturn(1);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertUpdateInsert()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(3);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-01 00:00:00', null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(2))
                ->method('parseInsert');
        $baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $baseAdapterMock->expects($this->exactly(2))
                ->method('lastInsertId')
                ->willReturn(1);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithNothingUpdateInsert()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(2);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 3], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([2, 2, null, 3, '2020-01-02 00:00:00', '2020-01-01 00:00:00', null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(1))
                ->method('parseInsert');
        $baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $baseAdapterMock->expects($this->exactly(1))
                ->method('lastInsertId')
                ->willReturn(1);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 2;
        $referencedSample->text = 'referenced sample';
        $referencedSample->modified = false;
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 3;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertInsertUpdate()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(3);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample"], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-01 00:00:00', null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 2], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(2))
                ->method('parseInsert');
        $baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $baseAdapterMock->expects($this->exactly(2))
                ->method('lastInsertId')
                ->willReturn(1);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 2;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertUpdateUpdate()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(3);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-01 00:00:00', null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 3], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(1))
                ->method('parseInsert');
        $baseAdapterMock->expects($this->exactly(2))
                ->method('parseUpdate');
        $baseAdapterMock->expects($this->exactly(1))
                ->method('lastInsertId')
                ->willReturn(1);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 3;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithNothingUpdateUpdate()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(2);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-01 00:00:00', null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 3], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(2))
                ->method('parseUpdate');
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 1;
        $referencedSample->text = 'referenced sample';
        $referencedSample->modified = false;
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 3;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertInsertNothing()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(2);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample"], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(2))
                ->method('parseInsert');
        $baseAdapterMock->expects($this->exactly(2))
                ->method('lastInsertId')
                ->willReturn(1);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 2;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $baseSample->modified = false;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithInsertUpdateNothing()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(2);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(1))
                ->method('parseInsert');
        $baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        $baseAdapterMock->expects($this->exactly(1))
                ->method('lastInsertId')
                ->willReturn(1);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 3;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $baseSample->modified = false;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testSaveNewBaseEntityWithNothingUpdateNothing()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(1);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample", 2], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(1))
                ->method('parseUpdate');
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->id = 1;
        $referencedSample->text = 'referenced sample';
        $referencedSample->modified = false;
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->id = 2;
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 3;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $baseSample->modified = false;
        $dataMapper = new DataMapper();
        $dataMapper->save($baseSample);
    }

    public function testNewReferencedEntityWithInsertInsertInsert()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $matcher = $this->exactly(3);
        $baseAdapterMock->expects($matcher)
                ->method('execute')
                ->willReturnCallback(function ($param1, $param2, $param3) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["referenced sample", null], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 2:
                            $this->assertEquals('', $param1);
                            $this->assertEquals(["other referenced sample"], $param2);
                            $this->assertEquals([], $param3);
                            break;
                        case 3:
                            $this->assertEquals('', $param1);
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-01 00:00:00', null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
                            $this->assertEquals([], $param3);
                            break;
                    }
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(3))
                ->method('parseInsert');
        $baseAdapterMock->expects($this->exactly(3))
                ->method('lastInsertId')
                ->willReturn(1);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $baseSampleCollection = new SismaCollection(BaseSample::class);
        $baseSampleCollection->append($baseSample);
        $referencedSample->setBaseSampleCollectionReferencedEntityWithoutInitialization($baseSampleCollection);
        $referencedSample->setBaseSampleCollectionReferencedEntityWithInitialization($baseSampleCollection);
        $dataMapper = new DataMapper();
        $dataMapper->save($referencedSample);
    }
}

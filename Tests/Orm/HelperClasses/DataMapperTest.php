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
use SismaFramework\Core\Exceptions\InvalidTypeException;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\Exceptions\DataMapperException;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaTime;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;
use SismaFramework\TestsApplication\Entities\OtherReferencedSample;
use SismaFramework\TestsApplication\Entities\DependentEntityOne;
use SismaFramework\TestsApplication\Entities\DependentEntityTwo;
use SismaFramework\TestsApplication\Entities\EntityWithTwoCollection;
use SismaFramework\TestsApplication\Entities\SubdependentEntity;
use SismaFramework\TestsApplication\Enumerations\SampleType;

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
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
                            $this->assertEquals([2, 2, null, 3, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 2], $param2);
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 3], $param2);
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
                            $this->assertEquals([1, 1, null, 2, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1, 3], $param2);
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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
                            $this->assertEquals([1, 1, null, 1, '2020-01-02 00:00:00', '2020-01-02', '10:25:31', '2020-01-01 00:00:00', '2020-01-01', '10:31:25', null, null, null, 'T', 'O', null, "base sample", "base sample", null, null, 1], $param2);
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
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

    public function testInitQuery()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $query = $this->createMock(Query::class);
        $query->expects($this->any())
                ->method('setTable')
                ->with('entity_name');
        $dataMapper = new DataMapper($baseAdapterMock);
        $this->assertInstanceOf(Query::class, $dataMapper->initQuery(BaseSample::class));
    }

    public function testInsertAutomaticStartTransaction()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturn(true);
        $baseAdapterMock->expects($this->exactly(3))
                ->method('execute')
                ->willReturn(true);
        $baseAdapterMock->expects($this->exactly(3))
                ->method('lastInsertId')
                ->willReturn(1);
        $baseAdapterMock->expects($this->once())
                ->method('commitTransaction')
                ->willReturn(true);
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper($baseAdapterMock);
        $dataMapper->save($baseSample);
    }

    public function testInsertManualStartTransaction()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $callsOrder = [];
        $baseAdapterMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'beginTransaction';
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(3))
                ->method('execute')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'execute';
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(3))
                ->method('lastInsertId')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'lastInsertId';
                    return 1;
                });
        $baseAdapterMock->expects($this->once())
                ->method('commitTransaction')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'commitTransaction';
                    return true;
                });
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
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper($baseAdapterMock);
        $dataMapper->startTransaction();
        $dataMapper->save($baseSample);
        $dataMapper->commitTransaction();
        $this->assertEquals(['beginTransaction', 'execute', 'lastInsertId', 'execute', 'lastInsertId', 'execute', 'lastInsertId', 'commitTransaction'], $callsOrder);
    }

    public function testNotDuplicateSavingCollection()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $callsOrder = [];
        $baseAdapterMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'beginTransaction';
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(3))
                ->method('execute')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'execute';
                    return true;
                });
        $matcher = $this->exactly(3);
        $baseAdapterMock->expects($matcher)
                ->method('lastInsertId')
                ->willReturnCallback(function () use (&$callsOrder, $matcher) {
                    $callsOrder[] = 'lastInsertId';
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            return 1;
                        case 2:
                            return 2;
                        case 3:
                            return 3;
                    }
                });
        $baseAdapterMock->expects($this->once())
                ->method('commitTransaction')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'commitTransaction';
                    return true;
                });
        BaseAdapter::setDefault($baseAdapterMock);
        $entityWithTwoCollection = new EntityWithTwoCollection();
        $entityWithTwoCollection->addDependentEntityOne(new DependentEntityOne());
        $entityWithTwoCollection->addDependentEntityTwo(new DependentEntityTwo());
        $dataMapper = new DataMapper($baseAdapterMock);
        $dataMapper->save($entityWithTwoCollection);
        $this->assertEquals(['beginTransaction', 'execute', 'lastInsertId', 'execute', 'lastInsertId', 'execute', 'lastInsertId', 'commitTransaction'], $callsOrder);
    }

    public function testSaveModificationOnSubnidificateCollection()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $callsOrder = [];
        $baseAdapterMock->expects($this->exactly(2))
                ->method('beginTransaction')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'beginTransaction';
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(5))
                ->method('execute')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'execute';
                    return true;
                });
        $matcherOne = $this->exactly(5);
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeTable')
                ->willReturnCallback(function ($entity) use (&$matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals("entity_with_two_collection", $entity);
                            break;
                        case 2:
                            $this->assertEquals("dependent_entity_one", $entity);
                            break;
                        case 3:
                            $this->assertEquals("dependent_entity_two", $entity);
                            break;
                        case 4:
                            $this->assertEquals("subdependent_entity", $entity);
                            break;
                        case 5:
                            $this->assertEquals("subdependent_entity", $entity);
                            break;
                    }
                    return $entity;
                });
        $matcherTwo = $this->exactly(4);
        $baseAdapterMock->expects($matcherTwo)
                ->method('parseInsert')
                ->willReturnCallback(function ($query) use (&$callsOrder, $matcherTwo) {
                    $callsOrder[] = 'parseInsert';
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals("entity_with_two_collection", $query);
                            break;
                        case 2:
                            $this->assertEquals("dependent_entity_one", $query);
                            break;
                        case 3:
                            $this->assertEquals("dependent_entity_two", $query);
                            break;
                        case 4:
                            $this->assertEquals("subdependent_entity", $query);
                            break;
                    }
                    return $query;
                });
        $baseAdapterMock->expects($this->once())
                ->method('parseUpdate')
                ->willReturnCallback(function ($query) use (&$callsOrder) {
                    $callsOrder[] = 'parseUpdate';
                    $this->assertEquals('subdependent_entity', $query);
                    return $query;
                });
        $matcherThree = $this->exactly(4);
        $baseAdapterMock->expects($matcherThree)
                ->method('lastInsertId')
                ->willReturnCallback(function () use (&$callsOrder, $matcherThree) {
                    $callsOrder[] = 'lastInsertId';
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            return 1;
                        case 2:
                            return 2;
                        case 3:
                            return 3;
                        case 4:
                            return 4;
                    }
                });
        $baseAdapterMock->expects($this->exactly(2))
                ->method('commitTransaction')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'commitTransaction';
                    return true;
                });
        BaseAdapter::setDefault($baseAdapterMock);
        $entityWithTwoCollection = new EntityWithTwoCollection();
        $entityWithTwoCollection->addDependentEntityOne(new DependentEntityOne());
        $dependentEntityTwo = new DependentEntityTwo();
        $subdependentEntity = new SubdependentEntity();
        $subdependentEntity->string = 'test';
        $dependentEntityTwo->addSubdependentEntity($subdependentEntity);
        $entityWithTwoCollection->addDependentEntityTwo($dependentEntityTwo);
        $dataMapper = new DataMapper($baseAdapterMock);
        $dataMapper->save($entityWithTwoCollection);
        $subdependentEntity->string = 'testTwo';
        $dataMapper->save($entityWithTwoCollection);
        $this->assertEquals([
            'beginTransaction',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'commitTransaction',
            'beginTransaction',
            'parseUpdate',
            'execute',
            'commitTransaction'
                ], $callsOrder);
    }

    public function testSaveModificationOnSubnidificateEntity()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $callsOrder = [];
        $baseAdapterMock->expects($this->exactly(2))
                ->method('beginTransaction')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'beginTransaction';
                    return true;
                });
        $baseAdapterMock->expects($this->exactly(5))
                ->method('execute')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'execute';
                    return true;
                });
        $matcherOne = $this->exactly(5);
        $baseAdapterMock->expects($matcherOne)
                ->method('escapeTable')
                ->willReturnCallback(function ($entity) use (&$matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals("dependent_entity_one", $entity);
                            break;
                        case 2:
                            $this->assertEquals("entity_with_two_collection", $entity);
                            break;
                        case 3:
                            $this->assertEquals("dependent_entity_two", $entity);
                            break;
                        case 4:
                            $this->assertEquals("subdependent_entity", $entity);
                            break;
                        case 5:
                            $this->assertEquals("subdependent_entity", $entity);
                            break;
                    }
                    return $entity;
                });
        $matcherTwo = $this->exactly(4);
        $baseAdapterMock->expects($matcherTwo)
                ->method('parseInsert')
                ->willReturnCallback(function ($query) use (&$callsOrder, $matcherTwo) {
                    $callsOrder[] = 'parseInsert';
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals("entity_with_two_collection", $query);
                            break;
                        case 2:
                            $this->assertEquals("dependent_entity_two", $query);
                            break;
                        case 3:
                            $this->assertEquals("subdependent_entity", $query);
                            break;
                        case 4:
                            $this->assertEquals("dependent_entity_one", $query);
                            break;
                    }
                    return $query;
                });
        $baseAdapterMock->expects($this->once())
                ->method('parseUpdate')
                ->willReturnCallback(function ($query) use (&$callsOrder) {
                    $callsOrder[] = 'parseUpdate';
                    $this->assertEquals('subdependent_entity', $query);
                    return $query;
                });
        $matcherThree = $this->exactly(4);
        $baseAdapterMock->expects($matcherThree)
                ->method('lastInsertId')
                ->willReturnCallback(function () use (&$callsOrder, $matcherThree) {
                    $callsOrder[] = 'lastInsertId';
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            return 1;
                        case 2:
                            return 2;
                        case 3:
                            return 3;
                        case 4:
                            return 4;
                    }
                });
        $baseAdapterMock->expects($this->exactly(2))
                ->method('commitTransaction')
                ->willReturnCallback(function () use (&$callsOrder) {
                    $callsOrder[] = 'commitTransaction';
                    return true;
                });
        BaseAdapter::setDefault($baseAdapterMock);
        $dependentEntityOne = new DependentEntityOne();
        $dependentEntityOne->entityWithTwoCollection = new EntityWithTwoCollection();
        $dependentEntityTwo = new DependentEntityTwo();
        $subdependentEntity = new SubdependentEntity();
        $subdependentEntity->string = 'test';
        $dependentEntityTwo->addSubdependentEntity($subdependentEntity);
        $dependentEntityOne->entityWithTwoCollection->addDependentEntityTwo($dependentEntityTwo);
        $dataMapper = new DataMapper($baseAdapterMock);
        $dataMapper->save($dependentEntityOne);
        $subdependentEntity->string = 'testTwo';
        $dataMapper->save($dependentEntityOne);
        $this->assertEquals([
            'beginTransaction',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'parseInsert',
            'execute',
            'lastInsertId',
            'commitTransaction',
            'beginTransaction',
            'parseUpdate',
            'execute',
            'commitTransaction'
                ], $callsOrder);
    }

    public function testUpdateAutomaticStartTransaction()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('beginTransaction')
                ->willReturn(true);
        $baseAdapterMock->expects($this->exactly(3))
                ->method('execute')
                ->willReturn(true);
        $baseAdapterMock->expects($this->exactly(2))
                ->method('lastInsertId')
                ->willReturn(1);
        $baseAdapterMock->expects($this->once())
                ->method('commitTransaction')
                ->willReturn(true);
        BaseAdapter::setDefault($baseAdapterMock);
        $referencedSample = new ReferencedSample();
        $referencedSample->text = 'referenced sample';
        $otherReferencedSample = new OtherReferencedSample();
        $otherReferencedSample->text = 'other referenced sample';
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        $baseSample->referencedEntityWithoutInitialization = $referencedSample;
        $baseSample->referencedEntityWithInitialization = $referencedSample;
        $baseSample->otherReferencedSample = $otherReferencedSample;
        $baseSample->datetimeWithoutInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-02 00:00:00');
        $baseSample->dateWithoutInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-02');
        $baseSample->timeWithoutInitialization = SismaTime::createFromStandardTimeFormat('10:25:31');
        $baseSample->enumWithoutInitialization = SampleType::two;
        $baseSample->stringWithoutInizialization = 'base sample';
        $baseSample->boolean = true;
        $dataMapper = new DataMapper($baseAdapterMock);
        $dataMapper->save($baseSample);
    }

    public function testDelete()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(2))
                ->method('execute')
                ->willReturnOnConsecutiveCalls(false, true);
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->exactly(2))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = new DataMapper($baseAdapterMock);
        $baseSampleOne = new BaseSample();
        $baseSampleOne->id = 1;
        $baseSampleOne->setPrimaryKeyPropertyName('');
        $this->assertFalse($dataMapper->delete($baseSampleOne, $queryMock));
        $baseSampleTwo = new BaseSample();
        $baseSampleTwo->id = 1;
        $this->assertFalse($dataMapper->delete($baseSampleTwo, $queryMock));
        $baseSampleTree = new BaseSample();
        $baseSampleTree->id = 1;
        $this->assertTrue($dataMapper->delete($baseSampleTree, $queryMock));
    }

    public function testDeleteBatch()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(2))
                ->method('execute')
                ->willReturnOnConsecutiveCalls(false, true);
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->exactly(2))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = new DataMapper($baseAdapterMock);
        $this->assertFalse($dataMapper->deleteBatch($queryMock));
        $this->assertTrue($dataMapper->deleteBatch($queryMock));
    }

    public function testFind()
    {
        $firstInitializedBaseSample = new BaseSample();
        $firstInitializedBaseSample->id = 1;
        $secondInitializedBaseSample = new BaseSample();
        $secondInitializedBaseSample->id = 2;
        $thirdInitializedBaseSample = new BaseSample();
        $thirdInitializedBaseSample->id = 2;
        $firstBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $firstBaseResultSetMock->expects($this->exactly(2))
                ->method('current')
                ->willReturnOnConsecutiveCalls($firstInitializedBaseSample, $secondInitializedBaseSample);
        $firstBaseResultSetMock->expects($this->any())
                ->method('valid')
                ->willReturnOnConsecutiveCalls(true, true, false);
        $firstBaseResultSetMock->expects($this->any())
                ->method('key')
                ->willReturnOnConsecutiveCalls(0, 1);
        $firstBaseResultSetMock->expects($this->any())
                ->method('next')
                ->willReturnSelf();
        $firstBaseResultSetMock->expects($this->any())
                ->method('rewind')
                ->willReturnSelf();
        $secondBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $secondBaseResultSetMock->expects($this->exactly(1))
                ->method('current')
                ->willReturnOnConsecutiveCalls($thirdInitializedBaseSample);
        $secondBaseResultSetMock->expects($this->any())
                ->method('valid')
                ->willReturnOnConsecutiveCalls(true, false);
        $secondBaseResultSetMock->expects($this->any())
                ->method('key')
                ->willReturnOnConsecutiveCalls(0);
        $secondBaseResultSetMock->expects($this->any())
                ->method('next')
                ->willReturnSelf();
        $secondBaseResultSetMock->expects($this->any())
                ->method('rewind')
                ->willReturnSelf();
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(3))
                ->method('select')
                ->willReturnOnConsecutiveCalls(null, $firstBaseResultSetMock, $secondBaseResultSetMock);
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->exactly(3))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = new DataMapper($baseAdapterMock);
        $firstEntityCollection = $dataMapper->find(BaseSample::class, $queryMock);
        $this->assertInstanceOf(SismaCollection::class, $firstEntityCollection);
        $this->assertCount(0, $firstEntityCollection);
        $secondEntityCollection = $dataMapper->find(BaseSample::class, $queryMock);
        $this->assertInstanceOf(SismaCollection::class, $secondEntityCollection);
        $this->assertCount(2, $secondEntityCollection);
        $this->expectException(InvalidTypeException::class);
        $dataMapper->find(ReferencedSample::class, $queryMock);
    }

    public function testGetCount()
    {
        $standardEntity = new StandardEntity();
        $standardEntity->_numrows = 5;
        $baseResultSetMock = $this->createMock(BaseResultSet::class);
        $baseResultSetMock->expects($this->exactly(2))
                ->method('fetch')
                ->willReturnOnConsecutiveCalls(null, $standardEntity);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(3))
                ->method('select')
                ->willReturnOnConsecutiveCalls(null, $baseResultSetMock, $baseResultSetMock);
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->exactly(3))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = new DataMapper($baseAdapterMock);
        $this->assertEquals(0, $dataMapper->getCount($queryMock));
        $this->assertEquals(0, $dataMapper->getCount($queryMock));
        $this->assertEquals(5, $dataMapper->getCount($queryMock));
    }

    public function testFindFirst()
    {
        $initializedBaseSample = new BaseSample();
        $initializedBaseSample->id = 1;
        $firstBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $firstBaseResultSetMock->expects($this->any())
                ->method('numRows')
                ->willReturn(0);
        $secondBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $secondBaseResultSetMock->expects($this->exactly(1))
                ->method('fetch')
                ->willReturn($initializedBaseSample);
        $secondBaseResultSetMock->expects($this->any())
                ->method('numRows')
                ->willReturn(1);
        $thidBaseResultSetMock = $this->createMock(BaseResultSet::class);
        $thidBaseResultSetMock->expects($this->any())
                ->method('numRows')
                ->willReturn(2);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->exactly(4))
                ->method('select')
                ->willReturnOnConsecutiveCalls(null, $firstBaseResultSetMock, $secondBaseResultSetMock, $thidBaseResultSetMock);
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->exactly(4))
                ->method('getCommandToExecute')
                ->willReturn('');
        $dataMapper = new DataMapper($baseAdapterMock);
        $this->assertNull($dataMapper->findFirst(BaseSample::class, $queryMock));
        $this->assertNull($dataMapper->findFirst(BaseSample::class, $queryMock));
        $this->assertInstanceOf(BaseSample::class, $dataMapper->findFirst(BaseSample::class, $queryMock));
        $this->expectException(DataMapperException::class);
        $dataMapper->findFirst(BaseSample::class, $queryMock);
    }
}

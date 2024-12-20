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

namespace SismaFramework\Tests\Orm\CustomTypes;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Entities\EntityWithDateTimeProperty;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SismaDateTimeTest extends TestCase
{
    public function testSismaDateTime()
    {
        $originalDate = "2020-01-15 12:00:00";
        $sismaDate = new SismaDateTime(null, $originalDate);
        $this->assertInstanceOf(SismaDateTime::class, $sismaDate);
        $date = $sismaDate->format('Y-m-d H:i:s');
        $this->assertIsString($date);
        $this->assertEquals($originalDate, $date);
    }
    
    public function testEquals()
    {
        $sismaDateOne = new SismaDateTime(null, '2020-01-01 12:00:00');
        $sismaDateTwo = new SismaDateTime(null, '2020-01-01 12:00:00');
        $sismaDateThree = new SismaDateTime(null, '2020-01-02 12:00:00');
        $this->assertTrue($sismaDateOne->equals($sismaDateTwo));
        $this->assertfalse($sismaDateOne->equals($sismaDateThree));
    }
    
    public function testSetEntityModifiedInternal()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $dataMapperMock = $this->createMock(DataMapper::class);
        $entityWithDateTimePropertyOne = new EntityWithDateTimeProperty($dataMapperMock);
        $entityWithDateTimePropertyOne->initializedDatetime->add(new \DateInterval("P1D"));
        $this->assertTrue($entityWithDateTimePropertyOne->modified);
    }
    
    public function testSetEntityModifiedExternal()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $dataMapperMock = $this->createMock(DataMapper::class);
        $entityWithDateTimePropertyTwo = new EntityWithDateTimeProperty($dataMapperMock);
        $entityWithDateTimePropertyTwo->notInitializedDatetime = new SismaDateTime();
        $this->assertTrue($entityWithDateTimePropertyTwo->modified);
        $entityWithDateTimePropertyTwo->modified = false;
        $entityWithDateTimePropertyTwo->notInitializedDatetime->add(new \DateInterval("P1D"));
        $this->assertTrue($entityWithDateTimePropertyTwo->modified);
        $entityWithDateTimePropertyTwo->modified = false;
        $entityWithDateTimePropertyTwo->notInitializedDatetime->sub(new \DateInterval("P1D"));
        $this->assertTrue($entityWithDateTimePropertyTwo->modified);
    }
}

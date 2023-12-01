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

namespace SismaFramework\Tests\Security\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Security\Enumerations\AccessControlEntry;
use SismaFramework\Core\Exceptions\AccessDeniedException;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Entities\ReferencedSample;
use SismaFramework\Sample\Permissions\SamplePermission;

/**
 * @author Valentino de Lapa
 */
class BasePermissionTest extends TestCase
{

    /**
     * @runInSeparateProcess
     */
    public function testIstanceNotPermitted()
    {
        $this->expectException(AccessDeniedException::class);
        $dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->disableOriginalConstructor()
                ->onlyMethods([])
                ->getMock();
        SamplePermission::isAllowed(new ReferencedSample($dataMapperMock), AccessControlEntry::allow);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCheckPermissionFalse()
    {
        $this->expectException(AccessDeniedException::class);
        $dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->disableOriginalConstructor()
                ->onlyMethods([])
                ->getMock();
        SamplePermission::isAllowed(new BaseSample($dataMapperMock), AccessControlEntry::allow);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCheckPermissionTrue()
    {
        $this->expectNotToPerformAssertions();
        $dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->disableOriginalConstructor()
                ->onlyMethods([])
                ->getMock();
        SamplePermission::isAllowed(new BaseSample($dataMapperMock), AccessControlEntry::deny);
    }
}

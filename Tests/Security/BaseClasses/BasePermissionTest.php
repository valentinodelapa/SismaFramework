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

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SismaFramework\Security\Enumerations\AccessControlEntry;
use SismaFramework\Core\Exceptions\AccessDeniedException;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;
use SismaFramework\TestsApplication\Permissions\SamplePermission;

/**
 * @author Valentino de Lapa
 */
class BasePermissionTest extends TestCase
{

    private DataMapper $dataMapperMock;
    
    #[\Override]
    public function setUp(): void
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }

    #[RunInSeparateProcess]
    public function testIstanceNotPermitted()
    {
        $this->expectException(AccessDeniedException::class);
        SamplePermission::isAllowed(new ReferencedSample($this->dataMapperMock), AccessControlEntry::allow);
    }

    #[RunInSeparateProcess]
    public function testCheckPermissionFalse()
    {
        $this->expectException(AccessDeniedException::class);
        SamplePermission::isAllowed(new BaseSample($this->dataMapperMock), AccessControlEntry::allow);
    }

    #[RunInSeparateProcess]
    public function testCheckPermissionTrue()
    {
        $this->expectNotToPerformAssertions();
        SamplePermission::isAllowed(new BaseSample($this->dataMapperMock), AccessControlEntry::deny);
    }
}

<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\FixturesManager;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * Description of FixturesManagerTest
 *
 * Copyright 2022 Valentino de Lapa.
 */
class FixturesManagerTest extends TestCase
{
    
    public function testIsFixtures()
    {
        $dataMapperMock = $this->createMock(DataMapper::class);
        $fixtureManager = new FixturesManager($dataMapperMock);
        $this->assertTrue($fixtureManager->isFixtures(['fixtures']));
        $this->assertFalse($fixtureManager->isFixtures(['fixtures', 'fixtures']));
        $this->assertFalse($fixtureManager->isFixtures(['cms', 'fixtures']));
        $this->assertFalse($fixtureManager->isFixtures(['index']));
        $this->assertFalse($fixtureManager->isFixtures([]));
    }

    public function testFixtureManager()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $dataMapperMock = $this->createMock(DataMapper::class);
        $dataMapperMock->method('save')
                ->willReturn(true);
        $fixtureManager = new FixturesManager($dataMapperMock);
        $fixtureManager->run();
        $this->assertTrue($fixtureManager->extecuted());
    }

}

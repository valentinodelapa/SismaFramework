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
use SismaFramework\Core\BaseClasses\BaseConfig;
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

    private DataMapper $dataMapperMock;
    private FixtureManagerConfigTest $configTest;

    public function setUp(): void
    {
        $this->configTest = new FixtureManagerConfigTest();
        BaseConfig::setInstance($this->configTest);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->setConstructorArgs([$baseAdapterMock, $this->configTest])
                ->getMock();
    }

    public function testIsFixtures()
    {
        $fixtureManager = new FixturesManager($this->dataMapperMock, $this->configTest);
        $this->assertTrue($fixtureManager->isFixtures(['fixtures']));
        $this->assertFalse($fixtureManager->isFixtures(['fixtures', 'fixtures']));
        $this->assertFalse($fixtureManager->isFixtures(['cms', 'fixtures']));
        $this->assertFalse($fixtureManager->isFixtures(['index']));
        $this->assertFalse($fixtureManager->isFixtures([]));
    }

    public function testFixtureManager()
    {
        $this->dataMapperMock->method('save')
                ->willReturn(true);
        $fixtureManager = new FixturesManager($this->dataMapperMock, $this->configTest);
        $fixtureManager->run();
        $this->assertTrue($fixtureManager->extecuted());
    }
}

class FixtureManagerConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        switch ($name) {
            case 'rootPath':
                return true;
            default:
                return false;
        }
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->developmentEnvironment = true;
        $this->fixtures = 'Fixtures';
        $this->fixtureNamespace = 'TestsApplication\\' . $this->fixtures . '\\';
        $this->fixturePath = 'TestsApplication' . DIRECTORY_SEPARATOR . $this->fixtures . DIRECTORY_SEPARATOR;
        $this->moduleFolders = [
            'SismaFramework',
        ];
        $this->ormCache = true;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        $this->rootPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    }
}

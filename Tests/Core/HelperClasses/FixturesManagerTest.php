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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\FixturesManager;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\DataMapper\TransactionManager;
use SismaFramework\Orm\HelperClasses\DataMapper\EntityPersister;
use SismaFramework\Orm\HelperClasses\DataMapper\QueryExecutor;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;

/**
 * Description of FixturesManagerTest
 *
 * Copyright 2022 Valentino de Lapa.
 */
class FixturesManagerTest extends TestCase
{

    private DataMapper $dataMapperMock;
    private Config $configMock;

    public function setUp(): void
    {
        $fixtures = 'Fixtures';
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['developmentEnvironment', true],
                    ['fixtures', $fixtures],
                    ['fixtureNamespace', 'TestsApplication\\' . $fixtures . '\\'],
                    ['fixturePath', 'TestsApplication' . DIRECTORY_SEPARATOR . $fixtures],
                    ['moduleFolders', ['SismaFramework']],
                    ['ormCache', true],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
        ]);
        Config::setInstance($this->configMock);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $processedEntitesCollectionMock = $this->createMock(ProcessedEntitiesCollection::class);

        $transactionManager = new TransactionManager($baseAdapterMock, $processedEntitesCollectionMock);
        $entityPersister = new EntityPersister($baseAdapterMock, fn() => $this->configMock->ormCache);
        $queryExecutor = new QueryExecutor($baseAdapterMock, fn() => $this->configMock->ormCache);

        $this->dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->setConstructorArgs([
                    $processedEntitesCollectionMock,
                    $this->configMock,
                    $transactionManager,
                    $entityPersister,
                    $queryExecutor
                ])
                ->getMock();
    }

    public function testIsFixtures()
    {
        $fixtureManager = new FixturesManager($this->dataMapperMock, $this->configMock);
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
        $fixtureManager = new FixturesManager($this->dataMapperMock, $this->configMock);
        $fixtureManager->run();
        $this->assertTrue($fixtureManager->extecuted());
    }
}

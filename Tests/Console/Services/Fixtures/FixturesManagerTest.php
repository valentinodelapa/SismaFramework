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

namespace SismaFramework\Tests\Console\Services\Fixtures;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Console\Services\Fixtures\FixturesManager;
use SismaFramework\Odm\BaseClasses\BaseAdapter as OdmBaseAdapter;
use SismaFramework\Odm\HelperClasses\DocumentMapper;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @author Valentino de Lapa
 */
class FixturesManagerTest extends TestCase
{

    private DataMapper $dataMapperMock;
    private DocumentMapper $documentMapperMock;
    private Config $configStub;

    public function setUp(): void
    {
        $fixtures = 'Fixtures';
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['developmentEnvironment', true],
                    ['fixtureNamespace', 'TestsApplication\\' . $fixtures . '\\'],
                    ['fixturePath', 'TestsApplication' . DIRECTORY_SEPARATOR . $fixtures],
                    ['moduleFolders', ['SismaFramework']],
                    ['ormCache', true],
                    ['odmCache', true],
                    ['rootPath', dirname(__DIR__, 5) . DIRECTORY_SEPARATOR],
        ]);
        Config::setInstance($this->configStub);
        $baseAdapterMock = $this->createStub(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $odmBaseAdapterMock = $this->createStub(OdmBaseAdapter::class);
        OdmBaseAdapter::setDefault($odmBaseAdapterMock);
        $this->dataMapperMock = $this->createStub(DataMapper::class);
        $this->documentMapperMock = $this->createStub(DocumentMapper::class);
    }

    public function testFixtureManager()
    {
        $this->dataMapperMock->method('save')
                ->willReturn(true);
        $fixtureManager = new FixturesManager($this->dataMapperMock, $this->configStub, $this->documentMapperMock);
        $fixtureManager->run();
        $this->assertTrue($fixtureManager->extecuted());
    }

    public function testFixtureManagerExecutesDocumentFixtures()
    {
        $this->dataMapperMock->method('save')
                ->willReturn(true);
        $this->documentMapperMock = $this->createMock(DocumentMapper::class);
        $this->documentMapperMock->expects($this->atLeastOnce())->method('save');
        $fixtureManager = new FixturesManager($this->dataMapperMock, $this->configStub, $this->documentMapperMock);
        $fixtureManager->run();
        $this->assertTrue($fixtureManager->extecuted());
    }
}

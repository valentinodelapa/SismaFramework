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
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\Exceptions\BadRequestException;
use SismaFramework\Core\Exceptions\PageNotFoundException;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Dispatcher;
use SismaFramework\Core\HelperClasses\FixturesManager;
use SismaFramework\Core\HelperClasses\ResourceMaker;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\Interfaces\Services\CrawlComponentMakerInterface;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * Description of DispatcherTest
 *
 * @author Valentino de Lapa
 */
class DispatcherTest extends TestCase
{

    private DispatcherConfigTest $configTest;
    private Request $requestMock;
    private FixturesManager $fixturesManagerMock;
    private DataMapper $dataMapperMock;

    #[\Override]
    public function setUp(): void
    {
        $this->configTest = new DispatcherConfigTest();
        BaseConfig::setInstance($this->configTest);
        $this->requestMock = $this->createMock(Request::class);
        $this->requestMock->server['REQUEST_URI'] = '/';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->fixturesManagerMock = $this->createMock(FixturesManager::class);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
        Debugger::startExecutionTimeCalculation();
    }

    public function testRunWithReloadQueryString()
    {
        $this->requestMock->server['REQUEST_URI'] = 'sample/error?message=error';
        $this->requestMock->server['QUERY_STRING'] = 'message=error';
        $resourceMakerMock = $this->createMock(ResourceMaker::class);
        $resourceMakerMock->expects($this->once())
                ->method('isAcceptedResourceFile')
                ->willReturn(false);
        $routerMock = $this->createMock(Router::class);
        $routerMock->expects($this->once())
                ->method('reloadWithParsedQueryString');
        $dispatcher = new Dispatcher($this->requestMock, $resourceMakerMock, $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run($routerMock);
    }

    public function testStructuralFileFopen()
    {
        $this->requestMock->server['REQUEST_URI'] = '/css/debugBar.css';
        $resourceMakerMock = $this->createMock(ResourceMaker::class);
        $resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configTest->structuralAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'debugBar.css');
        $dispatcher = new Dispatcher($this->requestMock, $resourceMakerMock, $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testModuleFile()
    {
        $this->requestMock->server['REQUEST_URI'] = '/css/sample.css';
        $resourceMakerMock = $this->createMock(ResourceMaker::class);
        $resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configTest->systemPath . $this->configTest->applicationAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'sample.css');
        $dispatcher = new Dispatcher($this->requestMock, $resourceMakerMock, $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testModuleFileInSubfolder()
    {
        $this->requestMock->server['REQUEST_URI'] = '/vendor/sample-vendor/sample-vendor.css';
        $resourceMakerMock = $this->createMock(ResourceMaker::class);
        $resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configTest->systemPath . $this->configTest->applicationAssetsPath . 'vendor' . DIRECTORY_SEPARATOR . 'sample-vendor' . DIRECTORY_SEPARATOR . 'sample-vendor.css');
        $dispatcher = new Dispatcher($this->requestMock, $resourceMakerMock, $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testDirectAccessToFile()
    {
        $this->requestMock->server['REQUEST_URI'] = 'SismaFramework/TestsApplication/Assets/css/sample.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $resourceMakerMock = $this->createMock(ResourceMaker::class);
        $resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configTest->systemPath . $this->configTest->applicationAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'sample.css');
        $dispatcher = new Dispatcher($this->requestMock, $resourceMakerMock, $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testDirectAccessToFileInRoot()
    {
        $this->requestMock->server['REQUEST_URI'] = 'composer.json';
        $this->requestMock->server['QUERY_STRING'] = '';
        $resourceMakerMock = $this->createMock(ResourceMaker::class);
        $resourceMakerMock->expects($this->exactly(3))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configTest->rootPath . 'composer.json');
        $dispatcher = new Dispatcher($this->requestMock, $resourceMakerMock, $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testFileWithStreamContent()
    {
        $this->requestMock->server['REQUEST_URI'] = '/javascript/sample.js?resource=resource';
        $this->requestMock->server['QUERY_STRING'] = 'resource=resource';
        $resourceMakerMock = $this->createMock(ResourceMaker::class);
        $resourceMakerMock->expects($this->once())
                ->method('setStreamContex');
        $resourceMakerMock->expects($this->exactly(3))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configTest->systemPath . $this->configTest->applicationAssetsPath . 'javascript' . DIRECTORY_SEPARATOR . 'sample.js');
        $dispatcher = new Dispatcher($this->requestMock, $resourceMakerMock, $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testNotExistentFileFile()
    {
        $this->requestMock->server['REQUEST_URI'] = 'fake/fake/fake/fake/fake.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->expectException(PageNotFoundException::class);
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testNotExistentFileFileInRoot()
    {
        $this->requestMock->server['REQUEST_URI'] = 'fake.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->expectException(PageNotFoundException::class);
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testRunFixture()
    {
        $this->fixturesManagerMock->expects($this->exactly(2))
                ->method('isFixtures')
                ->willReturn(true);
        $this->fixturesManagerMock->expects($this->once())
                ->method('run');
        $this->requestMock->server['REQUEST_URI'] = '/fixtures/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testCrawlComponentMaker()
    {
        $sitemapBuilderMock = $this->createMock(CrawlComponentMakerInterface::class);
        $sitemapBuilderMock->expects($this->once())
                ->method('isCrawlComponent')
                ->willReturn(true);
        $sitemapBuilderMock->expects($this->once())
                ->method('generate');
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->addCrawlComponentMaker($sitemapBuilderMock);
        $dispatcher->run();
    }

    public function testRootPath()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->requestMock->server['REQUEST_URI'] = '/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testIndexPath()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->requestMock->server['REQUEST_URI'] = '/sample/index/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testIndexPathTwo()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->requestMock->server['REQUEST_URI'] = '/index/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testIndexPathThree()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->requestMock->server['REQUEST_URI'] = '/sample/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testNotifyPath()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/test message/');
        $this->requestMock->server['REQUEST_URI'] = '/notify/message/test+message/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testOtherIndexPath()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - index/');
        $this->expectOutputRegex('/test message/');
        $this->requestMock->server['REQUEST_URI'] = '/other/parameter/test+message/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testPathWithReload()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - index/');
        $this->expectOutputRegex('/other test message/');
        $this->requestMock->server['REQUEST_URI'] = '/fake/other/index/parameter/other+test+message/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testPathWithRequestParameter()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - action-with-request/');
        $this->expectOutputRegex('/test parameter/');
        $this->requestMock->request['parameter'] = 'test parameter';
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-request/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testPathWithAuthenticationParameter()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - action-with-authentication/');
        $this->expectOutputRegex('/is not submitted/');
        $_POST['username'] = 'username';
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-authentication/';
        $this->requestMock->server['REQUEST_METHOD'] = 'GET';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testPathWithDefaultValueParameter()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - action-with-default-value/');
        $this->expectOutputRegex('/is default/');
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-default-value/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testPathWithArrayParameter()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - action-with-array/');
        $this->expectOutputRegex('/0: first/');
        $this->expectOutputRegex('/1: second/');
        $this->expectOutputRegex('/2: third/');
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-array/array/first/array/second/array/third/';
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testPathWithInvalidParameter()
    {
        $this->requestMock->server['REQUEST_URI'] = '/other/fake/test/';
        $this->expectException(BadRequestException::class);
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function testFakePath()
    {
        $this->requestMock->server['REQUEST_URI'] = '/fake/fake/fake/fake/fake/fake/';
        $this->expectException(PageNotFoundException::class);
        $dispatcher = new Dispatcher($this->requestMock, new ResourceMaker(), $this->fixturesManagerMock, $this->dataMapperMock, $this->configTest);
        $dispatcher->run();
    }

    public function tearDown(): void
    {
        Router::resetMetaUrl();
    }
}

class DispatcherConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        switch ($name) {
            case 'rootPath':
            case 'autoloadNamespaceMapper':
            case 'autoloadClassMapper':
                return true;
            default:
                return false;
        }
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        $this->rootPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->applicationAssetsPath = 'TestsApplication' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR;
        $this->controllerNamespace = 'TestsApplication\\Controllers\\';
        $this->defaultAction = 'index';
        $this->defaultPath = 'sample';
        $this->developmentEnvironment = false;
        $this->httpsIsForced = false;
        $this->language = Language::italian;
        $this->localesPath = 'TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR;
        $this->maxReloadAttempts = 3;
        $this->moduleFolders = [
            'SismaFramework',
        ];
        $this->systemPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
        $this->structuralAssetsPath = $this->systemPath . 'Structural' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR;
        $this->viewsPath = 'TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR;
    }
}

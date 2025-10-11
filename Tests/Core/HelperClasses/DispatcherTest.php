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
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\Exceptions\BadRequestException;
use SismaFramework\Core\Exceptions\PageNotFoundException;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Dispatcher;
use SismaFramework\Core\HelperClasses\Dispatcher\FixturesManager;
use SismaFramework\Core\HelperClasses\Dispatcher\ResourceHandler;
use SismaFramework\Core\HelperClasses\Dispatcher\ResourceMaker;
use SismaFramework\Core\HelperClasses\Dispatcher\RouteResolver;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
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

    private Config $configMock;
    private Request $requestMock;
    private ResourceMaker $resourceMakerMock;
    private FixturesManager $fixturesManagerMock;
    private DataMapper $dataMapperMock;
    private RouteResolver $routeResolverMock;
    private ResourceHandler $resourceHandlerMock;

    #[\Override]
    public function setUp(): void
    {
        $systemPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['applicationAssetsPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR],
                    ['controllerNamespace', 'TestsApplication\\Controllers\\'],
                    ['defaultAction', 'index'],
                    ['defaultPath', 'sample'],
                    ['developmentEnvironment', false],
                    ['httpsIsForced', false],
                    ['language', Language::italian],
                    ['localesPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR],
                    ['maxReloadAttempts', 3],
                    ['moduleFolders', ['SismaFramework']],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
                    ['systemPath', $systemPath],
                    ['structuralAssetsPath', $systemPath . 'Structural' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR],
                    ['viewsPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
        ]);
        Config::setInstance($this->configMock);
        $this->requestMock = $this->getMockBuilder(Request::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->requestMock->server['REQUEST_URI'] = '/';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->requestMock->request = [];
        $this->resourceMakerMock = $this->createMock(ResourceMaker::class);
        $this->fixturesManagerMock = $this->createMock(FixturesManager::class);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);

        $this->routeResolverMock = new RouteResolver($this->resourceMakerMock, $this->fixturesManagerMock, $this->configMock);
        $this->resourceHandlerMock = new ResourceHandler($this->resourceMakerMock, $this->configMock);

        Debugger::startExecutionTimeCalculation();
    }

    private function createDispatcher(): Dispatcher
    {
        return new Dispatcher($this->requestMock, $this->dataMapperMock, $this->routeResolverMock, null, null, $this->resourceHandlerMock);
    }

    public function testRunWithReloadQueryString()
    {
        $this->requestMock->server['REQUEST_URI'] = 'sample/error?message=error';
        $this->requestMock->server['QUERY_STRING'] = 'message=error';
        $this->resourceMakerMock->expects($this->once())
                ->method('isAcceptedResourceFile')
                ->willReturn(false);
        $routerMock = $this->createMock(Router::class);
        $routerMock->expects($this->once())
                ->method('reloadWithParsedQueryString');
        $dispatcher = $this->createDispatcher();
        $dispatcher->run($routerMock);
    }

    public function testStructuralFileFopen()
    {
        $this->requestMock->server['REQUEST_URI'] = '/css/debugBar.css';
        $this->resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configMock->structuralAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'debugBar.css');
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testModuleFile()
    {
        $this->requestMock->server['REQUEST_URI'] = '/css/sample.css';
        $this->resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configMock->systemPath . $this->configMock->applicationAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'sample.css');
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testModuleFileInSubfolder()
    {
        $this->requestMock->server['REQUEST_URI'] = '/vendor/sample-vendor/sample-vendor.css';
        $this->resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configMock->systemPath . $this->configMock->applicationAssetsPath . 'vendor' . DIRECTORY_SEPARATOR . 'sample-vendor' . DIRECTORY_SEPARATOR . 'sample-vendor.css');
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testDirectAccessToFile()
    {
        $this->requestMock->server['REQUEST_URI'] = 'SismaFramework/TestsApplication/Assets/css/sample.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configMock->systemPath . $this->configMock->applicationAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'sample.css');
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testDirectAccessToFileInRoot()
    {
        $this->requestMock->server['REQUEST_URI'] = 'composer.json';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->resourceMakerMock->expects($this->exactly(3))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configMock->rootPath . 'composer.json');
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testNotExistentFileFile()
    {
        $this->requestMock->server['REQUEST_URI'] = 'fake/fake/fake/fake/fake.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->resourceMakerMock->expects($this->exactly(6))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->expectException(PageNotFoundException::class);
        $dispatcher = $this->createDispatcher();
        $dispatcher->run();
    }

    public function testNotExistentFileFileInRoot()
    {
        $this->requestMock->server['REQUEST_URI'] = 'fake.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->resourceMakerMock->expects($this->exactly(7))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->expectException(PageNotFoundException::class);
        $dispatcher = $this->createDispatcher();
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
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testCrawlComponentMaker()
    {
        $crawlComponentMakerMock = $this->createMock(CrawlComponentMakerInterface::class);
        $crawlComponentMakerMock->expects($this->once())
                ->method('isCrawlComponent')
                ->willReturn(true);
        $crawlComponentMakerMock->expects($this->once())
                ->method('generate');
        $dispatcher = $this->createDispatcher();
        $dispatcher->addCrawlComponentMaker($crawlComponentMakerMock);
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testRootPath()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->requestMock->server['REQUEST_URI'] = '/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testIndexPath()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->requestMock->server['REQUEST_URI'] = '/sample/index/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testIndexPathTwo()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->requestMock->server['REQUEST_URI'] = '/index/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testIndexPathThree()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->requestMock->server['REQUEST_URI'] = '/sample/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testNotifyPath()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/test message/');
        $this->requestMock->server['REQUEST_URI'] = '/notify/message/test+message/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testOtherIndexPath()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - index/');
        $this->expectOutputRegex('/test message/');
        $this->requestMock->server['REQUEST_URI'] = '/other/parameter/test+message/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithReload()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - index/');
        $this->expectOutputRegex('/other test message/');
        $this->requestMock->server['REQUEST_URI'] = '/fake/other/index/parameter/other+test+message/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithRequestParameter()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - action-with-request/');
        $this->expectOutputRegex('/test parameter/');
        $this->requestMock->request['parameter'] = 'test parameter';
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-request/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithAuthenticationParameter()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - action-with-authentication/');
        $this->expectOutputRegex('/is not submitted/');
        $_POST['username'] = 'username';
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-authentication/';
        $this->requestMock->server['REQUEST_METHOD'] = 'GET';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithDefaultValueParameter()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - action-with-default-value/');
        $this->expectOutputRegex('/is default/');
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-default-value/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithArrayParameter()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/other - action-with-array/');
        $this->expectOutputRegex('/0: first/');
        $this->expectOutputRegex('/1: second/');
        $this->expectOutputRegex('/2: third/');
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-array/array/first/array/second/array/third/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithInvalidParameter()
    {
        $this->requestMock->server['REQUEST_URI'] = '/other/fake/test/';
        $this->expectException(BadRequestException::class);
        $dispatcher = $this->createDispatcher();
        $dispatcher->run();
    }

    public function testFakePath()
    {
        $this->requestMock->server['REQUEST_URI'] = '/fake/fake/fake/fake/fake/fake/';
        $this->expectException(PageNotFoundException::class);
        $dispatcher = $this->createDispatcher();
        $dispatcher->run();
    }

    public function testSimpleSlug()
    {
        \ob_end_clean();
        $this->expectOutputString('slug/singlePageSlug/');
        $this->requestMock->server['REQUEST_URI'] = '/slug/single-page-slug/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testGerarchicSlug()
    {
        \ob_end_clean();
        $this->expectOutputString('slug/categorySlug/child-page-slug');
        $this->requestMock->server['REQUEST_URI'] = '/slug/category-slug/child-page-slug/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testMultipleGerarchicSlug()
    {
        \ob_end_clean();
        $this->expectOutputString('slug/categorySlug/subcategory-slug/child-page-slug');
        $this->requestMock->server['REQUEST_URI'] = '/slug/category-slug/subcategory-slug/child-page-slug/';
        $dispatcher = $this->createDispatcher();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function tearDown(): void
    {
        Router::resetMetaUrl();
    }
}

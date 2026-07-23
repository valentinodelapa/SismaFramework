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

    private Config $configStub;
    private Request $requestMock;
    private ResourceMaker $resourceMakerMock;
    private DataMapper $dataMapperMock;
    private RouteResolver $routeResolverMock;
    private ResourceHandler $resourceHandlerMock;

    #[\Override]
    public function setUp(): void
    {
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')
                ->willReturnMap($this->buildConfigMap(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR));
        Config::setInstance($this->configStub);
        $this->requestMock = $this->createStub(Request::class);
        $this->requestMock->server['REQUEST_URI'] = '/';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->requestMock->request = [];
        $baseAdapterMock = $this->createStub(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createStub(DataMapper::class);
    }

    private function buildConfigMap(string $rootPath): array
    {
        $systemPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
        return [
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
            ['rootPath', $rootPath],
            ['systemPath', $systemPath],
            ['structuralAssetsPath', $systemPath . 'Structural' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR],
            ['viewsPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
        ];
    }

    private function createDispatcherWithResourceMakerStub(): Dispatcher
    {
        $resourceMakerStub = $this->createStub(ResourceMaker::class);
        $this->routeResolverMock = new RouteResolver($resourceMakerStub, $this->configStub);
        $this->resourceHandlerMock = new ResourceHandler($resourceMakerStub, $this->configStub);
        return new Dispatcher($this->requestMock, $this->dataMapperMock, $this->routeResolverMock, $this->resourceHandlerMock, null, null);
    }

    private function createDispatcherWithResourceMakerMock(?Config $configStub = null):Dispatcher
    {
        $configStub ??= $this->configStub;
        $this->resourceMakerMock = $this->createMock(ResourceMaker::class);
        $this->routeResolverMock = new RouteResolver($this->resourceMakerMock, $configStub);
        $this->resourceHandlerMock = new ResourceHandler($this->resourceMakerMock, $configStub);
        return new Dispatcher($this->requestMock, $this->dataMapperMock, $this->routeResolverMock, $this->resourceHandlerMock, null, null);
    }

    public function testRunWithReloadQueryString()
    {
        $dispatcher = $this->createDispatcherWithResourceMakerMock();
        $this->requestMock->server['REQUEST_URI'] = 'sample/error?message=error';
        $this->requestMock->server['QUERY_STRING'] = 'message=error';
        $this->resourceMakerMock->expects($this->once())
                ->method('isAcceptedResourceFile')
                ->willReturn(false);
        $routerServiceMock = $this->createMock(\SismaFramework\Core\Services\RouterService::class);
        $routerServiceMock->expects($this->once())
                ->method('reloadWithParsedQueryString');
        \SismaFramework\Core\Services\RouterService::setInstance($routerServiceMock);
        $router = new Router();
        $dispatcher->run($router);
        \SismaFramework\Core\Services\RouterService::resetInstance();
    }

    public function testStructuralFileFopen()
    {
        $dispatcher = $this->createDispatcherWithResourceMakerMock();
        $this->requestMock->server['REQUEST_URI'] = '/css/debugBar.css';
        $this->resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configStub->structuralAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'debugBar.css');
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testModuleFile()
    {
        $dispatcher = $this->createDispatcherWithResourceMakerMock();
        $this->requestMock->server['REQUEST_URI'] = '/css/sample.css';
        $this->resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configStub->systemPath . $this->configStub->applicationAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'sample.css');
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testModuleFileInSubfolder()
    {
        $dispatcher = $this->createDispatcherWithResourceMakerMock();
        $this->requestMock->server['REQUEST_URI'] = '/vendor/sample-vendor/sample-vendor.css';
        $this->resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configStub->systemPath . $this->configStub->applicationAssetsPath . 'vendor' . DIRECTORY_SEPARATOR . 'sample-vendor' . DIRECTORY_SEPARATOR . 'sample-vendor.css');
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testDirectAccessToFile()
    {
        $dispatcher = $this->createDispatcherWithResourceMakerMock();
        $this->requestMock->server['REQUEST_URI'] = 'SismaFramework/TestsApplication/Assets/css/sample.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->resourceMakerMock->expects($this->exactly(2))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($this->configStub->systemPath . $this->configStub->applicationAssetsPath . 'css' . DIRECTORY_SEPARATOR . 'sample.css');
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testDirectAccessToFileInRoot()
    {
        $rootPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap($this->buildConfigMap($rootPath));
        $dispatcher = $this->createDispatcherWithResourceMakerMock($configStub);
        $this->requestMock->server['REQUEST_URI'] = 'composer.json';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->resourceMakerMock->expects($this->exactly(3))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->resourceMakerMock->expects($this->once())
                ->method('makeResource')
                ->with($rootPath . 'composer.json');
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testNotExistentFileFile()
    {
        $dispatcher = $this->createDispatcherWithResourceMakerMock();
        $this->requestMock->server['REQUEST_URI'] = 'fake/fake/fake/fake/fake.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->resourceMakerMock->expects($this->exactly(6))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->expectException(PageNotFoundException::class);
        $dispatcher->run();
    }

    public function testNotExistentFileFileInRoot()
    {
        $dispatcher = $this->createDispatcherWithResourceMakerMock();
        $this->requestMock->server['REQUEST_URI'] = 'fake.css';
        $this->requestMock->server['QUERY_STRING'] = '';
        $this->resourceMakerMock->expects($this->exactly(7))
                ->method('isAcceptedResourceFile')
                ->willReturn(true);
        $this->expectException(PageNotFoundException::class);
        $dispatcher->run();
    }

    public function testCrawlComponentMaker()
    {
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $crawlComponentMakerMock = $this->createMock(CrawlComponentMakerInterface::class);
        $crawlComponentMakerMock->expects($this->once())
                ->method('isCrawlComponent')
                ->willReturn(true);
        $crawlComponentMakerMock->expects($this->once())
                ->method('generate');
        $dispatcher->addCrawlComponentMaker($crawlComponentMakerMock);
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testRootPath()
    {
        $this->expectOutputRegex('/(?=.*sample - index)(?=.*Hello World)/s');
        $this->requestMock->server['REQUEST_URI'] = '/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testIndexPath()
    {
        $this->expectOutputRegex('/(?=.*sample - index)(?=.*Hello World)/s');
        $this->requestMock->server['REQUEST_URI'] = '/sample/index/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testIndexPathTwo()
    {
        $this->expectOutputRegex('/(?=.*sample - index)(?=.*Hello World)/s');
        $this->requestMock->server['REQUEST_URI'] = '/index/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testIndexPathThree()
    {
        $this->expectOutputRegex('/(?=.*sample - index)(?=.*Hello World)/s');
        $this->requestMock->server['REQUEST_URI'] = '/sample/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testNotifyPath()
    {
        $this->expectOutputRegex('/(?=.*sample - notify)(?=.*test message)/s');
        $this->requestMock->server['REQUEST_URI'] = '/notify/message/test+message/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testOtherIndexPath()
    {
        $this->expectOutputRegex('/(?=.*other - index)(?=.*test message)/s');
        $this->requestMock->server['REQUEST_URI'] = '/other/parameter/test+message/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithReload()
    {
        $this->expectOutputRegex('/(?=.*other - index)(?=.*other test message)/s');
        $this->requestMock->server['REQUEST_URI'] = '/fake/other/index/parameter/other+test+message/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithRequestParameter()
    {
        $this->expectOutputRegex('/(?=.*other - action-with-request)(?=.*test parameter)/s');
        $this->requestMock->request['parameter'] = 'test parameter';
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-request/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithAuthenticationParameter()
    {
        $this->expectOutputRegex('/(?=.*other - action-with-authentication)(?=.*is not submitted)/s');
        $_POST['username'] = 'username';
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-authentication/';
        $this->requestMock->server['REQUEST_METHOD'] = 'GET';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithDefaultValueParameter()
    {
        $this->expectOutputRegex('/(?=.*other - action-with-default-value)(?=.*is default)/s');
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-default-value/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithArrayParameter()
    {
        $this->expectOutputRegex('/(?=.*other - action-with-array)(?=.*0: first)(?=.*1: second)(?=.*2: third)/s');
        $this->requestMock->server['REQUEST_URI'] = '/other/action-with-array/array/first/array/second/array/third/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testPathWithInvalidParameter()
    {
        $this->requestMock->server['REQUEST_URI'] = '/other/fake/test/';
        $this->expectException(BadRequestException::class);
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $dispatcher->run();
    }

    public function testFakePath()
    {
        $this->requestMock->server['REQUEST_URI'] = '/fake/fake/fake/fake/fake/fake/';
        $this->expectException(PageNotFoundException::class);
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $dispatcher->run();
    }

    public function testSimpleSlug()
    {
        $this->expectOutputString('slug/single-page-slug/');
        $this->requestMock->server['REQUEST_URI'] = '/slug/single-page-slug/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testGerarchicSlug()
    {
        $this->expectOutputString('slug/category-slug/child-page-slug');
        $this->requestMock->server['REQUEST_URI'] = '/slug/category-slug/child-page-slug/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testMultipleGerarchicSlug()
    {
        $this->expectOutputString('slug/category-slug/subcategory-slug/child-page-slug');
        $this->requestMock->server['REQUEST_URI'] = '/slug/category-slug/subcategory-slug/child-page-slug/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testSlugWhithNumericPart()
    {
        $this->expectOutputString('slug/slug-with-2000-01-01-day/');
        $this->requestMock->server['REQUEST_URI'] = '/slug/slug-with-2000-01-01-day/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function testRealMethodWithNumericPart()
    {
        $this->expectOutputString('/slug/real-action-with-1-number-in-name/');
        $this->requestMock->server['REQUEST_URI'] = '/slug/real-action-with-1-number-in-name/';
        $dispatcher = $this->createDispatcherWithResourceMakerStub();
        $this->assertInstanceOf(Response::class, $dispatcher->run());
    }

    public function tearDown(): void
    {
        Router::resetMetaUrl();
    }
}

<?php

/*
 * The MIT License
 *
 * Copyright (c) 2023-present Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Services\RouterService;
use SismaFramework\Core\Services\RenderService;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;

class TestController extends BaseController
{
}

/**
 * @author Valentino de Lapa
 */
class BaseControllerTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        RouterService::resetInstance();
        RenderService::resetInstance();
        
        $configStub = $this->createStub(\SismaFramework\Core\HelperClasses\Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['ormCache', false],
                    ['developmentEnvironment', false],
        ]);
        \SismaFramework\Core\HelperClasses\Config::setInstance($configStub);
        
        $baseAdapterStub = $this->createStub(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterStub);
    }

    #[\Override]
    protected function tearDown(): void
    {
        RouterService::resetInstance();
        RenderService::resetInstance();
    }

    public function testControllerInjectsRouterServiceInstance(): void
    {
        $controller = new TestController();
        
        $reflection = new \ReflectionClass($controller);
        $routerProperty = $reflection->getProperty('router');
        $routerProperty->setAccessible(true);
        
        $this->assertInstanceOf(RouterService::class, $routerProperty->getValue($controller));
    }

    public function testControllerInjectsRenderServiceInstance(): void
    {
        $controller = new TestController();
        
        $reflection = new \ReflectionClass($controller);
        $renderProperty = $reflection->getProperty('render');
        $renderProperty->setAccessible(true);
        
        $this->assertInstanceOf(RenderService::class, $renderProperty->getValue($controller));
    }

    public function testControllerUsesRouterServiceForVarsInitialization(): void
    {
        $routerMock = $this->createMock(RouterService::class);
        $routerMock->expects($this->once())
                ->method('getControllerUrl')
                ->willReturn('test-controller');
        $routerMock->expects($this->once())
                ->method('getActionUrl')
                ->willReturn('test-action');
        $routerMock->expects($this->once())
                ->method('getMetaUrl')
                ->willReturn('/meta');
        $routerMock->expects($this->once())
                ->method('getActualCleanUrl')
                ->willReturn('/meta/test-controller/test-action/');
        $routerMock->expects($this->once())
                ->method('getRootUrl')
                ->willReturn('http://localhost/meta');
        
        RouterService::setInstance($routerMock);
        
        $controller = new TestController();
        
        $reflection = new \ReflectionClass($controller);
        $varsProperty = $reflection->getProperty('vars');
        $varsProperty->setAccessible(true);
        $vars = $varsProperty->getValue($controller);
        
        $this->assertEquals('test-controller', $vars['controllerUrl']);
        $this->assertEquals('test-action', $vars['actionUrl']);
        $this->assertEquals('/meta', $vars['metaUrl']);
        $this->assertEquals('/meta/test-controller/test-action/', $vars['actualCleanUrl']);
        $this->assertEquals('http://localhost/meta', $vars['rootUrl']);
    }

    public function testControllerServiceInstancesCanBeMockedForTesting(): void
    {
        $mockRouter = $this->createMock(RouterService::class);
        $mockRender = $this->createMock(RenderService::class);
        
        RouterService::setInstance($mockRouter);
        RenderService::setInstance($mockRender);
        
        $controller = new TestController();
        
        $reflection = new \ReflectionClass($controller);
        
        $routerProperty = $reflection->getProperty('router');
        $routerProperty->setAccessible(true);
        
        $renderProperty = $reflection->getProperty('render');
        $renderProperty->setAccessible(true);
        
        $this->assertSame($mockRouter, $routerProperty->getValue($controller));
        $this->assertSame($mockRender, $renderProperty->getValue($controller));
    }

    public function testControllerInjectsDataMapperAndDebugger(): void
    {
        $controller = new TestController();
        
        $reflection = new \ReflectionClass($controller);
        
        $dataMapperProperty = $reflection->getProperty('dataMapper');
        $dataMapperProperty->setAccessible(true);
        
        $debuggerProperty = $reflection->getProperty('debugger');
        $debuggerProperty->setAccessible(true);
        
        $this->assertInstanceOf(DataMapper::class, $dataMapperProperty->getValue($controller));
        $this->assertInstanceOf(Debugger::class, $debuggerProperty->getValue($controller));
    }

    public function testControllerAcceptsCustomDataMapperAndDebugger(): void
    {
        $customDataMapper = $this->createStub(DataMapper::class);
        $customDebugger = $this->createStub(Debugger::class);
        
        $controller = new TestController($customDataMapper, $customDebugger);
        
        $reflection = new \ReflectionClass($controller);
        
        $dataMapperProperty = $reflection->getProperty('dataMapper');
        $dataMapperProperty->setAccessible(true);
        
        $debuggerProperty = $reflection->getProperty('debugger');
        $debuggerProperty->setAccessible(true);
        
        $this->assertSame($customDataMapper, $dataMapperProperty->getValue($controller));
        $this->assertSame($customDebugger, $debuggerProperty->getValue($controller));
    }
}

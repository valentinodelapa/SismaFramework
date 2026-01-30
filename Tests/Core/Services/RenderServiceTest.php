<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\Services;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Services\RenderService;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Localizator;
use SismaFramework\Core\HelperClasses\ModuleManager;

/**
 * @author Valentino de Lapa
 */
class RenderServiceTest extends TestCase
{
    private Debugger $debuggerMock;
    private Localizator $localizatorMock;
    private Config $configStubDevelop;
    private Config $configStubProduction;

    protected function setUp(): void
    {
        RenderService::resetInstance();
        
        $this->configStubDevelop = $this->createStub(Config::class);
        $this->configStubDevelop->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', true],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
                    ['structuralViewsPath', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'Structural' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
                    ['viewsPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
        ]);
        
        $this->configStubProduction = $this->createStub(Config::class);
        $this->configStubProduction->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
                    ['structuralViewsPath', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'Structural' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
                    ['viewsPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
        ]);
        
        Config::setInstance($this->configStubDevelop);
        ModuleManager::setApplicationModule('SismaFramework');
    }

    protected function tearDown(): void
    {
        RenderService::resetInstance();
    }

    private function initializeMocks(): void
    {
        $this->localizatorMock = $this->createMock(Localizator::class);
        $this->debuggerMock = $this->createMock(Debugger::class);
    }

    private function initializeStubs(): void
    {
        $this->localizatorMock = $this->createStub(Localizator::class);
        $this->debuggerMock = $this->createStub(Debugger::class);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = RenderService::getInstance();
        $instance2 = RenderService::getInstance();
        
        $this->assertInstanceOf(RenderService::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function testSetInstanceAllowsInjectingCustomInstance(): void
    {
        $stubRender = $this->createStub(RenderService::class);
        RenderService::setInstance($stubRender);
        
        $this->assertSame($stubRender, RenderService::getInstance());
    }

    public function testResetInstanceCreatesNewInstance(): void
    {
        $instance1 = RenderService::getInstance();
        RenderService::resetInstance();
        $instance2 = RenderService::getInstance();
        
        $this->assertNotSame($instance1, $instance2);
    }

    public function testSetStructural(): void
    {
        $service = RenderService::getInstance();
        
        $this->assertFalse($service->isStructural());
        
        $service->setStructural(true);
        $this->assertTrue($service->isStructural());
        
        $service->setStructural(false);
        $this->assertFalse($service->isStructural());
    }

    public function testGenerateViewInDevelopmentEnvironment(): void
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        
        $vars = [
            'metaUrl' => '',
            'controllerUrl' => 'sample',
            'actionUrl' => 'index',
        ];
        
        $this->initializeMocks();
        $this->localizatorMock->expects($this->once())
                ->method('getPageLocaleArray')
                ->willReturn([]);
        $this->debuggerMock->expects($this->once())
                ->method('generateDebugBar')
                ->willReturn('');
        
        $service = RenderService::getInstance();
        $service->generateView('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->debuggerMock, $this->configStubDevelop);
    }

    public function testGenerateViewNotInDevelopmentEnvironment(): void
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->expectOutputRegex('/^(?!.*(?:Database|Log|Form|Variables)).*$/s');
        
        $vars = [
            'metaUrl' => '',
            'controllerUrl' => 'sample',
            'actionUrl' => 'index',
        ];
        
        $this->initializeMocks();
        $this->localizatorMock->expects($this->once())
                ->method('getPageLocaleArray')
                ->willReturn([]);
        $this->debuggerMock->expects($this->never())
                ->method('generateDebugBar');
        
        $service = RenderService::getInstance();
        $service->generateView('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->debuggerMock, $this->configStubProduction);
    }

    public function testGenerateViewStructural(): void
    {
        \ob_end_clean();
        $this->expectOutputRegex('/debug bar/');
        
        $this->initializeMocks();
        $this->localizatorMock->expects($this->never())
                ->method('getPageLocaleArray');
        $this->debuggerMock->expects($this->once())
                ->method('generateDebugBar')
                ->willReturn('debug bar');
        
        $service = RenderService::getInstance();
        $service->setStructural();
        $service->generateView('framework/internalServerError', [], ResponseType::httpOk, $this->localizatorMock, $this->debuggerMock, $this->configStubDevelop);
        $service->setStructural(false);
    }

    public function testGenerateDataInDevelopmentEnvironment(): void
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->expectOutputRegex('/^(?!.*(?:Database|Log|Form|Variables)).*$/s');
        
        $this->initializeStubs();
        $vars = [
            'metaUrl' => '',
            'controllerUrl' => 'sample',
            'actionUrl' => 'index',
        ];
        
        $service = RenderService::getInstance();
        $service->generateData('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->configStubDevelop);
    }

    public function testGenerateDataNotInDevelopmentEnvironment(): void
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->expectOutputRegex('/^(?!.*(?:Database|Log|Form|Variables)).*$/s');
        
        $this->initializeStubs();
        $vars = [
            'metaUrl' => '',
            'controllerUrl' => 'sample',
            'actionUrl' => 'index',
        ];
        
        $service = RenderService::getInstance();
        $service->generateData('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->configStubProduction);
    }

    public function testGenerateJson(): void
    {
        \ob_end_clean();
        $this->expectOutputString('{"message":"test"}');
        
        $service = RenderService::getInstance();
        $service->setStructural();
        $service->generateJson(['message' => 'test']);
        $service->setStructural(false);
    }

    public function testGetView(): void
    {
        \ob_end_clean();
        $this->expectOutputRegex('/.*/s');
        
        $this->initializeStubs();
        $vars = [
            'metaUrl' => '',
            'controllerUrl' => 'sample',
            'actionUrl' => 'index',
        ];
        
        $service = RenderService::getInstance();
        $service->generateData('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->configStubDevelop);
        
        $this->assertEquals('sample/index', $service->getView());
    }
}

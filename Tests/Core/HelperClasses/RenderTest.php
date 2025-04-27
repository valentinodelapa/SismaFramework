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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Localizator;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Tests\Config\ConfigFramework;

/**
 * @author Valentino de Lapa
 */
class RenderTest extends TestCase
{

    private Debugger $debuggerMock;
    private Localizator $localizatorMock;
    private BaseConfig $configMockDevelop;
    private BaseConfig $configMockProduction;

    #[\Override]
    public function setUp(): void
    {
        $this->configMockDevelop = $this->createMock(BaseConfig::class);
        $this->configMockDevelop->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', true],
                    ['structuralViewsPath', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'Structural' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
                    ['viewsPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
        ]);
        $this->configMockProduction = $this->createMock(BaseConfig::class);
        $this->configMockProduction->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['structuralViewsPath', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'Structural' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
                    ['viewsPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR],
        ]);
        BaseConfig::setInstance(new ConfigFramework());
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->localizatorMock = $this->createMock(Localizator::class);
        $this->debuggerMock = $this->createMock(Debugger::class);
        ModuleManager::setApplicationModule('SismaFramework');
    }

    public function testGenerateViewInDevelopmentEnvironment()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $vars = [
            'metaUrl' => '',
            'controllerUrl' => 'sample',
            'actionUrl' => 'index',
        ];
        $this->localizatorMock->expects($this->once())
                ->method('getPageLocaleArray')
                ->willReturn([]);
        $this->debuggerMock->expects($this->once())
                ->method('generateDebugBar')
                ->willReturn('');
        Render::generateView('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->debuggerMock, $this->configMockDevelop);
    }

    public function testGenerateViewNotInDevelopmentEnvironment()
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
        $this->localizatorMock->expects($this->once())
                ->method('getPageLocaleArray')
                ->willReturn([]);
        $this->debuggerMock->expects($this->never())
                ->method('generateDebugBar');
        Render::generateView('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->debuggerMock, $this->configMockProduction);
    }

    public function testGenerateViewStructural()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/debug bar/');
        $this->localizatorMock->expects($this->never())
                ->method('getPageLocaleArray');
        $this->debuggerMock->expects($this->once())
                ->method('generateDebugBar')
                ->willReturn('debug bar');
        Render::setStructural();
        Render::generateView('framework/internalServerError', [], ResponseType::httpOk, $this->localizatorMock, $this->debuggerMock, $this->configMockDevelop);
        Render::setStructural(false);
    }

    public function testGenerateDataInDevelopmentEnvironment()
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
        Debugger::startExecutionTimeCalculation();
        Render::generateData('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->configMockDevelop);
    }

    public function testGenerateDataNotInDevelopmentEnvironment()
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
        Debugger::startExecutionTimeCalculation();
        Render::generateData('sample/index', $vars, ResponseType::httpOk, $this->localizatorMock, $this->configMockProduction);
    }

    public function testGenerareJson()
    {
        \ob_end_clean();
        $this->expectOutputString('{"message":"test"}');
        Render::setStructural();
        Render::generateJson(['message' => 'test']);
        Render::setStructural(false);
    }
}

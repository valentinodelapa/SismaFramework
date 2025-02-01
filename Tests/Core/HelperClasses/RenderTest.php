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
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Localizator;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @author Valentino de Lapa
 */
class RenderTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        ModuleManager::setApplicationModule('SismaFramework');
    }

    public function testGenerateViewInDevelopementEnvironment()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $vars = [
            'metaUrl' => '',
            'controllerUrl' => 'sample',
            'actionUrl' => 'index',
        ];
        $localizatorMock = $this->createMock(Localizator::class);
        $localizatorMock->expects($this->once())
                ->method('getPageLocaleArray')
                ->willReturn([]);
        $dataMapperMock = $this->createMock(DataMapper::class);
        $debuggerMock = $this->createMock(Debugger::class);
        $debuggerMock->expects($this->once())
                ->method('generateDebugBar')
                ->willReturn('');
        Render::setDevelopementEnvironment();
        Render::generateView('sample/index', $vars, ResponseType::httpOk, $localizatorMock, $debuggerMock, $dataMapperMock);
    }
    
    public function testGenerateViewNotInDevelopementEnvironment()
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
        $localizatorMock = $this->createMock(Localizator::class);
        $localizatorMock->expects($this->once())
                ->method('getPageLocaleArray')
                ->willReturn([]);
        $dataMapperMock = $this->createMock(DataMapper::class);
        $debuggerMock = $this->createMock(Debugger::class);
        $debuggerMock->expects($this->never())
                ->method('generateDebugBar');
        Render::setDevelopementEnvironment(false);
        Render::generateView('sample/index', $vars, ResponseType::httpOk, $localizatorMock, $debuggerMock, $dataMapperMock);
    }
    
    public function testGenerateViewStructural()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/Unexpected Error/');
        $localizatorMock = $this->createMock(Localizator::class);
        $localizatorMock->expects($this->never())
                ->method('getPageLocaleArray');
        $dataMapperMock = $this->createMock(DataMapper::class);
        $debuggerMock = $this->createMock(Debugger::class);
        Render::setStructural();
        Render::generateView('framework/internalServerError', [], ResponseType::httpOk, $localizatorMock, $debuggerMock, $dataMapperMock);
        Render::setStructural(false);
    }
    
    public function testGenerateDataInDevelopementEnvironment()
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
        Render::setDevelopementEnvironment();
        Render::generateData('sample/index', $vars);
    }
    
    public function testGenerateDataNotInDevelopementEnvironment()
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
        Render::setDevelopementEnvironment(false);
        Render::generateData('sample/index', $vars);
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

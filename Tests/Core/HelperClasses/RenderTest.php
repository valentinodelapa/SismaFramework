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
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Render;

/**
 * @author Valentino de Lapa
 */
class RenderTest extends TestCase
{
    public function testGenerateViewInDevelopementEnvironment()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/sample - index/');
        $this->expectOutputRegex('/Hello World/');
        $this->expectOutputRegex('/Database/');
        $this->expectOutputRegex('/Log/');
        $this->expectOutputRegex('/Form/');
        $this->expectOutputRegex('/Variables/');
        $vars = [
            'metaUrl' => '',
            'controllerUrl' => 'sample',
            'actionUrl' => 'index',
        ];
        Debugger::startExecutionTimeCalculation();
        Render::setDevelopementEnvironment();
        Render::generateView('sample/index', $vars);
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
        Debugger::startExecutionTimeCalculation();
        Render::setDevelopementEnvironment(false);
        Render::generateView('sample/index', $vars);
    }
    
    public function testGenerateViewStructural()
    {
        \ob_end_clean();
        $this->expectOutputRegex('/Unexpected Error/');
        Render::setStructural();
        Render::generateView('framework/internalServerError', []);
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
    
    public function testGenerareJsonInDevelopementEnvironment()
    {
        \ob_end_clean();
        $this->expectOutputString('{"title":"Homepage","message":"test"}');
        Render::setDevelopementEnvironment();
        Render::generateJson(['message' => 'test']);
    }
    
    public function testGenerareJsonNotInDevelopementEnvironment()
    {
        \ob_end_clean();
        $this->expectOutputString('{"title":"Homepage","message":"test"}');
        Render::setDevelopementEnvironment(false);
        Render::generateJson(['message' => 'test']);
    }
    
    public function testGenerareJsonStructural()
    {
        \ob_end_clean();
        $this->expectOutputString('{"message":"test"}');
        Render::setStructural();
        Render::generateJson(['message' => 'test']);
        Render::setStructural(false);
    }
}

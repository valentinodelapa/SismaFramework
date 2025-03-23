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

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\CustomTypes\FormFilterError;
use SismaFramework\Core\HelperClasses\Debugger;

/**
 * Description of DebuggerTest
 *
 * @author Valentino de Lapa
 */
#[RunTestsInSeparateProcesses]
class DebuggerTest extends TestCase
{

    #[\Override]
    public function setUp(): void
    {
        BaseConfig::setInstance(new DebuggerConfigTest());
        Debugger::startExecutionTimeCalculation();
    }

    public function testStartExecutionTimeCalculation()
    {
        $debugger = new Debugger();
        $result = $debugger->getInformations();
        $this->assertIsArray($result);
        $this->isFloat($result['memoryUsed']);
    }

    public function testGenerateDebugBar()
    {
        BaseConfig::setInstance(new DebuggerConfigTest);
        Debugger::addQueryExecuted('sample query');
        $debugger = new Debugger();
        $result = $debugger->generateDebugBar();
        $this->assertIsString($result);
        $this->assertStringContainsString('sample query', $result);
    }

    public function testGetInformations()
    {
        $debugger = new Debugger();
        $result = $debugger->getInformations();
        $this->assertIsArray($result);
        $this->isFloat($result['memoryUsed']);
    }

    public function testAddQueryExecuted()
    {
        Debugger::addQueryExecuted('sample query one');
        Debugger::addQueryExecuted('sample query two');
        $debugger = new Debugger();
        $informations = $debugger->getInformations();
        $this->assertEquals(2, $informations['queryExecutedNumber']);
        $debugBbar = $debugger->generateDebugBar();
        $this->assertStringContainsString('sample query one', $debugBbar);
        $this->assertStringContainsString('sample query two', $debugBbar);
    }

    public function testSetFormFilter()
    {
        $formFilter = new FormFilterError();
        $formFilter->sampleFieldOneError = false;
        $formFilter->sampleFieldTwoError = true;
        $formFilter->sampleFieldThreeError = true;
        $debugger = new Debugger();
        $debugger::setFormFilter($formFilter);
        $informations = $debugger->getInformations();
        $this->assertEquals(3, $informations['formFilterNumber']);
        $debugBbar = $debugger->generateDebugBar();
        $this->assertStringContainsString('sampleFieldOneError', $debugBbar);
        $this->assertStringContainsString('sampleFieldTwoError', $debugBbar);
        $this->assertStringContainsString('sampleFieldThreeError', $debugBbar);
    }

    public function testSetVars()
    {
        $vars = [
            'boolean' => true,
            'integer' => 1,
            'double' => 1.1,
            'string' => 'sample',
            'object' => new \stdClass(),
        ];
        $debugger = new Debugger();
        $debugger->setVars($vars);
        $informations = $debugger->getInformations();
        $this->assertEquals(5, $informations['varsNumber']);
        $debugBbar = $debugger->generateDebugBar();
        $this->assertStringContainsString('1', $debugBbar);
        $this->assertStringContainsString('1', $debugBbar);
        $this->assertStringContainsString('1.1', $debugBbar);
        $this->assertStringContainsString('sample', $debugBbar);
        $this->assertStringContainsString('object', $debugBbar);
    }
}

class DebuggerConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        return false;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->structuralTemplatesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'Structural' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR;
        $this->logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->logPath = $this->logDirectoryPath . 'log.txt';
    }
}

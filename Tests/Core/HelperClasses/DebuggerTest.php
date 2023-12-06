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
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Templater;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * Description of DebuggerTest
 *
 * @author Valentino de Lapa
 * @runTestsInSeparateProcesses
 */
class DebuggerTest extends TestCase
{

    private \ReflectionClass $debuggerReflection;

    public function __construct($name = null, $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->debuggerReflection = new \ReflectionClass(Debugger::class);
    }

    public function testStartExecutionTimeCalculation()
    {
        $microtimeProperty = $this->debuggerReflection->getProperty('microtime');
        $microtimeProperty->setAccessible(true);
        Debugger::startExecutionTimeCalculation();
        $this->assertIsFloat($microtimeProperty->getValue());
    }

    public function testEndExecutionTimeCalculation()
    {
        $executionTimeProperty = $this->debuggerReflection->getProperty('executionTime');
        $executionTimeProperty->setAccessible(true);
        Debugger::startExecutionTimeCalculation();
        Debugger::endExecutionTimeCalculation();
        $this->assertIsFloat($executionTimeProperty->getValue());
    }
    
    public function testGenerateDebugBar()
    {
        $queryExecutedProperty = $this->debuggerReflection->getProperty('queryExecuted');
        $queryExecutedProperty->setAccessible(true);
        $queryExecutedProperty->setValue(new Debugger, ['sample query']);
        $generateDebugBarMethod = $this->debuggerReflection->getMethod('generateDebugBar');
        Debugger::startExecutionTimeCalculation();
        Debugger::endExecutionTimeCalculation();
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $dataMapperMock = $this->createMock(DataMapper::class);
        $result = $generateDebugBarMethod->invoke(new Debugger(), $dataMapperMock);
        $this->assertIsString($result);
        $this->assertStringContainsString('sample query', $result);
    }
    
    public function testGenerateDebugBarForm()
    {
        Templater::setStructural();
        $generateDebugBarFormMethod = $this->debuggerReflection->getMethod('generateDebugBarForm');
        $result = $generateDebugBarFormMethod->invoke(new Debugger, ['sampleSimpleField' => false, 'sampleComplexField' => ['sampleSubField' => true]]);
        $this->assertIsString($result);
        $this->assertStringContainsString('sampleSimpleField', $result);
        $this->assertStringContainsString('sampleComplexField', $result);
        $this->assertStringContainsString('sampleSubField', $result);
    }
    
    public function testGenerateDebugBarVars()
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        Templater::setStructural();
        $varsProperty = $this->debuggerReflection->getProperty('vars');
        $varsProperty->setAccessible(true);
        $varsProperty->setValue(new Debugger, ['sampleVars' => 'sample value']);
        $generateDebugBarVarsMethod = $this->debuggerReflection->getMethod('generateDebugBarVars');
        $result = $generateDebugBarVarsMethod->invoke(new Debugger());
        $this->assertIsString($result);
        $this->assertStringContainsString('sampleVars', $result);
        $this->assertStringContainsString('sample value', $result);
    }
    
    public function testGetInformations()
    {
        Debugger::startExecutionTimeCalculation();
        Debugger::endExecutionTimeCalculation();
        $geInformationsMethod = $this->debuggerReflection->getMethod('getInformations');
        $result = $geInformationsMethod->invoke(new Debugger);
        $this->assertIsArray($result);
    }
    
    public function testGetMemoryUsed()
    {
        $getMemoryUsedMethod = $this->debuggerReflection->getMethod('getMemoryUsed');
        $result = $getMemoryUsedMethod->invoke(new Debugger);
        $this->assertIsFloat($result);
    }
    
    public function testAddQueryExecuted()
    {
        $addQueryExecutedMethod = $this->debuggerReflection->getMethod('addQueryExecuted');
        $addQueryExecutedMethod->invoke(new Debugger, 'sample query');
        $queryExecutedProperty = $this->debuggerReflection->getProperty('queryExecuted');
        $queryExecutedProperty->setAccessible(true);
        $this->assertIsArray($queryExecutedProperty->getValue());
        $this->assertEquals('sample query', $queryExecutedProperty->getValue()[0]);
    }
    
    public function testSetFormFilter()
    {
        $setFormFilterMethod = $this->debuggerReflection->getMethod('setFormFilter');
        $setFormFilterMethod->invoke(new Debugger, ['sampleField' => false]);
        $formFilterProperty = $this->debuggerReflection->getProperty('formFilter');
        $formFilterProperty->setAccessible(true);
        $this->assertIsArray($formFilterProperty->getValue());
        $this->assertArrayHasKey('sampleField', $formFilterProperty->getValue());
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
        $setVarsMethod = $this->debuggerReflection->getMethod('setVars');
        $setVarsMethod->invoke(new Debugger, $vars);
        $varsProperty = $this->debuggerReflection->getProperty('vars');
        $varsProperty->setAccessible(true);
        $this->assertIsArray($varsProperty->getValue());
        $this->assertIsBool($varsProperty->getValue()['boolean']);
        $this->assertIsInt($varsProperty->getValue()['integer']);
        $this->assertIsFloat($varsProperty->getValue()['double']);
        $this->assertIsString($varsProperty->getValue()['string']);
        $this->assertIsString($varsProperty->getValue()['object']);
    }

}

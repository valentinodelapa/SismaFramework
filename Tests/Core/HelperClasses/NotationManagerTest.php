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
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Entities\BaseSample;

/**
 * @author Valentino de Lapa
 */
class NotationManagerTest extends TestCase
{
    private \ReflectionClass $reflectionNotationManager;
    
    public function testConvertToStudlyCaps()
    {
        $result = NotationManager::convertToStudlyCaps('fake-fake-fake');
        $this->assertEquals('FakeFakeFake', $result);
    }

    public function testConvertToCamelCase()
    {
        $result = NotationManager::convertToCamelCase('fake-fake-fake');
        $this->assertEquals('fakeFakeFake', $result);
    }
    
    public function testConvertToKebabKase()
    {
        $result = NotationManager::convertToKebabCase('fakeFakeFake');
        $this->assertEquals('fake-fake-fake', $result);
    }
    
    public function testConvertToSnakeCase()
    {
        $result = NotationManager::convertToSnakeCase('StudlyCaps');
        $this->assertEquals('studly_caps', $result);
        $result = NotationManager::convertToSnakeCase('camelCase');
        $this->assertEquals('camel_case', $result);
    }
    
    public function testConvertEntityToTableName()
    {
        $configTest = new NotationManagerConfigTest();
        BaseConfig::setInstance($configTest);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->setConstructorArgs([$baseAdapterMock, $configTest])
                ->getMock();
        $baseSample = new BaseSample($dataMapperMock, $configTest);
        $result =NotationManager::convertEntityToTableName($baseSample);
        $this->assertEquals('base_sample', $result);
    }
    
    public function testConvertEntityNameToTableName()
    {
        $result = NotationManager::convertEntityNameToTableName(BaseSample::class);
        $this->assertEquals('base_sample', $result);
    }
    
    public function testConvertColumnNameToPropertyName()
    {
        $result = NotationManager::convertColumnNameToPropertyName('referenced_entity_without_initialization_id');
        $this->assertEquals('referencedEntityWithoutInitialization', $result);
    }
}

class NotationManagerConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
                return false;
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->ormCache = true;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        
    }
}
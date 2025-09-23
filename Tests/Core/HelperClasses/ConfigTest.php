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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Orm\Enumerations\AdapterType;

/**
 * @author Valentino de Lapa
 */
class ConfigTest extends TestCase
{

    private Config $originalConfig;

    #[\Override]
    public function setUp(): void
    {
        // Store the original config instance to restore it later
        $this->originalConfig = Config::getInstance();
    }

    #[\Override]
    public function tearDown(): void
    {
        // Restore the original config instance
        Config::setInstance($this->originalConfig);
    }

    public function testSingletonPattern()
    {
        $config1 = Config::getInstance();
        $config2 = Config::getInstance();

        $this->assertSame($config1, $config2);
        $this->assertInstanceOf(Config::class, $config1);
    }

    public function testConstructorSetsInstance()
    {
        $config = new Config();
        $this->assertSame($config, Config::getInstance());
    }

    public function testSetInstance()
    {
        $mockConfig = $this->createMock(Config::class);
        Config::setInstance($mockConfig);

        $this->assertSame($mockConfig, Config::getInstance());
    }

    public function testMagicGetMethodExists()
    {
        $config = new Config();
        $reflection = new \ReflectionClass($config);

        // Test that __get method exists
        $this->assertTrue($reflection->hasMethod('__get'));
        $method = $reflection->getMethod('__get');
        $this->assertTrue($method->isPublic());
    }

    public function testSetInstanceAndGetInstance()
    {
        $mockConfig = $this->createMock(Config::class);

        Config::setInstance($mockConfig);
        $retrievedConfig = Config::getInstance();

        $this->assertSame($mockConfig, $retrievedConfig);
    }

    public function testConfigPropertiesExist()
    {
        $config = new Config();
        $reflection = new \ReflectionClass($config);

        // Test that essential properties are defined
        $properties = [
            'adapters',
            'application',
            'core',
            'developmentEnvironment',
            'databaseHost',
            'databaseName',
            'entityNamespace',
            'controllerNamespace',
            'language'
        ];

        foreach ($properties as $property) {
            $this->assertTrue(
                $reflection->hasProperty($property),
                "Property '{$property}' should exist in Config class"
            );
        }
    }

    public function testReadonlyProperties()
    {
        $config = new Config();
        $reflection = new \ReflectionClass($config);

        $readonlyProperties = [
            'adapters',
            'application',
            'developmentEnvironment',
            'databaseHost'
        ];

        foreach ($readonlyProperties as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue(
                $property->isReadonly(),
                "Property '{$propertyName}' should be readonly"
            );
        }
    }

    public function testProtectedProperties()
    {
        $config = new Config();
        $reflection = new \ReflectionClass($config);

        $protectedProperties = [
            'adapters',
            'application',
            'language',
            'developmentEnvironment'
        ];

        foreach ($protectedProperties as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue(
                $property->isProtected(),
                "Property '{$propertyName}' should be protected"
            );
        }
    }

    public function testLanguagePropertyType()
    {
        $config = new Config();
        $reflection = new \ReflectionClass($config);
        $property = $reflection->getProperty('language');

        $this->assertEquals('SismaFramework\Core\Enumerations\Language', $property->getType()->getName());
    }

    public function testAdapterTypePropertyType()
    {
        $config = new Config();
        $reflection = new \ReflectionClass($config);
        $property = $reflection->getProperty('defaultAdapterType');

        $this->assertEquals('SismaFramework\Orm\Enumerations\AdapterType', $property->getType()->getName());
    }

    public function testInstancePropertyExists()
    {
        $reflection = new \ReflectionClass(Config::class);
        $property = $reflection->getProperty('instance');

        $this->assertTrue($property->isStatic());
        $this->assertTrue($property->isPrivate());
        $this->assertEquals('SismaFramework\Core\HelperClasses\Config', $property->getType()->getName());
    }
}
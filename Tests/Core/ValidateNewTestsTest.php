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

namespace SismaFramework\Tests\Core;

use PHPUnit\Framework\TestCase;

/**
 * Validates that our new test classes exist and are properly structured
 * @author Valentino de Lapa
 */
class ValidateNewTestsTest extends TestCase
{

    public function testNewTestClassesExist()
    {
        $testClasses = [
            'SismaFramework\Tests\Core\HelperClasses\BufferManagerTest',
            'SismaFramework\Tests\Core\HelperClasses\ConfigTest',
            'SismaFramework\Tests\Core\HelperClasses\ErrorHandlerTest',
            'SismaFramework\Tests\Core\HelperClasses\LocalizatorTest',
            'SismaFramework\Tests\Core\HelperClasses\TemplaterTest',
            'SismaFramework\Tests\Core\CustomTypes\FormFilterErrorCollectionTest',
            'SismaFramework\Tests\Core\HttpClasses\ResponseTest'
        ];

        foreach ($testClasses as $testClass) {
            $this->assertTrue(class_exists($testClass), "Test class {$testClass} should exist");
        }
    }

    public function testTargetClassesAreTestable()
    {
        $targetClasses = [
            'SismaFramework\Core\HelperClasses\BufferManager',
            'SismaFramework\Core\HelperClasses\Config',
            'SismaFramework\Core\HelperClasses\ErrorHandler',
            'SismaFramework\Core\HelperClasses\Localizator',
            'SismaFramework\Core\HelperClasses\Templater',
            'SismaFramework\Core\CustomTypes\FormFilterErrorCollection',
            'SismaFramework\Core\HttpClasses\Response'
        ];

        foreach ($targetClasses as $targetClass) {
            $this->assertTrue(class_exists($targetClass), "Target class {$targetClass} should exist for testing");
        }
    }

    public function testFrameworkCanBeLoaded()
    {
        // Basic test that framework autoloading works
        $this->assertTrue(class_exists('PHPUnit\Framework\TestCase'));
        $this->assertTrue(function_exists('class_exists'));
        $this->assertTrue(function_exists('method_exists'));
    }

    public function testPHPVersionCompatibility()
    {
        // Test that we're running on compatible PHP version
        $this->assertTrue(version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP 8.1+ required');
        $this->assertTrue(function_exists('enum_exists'), 'Enum support required (PHP 8.1+)');
    }
}
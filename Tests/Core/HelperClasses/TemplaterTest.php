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
use SismaFramework\Core\HelperClasses\Templater;

/**
 * @author Valentino de Lapa
 */
class TemplaterTest extends TestCase
{

    private string $tempTemplateDir;
    private string $tempTemplateFile;

    #[\Override]
    public function setUp(): void
    {
        // Create temporary template directory and files for testing
        $this->tempTemplateDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('template_', true) . DIRECTORY_SEPARATOR;
        if (!is_dir($this->tempTemplateDir)) {
            mkdir($this->tempTemplateDir, 0777, true);
        }

        $this->tempTemplateFile = $this->tempTemplateDir . 'test.tpl';
        file_put_contents($this->tempTemplateFile, 'Hello {{name}}, welcome to {{site}}!');
    }

    #[\Override]
    public function tearDown(): void
    {
        // Clean up temporary files
        if (file_exists($this->tempTemplateFile)) {
            unlink($this->tempTemplateFile);
        }
        if (is_dir($this->tempTemplateDir)) {
            rmdir($this->tempTemplateDir);
        }
    }

    public function testClassExists()
    {
        $this->assertTrue(class_exists('SismaFramework\Core\HelperClasses\Templater'));
    }

    public function testParseTemplateMethodExists()
    {
        $reflection = new \ReflectionClass(Templater::class);
        $this->assertTrue($reflection->hasMethod('parseTemplate'));
        $this->assertTrue($reflection->getMethod('parseTemplate')->isStatic());
        $this->assertTrue($reflection->getMethod('parseTemplate')->isPublic());
    }

    public function testGenerateTemplateMethodExists()
    {
        $reflection = new \ReflectionClass(Templater::class);
        $this->assertTrue($reflection->hasMethod('generateTemplate'));
        $this->assertTrue($reflection->getMethod('generateTemplate')->isStatic());
        $this->assertTrue($reflection->getMethod('generateTemplate')->isPublic());
    }

    public function testGenerateStructuralTemplateMethodExists()
    {
        $reflection = new \ReflectionClass(Templater::class);
        $this->assertTrue($reflection->hasMethod('generateStructuralTemplate'));
        $this->assertTrue($reflection->getMethod('generateStructuralTemplate')->isStatic());
        $this->assertTrue($reflection->getMethod('generateStructuralTemplate')->isPublic());
    }

    public function testParseTemplate()
    {
        $vars = [
            'name' => 'John',
            'site' => 'Example.com'
        ];

        $result = Templater::parseTemplate($this->tempTemplateFile, $vars);
        $this->assertEquals('Hello John, welcome to Example.com!', $result);
    }

    public function testParseTemplateWithEmptyVars()
    {
        $vars = [];

        // Suppress warnings since we're testing error handling behavior
        $result = @Templater::parseTemplate($this->tempTemplateFile, $vars);

        // When variables are missing, the template method should still return a string
        $this->assertIsString($result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function testParseTemplateWithExtraVars()
    {
        $vars = [
            'name' => 'John',
            'site' => 'Example.com',
            'extra' => 'unused'
        ];

        $result = Templater::parseTemplate($this->tempTemplateFile, $vars);
        $this->assertEquals('Hello John, welcome to Example.com!', $result);
    }

    public function testParseTemplateWithComplexContent()
    {
        $complexTemplate = $this->tempTemplateDir . 'complex.tpl';
        file_put_contents($complexTemplate, '{{greeting}} {{name}}! Your balance is ${{balance}}.{{newline}}Thanks for visiting {{site}}.');

        $vars = [
            'greeting' => 'Hi',
            'name' => 'Alice',
            'balance' => '100.50',
            'newline' => "\n",
            'site' => 'Test Site'
        ];

        $result = Templater::parseTemplate($complexTemplate, $vars);
        $expected = "Hi Alice! Your balance is $100.50.\nThanks for visiting Test Site.";
        $this->assertEquals($expected, $result);

        unlink($complexTemplate);
    }
}
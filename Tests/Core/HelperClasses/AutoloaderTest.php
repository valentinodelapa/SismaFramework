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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Autoloader;
use SismaFramework\Core\HelperClasses\Config;

/**
 * Description of AutoloaderTest
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class AutoloaderTest extends TestCase
{

    private Config $configStub;

    public function setUp(): void
    {
        $this->configStub = $this->createStub(Config::class);
    }

    public function testDirectAccessClass()
    {
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
        ]);
        $autoloader = new Autoloader(Autoloader::class, $this->configStub);
        $this->assertTrue($autoloader->findClass());
        $this->assertEquals(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'HelperClasses' . DIRECTORY_SEPARATOR . 'Autoloader.php', $autoloader->getClassPath());
    }

    public function testMapNamespace()
    {
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
                    ['autoloadNamespaceMapper', ['TestsApplication\\Vendor' => 'SismaFramework' . DIRECTORY_SEPARATOR . 'TestsApplication' . DIRECTORY_SEPARATOR . 'Vendor']],
        ]);
        $autoloader = new Autoloader('TestsApplication\\Vendor\\ClassWithNamespace', $this->configStub);
        $this->assertTrue($autoloader->findClass());
        $this->assertEquals(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'TestsApplication' . DIRECTORY_SEPARATOR . 'Vendor' . DIRECTORY_SEPARATOR . 'ClassWithNamespace.php', $autoloader->getClassPath());
    }

    public function testMapClass()
    {
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
                    ['autoloadNamespaceMapper', []],
                    ['autoloadClassMapper', ['ClassWithoutNamespace' => 'SismaFramework' . DIRECTORY_SEPARATOR . 'TestsApplication' . DIRECTORY_SEPARATOR . 'Vendor' . DIRECTORY_SEPARATOR . 'ClassWithoutNamespace']],
        ]);
        $autoloader = new Autoloader('ClassWithoutNamespace', $this->configStub);
        $this->assertTrue($autoloader->findClass());
        $this->assertEquals(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'TestsApplication' . DIRECTORY_SEPARATOR . 'Vendor' . DIRECTORY_SEPARATOR . 'ClassWithoutNamespace.php', $autoloader->getClassPath());
    }

    public function testFakeClass()
    {
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
                    ['autoloadNamespaceMapper', []],
                    ['autoloadClassMapper', []],
        ]);
        $autoloader = new Autoloader('FakeClass', $this->configStub);
        $this->assertFalse($autoloader->findClass());
    }
}

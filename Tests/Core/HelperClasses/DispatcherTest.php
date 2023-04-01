<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa <valentino.delapa@gmail.com>.
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
use SismaFramework\Core\Exceptions\PageNotFoundException;
use SismaFramework\Core\Exceptions\QueryStringException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\HelperClasses\Dispatcher;
use SismaFramework\Core\HelperClasses\FixturesManager;
use SismaFramework\Core\HelperClasses\Router;

/**
 * Description of DispatcherTest
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class DispatcherTest extends TestCase
{

    private \ReflectionClass $dispatcherClass;
    private Dispatcher $dispatcherInstance;

    public function __construct($name = null, $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->dispatcherClass = new \ReflectionClass(Dispatcher::class);
        $this->dispatcherInstance = new Dispatcher();
    }

    /**
     * @runInSeparateProcess
     */
    public function testStructuralFileFopen()
    {
        $_SERVER['REQUEST_URI'] = '/css/DebugBar.css';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Structural/Assets/css/DebugBar.css'), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testModuleFile()
    {
        $_SERVER['REQUEST_URI'] = '/css/sample.css';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Sample/Assets/css/sample.css'), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDirectAccessToFile()
    {
        $_SERVER['REQUEST_URI'] = 'SismaFramework/Sample/Assets/css/sample.css';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Sample/Assets/css/sample.css'), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFileWithReadfile()
    {
        $_SERVER['REQUEST_URI'] = '/javascript/jquery.debugBar.js';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Structural/Assets/javascript/jquery.debugBar.js'), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFileWithStreamContent()
    {
        $_SERVER['REQUEST_URI'] = '/javascript/sample.js?resource=resource';
        $_SERVER['QUERY_STRING'] = 'resource=resource';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Sample/Assets/javascript/sample.js'), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testNotExistentFileFile()
    {
        $_SERVER['REQUEST_URI'] = 'fake/fake/fake/fake/fake.css';
        $_SERVER['QUERY_STRING'] = '';
        $this->expectException(PageNotFoundException::class);
        $this->dispatcherInstance->run();
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunFixture()
    {
        $fixturesManagerMock = $this->createMock(FixturesManager::class);
        $fixturesManagerMock->expects($this->once())
                ->method('run');
        $fixturesManagerProperty = $this->dispatcherClass->getProperty('fixturesManager');
        $fixturesManagerProperty->setValue($this->dispatcherInstance, $fixturesManagerMock);
        $_SERVER['REQUEST_URI'] = '/fixtures/';
        $this->dispatcherInstance->run();
    }

    /**
     * @runInSeparateProcess
     */
    public function testRootPath()
    {
        $_SERVER['REQUEST_URI'] = '/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('sample - index', $result);
        $this->assertStringContainsString('Hello World', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIndexPath()
    {
        $_SERVER['REQUEST_URI'] = '/sample/index/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('sample - index', $result);
        $this->assertStringContainsString('Hello World', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIndexPathTwo()
    {
        $_SERVER['REQUEST_URI'] = '/index/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('sample - index', $result);
        $this->assertStringContainsString('Hello World', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIndexPathThree()
    {
        $_SERVER['REQUEST_URI'] = '/sample/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('sample - index', $result);
        $this->assertStringContainsString('Hello World', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testNotifyPath()
    {
        $_SERVER['REQUEST_URI'] = '/notify/message/test+message';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('sample - notify', $result);
        $this->assertStringContainsString('test message', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testNotifyPathWithQueryString()
    {
        $_SERVER['REQUEST_URI'] = '/notify?message=message';
        $_SERVER['QUERY_STRING'] = 'message=test+message';
        $this->expectException(QueryStringException::class);
        $this->dispatcherInstance->run();
    }

    /**
     * @runInSeparateProcess
     */
    public function testOtherIndexPath()
    {
        $_SERVER['REQUEST_URI'] = '/other/parameter/test+message/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('other - index', $result);
        $this->assertStringContainsString('test message', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPathWithReload()
    {
        $_SERVER['REQUEST_URI'] = '/fake/other/index/parameter/other+test+message/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('other - index', $result);
        $this->assertStringContainsString('other test message', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPathWithRequestParameter()
    {
        $_POST['parameter'] = 'test parameter';
        $_SERVER['REQUEST_URI'] = '/other/action-with-request/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('other - action-with-request', $result);
        $this->assertStringContainsString('test parameter', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPathWithAuthenticationParameter()
    {
        $_POST['username'] = 'username';
        $_SERVER['REQUEST_URI'] = '/other/action-with-authentication/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('other - action-with-authentication', $result);
        $this->assertStringContainsString('is not submitted', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPathWithDefaultValueParameter()
    {
        $_SERVER['REQUEST_URI'] = '/other/action-with-default-value/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('other - action-with-default-value', $result);
        $this->assertStringContainsString('is default', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPathWithArrayParameter()
    {
        $_SERVER['REQUEST_URI'] = '/other/action-with-array/array/first/array/second/array/third/';
        \ob_start();
        $this->dispatcherInstance->run();
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertStringContainsString('other - action-with-array', $result);
        $this->assertStringContainsString('<div>0: first</div>', $result);
        $this->assertStringContainsString('<div>1: second</div>', $result);
        $this->assertStringContainsString('<div>2: third</div>', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPathWithInvalidParameter()
    {
        $_SERVER['REQUEST_URI'] = '/other/fake/test/';
        $this->expectException(InvalidArgumentException::class);
        $this->dispatcherInstance->run();
    }

    /**
     * @runInSeparateProcess
     */
    public function testFakePath()
    {
        $_SERVER['REQUEST_URI'] = '/fake/fake/fake/fake/fake/fake/';
        $this->expectException(PageNotFoundException::class);
        $this->dispatcherInstance->run();
    }

}

<?php

/*
 * The MIT License
 *
 * Copyright (c) 2023-present Valentino de Lapa.
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
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\HttpClasses\Request;

/**
 * @author Valentino de Lapa
 */
class SessionTest extends TestCase
{
    private Request $requestMock;

    public function setUp(): void
    {
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', true],
                    ['httpsIsForced', false],
        ]);
        Config::setInstance($configMock);
        Session::end();
        $this->requestMock = $this->getMockBuilder(Request::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->requestMock->server = [
            'HTTP_HOST' => '',
            'HTTP_USER_AGENT' => '',
            'REMOTE_ADDR' => '',
        ];
    }

    public function testSessionStart()
    {
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        Session::start($this->requestMock);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        Session::end();
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        $session = new Session($this->requestMock);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        unset($session);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testSessionSetUnsetSampleItemStatic()
    {
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        Session::start($this->requestMock);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertArrayNotHasKey('test', $_SESSION);
        Session::setItem('test', 'value');
        $this->assertArrayHasKey('test', $_SESSION);
        $this->assertTrue(Session::hasItem('test'));
        $this->assertEquals('value', $_SESSION['test']);
        $this->assertEquals('value', Session::getItem('test'));
        Session::unsetItem('test');
        $this->assertArrayNotHasKey('test', $_SESSION);
        $this->assertFalse(Session::hasItem('test'));
        Session::end();
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    public function testSessionSetUnsetSampleItemObject()
    {
        $session = new Session($this->requestMock);
        $this->assertArrayNotHasKey('test', $_SESSION);
        $session->test = 'value';
        $this->assertArrayHasKey('test', $_SESSION);
        $this->assertTrue(isset($session->test));
        $this->assertEquals('value', $_SESSION['test']);
        $this->assertEquals('value', $session->test);
        unset($session->test);
        $this->assertArrayNotHasKey('test', $_SESSION);
        $this->assertFalse(isset($session->test));
        unset($session);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testSessionSetUnsetNestedItemStatic()
    {
        Session::end();
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        Session::start($this->requestMock);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertArrayNotHasKey('test', $_SESSION);
        Session::setItem('test[one][two]', 'valueOne');
        Session::setItem('test[one][three]', 'valueTwo');
        $this->assertArrayHasKey('test', $_SESSION);
        $this->assertTrue(Session::hasItem('test'));
        $this->assertArrayHasKey('one', $_SESSION['test']);
        $this->assertTrue(Session::hasItem('test[one]'));
        $this->assertArrayHasKey('two', $_SESSION['test']['one']);
        $this->assertTrue(Session::hasItem('test[one][two]'));
        $this->assertArrayHasKey('three', $_SESSION['test']['one']);
        $this->assertTrue(Session::hasItem('test[one][three]'));
        $this->assertEquals('valueOne', $_SESSION['test']['one']['two']);
        $this->assertEquals('valueOne', Session::getItem('test[one][two]'));
        $this->assertEquals('valueTwo', $_SESSION['test']['one']['three']);
        $this->assertEquals('valueTwo', Session::getItem('test[one][three]'));
        Session::unsetItem('test[one][two]');
        $this->assertArrayHasKey('test', $_SESSION);
        $this->assertTrue(Session::hasItem('test'));
        $this->assertArrayHasKey('one', $_SESSION['test']);
        $this->assertTrue(Session::hasItem('test[one]'));
        $this->assertArrayNotHasKey('two', $_SESSION['test']);
        $this->assertFalse(Session::hasItem('test[one][two]'));
        $this->assertArrayHasKey('three', $_SESSION['test']['one']);
        $this->assertTrue(Session::hasItem('test[one][three]'));
        $this->assertEquals('valueTwo', $_SESSION['test']['one']['three']);
        $this->assertEquals('valueTwo', Session::getItem('test[one][three]'));
        Session::unsetItem('test');
        $this->assertArrayNotHasKey('test', $_SESSION);
        $this->assertFalse(Session::hasItem('test'));
        Session::end();
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    public function testSessionAppendSimpleItem()
    {
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        Session::start($this->requestMock);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertArrayNotHasKey('test', $_SESSION);
        Session::setItem('test[0]', 'valueOne');
        $this->assertArrayHasKey(0, $_SESSION['test']);
        $this->assertTrue(Session::hasItem('test[0]'));
        $this->assertEquals('valueOne', $_SESSION['test'][0]);
        $this->assertEquals('valueOne', Session::getItem('test[0]'));
        Session::appendItem('test', 'valueTwo');
        $this->assertArrayHasKey(1, $_SESSION['test']);
        $this->assertTrue(Session::hasItem('test[1]'));
        $this->assertEquals('valueTwo', $_SESSION['test'][1]);
        $this->assertEquals('valueTwo', Session::getItem('test[1]'));
        Session::unsetItem('test');
        $this->assertArrayNotHasKey('test', $_SESSION);
        $this->assertFalse(Session::hasItem('test'));
        Session::end();
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    public function testSessionAppendNestedItem()
    {
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        Session::start($this->requestMock);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertArrayNotHasKey('test', $_SESSION);
        Session::setItem('test[one][0]', 'valueOne');
        $this->assertArrayHasKey('test', $_SESSION);
        $this->assertTrue(Session::hasItem('test'));
        $this->assertArrayHasKey('one', $_SESSION['test']);
        $this->assertTrue(Session::hasItem('test[one]'));
        $this->assertArrayHasKey(0, $_SESSION['test']['one']);
        $this->assertTrue(Session::hasItem('test[one][0]'));
        $this->assertEquals('valueOne', $_SESSION['test']['one'][0]);
        Session::appendItem('test[one]', 'valueTwo');
        $this->assertArrayHasKey(1, $_SESSION['test']['one']);
        $this->assertTrue(Session::hasItem('test[one][1]'));
        $this->assertEquals('valueTwo', $_SESSION['test']['one'][1]);
        $this->assertEquals('valueTwo', Session::getItem('test[one][1]'));
        Session::unsetItem('test');
        $this->assertArrayNotHasKey('test', $_SESSION);
        $this->assertFalse(Session::hasItem('test'));
        Session::end();
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }
}

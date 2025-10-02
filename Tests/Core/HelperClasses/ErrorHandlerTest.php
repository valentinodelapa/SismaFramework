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
use SismaFramework\Core\HelperClasses\ErrorHandler;

/**
 * @author Valentino de Lapa
 */
class ErrorHandlerTest extends TestCase
{

    public function testClassExists()
    {
        $this->assertTrue(class_exists('SismaFramework\Core\HelperClasses\ErrorHandler'));
    }

    public function testDisableErrorDisplayMethodExists()
    {
        $reflection = new \ReflectionClass(ErrorHandler::class);
        $this->assertTrue($reflection->hasMethod('disabligErrorDisplay'));
        $this->assertTrue($reflection->getMethod('disabligErrorDisplay')->isStatic());
        $this->assertTrue($reflection->getMethod('disabligErrorDisplay')->isPublic());
    }

    public function testHandleBaseExceptionMethodExists()
    {
        $reflection = new \ReflectionClass(ErrorHandler::class);
        $this->assertTrue($reflection->hasMethod('handleBaseException'));
        $this->assertTrue($reflection->getMethod('handleBaseException')->isStatic());
        $this->assertTrue($reflection->getMethod('handleBaseException')->isPublic());
    }

    public function testHandleThrowableErrorMethodExists()
    {
        $reflection = new \ReflectionClass(ErrorHandler::class);
        $this->assertTrue($reflection->hasMethod('handleThrowableError'));
        $this->assertTrue($reflection->getMethod('handleThrowableError')->isStatic());
        $this->assertTrue($reflection->getMethod('handleThrowableError')->isPublic());
    }

    public function testHandleNonThrowableErrorMethodExists()
    {
        $reflection = new \ReflectionClass(ErrorHandler::class);
        $this->assertTrue($reflection->hasMethod('handleNonThrowableError'));
        $this->assertTrue($reflection->getMethod('handleNonThrowableError')->isStatic());
        $this->assertTrue($reflection->getMethod('handleNonThrowableError')->isPublic());
    }

    public function testDisableErrorDisplay()
    {
        $originalDisplayErrors = ini_get('display_errors');
        $originalDisplayStartupErrors = ini_get('display_startup_errors');
        $originalErrorReporting = error_reporting();

        ErrorHandler::disabligErrorDisplay();

        $this->assertEquals('0', ini_get('display_errors'));
        $this->assertEquals('0', ini_get('display_startup_errors'));
        $this->assertEquals(0, error_reporting());

        // Restore original settings
        ini_set('display_errors', $originalDisplayErrors);
        ini_set('display_startup_errors', $originalDisplayStartupErrors);
        error_reporting($originalErrorReporting);
    }
}
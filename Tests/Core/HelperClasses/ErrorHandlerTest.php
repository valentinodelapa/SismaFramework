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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Tests\Core\Fixtures\TestLoggableException;
use SismaFramework\Tests\Core\Fixtures\TestNonLoggableException;
use Psr\Log\LoggerInterface;
use SismaFramework\Core\Interfaces\Controllers\DefaultControllerInterface;
use SismaFramework\Core\Interfaces\Controllers\StructuralControllerInterface;

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
        $this->assertTrue($reflection->hasMethod('disableErrorDisplay'));
        $this->assertFalse($reflection->getMethod('disableErrorDisplay')->isStatic());
        $this->assertTrue($reflection->getMethod('disableErrorDisplay')->isPublic());
    }

    public function testHandleBaseExceptionMethodExists()
    {
        $reflection = new \ReflectionClass(ErrorHandler::class);
        $this->assertTrue($reflection->hasMethod('handleBaseException'));
        $this->assertFalse($reflection->getMethod('handleBaseException')->isStatic());
        $this->assertTrue($reflection->getMethod('handleBaseException')->isPublic());
    }

    public function testHandleThrowableErrorMethodExists()
    {
        $reflection = new \ReflectionClass(ErrorHandler::class);
        $this->assertTrue($reflection->hasMethod('handleThrowableError'));
        $this->assertFalse($reflection->getMethod('handleThrowableError')->isStatic());
        $this->assertTrue($reflection->getMethod('handleThrowableError')->isPublic());
    }

    public function testRegisterNonThrowableErrorHandlerMethodExists()
    {
        $reflection = new \ReflectionClass(ErrorHandler::class);
        $this->assertTrue($reflection->hasMethod('registerNonThrowableErrorHandler'));
        $this->assertFalse($reflection->getMethod('registerNonThrowableErrorHandler')->isStatic());
        $this->assertTrue($reflection->getMethod('registerNonThrowableErrorHandler')->isPublic());
    }

    public function testDisableErrorDisplay()
    {
        $originalDisplayErrors = ini_get('display_errors');
        $originalDisplayStartupErrors = ini_get('display_startup_errors');
        $originalErrorReporting = error_reporting();

        $errorHandler = new ErrorHandler();
        $errorHandler->disableErrorDisplay();

        $this->assertEquals('0', ini_get('display_errors'));
        $this->assertEquals('0', ini_get('display_startup_errors'));
        $this->assertEquals(0, error_reporting());

        ini_set('display_errors', $originalDisplayErrors);
        ini_set('display_startup_errors', $originalDisplayStartupErrors);
        error_reporting($originalErrorReporting);
    }

    public function testErrorHandlerAcceptsLoggerInterface()
    {
        $loggerStub = $this->createStub(LoggerInterface::class);
        $errorHandler = new ErrorHandler($loggerStub);

        $this->assertInstanceOf(ErrorHandler::class, $errorHandler);
    }

    public function testHandleBaseExceptionLogsWhenShouldBeLoggedException()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
            ->willReturnMap([
                ['developmentEnvironment', true],
                ['logVerboseActive', false]
            ]);

        $loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Test loggable exception'),
                $this->callback(function ($context) {
                    return isset($context['code']) &&
                           isset($context['file']) &&
                           isset($context['line']);
                })
            );

        $defaultControllerStub = $this->createStub(DefaultControllerInterface::class);
        $structuralControllerStub = $this->createStub(StructuralControllerInterface::class);

        $errorHandler = new ErrorHandler($loggerMock, $configStub);
        $exception = new TestLoggableException('Test loggable exception', 500);

        try {
            $errorHandler->handleBaseException($exception, $defaultControllerStub, $structuralControllerStub);
        } catch (\Exception $e) {
        }
    }

    public function testHandleBaseExceptionDoesNotLogWhenNotShouldBeLoggedException()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
            ->willReturnMap([
                ['developmentEnvironment', true],
                ['logVerboseActive', false]
            ]);

        $loggerMock->expects($this->never())
            ->method('error');

        $defaultControllerStub = $this->createStub(DefaultControllerInterface::class);
        $structuralControllerStub = $this->createStub(StructuralControllerInterface::class);

        $errorHandler = new ErrorHandler($loggerMock, $configStub);
        $exception = new TestNonLoggableException('Test non-loggable exception', 400);

        try {
            $errorHandler->handleBaseException($exception, $defaultControllerStub, $structuralControllerStub);
        } catch (\Exception $e) {
        }
    }

    public function testHandleThrowableErrorAlwaysLogs()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
            ->willReturnMap([
                ['developmentEnvironment', true],
                ['logVerboseActive', false]
            ]);

        $loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Generic throwable'),
                $this->callback(function ($context) {
                    return isset($context['code']) &&
                           isset($context['file']) &&
                           isset($context['line']);
                })
            );

        $structuralControllerStub = $this->createStub(StructuralControllerInterface::class);

        $errorHandler = new ErrorHandler($loggerMock, $configStub);
        $throwable = new \Exception('Generic throwable', 999);

        try {
            $errorHandler->handleThrowableError($throwable, $structuralControllerStub);
        } catch (\Exception $e) {
        }
    }

    public function testLogIncludesTraceWhenVerboseActive()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
            ->willReturnMap([
                ['developmentEnvironment', true],
                ['logVerboseActive', true]
            ]);

        $loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->anything(),
                $this->callback(function ($context) {
                    return isset($context['trace']) && is_array($context['trace']);
                })
            );

        $defaultControllerStub = $this->createStub(DefaultControllerInterface::class);
        $structuralControllerStub = $this->createStub(StructuralControllerInterface::class);

        $errorHandler = new ErrorHandler($loggerMock, $configStub);
        $exception = new TestLoggableException('Test with trace', 500);

        try {
            $errorHandler->handleBaseException($exception, $defaultControllerStub, $structuralControllerStub);
        } catch (\Exception $e) {
        }
    }

    public function testLogDoesNotIncludeTraceWhenVerboseInactive()
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
            ->willReturnMap([
                ['developmentEnvironment', true],
                ['logVerboseActive', false]
            ]);

        $loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->anything(),
                $this->callback(function ($context) {
                    return !isset($context['trace']);
                })
            );

        $defaultControllerStub = $this->createStub(DefaultControllerInterface::class);
        $structuralControllerStub = $this->createStub(StructuralControllerInterface::class);

        $errorHandler = new ErrorHandler($loggerMock, $configStub);
        $exception = new TestLoggableException('Test without trace', 500);

        try {
            $errorHandler->handleBaseException($exception, $defaultControllerStub, $structuralControllerStub);
        } catch (\Exception $e) {
        }
    }
}
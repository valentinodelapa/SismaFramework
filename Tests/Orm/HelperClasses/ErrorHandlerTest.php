<?php

/*
 * The MIT License
 *
 * Copyright 2024 Valentino de Lapa.
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

namespace SismaFramework\Tests\Orm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\ErrorHandler;
use SismaFramework\Security\BaseClasses\BaseException;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\Interfaces\Controllers\DefaultControllerInterface;
use SismaFramework\Core\Interfaces\Controllers\StructuralControllerInterface;

/**
 * Description of ErrorHandlerTest
 *
 * @author Valentino de Lapa
 */
class ErrorHandlerTest extends TestCase
{
    public function testHandleBaseExceptionInDevelopmentEnvironment()
    {
        $defaultControllerMock = $this->createMock(DefaultControllerInterface::class);
        $structuralControllerMock = $this->createMock(StructuralControllerInterface::class);
        $baseExceptionMock = $this->createMock(BaseException::class);
        $structuralControllerMock->expects($this->once())
                ->method('throwableError')
                ->with($baseExceptionMock);
        ErrorHandler::setDevelopmentEnvironment(true);
        ErrorHandler::handleBaseException($baseExceptionMock,$defaultControllerMock, $structuralControllerMock);
    }
    
    public function testHandleBaseExceptionNotInDevelopmentEnvironment()
    {
        $defaultControllerMock = $this->createMock(DefaultControllerInterface::class);
        $structuralControllerMock = $this->createMock(StructuralControllerInterface::class);
        $baseExceptionMock = $this->createMock(BaseException::class);
        $defaultControllerMock->expects($this->once())
                ->method('error')
                ->with('', ResponseType::httpInternalServerError);
        $baseExceptionMock->expects($this->once())
                ->method('getResponseType')
                ->willReturn(ResponseType::httpInternalServerError);
        ErrorHandler::setDevelopmentEnvironment(false);
        ErrorHandler::handleBaseException($baseExceptionMock,$defaultControllerMock, $structuralControllerMock);
    }
    public function testHandleThrowableErrorInDevelopmentEnvironment()
    {
        $structuralControllerMock = $this->createMock(StructuralControllerInterface::class);
        $throwableMock = $this->createMock(\Throwable::class);
        $structuralControllerMock->expects($this->once())
                ->method('throwableError')
                ->with($throwableMock);
        ErrorHandler::setDevelopmentEnvironment(true);
        ErrorHandler::handleThrowableError($throwableMock, $structuralControllerMock);
    }
    
    public function testHandleThrowableErrorNotInDevelopmentEnvironment()
    {
        $structuralControllerMock = $this->createMock(StructuralControllerInterface::class);
        $throwableMock = $this->createMock(\Throwable::class);
        $structuralControllerMock->expects($this->once())
                ->method('internalServerError');
        ErrorHandler::setDevelopmentEnvironment(false);
        ErrorHandler::handleThrowableError($throwableMock, $structuralControllerMock);
    }
}

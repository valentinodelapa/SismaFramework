<?php

/*
 * The MIT License
 *
 * Copyright (c) 2024-present Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\CustomTypes;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\CustomTypes\FormFilterError;

/**
 * Description of FormFilterErrorTest
 *
 * @author Valentino de Lapa
 */
class FormFilterErrorTest extends TestCase
{
    public function testGetErrorsOnDirectlyForm()
    {
        $formFilterError = new FormFilterError();
        $this->assertFalse($formFilterError->errorNotInitializedError);
        $formFilterError->errorInitializedError = false;
        $formFilterError->errorInitializedCustomMessage = 'custom message';
        $this->assertFalse($formFilterError->errorInitializedError);
        $this->assertEquals('custom message', $formFilterError->errorInitializedCustomMessage);
        $formFilterError->errorInitializedError = true;
        $formFilterError->errorInitializedCustomMessage = 'custom message';
        $this->assertTrue($formFilterError->errorInitializedError);
        $this->assertEquals('custom message', $formFilterError->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsOnForeignKeyFormNotInitialized()
    {
        $foreignKeyFormFilterError = new FormFilterError();
        $foreignKeyFormMock = $this->createMock(BaseForm::class);
        $foreignKeyFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($foreignKeyFormFilterError);
        $formFilterError = new FormFilterError();
        $this->assertInstanceOf(FormFilterError::class, $formFilterError->foreignKeyForm);
        $this->assertFalse($formFilterError->foreignKeyForm->errorNotInitializedError);
        $this->assertFalse($formFilterError->foreignKeyForm->errorNotInitializedCustomMessage);
        $formFilterError->generateFormFilterErrorFromForm(['foreignKeyForm' => $foreignKeyFormMock]);
        $this->assertInstanceOf(FormFilterError::class, $formFilterError->foreignKeyForm);
        $this->assertFalse($formFilterError->foreignKeyForm->errorNotInitializedError);
        $this->assertFalse($formFilterError->foreignKeyForm->errorNotInitializedCustomMessage);
    }
    
    public function testGetErrorsOnForeignKeyFormInitializedFalse()
    {
        $foreignKeyFormFilterError = new FormFilterError();
        $foreignKeyFormFilterError->errorInitializedError = false;
        $foreignKeyFormFilterError->errorInitializedCustomMessage = 'custom message';
        $foreignKeyFormMock = $this->createMock(BaseForm::class);
        $foreignKeyFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($foreignKeyFormFilterError);
        $formFilterError = new FormFilterError();
        $formFilterError->generateFormFilterErrorFromForm(['foreignKeyForm' => $foreignKeyFormMock]);
        $this->assertInstanceOf(FormFilterError::class, $formFilterError->foreignKeyForm);
        $this->assertFalse($formFilterError->foreignKeyForm->errorInitializedError);
        $this->assertEquals('custom message', $formFilterError->foreignKeyForm->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsOnForeignKeyFormInitializedTrue()
    {
        $foreignKeyFormFilterError = new FormFilterError();
        $foreignKeyFormFilterError->errorInitializedError = true;
        $foreignKeyFormFilterError->errorInitializedCustomMessage = 'custom message';
        $foreignKeyFormMock = $this->createMock(BaseForm::class);
        $foreignKeyFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($foreignKeyFormFilterError);
        $formFilterError = new FormFilterError();
        $formFilterError->generateFormFilterErrorFromForm(['foreignKeyForm' => $foreignKeyFormMock]);
        $this->assertInstanceOf(FormFilterError::class, $formFilterError->foreignKeyForm);
        $this->assertTrue($formFilterError->foreignKeyForm->errorInitializedError);
        $this->assertEquals('custom message', $formFilterError->foreignKeyForm->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsOnSismaCollectionFormNotInitialized()
    {
        $sismaCollectionFormFilterError = new FormFilterError();
        $sismaCollectionFormMock = $this->createMock(BaseForm::class);
        $sismaCollectionFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($sismaCollectionFormFilterError);
        $formFilterError = new FormFilterError();
        $this->assertInstanceOf(FormFilterError::class, $formFilterError->sismaCollectionForm[0]);
        $this->assertFalse($formFilterError->sismaCollectionForm[0]->errorNotInitializedError);
        $this->assertFalse($formFilterError->sismaCollectionForm[0]->errorNotInitializedCustomMessage);
        $formFilterError->generateFormFilterErrorFromForm(['sismaCollectionForm' => [$sismaCollectionFormMock]]);
        $this->assertInstanceOf(FormFilterError::class, $formFilterError->sismaCollectionForm[0]);
        $this->assertFalse($formFilterError->sismaCollectionForm[0]->errorNotInitializedError);
        $this->assertFalse($formFilterError->sismaCollectionForm[0]->errorNotInitializedCustomMessage);
        $this->assertFalse($formFilterError->sismaCollectionForm[1]->errorNotInitializedError);
        $this->assertFalse($formFilterError->sismaCollectionForm[1]->errorNotInitializedCustomMessage);
    }
    
    public function testGetErrorsOnSismaCollectionFormInitializedFalse()
    {
        $sismaCollectionFormFilterError = new FormFilterError();
        $sismaCollectionFormFilterError->errorInitializedError = false;
        $sismaCollectionFormFilterError->errorInitializedCustomMessage = 'custom message';
        $sismaCollectionFormMock = $this->createMock(BaseForm::class);
        $sismaCollectionFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($sismaCollectionFormFilterError);
        $formFilterError = new FormFilterError();
        $formFilterError->generateFormFilterErrorFromForm(['sismaCollectionForm' => [$sismaCollectionFormMock]]);
        $this->assertInstanceOf(FormFilterError::class, $formFilterError->sismaCollectionForm[0]);
        $this->assertFalse($formFilterError->sismaCollectionForm[0]->errorInitializedError);
        $this->assertEquals('custom message', $formFilterError->sismaCollectionForm[0]->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsOnSismaCollectionFormInitializedTrue()
    {
        $sismaCollectionFormFilterError = new FormFilterError();
        $sismaCollectionFormFilterError->errorInitializedError = true;
        $sismaCollectionFormFilterError->errorInitializedCustomMessage = 'custom message';
        $sismaCollectionFormMock = $this->createMock(BaseForm::class);
        $sismaCollectionFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($sismaCollectionFormFilterError);
        $formFilterError = new FormFilterError();
        $formFilterError->generateFormFilterErrorFromForm(['sismaCollectionForm' => [$sismaCollectionFormMock]]);
        $this->assertInstanceOf(FormFilterError::class, $formFilterError->sismaCollectionForm[0]);
        $this->assertTrue($formFilterError->sismaCollectionForm[0]->errorInitializedError);
        $this->assertEquals('custom message', $formFilterError->sismaCollectionForm[0]->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsToArray()
    {
        $foreignKeyFormFilterError = new FormFilterError();
        $foreignKeyFormFilterError->errorInitializedError = true;
        $foreignKeyFormFilterError->errorInitializedCustomMessage = 'custom message';
        $foreignKeyFormMock = $this->createMock(BaseForm::class);
        $foreignKeyFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($foreignKeyFormFilterError);
        $sismaCollectionFormFilterError = new FormFilterError();
        $sismaCollectionFormFilterError->errorInitializedError = true;
        $sismaCollectionFormFilterError->errorInitializedCustomMessage = 'custom message';
        $sismaCollectionFormMock = $this->createMock(BaseForm::class);
        $sismaCollectionFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($sismaCollectionFormFilterError);
        $formFilterError = new FormFilterError();
        $formFilterError->errorInitializedError = true;
        $formFilterError->errorInitializedCustomMessage = 'custom message';
        $formFilterError->generateFormFilterErrorFromForm(['foreignKeyForm' => $foreignKeyFormMock]);
        $formFilterError->generateFormFilterErrorFromForm(['sismaCollectionForm' => [$sismaCollectionFormMock]]);
        $errorArray = $formFilterError->getErrorsToArray();
        $this->assertArrayHasKey('errorInitializedError', $errorArray);
        $this->assertArrayHasKey('foreignKeyForm', $errorArray);
        $this->assertArrayHasKey('errorInitializedError', $errorArray['foreignKeyForm']);
        $this->assertArrayHasKey('sismaCollectionForm', $errorArray);
        $this->assertArrayHasKey(0, $errorArray['sismaCollectionForm']);
        $this->assertArrayHasKey('errorInitializedError', $errorArray['sismaCollectionForm'][0]);
    }
}

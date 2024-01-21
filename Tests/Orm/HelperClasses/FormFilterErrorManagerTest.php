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

namespace SismaFramework\Tests\Orm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\HelperClasses\FormFilterErrorManager;

/**
 * Description of FormFilterErrorTest
 *
 * @author Valentino de Lapa
 */
class FormFilterErrorManagerTest extends TestCase
{
    public function testGetErrorsOnDirectlyForm()
    {
        $formFilterErrorManager = new FormFilterErrorManager();
        $this->assertFalse($formFilterErrorManager->errorNotInitializedError);
        $formFilterErrorManager->errorInitializedError = false;
        $formFilterErrorManager->errorInitializedCustomMessage = 'custom message';
        $this->assertFalse($formFilterErrorManager->errorInitializedError);
        $this->assertEquals('custom message', $formFilterErrorManager->errorInitializedCustomMessage);
        $formFilterErrorManager->errorInitializedError = true;
        $formFilterErrorManager->errorInitializedCustomMessage = 'custom message';
        $this->assertTrue($formFilterErrorManager->errorInitializedError);
        $this->assertEquals('custom message', $formFilterErrorManager->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsOnForeignKeyFormNotInitialized()
    {
        $foreignKeyFormFilterErrorManager = new FormFilterErrorManager();
        $foreignKeyFormMock = $this->createMock(BaseForm::class);
        $foreignKeyFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($foreignKeyFormFilterErrorManager);
        $formFilterErrorManager = new FormFilterErrorManager();
        $formFilterErrorManager->generateFormFilterErrorManagerFromForm(['foreignKeyForm' => $foreignKeyFormMock]);
        $this->assertInstanceOf(FormFilterErrorManager::class, $formFilterErrorManager->foreignKeyForm);
        $this->assertFalse($formFilterErrorManager->foreignKeyForm->errorNotInitializedError);
        $this->assertFalse($formFilterErrorManager->foreignKeyForm->errorNotInitializedCustomMessage);
    }
    
    public function testGetErrorsOnForeignKeyFormInitializedFalse()
    {
        $foreignKeyFormFilterErrorManager = new FormFilterErrorManager();
        $foreignKeyFormFilterErrorManager->errorInitializedError = false;
        $foreignKeyFormFilterErrorManager->errorInitializedCustomMessage = 'custom message';
        $foreignKeyFormMock = $this->createMock(BaseForm::class);
        $foreignKeyFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($foreignKeyFormFilterErrorManager);
        $formFilterErrorManager = new FormFilterErrorManager();
        $formFilterErrorManager->generateFormFilterErrorManagerFromForm(['foreignKeyForm' => $foreignKeyFormMock]);
        $this->assertInstanceOf(FormFilterErrorManager::class, $formFilterErrorManager->foreignKeyForm);
        $this->assertFalse($formFilterErrorManager->foreignKeyForm->errorInitializedError);
        $this->assertEquals('custom message', $formFilterErrorManager->foreignKeyForm->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsOnForeignKeyFormInitializedTrue()
    {
        $foreignKeyFormFilterErrorManager = new FormFilterErrorManager();
        $foreignKeyFormFilterErrorManager->errorInitializedError = true;
        $foreignKeyFormFilterErrorManager->errorInitializedCustomMessage = 'custom message';
        $foreignKeyFormMock = $this->createMock(BaseForm::class);
        $foreignKeyFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($foreignKeyFormFilterErrorManager);
        $formFilterErrorManager = new FormFilterErrorManager();
        $formFilterErrorManager->generateFormFilterErrorManagerFromForm(['foreignKeyForm' => $foreignKeyFormMock]);
        $this->assertInstanceOf(FormFilterErrorManager::class, $formFilterErrorManager->foreignKeyForm);
        $this->assertTrue($formFilterErrorManager->foreignKeyForm->errorInitializedError);
        $this->assertEquals('custom message', $formFilterErrorManager->foreignKeyForm->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsOnSismaCollectionFormNotInitialized()
    {
        $sismaCollectionFormFilterErrorManager = new FormFilterErrorManager();
        $sismaCollectionFormMock = $this->createMock(BaseForm::class);
        $sismaCollectionFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($sismaCollectionFormFilterErrorManager);
        $formFilterErrorManager = new FormFilterErrorManager();
        $formFilterErrorManager->generateFormFilterErrorManagerFromForm(['sismaCollectionForm' => [$sismaCollectionFormMock]]);
        $this->assertInstanceOf(FormFilterErrorManager::class, $formFilterErrorManager->sismaCollectionForm[0]);
        $this->assertFalse($formFilterErrorManager->sismaCollectionForm[0]->errorNotInitializedError);
        $this->assertFalse($formFilterErrorManager->sismaCollectionForm[0]->errorNotInitializedCustomMessage);
        $this->assertFalse($formFilterErrorManager->sismaCollectionForm[1]->errorNotInitializedError);
        $this->assertFalse($formFilterErrorManager->sismaCollectionForm[1]->errorNotInitializedCustomMessage);
    }
    
    public function testGetErrorsOnSismaCollectionFormInitializedFalse()
    {
        $sismaCollectionFormFilterErrorManager = new FormFilterErrorManager();
        $sismaCollectionFormFilterErrorManager->errorInitializedError = false;
        $sismaCollectionFormFilterErrorManager->errorInitializedCustomMessage = 'custom message';
        $sismaCollectionFormMock = $this->createMock(BaseForm::class);
        $sismaCollectionFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($sismaCollectionFormFilterErrorManager);
        $formFilterErrorManager = new FormFilterErrorManager();
        $formFilterErrorManager->generateFormFilterErrorManagerFromForm(['sismaCollectionForm' => [$sismaCollectionFormMock]]);
        $this->assertInstanceOf(FormFilterErrorManager::class, $formFilterErrorManager->sismaCollectionForm[0]);
        $this->assertFalse($formFilterErrorManager->sismaCollectionForm[0]->errorInitializedError);
        $this->assertEquals('custom message', $formFilterErrorManager->sismaCollectionForm[0]->errorInitializedCustomMessage);
    }
    
    public function testGetErrorsOnSismaCollectionFormInitializedTrue()
    {
        $sismaCollectionFormFilterErrorManager = new FormFilterErrorManager();
        $sismaCollectionFormFilterErrorManager->errorInitializedError = true;
        $sismaCollectionFormFilterErrorManager->errorInitializedCustomMessage = 'custom message';
        $sismaCollectionFormMock = $this->createMock(BaseForm::class);
        $sismaCollectionFormMock->expects($this->once())
                ->method('getFilterErrors')
                ->willReturn($sismaCollectionFormFilterErrorManager);
        $formFilterErrorManager = new FormFilterErrorManager();
        $formFilterErrorManager->generateFormFilterErrorManagerFromForm(['sismaCollectionForm' => [$sismaCollectionFormMock]]);
        $this->assertInstanceOf(FormFilterErrorManager::class, $formFilterErrorManager->sismaCollectionForm[0]);
        $this->assertTrue($formFilterErrorManager->sismaCollectionForm[0]->errorInitializedError);
        $this->assertEquals('custom message', $formFilterErrorManager->sismaCollectionForm[0]->errorInitializedCustomMessage);
    }
}

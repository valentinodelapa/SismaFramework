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

namespace SismaFramework\Tests\Core\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Entities\FakeBaseSample;
use SismaFramework\Sample\Entities\FakeReferencedSample;
use SismaFramework\Sample\Entities\ReferencedSample;
use SismaFramework\Sample\Forms\BaseSampleForm;
use SismaFramework\Sample\Forms\EntityNotInitializedForm;
use SismaFramework\Sample\Forms\FakeBaseSampleForm;
use SismaFramework\Sample\Forms\FakeReferencedSampleForm;
use SismaFramework\Sample\Forms\ReferencedSampleForm;
use SismaFramework\Core\Exceptions\FormException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\Adapters\AdapterMysql;

/**
 * Description of BaseFormTest
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class BaseFormTest extends TestCase
{
    private SampleForm $sampleForm;
    
    public function testFormForBaseEntityNotSubmitted()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $baseSampleForm = new BaseSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertFalse($baseSampleForm->isSubmitted());
        $this->assertFalse($baseSampleForm->isValid());
    }
    
    public function testFormForBaseEntitySubmittedNotValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $baseSampleForm = new BaseSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'submitted' => 'on'
        ];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertTrue($baseSampleForm->isSubmitted());
        $this->assertFalse($baseSampleForm->isValid());
        $filterErrors = $baseSampleForm->returnFilterErrors();
        $this->assertArrayHasKey('textError', $filterErrors);
        $this->assertTrue($filterErrors['textError']);
        $this->assertArrayHasKey('referencedSampleError', $filterErrors);
    }
    
    public function testFormForBaseEntitySubmittedValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $baseSampleForm = new BaseSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'base sample',
            'referencedSample' => [
                'text' => 'referenced sample',
            ],
            'submitted' => 'on'
        ];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertTrue($baseSampleForm->isSubmitted());
        $this->assertTrue($baseSampleForm->isValid());
        $baseSampleResult = $baseSampleForm->resolveEntity();
        $this->assertInstanceOf(BaseSample::class, $baseSampleResult);
        $this->assertEquals('base sample', $baseSampleResult->text);
        $this->assertInstanceOf(ReferencedSample::class, $baseSampleResult->referencedSample);
        $this->assertEquals('referenced sample', $baseSampleResult->referencedSample->text);
    }
    
    public function testFormUpdateForBaseEntitySubmittedValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $baseSample = new BaseSample($adapterMysqlMock);
        $baseSample->id = 1;
        $baseSample->referencedSample = new ReferencedSample($adapterMysqlMock);
        $baseSample->referencedSample->id = 2;
        $baseSampleForm = new BaseSampleForm($baseSample, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'base sample',
            'referencedSample' => [
                'text' => 'referenced sample',
            ],
            'submitted' => 'on'
        ];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertTrue($baseSampleForm->isSubmitted());
        $this->assertTrue($baseSampleForm->isValid());
        $baseSampleResult = $baseSampleForm->resolveEntity();
        $this->assertInstanceOf(BaseSample::class, $baseSampleResult);
        $this->assertEquals(1, $baseSampleResult->id);
        $this->assertEquals('base sample', $baseSampleResult->text);
        $this->assertInstanceOf(ReferencedSample::class, $baseSampleResult->referencedSample);
        $this->assertEquals(2, $baseSampleResult->referencedSample->id);
        $this->assertEquals('referenced sample', $baseSampleResult->referencedSample->text);
    }
    
    public function testFormForReferencedEntityWithCollectionNotValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $referencedSampleForm = new ReferencedSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollection' => [[],[],],
            'submitted' => 'on'
        ];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($referencedSampleForm->isSubmitted());
        $this->assertFalse($referencedSampleForm->isValid());
    }
    
    public function testFormForReferencedEntityWithCollectionValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $referencedSampleForm = new ReferencedSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollection' => [
                ['text' => 'base sample one'],
                ['text' => 'base sample two'],
            ],
            'submitted' => 'on'
        ];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($referencedSampleForm->isSubmitted());
        $this->assertTrue($referencedSampleForm->isValid());
        $referencedSampleResult = $referencedSampleForm->resolveEntity();
        $this->assertInstanceOf(ReferencedSample::class, $referencedSampleResult);
        $this->assertCount(2, $referencedSampleResult->baseSampleCollection);
        $this->assertEquals('base sample one', $referencedSampleResult->baseSampleCollection[0]->text);
        $this->assertEquals('base sample two', $referencedSampleResult->baseSampleCollection[1]->text);
    }
    
    public function testFormUpdateForReferencedEntityWithCollectionValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $referencedSample = new ReferencedSample($adapterMysqlMock);
        $referencedSample->id = 1;
        $referencedSample->addBaseSample(new BaseSample($adapterMysqlMock));
        $referencedSample->addBaseSample(new BaseSample($adapterMysqlMock));
        $referencedSample->baseSampleCollection[0]->id = 2;
        $referencedSample->baseSampleCollection[1]->id = 3;
        $referencedSampleForm = new ReferencedSampleForm($referencedSample, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollection' => [
                ['text' => 'base sample one'],
                ['text' => 'base sample two'],
            ],
            'submitted' => 'on'
        ];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($referencedSampleForm->isSubmitted());
        $this->assertTrue($referencedSampleForm->isValid());
        $referencedSampleResult = $referencedSampleForm->resolveEntity();
        $this->assertInstanceOf(ReferencedSample::class, $referencedSampleResult);
        $this->assertEquals(1, $referencedSampleResult->id);
        $this->assertCount(2, $referencedSampleResult->baseSampleCollection);
        $this->assertEquals('base sample one', $referencedSampleResult->baseSampleCollection[0]->text);
        $this->assertEquals('base sample two', $referencedSampleResult->baseSampleCollection[1]->text);
        $this->assertEquals(2, $referencedSampleResult->baseSampleCollection[0]->id);
        $this->assertEquals(3, $referencedSampleResult->baseSampleCollection[1]->id);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFormWhithNotInitializedEntity()
    {
        $this->expectException(FormException::class);
        $entityNotInitializedForm = new EntityNotInitializedForm();
    }

    /**
     * @runInSeparateProcess
     */
    public function testFormWithNotValidEntity()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $this->expectException(InvalidArgumentException::class);
        $baseSampleForm = new BaseSampleForm(new ReferencedSample($adapterMysqlMock));
    }

    /**
     * @runInSeparateProcess
     */
    public function testFormUpdateWithNotValidReferencedEntityType()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $this->expectException(InvalidArgumentException::class);
        $fakeBaseSample = new FakeBaseSample($adapterMysqlMock);
        $fakeBaseSample->fakeReferencedSample = new FakeReferencedSample($adapterMysqlMock);
        $fakeBaseSampleForm = new FakeBaseSampleForm($fakeBaseSample, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $fakeBaseSampleForm->handleRequest($requestMock);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFormUpdateWithNotValidReferencedEntityTypeInCollection()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $this->expectException(InvalidArgumentException::class);
        $fakeReferencedSample = new FakeReferencedSample($adapterMysqlMock);
        $fakeReferencedSample->addFakeBaseSample(new FakeBaseSample($adapterMysqlMock));
        $fakeReferencedSample->addFakeBaseSample(new FakeBaseSample($adapterMysqlMock));
        $fakeReferencedSampleForm = new FakeReferencedSampleForm($fakeReferencedSample, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $fakeReferencedSampleForm->handleRequest($requestMock);
    }
}

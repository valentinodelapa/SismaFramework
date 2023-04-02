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
use SismaFramework\Sample\Entities\OtherReferencedSample;
use SismaFramework\Sample\Entities\ReferencedSample;
use SismaFramework\Sample\Entities\SelfReferencedSample;
use SismaFramework\Sample\Forms\BaseSampleForm;
use SismaFramework\Sample\Forms\EntityNotInitializedForm;
use SismaFramework\Sample\Forms\FakeBaseSampleForm;
use SismaFramework\Sample\Forms\FakeReferencedSampleForm;
use SismaFramework\Sample\Forms\OtherReferencedSampleForm;
use SismaFramework\Sample\Forms\ReferencedSampleForm;
use SismaFramework\Sample\Forms\SelfReferencedSampleForm;
use SismaFramework\Core\Exceptions\FormException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\Adapters\AdapterMysql;
use SismaFramework\ProprietaryTypes\SismaCollection;

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
            'nullableSecureString' => 'is not secure',
            'submitted' => 'on'
        ];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertTrue($baseSampleForm->isSubmitted());
        $this->assertFalse($baseSampleForm->isValid());
        $filterErrors = $baseSampleForm->returnFilterErrors();
        $this->assertArrayHasKey('textError', $filterErrors);
        $this->assertTrue($filterErrors['textError']);
        $this->assertArrayHasKey('referencedSampleError', $filterErrors);
        $this->assertTrue($filterErrors['referencedSampleError']['textError']);
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

    public function testFormForReferencedEntityNotSubmitted()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $referencedSampleForm = new ReferencedSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertFalse($referencedSampleForm->isSubmitted());
        $this->assertFalse($referencedSampleForm->isValid());
    }

    public function testFormForReferencedEntityWithCollectionNotValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $referencedSampleForm = new ReferencedSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollectionReferencedSample' => [[], [],],
            'submitted' => 'on'
        ];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($referencedSampleForm->isSubmitted());
        $this->assertFalse($referencedSampleForm->isValid());
        $filterErrors = $referencedSampleForm->returnFilterErrors();
        $this->assertArrayHasKey('baseSampleCollectionReferencedSampleError', $filterErrors);
        $this->assertCount(2, $filterErrors['baseSampleCollectionReferencedSampleError']);
        $this->assertTrue($filterErrors['baseSampleCollectionReferencedSampleError'][0]['textError']);
        $this->assertTrue($filterErrors['baseSampleCollectionReferencedSampleError'][1]['textError']);
    }

    public function testFormForReferencedEntityWithCollectionValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $referencedSampleForm = new ReferencedSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollectionReferencedSample' => [
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
        $this->assertCount(2, $referencedSampleResult->baseSampleCollectionReferencedSample);
        $this->assertEquals('base sample one', $referencedSampleResult->baseSampleCollectionReferencedSample[0]->text);
        $this->assertEquals('base sample two', $referencedSampleResult->baseSampleCollectionReferencedSample[1]->text);
    }

    public function testFormUpdateForReferencedEntityWithCollectionValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $referencedSample = new ReferencedSample($adapterMysqlMock);
        $referencedSample->id = 1;
        $baseSampleCollection = new SismaCollection(BaseSample::class);
        $baseSampleCollection->append(new BaseSample($adapterMysqlMock));
        $baseSampleCollection->append(new BaseSample($adapterMysqlMock));
        $referencedSample->setBaseSampleCollectionReferencedSample($baseSampleCollection);
        $referencedSample->baseSampleCollectionReferencedSample[0]->id = 2;
        $referencedSample->baseSampleCollectionReferencedSample[1]->id = 3;
        $referencedSample->setBaseSampleCollectionReferencedSampleTwo(new SismaCollection(BaseSample::class));
        $referencedSampleForm = new ReferencedSampleForm($referencedSample, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollectionReferencedSample' => [
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
        $this->assertCount(2, $referencedSampleResult->baseSampleCollectionReferencedSample);
        $this->assertEquals('base sample one', $referencedSampleResult->baseSampleCollectionReferencedSample[0]->text);
        $this->assertEquals('base sample two', $referencedSampleResult->baseSampleCollectionReferencedSample[1]->text);
        $this->assertEquals(2, $referencedSampleResult->baseSampleCollectionReferencedSample[0]->id);
        $this->assertEquals(3, $referencedSampleResult->baseSampleCollectionReferencedSample[1]->id);
    }

    public function testFormForOtherReferencedEntityWithCollectionNotValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $otherReferencedSampleForm = new OtherReferencedSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollection' => [[], []],
            'submitted' => 'on'
        ];
        $otherReferencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($otherReferencedSampleForm->isSubmitted());
        $this->assertFalse($otherReferencedSampleForm->isValid());
        $filterErrors = $otherReferencedSampleForm->returnFilterErrors();
        $this->assertArrayHasKey('baseSampleCollectionError', $filterErrors);
        $this->assertCount(2, $filterErrors['baseSampleCollectionError']);
        $this->assertTrue($filterErrors['baseSampleCollectionError'][0]['textError']);
        $this->assertTrue($filterErrors['baseSampleCollectionError'][1]['textError']);
    }

    public function testFormForOtherReferencedEntityWithCollectionValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $otherReferencedSampleForm = new OtherReferencedSampleForm(null, $adapterMysqlMock);
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
        $otherReferencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($otherReferencedSampleForm->isSubmitted());
        $this->assertTrue($otherReferencedSampleForm->isValid());
        $otherReferencedSampleResult = $otherReferencedSampleForm->resolveEntity();
        $this->assertInstanceOf(OtherReferencedSample::class, $otherReferencedSampleResult);
        $this->assertEquals('referenced sample', $otherReferencedSampleResult->text);
        $this->assertCount(2, $otherReferencedSampleResult->baseSampleCollection);
        $this->assertEquals('base sample one', $otherReferencedSampleResult->baseSampleCollection[0]->text);
        $this->assertEquals('base sample two', $otherReferencedSampleResult->baseSampleCollection[1]->text);
    }

    public function testFormUpdateForOtherReferencedEntityWithCollectionValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $otherReferencedSample = new OtherReferencedSample($adapterMysqlMock);
        $otherReferencedSample->id = 1;
        $baseSampleCollection = new SismaCollection(BaseSample::class);
        $baseSampleCollection->append(new BaseSample($adapterMysqlMock));
        $baseSampleCollection->append(new BaseSample($adapterMysqlMock));
        $otherReferencedSample->setBaseSampleCollection($baseSampleCollection);
        $otherReferencedSample->baseSampleCollection[0]->id = 2;
        $otherReferencedSample->baseSampleCollection[1]->id = 3;
        $otherReferencedSampleForm = new OtherReferencedSampleForm($otherReferencedSample, $adapterMysqlMock);
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
        $otherReferencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($otherReferencedSampleForm->isSubmitted());
        $this->assertTrue($otherReferencedSampleForm->isValid());
        $otherReferencedSampleResult = $otherReferencedSampleForm->resolveEntity();
        $this->assertInstanceOf(OtherReferencedSample::class, $otherReferencedSampleResult);
        $this->assertEquals(1, $otherReferencedSampleResult->id);
        $this->assertEquals('referenced sample', $otherReferencedSampleResult->text);
        $this->assertCount(2, $otherReferencedSampleResult->baseSampleCollection);
        $this->assertEquals('base sample one', $otherReferencedSampleResult->baseSampleCollection[0]->text);
        $this->assertEquals('base sample two', $otherReferencedSampleResult->baseSampleCollection[1]->text);
        $this->assertEquals(2, $otherReferencedSampleResult->baseSampleCollection[0]->id);
        $this->assertEquals(3, $otherReferencedSampleResult->baseSampleCollection[1]->id);
    }
    
    public function testFormForSelfReferencedEntityNotValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $selfReferencedSampleForm = new SelfReferencedSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'self referenced sample one',
            'sonCollection' => [[],[]],
            'submitted' => 'on'
        ];
        $selfReferencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($selfReferencedSampleForm->isSubmitted());
        $this->assertFalse($selfReferencedSampleForm->isValid());
        $filterErrors = $selfReferencedSampleForm->returnFilterErrors();
        $this->assertArrayHasKey('sonCollectionError', $filterErrors);
        $this->assertCount(2, $filterErrors['sonCollectionError']);
        $this->assertTrue($filterErrors['sonCollectionError'][0]['textError']);
        $this->assertTrue($filterErrors['sonCollectionError'][1]['textError']);
    }
    
    public function testFormForSelfReferencedEntityValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $selfReferencedSampleForm = new SelfReferencedSampleForm(null, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'self referenced sample one',
            'sonCollection' => [
                ['text' => 'self referenced sample two'],
                ['text' => 'self referenced sample three'],
            ],
            'submitted' => 'on'
        ];
        $selfReferencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($selfReferencedSampleForm->isSubmitted());
        $this->assertTrue($selfReferencedSampleForm->isValid());
        $selfReferencedSampleResult = $selfReferencedSampleForm->resolveEntity();
        $this->assertInstanceOf(SelfReferencedSample::class, $selfReferencedSampleResult);
        $this->assertEquals('self referenced sample one', $selfReferencedSampleResult->text);
        $this->assertCount(2, $selfReferencedSampleResult->sonCollection);
        $this->assertEquals('self referenced sample two', $selfReferencedSampleResult->sonCollection[0]->text);
        $this->assertEquals('self referenced sample three', $selfReferencedSampleResult->sonCollection[1]->text);
    }
    
    public function testFormUpdateForSelfReferencedEntityValid()
    {
        $adapterMysqlMock = $this->createMock(AdapterMysql::class);
        $selfReferencedSample = new SelfReferencedSample($adapterMysqlMock);
        $selfReferencedSample->id = 1;
        $sonCollection = new SismaCollection(SelfReferencedSample::class);
        $sonSelfReferencedSampleOne = new SelfReferencedSample($adapterMysqlMock);
        $sonSelfReferencedSampleOne->sonCollection = new SismaCollection(SelfReferencedSample::class);
        $sonCollection->append($sonSelfReferencedSampleOne);
        $sonSelfReferencedSampleTwo = new SelfReferencedSample($adapterMysqlMock);
        $sonSelfReferencedSampleTwo->sonCollection = new SismaCollection(SelfReferencedSample::class);
        $sonCollection->append($sonSelfReferencedSampleTwo);
        $selfReferencedSample->setSonCollection($sonCollection);
        $selfReferencedSample->sonCollection[0]->id = 2;
        $selfReferencedSample->sonCollection[1]->id = 3;
        $selfReferencedSampleForm = new SelfReferencedSampleForm($selfReferencedSample, $adapterMysqlMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'self referenced sample one',
            'sonCollection' => [
                ['text' => 'self referenced sample two'],
                ['text' => 'self referenced sample three'],
            ],
            'submitted' => 'on'
        ];
        $selfReferencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($selfReferencedSampleForm->isSubmitted());
        $this->assertTrue($selfReferencedSampleForm->isValid());
        $selfReferencedSampleResult = $selfReferencedSampleForm->resolveEntity();
        $this->assertInstanceOf(SelfReferencedSample::class, $selfReferencedSampleResult);
        $this->assertEquals(1, $selfReferencedSampleResult->id);
        $this->assertEquals('self referenced sample one', $selfReferencedSampleResult->text);
        $this->assertCount(2, $selfReferencedSampleResult->sonCollection);
        $this->assertEquals(2, $selfReferencedSampleResult->sonCollection[0]->id);
        $this->assertEquals('self referenced sample two', $selfReferencedSampleResult->sonCollection[0]->text);
        $this->assertEquals(3, $selfReferencedSampleResult->sonCollection[1]->id);
        $this->assertEquals('self referenced sample three', $selfReferencedSampleResult->sonCollection[1]->text);
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

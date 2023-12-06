<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa.
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
use SismaFramework\Core\ExtendedClasses\StandardEntity;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\ProprietaryTypes\SismaCollection;

/**
 * Description of BaseFormTest
 *
 * @author Valentino de Lapa
 */
class BaseFormTest extends TestCase
{

    private DataMapper $dataMapperMock;

    public function __construct($name = null, $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }

    public function testFormForBaseEntityNotSubmitted()
    {
        $baseSampleForm = new BaseSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertFalse($baseSampleForm->isSubmitted());
        $this->assertFalse($baseSampleForm->isValid());
    }

    public function testFormForBaseEntitySubmittedNotValid()
    {
        $baseSampleForm = new BaseSampleForm(null, $this->dataMapperMock);
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
        $this->assertArrayHasKey('nullableSecureStringError', $filterErrors);
        $this->assertTrue($filterErrors['nullableSecureStringError']);
        $this->assertArrayHasKey('referencedEntityWithoutInitializationError', $filterErrors);
        $this->assertTrue($filterErrors['referencedEntityWithoutInitializationError']['textError']);
    }

    public function testFormForBaseEntityWithForeignKeySubmittedNotValid()
    {
        $baseSampleForm = new BaseSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'referencedEntityWithoutInitialization' => [
                'text' => 'referenced sample',
            ],
            'submitted' => 'on'
        ];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertTrue($baseSampleForm->isSubmitted());
        $this->assertFalse($baseSampleForm->isValid());
        $filterErrors = $baseSampleForm->returnFilterErrors();
        $this->assertArrayHasKey('stringWithoutInizializationError', $filterErrors);
        $this->assertTrue($filterErrors['stringWithoutInizializationError']);
        $baseSampleResult = $baseSampleForm->getEntityDataToStandardEntity();
        $this->assertInstanceOf(StandardEntity::class, $baseSampleResult);
        $this->assertInstanceOf(StandardEntity::class, $baseSampleResult->referencedEntityWithoutInitialization);
        $this->assertEquals('referenced sample', $baseSampleResult->referencedEntityWithoutInitialization->text);
    }

    public function testFormForBaseEntitySubmittedValid()
    {
        $baseSampleForm = new BaseSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'stringWithoutInizialization' => 'base sample',
            'referencedEntityWithoutInitialization' => [
                'text' => 'referenced sample',
            ],
            'submitted' => 'on'
        ];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertTrue($baseSampleForm->isSubmitted());
        $this->assertTrue($baseSampleForm->isValid());
        $baseSampleResult = $baseSampleForm->resolveEntity();
        $this->assertInstanceOf(BaseSample::class, $baseSampleResult);
        $this->assertEquals('base sample', $baseSampleResult->stringWithoutInizialization);
        $this->assertInstanceOf(ReferencedSample::class, $baseSampleResult->referencedEntityWithoutInitialization);
        $this->assertEquals('referenced sample', $baseSampleResult->referencedEntityWithoutInitialization->text);
    }

    public function testFormUpdateForBaseEntitySubmittedValid()
    {
        $baseSample = new BaseSample($this->dataMapperMock);
        $baseSample->id = 1;
        $baseSample->referencedEntityWithoutInitialization = new ReferencedSample($this->dataMapperMock);
        $baseSample->referencedEntityWithoutInitialization->id = 2;
        $baseSampleForm = new BaseSampleForm($baseSample, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'stringWithoutInizialization' => 'base sample',
            'referencedEntityWithoutInitialization' => [
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
        $this->assertEquals('base sample', $baseSampleResult->stringWithoutInizialization);
        $this->assertInstanceOf(ReferencedSample::class, $baseSampleResult->referencedEntityWithoutInitialization);
        $this->assertEquals(2, $baseSampleResult->referencedEntityWithoutInitialization->id);
        $this->assertEquals('referenced sample', $baseSampleResult->referencedEntityWithoutInitialization->text);
    }

    public function testFormForReferencedEntityNotSubmitted()
    {
        $referencedSampleForm = new ReferencedSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertFalse($referencedSampleForm->isSubmitted());
        $this->assertFalse($referencedSampleForm->isValid());
    }

    public function testFormForReferencedEntityWithCollectionNotValid()
    {
        $referencedSampleForm = new ReferencedSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollectionReferencedEntityWithoutInitialization' => [
                ['stringWithoutInizialization' => 'base sample one'],
                []
            ],
            'submitted' => 'on'
        ];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($referencedSampleForm->isSubmitted());
        $this->assertFalse($referencedSampleForm->isValid());
        $filterErrors = $referencedSampleForm->returnFilterErrors();
        $this->assertArrayHasKey('baseSampleCollectionReferencedEntityWithoutInitializationError', $filterErrors);
        $this->assertCount(2, $filterErrors['baseSampleCollectionReferencedEntityWithoutInitializationError']);
        $this->assertFalse($filterErrors['baseSampleCollectionReferencedEntityWithoutInitializationError'][0]['stringWithoutInizializationError']);
        $this->assertTrue($filterErrors['baseSampleCollectionReferencedEntityWithoutInitializationError'][1]['stringWithoutInizializationError']);
        $referencedSampleResult = $referencedSampleForm->getEntityDataToStandardEntity();
        $this->assertEquals('base sample one', $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization[0]->stringWithoutInizialization);
    }

    public function testFormForReferencedEntityWithCollectionValid()
    {
        $referencedSampleForm = new ReferencedSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollectionReferencedEntityWithoutInitialization' => [
                ['stringWithoutInizialization' => 'base sample one'],
                ['stringWithoutInizialization' => 'base sample two'],
            ],
            'submitted' => 'on'
        ];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($referencedSampleForm->isSubmitted());
        $this->assertTrue($referencedSampleForm->isValid());
        $referencedSampleResult = $referencedSampleForm->resolveEntity();
        $this->assertInstanceOf(ReferencedSample::class, $referencedSampleResult);
        $this->assertCount(2, $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization);
        $this->assertEquals('base sample one', $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization[0]->stringWithoutInizialization);
        $this->assertEquals('base sample two', $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization[1]->stringWithoutInizialization);
    }

    public function testFormUpdateForReferencedEntityWithCollectionValid()
    {
        $referencedSample = new ReferencedSample($this->dataMapperMock);
        $referencedSample->id = 1;
        $baseSampleCollection = new SismaCollection(BaseSample::class);
        $baseSampleCollection->append(new BaseSample($this->dataMapperMock));
        $baseSampleCollection->append(new BaseSample($this->dataMapperMock));
        $referencedSample->setBaseSampleCollectionReferencedEntityWithoutInitialization($baseSampleCollection);
        $referencedSample->baseSampleCollectionReferencedEntityWithoutInitialization[0]->id = 2;
        $referencedSample->baseSampleCollectionReferencedEntityWithoutInitialization[1]->id = 3;
        $referencedSample->setBaseSampleCollectionReferencedEntityWithInitialization(new SismaCollection(BaseSample::class));
        $referencedSampleForm = new ReferencedSampleForm($referencedSample, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollectionReferencedEntityWithoutInitialization' => [
                ['stringWithoutInizialization' => 'base sample one'],
                ['stringWithoutInizialization' => 'base sample two'],
            ],
            'submitted' => 'on'
        ];
        $referencedSampleForm->handleRequest($requestMock);
        $this->assertTrue($referencedSampleForm->isSubmitted());
        $this->assertTrue($referencedSampleForm->isValid());
        $referencedSampleResult = $referencedSampleForm->resolveEntity();
        $this->assertInstanceOf(ReferencedSample::class, $referencedSampleResult);
        $this->assertEquals(1, $referencedSampleResult->id);
        $this->assertCount(2, $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization);
        $this->assertEquals('base sample one', $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization[0]->stringWithoutInizialization);
        $this->assertEquals('base sample two', $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization[1]->stringWithoutInizialization);
        $this->assertEquals(2, $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization[0]->id);
        $this->assertEquals(3, $referencedSampleResult->baseSampleCollectionReferencedEntityWithoutInitialization[1]->id);
    }

    public function testFormForOtherReferencedEntityWithCollectionNotValid()
    {
        $otherReferencedSampleForm = new OtherReferencedSampleForm(null, $this->dataMapperMock);
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
        $this->assertTrue($filterErrors['baseSampleCollectionError'][0]['stringWithoutInizializationError']);
        $this->assertTrue($filterErrors['baseSampleCollectionError'][1]['stringWithoutInizializationError']);
    }

    public function testFormForOtherReferencedEntityWithCollectionValid()
    {
        $otherReferencedSampleForm = new OtherReferencedSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollection' => [
                ['stringWithoutInizialization' => 'base sample one'],
                ['stringWithoutInizialization' => 'base sample two'],
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
        $this->assertEquals('base sample one', $otherReferencedSampleResult->baseSampleCollection[0]->stringWithoutInizialization);
        $this->assertEquals('base sample two', $otherReferencedSampleResult->baseSampleCollection[1]->stringWithoutInizialization);
    }

    public function testFormUpdateForOtherReferencedEntityWithCollectionValid()
    {
        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $otherReferencedSample->id = 1;
        $baseSampleCollection = new SismaCollection(BaseSample::class);
        $baseSampleCollection->append(new BaseSample($this->dataMapperMock));
        $baseSampleCollection->append(new BaseSample($this->dataMapperMock));
        $otherReferencedSample->setBaseSampleCollection($baseSampleCollection);
        $otherReferencedSample->baseSampleCollection[0]->id = 2;
        $otherReferencedSample->baseSampleCollection[1]->id = 3;
        $otherReferencedSampleForm = new OtherReferencedSampleForm($otherReferencedSample, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'referenced sample',
            'baseSampleCollection' => [
                ['stringWithoutInizialization' => 'base sample one'],
                ['stringWithoutInizialization' => 'base sample two'],
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
        $this->assertEquals('base sample one', $otherReferencedSampleResult->baseSampleCollection[0]->stringWithoutInizialization);
        $this->assertEquals('base sample two', $otherReferencedSampleResult->baseSampleCollection[1]->stringWithoutInizialization);
        $this->assertEquals(2, $otherReferencedSampleResult->baseSampleCollection[0]->id);
        $this->assertEquals(3, $otherReferencedSampleResult->baseSampleCollection[1]->id);
    }

    public function testFormForSelfReferencedEntityNotValid()
    {
        $selfReferencedSampleForm = new SelfReferencedSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'text' => 'self referenced sample one',
            'sonCollection' => [[], []],
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
        $selfReferencedSampleForm = new SelfReferencedSampleForm(null, $this->dataMapperMock);
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
        $selfReferencedSample = new SelfReferencedSample($this->dataMapperMock);
        $selfReferencedSample->id = 1;
        $sonCollection = new SismaCollection(SelfReferencedSample::class);
        $sonSelfReferencedSampleOne = new SelfReferencedSample($this->dataMapperMock);
        $sonSelfReferencedSampleOne->sonCollection = new SismaCollection(SelfReferencedSample::class);
        $sonCollection->append($sonSelfReferencedSampleOne);
        $sonSelfReferencedSampleTwo = new SelfReferencedSample($this->dataMapperMock);
        $sonSelfReferencedSampleTwo->sonCollection = new SismaCollection(SelfReferencedSample::class);
        $sonCollection->append($sonSelfReferencedSampleTwo);
        $selfReferencedSample->setSonCollection($sonCollection);
        $selfReferencedSample->sonCollection[0]->id = 2;
        $selfReferencedSample->sonCollection[1]->id = 3;
        $selfReferencedSampleForm = new SelfReferencedSampleForm($selfReferencedSample, $this->dataMapperMock);
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
        $entityNotInitializedForm = new EntityNotInitializedForm(null, $this->dataMapperMock);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFormWithNotValidEntity()
    {
        $this->expectException(InvalidArgumentException::class);
        $baseSampleForm = new BaseSampleForm(new ReferencedSample($this->dataMapperMock), $this->dataMapperMock);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFormUpdateWithNotValidReferencedEntityType()
    {
        $this->expectException(InvalidArgumentException::class);
        $fakeBaseSample = new FakeBaseSample($this->dataMapperMock);
        $fakeBaseSample->fakeReferencedSample = new FakeReferencedSample($this->dataMapperMock);
        $fakeBaseSampleForm = new FakeBaseSampleForm($fakeBaseSample, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $fakeBaseSampleForm->handleRequest($requestMock);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFormUpdateWithNotValidReferencedEntityTypeInCollection()
    {
        $this->expectException(InvalidArgumentException::class);
        $fakeReferencedSample = new FakeReferencedSample($this->dataMapperMock);
        $fakeReferencedSample->addFakeBaseSample(new FakeBaseSample($this->dataMapperMock));
        $fakeReferencedSample->addFakeBaseSample(new FakeBaseSample($this->dataMapperMock));
        $fakeReferencedSampleForm = new FakeReferencedSampleForm($fakeReferencedSample, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $fakeReferencedSampleForm->handleRequest($requestMock);
    }
}

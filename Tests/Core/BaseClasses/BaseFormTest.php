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

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\FakeBaseSample;
use SismaFramework\TestsApplication\Entities\FakeReferencedSample;
use SismaFramework\TestsApplication\Entities\OtherReferencedSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;
use SismaFramework\TestsApplication\Entities\SelfReferencedSample;
use SismaFramework\TestsApplication\Entities\SimpleEntity;
use SismaFramework\TestsApplication\Forms\BaseSampleForm;
use SismaFramework\TestsApplication\Forms\BaseSampleFormWithFakeEntityFromForm;
use SismaFramework\TestsApplication\Forms\EntityNotInitializedForm;
use SismaFramework\TestsApplication\Forms\FakeBaseSampleForm;
use SismaFramework\TestsApplication\Forms\FakeReferencedSampleForm;
use SismaFramework\TestsApplication\Forms\IncompleteSimpleEntityFrom;
use SismaFramework\TestsApplication\Forms\OtherReferencedSampleForm;
use SismaFramework\TestsApplication\Forms\ReferencedSampleForm;
use SismaFramework\TestsApplication\Forms\SelfReferencedSampleForm;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\Exceptions\FormException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\CustomTypes\SismaCollection;

/**
 * Description of BaseFormTest
 *
 * @author Valentino de Lapa
 */
class BaseFormTest extends TestCase
{

    private DataMapper $dataMapperMock;

    #[\Override]
    public function setUp(): void
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }
    
    public function testAddEntityFromFormWithException()
    {
        $this->expectException(FormException::class);
        $baseSampleFormWithFakeEntityFromForm = new BaseSampleFormWithFakeEntityFromForm();
        $baseSampleFormWithFakeEntityFromForm->handleRequest(new Request());
    }

    public function testFormForBaseEntityNotSubmitted()
    {
        $baseSampleForm = new BaseSampleForm(null, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $baseSampleForm->handleRequest($requestMock);
        $this->assertFalse($baseSampleForm->isSubmitted());
        $this->assertFalse($baseSampleForm->isValid());
        $this->assertEquals(ResponseType::httpBadRequest, $baseSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpBadRequest, $baseSampleForm->getResponseType());
        $filterErrors = $baseSampleForm->getFilterErrors();
        $this->assertTrue($filterErrors->nullableSecureStringError);
        $this->assertTrue($filterErrors->referencedEntityWithoutInitialization->textError);
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
        $this->assertEquals(ResponseType::httpBadRequest, $baseSampleForm->getResponseType());
        $filterErrors = $baseSampleForm->getFilterErrors();
        $this->assertTrue($filterErrors->stringWithoutInizializationError);
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
        $this->assertEquals(ResponseType::httpOk, $baseSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpOk, $baseSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpBadRequest, $referencedSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpBadRequest, $referencedSampleForm->getResponseType());
        $filterErrors = $referencedSampleForm->getFilterErrors();
        $this->assertCount(2, $filterErrors->baseSampleCollectionReferencedEntityWithoutInitialization);
        $this->assertFalse($filterErrors->baseSampleCollectionReferencedEntityWithoutInitialization[0]->stringWithoutInizializationError);
        $this->assertTrue($filterErrors->baseSampleCollectionReferencedEntityWithoutInitialization[1]->stringWithoutInizializationError);
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
        $this->assertEquals(ResponseType::httpOk, $referencedSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpOk, $referencedSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpBadRequest, $otherReferencedSampleForm->getResponseType());
        $filterErrors = $otherReferencedSampleForm->getFilterErrors();
        $this->assertCount(2, $filterErrors->baseSampleCollection);
        $this->assertTrue($filterErrors->baseSampleCollection[0]->stringWithoutInizializationError);
        $this->assertTrue($filterErrors->baseSampleCollection[1]->stringWithoutInizializationError);
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
        $this->assertEquals(ResponseType::httpOk, $otherReferencedSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpOk, $otherReferencedSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpBadRequest, $selfReferencedSampleForm->getResponseType());
        $filterErrors = $selfReferencedSampleForm->getFilterErrors();
        $this->assertCount(2, $filterErrors->sonCollection);
        $this->assertTrue($filterErrors->sonCollection[0]->textError);
        $this->assertTrue($filterErrors->sonCollection[1]->textError);
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
        $this->assertEquals(ResponseType::httpOk, $selfReferencedSampleForm->getResponseType());
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
        $this->assertEquals(ResponseType::httpOk, $selfReferencedSampleForm->getResponseType());
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

    #[RunInSeparateProcess]
    public function testFormWhithNotInitializedEntity()
    {
        $this->expectException(FormException::class);
        $entityNotInitializedForm = new EntityNotInitializedForm(null, $this->dataMapperMock);
    }

    #[RunInSeparateProcess]
    public function testFormWithNotValidEntity()
    {
        $this->expectException(InvalidArgumentException::class);
        $baseSampleForm = new BaseSampleForm(new ReferencedSample($this->dataMapperMock), $this->dataMapperMock);
    }

    #[RunInSeparateProcess]
    public function testFormUpdateWithNotValidReferencedEntityType()
    {
        $this->expectException(InvalidArgumentException::class);
        $fakeBaseSample = new FakeBaseSample($this->dataMapperMock);
        $fakeBaseSample->fakeReferencedSample = new FakeReferencedSample($this->dataMapperMock);
        $fakeBaseSampleForm = new FakeBaseSampleForm($fakeBaseSample, $this->dataMapperMock);
        $requestMock = $this->createMock(Request::class);
        $fakeBaseSampleForm->handleRequest($requestMock);
    }

    #[RunInSeparateProcess]
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

    public function testNotFilteredSubmittedPropertyValue()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Typed property SismaFramework\TestsApplication\Entities\SimpleEntity::$string must not be accessed before initialization');
        $requestMock = $this->createMock(Request::class);
        $requestMock->query = $requestMock->request = $requestMock->cookie = $requestMock->files = $requestMock->server = $requestMock->headers = [];
        $requestMock->request = [
            'string' => 'sample-string',
            'submitted' => 'on'
        ];
        $incompleteSimpleEntityForm = new IncompleteSimpleEntityFrom();
        $incompleteSimpleEntityForm->handleRequest($requestMock);
        $this->assertTrue($incompleteSimpleEntityForm->isSubmitted());
        $this->assertTrue($incompleteSimpleEntityForm->isValid());
        $simpleEntity = $incompleteSimpleEntityForm->resolveEntity();
        $this->assertInstanceOf(SimpleEntity::class, $simpleEntity);
        $simpleEntity->string;
    }
}

<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
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

namespace SismaFramework\Tests\Odm\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\Indexing;
use SismaFramework\Odm\HelperClasses\DocumentMapper;
use SismaFramework\Odm\HelperClasses\DocumentQuery;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\TestsApplication\DocumentModels\SampleDocumentModel;
use SismaFramework\TestsApplication\Documents\SampleDocument;

/**
 * @author Valentino de Lapa
 */
class BaseModelTest extends TestCase
{
    private DocumentMapper $mapperMock;
    private SampleDocumentModel $model;

    #[\Override]
    public function setUp(): void
    {
        $this->mapperMock = $this->createMock(DocumentMapper::class);
        $this->model = new SampleDocumentModel($this->mapperMock);
    }

    public function testGetDocumentNameReturnsSampleDocument(): void
    {
        $this->assertEquals(SampleDocument::class, $this->model->getDocumentName());
    }

    public function testGetDocumentCollectionDelegatesToMapper(): void
    {
        $expectedCollection = new SismaCollection(SampleDocument::class);

        $this->mapperMock->expects($this->once())
            ->method('find')
            ->with(SampleDocument::class, $this->isInstanceOf(DocumentQuery::class))
            ->willReturn($expectedCollection);

        $this->assertSame($expectedCollection, $this->model->getDocumentCollection());
    }

    public function testGetDocumentCollectionWithOrderAppliesSort(): void
    {
        $capturedQuery = null;
        $this->mapperMock->method('find')
            ->willReturnCallback(function (string $class, DocumentQuery $query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return new SismaCollection($class);
            });

        $this->model->getDocumentCollection(null, 'createdAt', Indexing::desc);

        $this->assertArrayHasKey('createdAt', $capturedQuery->getSort());
        $this->assertSame(Indexing::desc, $capturedQuery->getSort()['createdAt']);
    }

    public function testGetDocumentCollectionWithLimitAppliesLimit(): void
    {
        $capturedQuery = null;
        $this->mapperMock->method('find')
            ->willReturnCallback(function (string $class, DocumentQuery $query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return new SismaCollection($class);
            });

        $this->model->getDocumentCollection(null, null, Indexing::asc, null, 10);

        $this->assertEquals(10, $capturedQuery->getLimit());
    }

    public function testGetDocumentCollectionWithOffsetAppliesOffset(): void
    {
        $capturedQuery = null;
        $this->mapperMock->method('find')
            ->willReturnCallback(function (string $class, DocumentQuery $query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return new SismaCollection($class);
            });

        $this->model->getDocumentCollection(null, null, Indexing::asc, 20);

        $this->assertEquals(20, $capturedQuery->getOffset());
    }

    public function testGetDocumentByIdDelegatesToFindFirst(): void
    {
        $document = new SampleDocument();
        $document->hydrate(['_id' => 'abc', 'title' => 'Test']);

        $this->mapperMock->expects($this->once())
            ->method('findFirst')
            ->with(SampleDocument::class, $this->isInstanceOf(DocumentQuery::class))
            ->willReturn($document);

        $this->assertSame($document, $this->model->getDocumentById('abc'));
    }

    public function testGetDocumentByIdBuildsCorrectFilter(): void
    {
        $capturedQuery = null;
        $this->mapperMock->method('findFirst')
            ->willReturnCallback(function (string $class, DocumentQuery $query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return null;
            });

        $this->model->getDocumentById('test-id');

        $conditions = $capturedQuery->getConditions();
        $this->assertCount(1, $conditions);
        $this->assertEquals('_id', $conditions[0]['field']);
        $this->assertSame(FilterOperator::equal, $conditions[0]['operator']);
        $this->assertEquals('test-id', $conditions[0]['value']);
    }

    public function testCountDocumentCollectionDelegatesToMapper(): void
    {
        $this->mapperMock->expects($this->once())
            ->method('getCount')
            ->with(SampleDocument::class, $this->isInstanceOf(DocumentQuery::class))
            ->willReturn(7);

        $this->assertEquals(7, $this->model->countDocumentCollection());
    }

    public function testSaveDelegatesToMapper(): void
    {
        $document = new SampleDocument();
        $document->title = 'New';

        $this->mapperMock->expects($this->once())->method('save')->with($document);

        $this->model->save($document);
    }

    public function testDeleteDocumentByIdCallsDeleteWhenFound(): void
    {
        $document = new SampleDocument();
        $document->hydrate(['_id' => 'del-id']);

        $this->mapperMock->method('findFirst')->willReturn($document);
        $this->mapperMock->expects($this->once())->method('delete')->with($document);

        $this->model->deleteDocumentById('del-id');
    }

    public function testDeleteDocumentByIdDoesNothingWhenNotFound(): void
    {
        $this->mapperMock->method('findFirst')->willReturn(null);
        $this->mapperMock->expects($this->never())->method('delete');

        $this->model->deleteDocumentById('nonexistent');
    }
}

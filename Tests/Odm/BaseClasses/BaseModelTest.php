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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Odm\Enumerations\FilterOperator;
use SismaFramework\Odm\Enumerations\Indexing;
use SismaFramework\Odm\Exceptions\DocumentMapperException;
use SismaFramework\Odm\HelperClasses\Cache;
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
    private Config $configStub;
    private SampleDocumentModel $model;

    #[\Override]
    public function setUp(): void
    {
        Cache::clearDocumentCache();
        $this->mapperMock = $this->createMock(DocumentMapper::class);
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')->willReturnMap([['odmCache', false]]);
        $this->model = new SampleDocumentModel($this->mapperMock, $this->configStub);
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

    public function testGetDocumentByIdReturnsCachedDocumentWithoutCallingMapper(): void
    {
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([['odmCache', true]]);
        $model = new SampleDocumentModel($this->mapperMock, $configStub);

        $document = new SampleDocument();
        $document->hydrate(['_id' => 'cached-id', 'title' => 'Cached']);
        Cache::setDocument($document);

        $this->mapperMock->expects($this->never())->method('findFirst');

        $this->assertSame($document, $model->getDocumentById('cached-id'));
    }

    public function testGetDocumentByIdFallsBackToMapperWhenCacheDisabled(): void
    {
        $document = new SampleDocument();
        $document->hydrate(['_id' => 'some-id', 'title' => 'Fresh']);
        Cache::setDocument($document);

        $this->mapperMock->expects($this->once())->method('findFirst')->willReturn($document);

        $this->model->getDocumentById('some-id');
    }

    public function testCallFindByPropertyBuildsCorrectQuery(): void
    {
        $capturedQuery = null;
        $this->mapperMock->method('find')
            ->willReturnCallback(function (string $class, DocumentQuery $query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return new SismaCollection($class);
            });

        $this->model->findByStatus('published');

        $conditions = $capturedQuery->getConditions();
        $this->assertCount(1, $conditions);
        $this->assertEquals('status', $conditions[0]['field']);
        $this->assertSame(FilterOperator::equal, $conditions[0]['operator']);
        $this->assertEquals('published', $conditions[0]['value']);
    }

    public function testCallGetByPropertyIsAliasForFind(): void
    {
        $expectedCollection = new SismaCollection(SampleDocument::class);
        $this->mapperMock->expects($this->once())->method('find')->willReturn($expectedCollection);

        $this->assertSame($expectedCollection, $this->model->getByStatus('draft'));
    }

    public function testCallCountByPropertyDelegatesToMapper(): void
    {
        $this->mapperMock->expects($this->once())
            ->method('getCount')
            ->with(SampleDocument::class, $this->isInstanceOf(DocumentQuery::class))
            ->willReturn(5);

        $this->assertEquals(5, $this->model->countByStatus('draft'));
    }

    public function testCallDeleteByPropertyDelegatesToMapper(): void
    {
        $this->mapperMock->expects($this->once())
            ->method('deleteBatch')
            ->with(SampleDocument::class, $this->isInstanceOf(DocumentQuery::class))
            ->willReturn(2);

        $this->assertEquals(2, $this->model->deleteByStatus('archived'));
    }

    public function testCallByMultiplePropertiesJoinedWithAnd(): void
    {
        $capturedQuery = null;
        $this->mapperMock->method('find')
            ->willReturnCallback(function (string $class, DocumentQuery $query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return new SismaCollection($class);
            });

        $this->model->findByStatusAndCount('published', 3);

        $conditions = $capturedQuery->getConditions();
        $this->assertCount(3, $conditions);
        $this->assertEquals('status', $conditions[0]['field']);
        $this->assertEquals('count', $conditions[2]['field']);
    }

    public function testCallWithUnknownActionThrowsException(): void
    {
        $this->expectException(DocumentMapperException::class);

        $this->model->frobnicateByStatus('draft');
    }

    public function testCallWithoutByThrowsException(): void
    {
        $this->expectException(DocumentMapperException::class);

        $this->model->findAllDocuments();
    }

    public function testCallWithMismatchedArgumentCountThrowsException(): void
    {
        $this->expectException(DocumentMapperException::class);

        $this->model->findByStatusAndCount('draft');
    }
}

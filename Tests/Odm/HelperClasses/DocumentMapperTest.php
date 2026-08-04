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

namespace SismaFramework\Tests\Odm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Odm\BaseClasses\BaseAdapter;
use SismaFramework\Odm\Enumerations\Indexing;
use SismaFramework\Odm\Exceptions\DocumentMapperException;
use SismaFramework\Odm\HelperClasses\Cache;
use SismaFramework\Odm\HelperClasses\DocumentMapper;
use SismaFramework\Odm\HelperClasses\DocumentQuery;
use SismaFramework\Odm\ResultSets\ResultSetMongodb;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\TestsApplication\Documents\SampleDocument;

/**
 * @author Valentino de Lapa
 */
class DocumentMapperTest extends TestCase
{
    private BaseAdapter $adapterMock;
    private DocumentMapper $mapper;

    #[\Override]
    public function setUp(): void
    {
        Cache::clearDocumentCache();
        $this->adapterMock = $this->createMock(BaseAdapter::class);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([['odmCache', false]]);
        $this->mapper = new DocumentMapper($this->adapterMock, $configStub);
    }

    public function testSaveNewDocumentCallsInsert(): void
    {
        $document = new SampleDocument();
        $document->title = 'New Doc';

        $this->adapterMock->expects($this->once())->method('connect');
        $this->adapterMock->expects($this->once())
            ->method('insert')
            ->with('sample_document', $this->arrayHasKey('title'))
            ->willReturn('new-id-123');

        $this->mapper->save($document);

        $this->assertEquals('new-id-123', $document->_id);
        $this->assertFalse($document->modified);
    }

    public function testSaveConvertsSismaDateTimeToFormattedString(): void
    {
        $document = new SampleDocument();
        $document->title = 'New Doc';
        $document->publishedAt = new SismaDateTime('2024-03-15 10:30:00');

        $this->adapterMock->method('connect');
        $this->adapterMock->expects($this->once())
            ->method('insert')
            ->with('sample_document', $this->callback(
                fn(array $data) => $data['publishedAt'] === '2024-03-15 10:30:00'
            ))
            ->willReturn('new-id-123');

        $this->mapper->save($document);
    }

    public function testSaveConvertsEnumToItsValue(): void
    {
        $document = new SampleDocument();
        $document->title = 'New Doc';
        $document->orderDirection = Indexing::desc;

        $this->adapterMock->method('connect');
        $this->adapterMock->expects($this->once())
            ->method('insert')
            ->with('sample_document', $this->callback(
                fn(array $data) => $data['orderDirection'] === -1
            ))
            ->willReturn('new-id-123');

        $this->mapper->save($document);
    }

    public function testSaveConvertsNestedEmbeddedArrayValues(): void
    {
        $document = new SampleDocument();
        $document->title = 'New Doc';
        $document->author = ['name' => 'Valentino', 'joinedAt' => new SismaDateTime('2024-01-01 00:00:00')];

        $this->adapterMock->method('connect');
        $this->adapterMock->expects($this->once())
            ->method('insert')
            ->with('sample_document', $this->callback(
                fn(array $data) => $data['author']['joinedAt'] === '2024-01-01 00:00:00' && $data['author']['name'] === 'Valentino'
            ))
            ->willReturn('new-id-123');

        $this->mapper->save($document);
    }

    public function testSaveExistingDocumentCallsUpdate(): void
    {
        $document = new SampleDocument();
        $document->hydrate(['_id' => 'existing-id', 'title' => 'Old Title']);
        $document->title = 'New Title';

        $this->adapterMock->expects($this->once())->method('connect');
        $this->adapterMock->expects($this->once())
            ->method('update')
            ->with('sample_document', 'existing-id', $this->arrayHasKey('title'));

        $this->mapper->save($document);

        $this->assertFalse($document->modified);
    }

    public function testSaveUnmodifiedDocumentDoesNotCallAdapterMethods(): void
    {
        $document = new SampleDocument();
        $document->hydrate(['_id' => 'some-id', 'title' => 'Title']);

        $this->adapterMock->expects($this->never())->method('connect');
        $this->adapterMock->expects($this->never())->method('insert');
        $this->adapterMock->expects($this->never())->method('update');

        $this->mapper->save($document);
    }

    public function testDeleteCallsAdapterDelete(): void
    {
        $document = new SampleDocument();
        $document->hydrate(['_id' => 'del-id', 'title' => 'To Delete']);

        $this->adapterMock->expects($this->once())->method('connect');
        $this->adapterMock->expects($this->once())
            ->method('delete')
            ->with('sample_document', 'del-id');

        $this->mapper->delete($document);

        $this->assertNull($document->_id);
    }

    public function testDeleteDocumentWithoutIdThrowsException(): void
    {
        $this->expectException(DocumentMapperException::class);
        $this->mapper->delete(new SampleDocument());
    }

    public function testFindReturnsSismaCollection(): void
    {
        $resultSet = new ResultSetMongodb([
            ['_id' => '1', 'title' => 'Doc A'],
            ['_id' => '2', 'title' => 'Doc B'],
        ]);

        $this->adapterMock->expects($this->once())->method('connect');
        $this->adapterMock->expects($this->once())->method('find')->willReturn($resultSet);

        $collection = $this->mapper->find(SampleDocument::class, new DocumentQuery());

        $this->assertInstanceOf(SismaCollection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertInstanceOf(SampleDocument::class, $collection[0]);
        $this->assertEquals('Doc A', $collection[0]->title);
    }

    public function testFindReturnsEmptyCollectionWhenNoResults(): void
    {
        $this->adapterMock->method('connect');
        $this->adapterMock->method('find')->willReturn(new ResultSetMongodb([]));

        $collection = $this->mapper->find(SampleDocument::class, new DocumentQuery());

        $this->assertInstanceOf(SismaCollection::class, $collection);
        $this->assertCount(0, $collection);
    }

    public function testFindFirstReturnsFirstDocument(): void
    {
        $this->adapterMock->method('connect');
        $this->adapterMock->method('find')->willReturn(new ResultSetMongodb([['_id' => '1', 'title' => 'First']]));

        $document = $this->mapper->findFirst(SampleDocument::class, new DocumentQuery());

        $this->assertInstanceOf(SampleDocument::class, $document);
        $this->assertEquals('First', $document->title);
    }

    public function testFindFirstReturnsNullWhenNoResults(): void
    {
        $this->adapterMock->method('connect');
        $this->adapterMock->method('find')->willReturn(new ResultSetMongodb([]));

        $this->assertNull($this->mapper->findFirst(SampleDocument::class, new DocumentQuery()));
    }

    public function testFindFirstSetsLimitToOne(): void
    {
        $query = new DocumentQuery();
        $this->adapterMock->method('connect');
        $this->adapterMock->method('find')->willReturn(new ResultSetMongodb([]));

        $this->mapper->findFirst(SampleDocument::class, $query);

        $this->assertEquals(1, $query->getLimit());
    }

    public function testGetCountDelegatesToAdapter(): void
    {
        $this->adapterMock->expects($this->once())->method('connect');
        $this->adapterMock->expects($this->once())
            ->method('count')
            ->with('sample_document')
            ->willReturn(42);

        $this->assertEquals(42, $this->mapper->getCount(SampleDocument::class, new DocumentQuery()));
    }

    public function testSaveCachesDocumentWhenCacheEnabled(): void
    {
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([['odmCache', true]]);
        $mapper = new DocumentMapper($this->adapterMock, $configStub);

        $document = new SampleDocument();
        $document->title = 'Cached Doc';

        $this->adapterMock->method('connect');
        $this->adapterMock->method('insert')->willReturn('cached-id-1');

        $mapper->save($document);

        $this->assertTrue(Cache::checkDocumentPresenceInCache(SampleDocument::class, 'cached-id-1'));
        $this->assertSame($document, Cache::getDocumentById(SampleDocument::class, 'cached-id-1'));
    }

    public function testSaveDoesNotCacheDocumentWhenCacheDisabled(): void
    {
        $document = new SampleDocument();
        $document->title = 'Not Cached';

        $this->adapterMock->method('connect');
        $this->adapterMock->method('insert')->willReturn('not-cached-id');

        $this->mapper->save($document);

        $this->assertFalse(Cache::checkDocumentPresenceInCache(SampleDocument::class, 'not-cached-id'));
    }

    public function testDeleteClearsCacheWhenCacheEnabled(): void
    {
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([['odmCache', true]]);
        $mapper = new DocumentMapper($this->adapterMock, $configStub);

        $document = new SampleDocument();
        $document->hydrate(['_id' => 'to-delete', 'title' => 'Title']);
        Cache::setDocument($document);

        $this->adapterMock->method('connect');
        $this->adapterMock->method('delete');

        $mapper->delete($document);

        $this->assertFalse(Cache::checkDocumentPresenceInCache(SampleDocument::class, 'to-delete'));
    }

    public function testDeleteBatchDelegatesToAdapterDeleteMany(): void
    {
        $this->adapterMock->expects($this->once())->method('connect');
        $this->adapterMock->expects($this->once())
            ->method('deleteMany')
            ->with('sample_document', $this->isInstanceOf(DocumentQuery::class))
            ->willReturn(3);

        $this->assertEquals(3, $this->mapper->deleteBatch(SampleDocument::class, new DocumentQuery()));
    }

    public function testDeleteBatchClearsCacheWhenCacheEnabled(): void
    {
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([['odmCache', true]]);
        $mapper = new DocumentMapper($this->adapterMock, $configStub);

        $document = new SampleDocument();
        $document->hydrate(['_id' => 'still-cached', 'title' => 'Title']);
        Cache::setDocument($document);

        $this->adapterMock->method('connect');
        $this->adapterMock->method('deleteMany')->willReturn(1);

        $mapper->deleteBatch(SampleDocument::class, new DocumentQuery());

        $this->assertFalse(Cache::checkDocumentPresenceInCache(SampleDocument::class, 'still-cached'));
    }
}

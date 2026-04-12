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
use SismaFramework\Odm\ResultSets\ResultSetMongodb;
use SismaFramework\TestsApplication\Documents\SampleDocument;

/**
 * @author Valentino de Lapa
 */
class BaseDocumentResultSetTest extends TestCase
{
    private function buildResultSet(array $rows): ResultSetMongodb
    {
        $resultSet = new ResultSetMongodb($rows);
        $resultSet->setReturnType(SampleDocument::class);
        return $resultSet;
    }

    public function testIteratesAllRows(): void
    {
        $rows = [
            ['_id' => '1', 'title' => 'Doc A'],
            ['_id' => '2', 'title' => 'Doc B'],
            ['_id' => '3', 'title' => 'Doc C'],
        ];
        $count = 0;
        foreach ($this->buildResultSet($rows) as $document) {
            $count++;
        }
        $this->assertEquals(3, $count);
    }

    public function testEmptyResultSetProducesNoIterations(): void
    {
        $count = 0;
        foreach ($this->buildResultSet([]) as $document) {
            $count++;
        }
        $this->assertEquals(0, $count);
    }

    public function testIteratorKeyIncrements(): void
    {
        $rows = [['_id' => '1'], ['_id' => '2']];
        $keys = [];
        foreach ($this->buildResultSet($rows) as $key => $document) {
            $keys[] = $key;
        }
        $this->assertEquals([0, 1], $keys);
    }

    public function testEachIterationReturnsBaseDocumentInstance(): void
    {
        foreach ($this->buildResultSet([['_id' => '1', 'title' => 'Hello']]) as $document) {
            $this->assertInstanceOf(SampleDocument::class, $document);
        }
    }

    public function testHydratedDocumentHasCorrectData(): void
    {
        $resultSet = $this->buildResultSet([['_id' => 'abc', 'title' => 'Test Title', 'status' => 'active']]);
        $resultSet->rewind();
        $document = $resultSet->current();

        $this->assertEquals('abc', $document->_id);
        $this->assertEquals('Test Title', $document->title);
        $this->assertEquals('active', $document->status);
    }

    public function testHydratedDocumentIsNotModified(): void
    {
        $resultSet = $this->buildResultSet([['_id' => 'abc', 'title' => 'Test']]);
        $resultSet->rewind();
        $this->assertFalse($resultSet->current()->modified);
    }

    public function testRewindAllowsSecondIteration(): void
    {
        $rows = [['_id' => '1'], ['_id' => '2']];
        $resultSet = $this->buildResultSet($rows);

        $firstPass = [];
        foreach ($resultSet as $doc) {
            $firstPass[] = $doc->_id;
        }

        $secondPass = [];
        foreach ($resultSet as $doc) {
            $secondPass[] = $doc->_id;
        }

        $this->assertEquals($firstPass, $secondPass);
    }

    public function testNumRows(): void
    {
        $this->assertEquals(3, $this->buildResultSet([['_id' => '1'], ['_id' => '2'], ['_id' => '3']])->numRows());
    }

    public function testReleaseEmptiesResultSet(): void
    {
        $resultSet = $this->buildResultSet([['_id' => '1'], ['_id' => '2']]);
        $resultSet->release();
        $this->assertEquals(0, $resultSet->numRows());
    }
}

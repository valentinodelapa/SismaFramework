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
use SismaFramework\TestsApplication\Documents\SampleDocument;

/**
 * @author Valentino de Lapa
 */
class BaseDocumentTest extends TestCase
{
    private SampleDocument $document;

    #[\Override]
    public function setUp(): void
    {
        $this->document = new SampleDocument();
    }

    public function testDefaultValuesAreSetOnConstruction(): void
    {
        $this->assertEquals('draft', $this->document->status);
        $this->assertEquals(0, $this->document->count);
    }

    public function testModifiedIsFalseOnConstruction(): void
    {
        $this->assertFalse($this->document->modified);
    }

    public function testGetCollectionName(): void
    {
        $this->assertEquals('sample_document', $this->document->getCollectionName());
    }

    public function testSetNewPropertyMarksAsModified(): void
    {
        $this->document->title = 'Hello';
        $this->assertTrue($this->document->modified);
    }

    public function testSetSameValueDoesNotMarkAsModified(): void
    {
        $this->document->status = 'draft';
        $this->assertFalse($this->document->modified);
    }

    public function testSetDifferentValueMarksAsModified(): void
    {
        $this->document->status = 'published';
        $this->assertTrue($this->document->modified);
    }

    public function testModifiedRemainsAfterMultipleChanges(): void
    {
        $this->document->title = 'First';
        $this->document->title = 'Second';
        $this->assertTrue($this->document->modified);
    }

    public function testGetExistingProperty(): void
    {
        $this->document->title = 'Test Title';
        $this->assertEquals('Test Title', $this->document->title);
    }

    public function testGetNonExistingPropertyReturnsNull(): void
    {
        $this->assertNull($this->document->nonExistentField);
    }

    public function testIssetReturnsTrueForSetProperty(): void
    {
        $this->document->title = 'Test';
        $this->assertTrue(isset($this->document->title));
    }

    public function testIssetReturnsFalseForUnsetProperty(): void
    {
        $this->assertFalse(isset($this->document->nonExistentField));
    }

    public function testUnsetMarksAsModified(): void
    {
        $this->document->title = 'Test';
        $this->document->modified = false;
        unset($this->document->title);
        $this->assertTrue($this->document->modified);
    }

    public function testUnsetRemovesProperty(): void
    {
        $this->document->title = 'Test';
        unset($this->document->title);
        $this->assertFalse(isset($this->document->title));
    }

    public function testUnsetNonExistingPropertyDoesNotMarkAsModified(): void
    {
        unset($this->document->nonExistentField);
        $this->assertFalse($this->document->modified);
    }

    public function testHydratePopulatesData(): void
    {
        $this->document->hydrate(['_id' => 'abc123', 'title' => 'Hydrated', 'status' => 'active']);
        $this->assertEquals('abc123', $this->document->_id);
        $this->assertEquals('Hydrated', $this->document->title);
        $this->assertEquals('active', $this->document->status);
    }

    public function testHydrateResetsModifiedFlag(): void
    {
        $this->document->title = 'dirty';
        $this->assertTrue($this->document->modified);
        $this->document->hydrate(['title' => 'clean']);
        $this->assertFalse($this->document->modified);
    }

    public function testHydrateOverwritesExistingData(): void
    {
        $this->document->hydrate(['status' => 'active', 'count' => 5]);
        $this->document->hydrate(['status' => 'archived']);
        $this->assertEquals('archived', $this->document->status);
        $this->assertNull($this->document->count);
    }

    public function testToArrayReturnsCorrectData(): void
    {
        $this->document->hydrate(['_id' => 'abc', 'title' => 'Test', 'status' => 'active']);
        $this->assertEquals(['_id' => 'abc', 'title' => 'Test', 'status' => 'active'], $this->document->toArray());
    }

    public function testToArrayReflectsChangesAfterSet(): void
    {
        $this->document->hydrate(['title' => 'Original']);
        $this->document->title = 'Modified';
        $this->assertEquals('Modified', $this->document->toArray()['title']);
    }
}

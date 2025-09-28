<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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

namespace SismaFramework\Tests\Orm\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;

/**
 * Test for BaseResultSet class
 * @author Valentino de Lapa
 */
class BaseResultSetTest extends TestCase
{
    private TestableResultSet $resultSet;

    protected function setUp(): void
    {
        $this->resultSet = new TestableResultSet();
    }

    public function testImplementsIteratorInterface()
    {
        $this->assertInstanceOf(\Iterator::class, $this->resultSet);
    }

    public function testSetReturnType()
    {
        $this->resultSet->setReturnType('TestClass');
        $this->assertEquals('TestClass', $this->resultSet->getReturnType());
    }

    public function testSetReturnTypeWithStringConversion()
    {
        $this->resultSet->setReturnType(123); // Should convert to string
        $this->assertEquals('123', $this->resultSet->getReturnType());
    }

    public function testSeekWithValidIndex()
    {
        $this->resultSet->seek(2);
        $this->assertEquals(2, $this->resultSet->key());
    }

    public function testSeekWithNegativeIndex()
    {
        $this->resultSet->seek(-5);
        $this->assertEquals(0, $this->resultSet->key());
    }

    public function testSeekWithIndexTooLarge()
    {
        $maxRecord = $this->resultSet->getMaxRecord();
        $this->resultSet->seek($maxRecord + 10);
        $this->assertEquals($maxRecord, $this->resultSet->key());
    }

    public function testRewind()
    {
        $this->resultSet->seek(3);
        $this->resultSet->rewind();
        $this->assertEquals(0, $this->resultSet->key());
    }

    public function testNext()
    {
        $initialKey = $this->resultSet->key();
        $this->resultSet->next();
        $this->assertEquals($initialKey + 1, $this->resultSet->key());
    }

    public function testValidWithValidPosition()
    {
        $this->resultSet->seek(2);
        $this->assertTrue($this->resultSet->valid());
    }

    public function testValidWithInvalidPosition()
    {
        $this->resultSet->seek(100); // Beyond max record
        $this->resultSet->next(); // Move beyond max
        $this->assertFalse($this->resultSet->valid());
    }

    public function testRelease()
    {
        $this->resultSet->release();
        $this->assertEquals(-1, $this->resultSet->getMaxRecord());
        $this->assertEquals(0, $this->resultSet->key());
        $this->assertFalse($this->resultSet->valid());
    }

    public function testCurrentReturnsEntityWithoutAdvancing()
    {
        $initialKey = $this->resultSet->key();
        $entity = $this->resultSet->current();

        $this->assertInstanceOf(StandardEntity::class, $entity);
        $this->assertEquals($initialKey, $this->resultSet->key()); // Should not advance
    }

    public function testIteratorFunctionality()
    {
        $count = 0;
        foreach ($this->resultSet as $key => $entity) {
            $this->assertIsInt($key);
            $this->assertInstanceOf(StandardEntity::class, $entity);
            $count++;
        }

        $this->assertEquals($this->resultSet->numRows(), $count);
    }

    public function testConstructorSetsMaxRecord()
    {
        $resultSet = new TestableResultSet();
        $this->assertEquals($resultSet->numRows() - 1, $resultSet->getMaxRecord());
    }

    public function testFetchWithAutoNextTrue()
    {
        $initialKey = $this->resultSet->key();
        $entity = $this->resultSet->fetch(true);

        $this->assertInstanceOf(StandardEntity::class, $entity);
        $this->assertEquals($initialKey + 1, $this->resultSet->key()); // Should advance
    }

    public function testFetchWithAutoNextFalse()
    {
        $initialKey = $this->resultSet->key();
        $entity = $this->resultSet->fetch(false);

        $this->assertInstanceOf(StandardEntity::class, $entity);
        $this->assertEquals($initialKey, $this->resultSet->key()); // Should not advance
    }

    public function testHydrateWithStandardEntity()
    {
        $this->resultSet->setReturnType(StandardEntity::class);
        $stdClass = new \stdClass();
        $stdClass->property1 = 'value1';
        $stdClass->property2 = 'value2';

        $entity = $this->resultSet->callHydrate($stdClass);

        $this->assertInstanceOf(StandardEntity::class, $entity);
        $this->assertEquals('value1', $entity->property1);
        $this->assertEquals('value2', $entity->property2);
    }
}

/**
 * Testable implementation of BaseResultSet for testing purposes
 */
class TestableResultSet extends BaseResultSet
{
    private array $data = [
        ['id' => 1, 'name' => 'Test 1'],
        ['id' => 2, 'name' => 'Test 2'],
        ['id' => 3, 'name' => 'Test 3'],
        ['id' => 4, 'name' => 'Test 4'],
        ['id' => 5, 'name' => 'Test 5']
    ];

    public function numRows(): int
    {
        return count($this->data);
    }

    public function fetch(bool $autoNext = true): null|StandardEntity|BaseEntity
    {
        if (!$this->valid()) {
            return null;
        }

        $row = $this->data[$this->currentRecord];
        $stdClass = new \stdClass();
        foreach ($row as $key => $value) {
            $stdClass->$key = $value;
        }

        $entity = $this->hydrate($stdClass);

        if ($autoNext) {
            $this->next();
        }

        return $entity;
    }

    // Helper methods for testing protected properties
    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function getMaxRecord(): int
    {
        return $this->maxRecord;
    }

    public function callHydrate(\stdClass $result): StandardEntity|BaseEntity
    {
        return $this->hydrate($result);
    }
}
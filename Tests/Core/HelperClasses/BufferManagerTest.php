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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\BufferManager;

/**
 * @author Valentino de Lapa
 */
class BufferManagerTest extends TestCase
{

    private int $initialBufferLevel;

    #[\Override]
    public function setUp(): void
    {
        // Store initial buffer level but don't clean existing buffers
        $this->initialBufferLevel = ob_get_level();
    }

    #[\Override]
    public function tearDown(): void
    {
        // Only clean buffers we created during testing
        while (ob_get_level() > $this->initialBufferLevel) {
            ob_end_clean();
        }
    }

    public function testClassExists()
    {
        $this->assertTrue(class_exists('SismaFramework\Core\HelperClasses\BufferManager'));
    }

    public function testStartMethodExists()
    {
        $reflection = new \ReflectionClass(BufferManager::class);
        $this->assertTrue($reflection->hasMethod('start'));
        $this->assertTrue($reflection->getMethod('start')->isStatic());
        $this->assertTrue($reflection->getMethod('start')->isPublic());
    }

    public function testClearMethodExists()
    {
        $reflection = new \ReflectionClass(BufferManager::class);
        $this->assertTrue($reflection->hasMethod('clear'));
        $this->assertTrue($reflection->getMethod('clear')->isStatic());
        $this->assertTrue($reflection->getMethod('clear')->isPublic());
    }

    public function testFlushMethodExists()
    {
        $reflection = new \ReflectionClass(BufferManager::class);
        $this->assertTrue($reflection->hasMethod('flush'));
        $this->assertTrue($reflection->getMethod('flush')->isStatic());
        $this->assertTrue($reflection->getMethod('flush')->isPublic());
    }

    public function testStartIncreasesBufferLevel()
    {
        $levelBefore = ob_get_level();
        BufferManager::start();
        $levelAfter = ob_get_level();
        $this->assertEquals($levelBefore + 1, $levelAfter);
    }

    public function testStartCanBeCalledMultipleTimes()
    {
        $levelBefore = ob_get_level();
        BufferManager::start();
        BufferManager::start();
        $levelAfter = ob_get_level();
        $this->assertEquals($levelBefore + 2, $levelAfter);
    }

    public function testClearDoesNotThrow()
    {
        BufferManager::start();
        BufferManager::clear();
        $this->assertTrue(true); // Test passes if no exception
    }

    public function testFlushDoesNotThrow()
    {
        BufferManager::start();
        BufferManager::flush();
        $this->assertTrue(true); // Test passes if no exception
    }

    public function testMethodsCanBeCalledWhenNoBufferActive()
    {
        // Ensure no extra buffers are active by restoring to initial level
        while (ob_get_level() > $this->initialBufferLevel) {
            ob_end_clean();
        }

        // These should not throw errors even when no buffer is active
        BufferManager::clear();
        BufferManager::flush();

        $this->assertTrue(true);
    }
}
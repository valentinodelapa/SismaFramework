<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Tests\ProprietaryTypes;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\CustomTypes\SismaTime;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SismaTimeTest extends TestCase
{

    public function testSismaTime()
    {
        $originalTime = "02:51:16";
        $sismaTime = SismaTime::createFromStandardTimeFormat($originalTime);
        $this->assertInstanceOf(SismaTime::class, $sismaTime);
        $time = $sismaTime->formatToStandardTimeFormat();
        $this->assertIsString($time);
        $this->assertEquals($originalTime, $time);
    }
}

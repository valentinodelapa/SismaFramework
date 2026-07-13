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

namespace SismaFramework\Core\HelperClasses;

/**
 * @author Valentino de Lapa
 */
class BufferManager
{

    private static ?int $baseLevel = null;

    public static function start(): void
    {
        self::ensureBaseLevel();
        \ob_start();
    }

    public static function clear(): void
    {
        $floor = self::ensureBaseLevel();
        while (\ob_get_level() > $floor) {
            \ob_end_clean();
        }
    }

    public static function flush(): void
    {
        $floor = self::ensureBaseLevel();
        while (\ob_get_level() > $floor) {
            \ob_end_flush();
        }
    }

    public static function discardAll(): void
    {
        while (\ob_get_level() > 0) {
            \ob_end_clean();
        }
        self::$baseLevel = null;
    }

    private static function ensureBaseLevel(): int
    {
        if (self::$baseLevel === null) {
            self::$baseLevel = \ob_get_level();
        }
        return self::$baseLevel;
    }
}

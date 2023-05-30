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
 *
 * @author Valentino de Lapa
 */
class Session
{

    public static function start(): void
    {
        //session_id(uniqid());
        session_start();
        self::setItem('token', hash("sha512", $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']));
    }

    public static function setItem($key, $value, $serialize = false): void
    {
        if (!$serialize) {
            $_SESSION[$key] = $value;
        } else {
            $_SESSION[$key] = serialize($value);
        }
    }
    
    public static function unsetItem($key):void
    {
        unset($_SESSION[$key]);
    }
    
    public static function getItem($key, $unserialize = false): string|array
    {
        if (!$unserialize) {
            return $_SESSION[$key];
        } else {
            return unserialize($_SESSION[$key]);
        }
    }

    public static function hasItem($key): bool
    {
        return (isset($_SESSION[$key]));
    }

    public static function isValidSession(): bool
    {
        $value = hash("sha512", $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
        return (self::getItem('token') === $value);
    }

    public static function end(): void
    {
        session_destroy();
        $_SESSION = [];
    }

}

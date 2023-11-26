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

use SismaFramework\Core\Enumerations\ComunicationProtocol;
use SismaFramework\Core\HttpClasses\Comunication;
use SismaFramework\Core\HttpClasses\Request;

/**
 *
 * @author Valentino de Lapa
 */
class Session
{

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            self::start();
        }
    }

    public static function start(): void
    {
        $request = new Request();
        session_set_cookie_params([
            'lifetime' => 3600,
            'path' => '/',
            'domain' => $request->server['HTTP_HOST'],
            'secure' => (Comunication::getComunicationProtocol() === ComunicationProtocol::https),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
        session_regenerate_id();
        self::setItem('token', hash("sha512", $request->server['HTTP_USER_AGENT'] . $request->server['REMOTE_ADDR']));
    }

    public function __set($name, $value)
    {
        self::setItem($name, $value);
    }

    public static function setItem(int|string $key, mixed $value, bool $serialize = false): void
    {
        preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        if ($serialize) {
            $_SESSION[$key] = serialize($value);
        } elseif (count($matches[1]) > 0) {
            preg_match("/^[^\\[]*/", $key, $match);
            self::setItemRecursive($_SESSION[$match[0]], $matches[1], $value);
        } else {
            $_SESSION[$key] = $value;
        }
    }

    private static function setItemRecursive(mixed &$currentPosition, array $keys, mixed $value): void
    {
        $actualKey = array_shift($keys);
        if (count($keys) > 0) {
            self::setItemRecursive($currentPosition[$actualKey], $keys, $value);
        } else {
            $currentPosition[$actualKey] = $value;
        }
    }
    
    public static function appendItem(int|string $key, mixed $value, bool $serialize = false): void
    {
        preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        if ($serialize) {
            $_SESSION[$key][] = serialize($value);
        } elseif (count($matches[1]) > 0) {
            preg_match("/^[^\\[]*/", $key, $match);
            self::appendItemRecursive($_SESSION[$match[0]], $matches[1], $value);
        } elseif((isset($_SESSION[$key]) && is_array($_SESSION[$key]) && (in_array($value, $_SESSION[$key]) === false)) || (isset($_SESSION[$key]) === false)) {
            $_SESSION[$key][] = $value;
        }
    }

    private static function appendItemRecursive(mixed &$currentPosition, array $keys, mixed $value): void
    {
        $actualKey = array_shift($keys);
        if (count($keys) > 0) {
            self::setItemRecursive($currentPosition[$actualKey], $keys, $value);
        } elseif(( isset($currentPosition[$actualKey]) && is_array($currentPosition[$actualKey]) && (in_array($value, $currentPosition[$actualKey]) === false)) || (isset($currentPosition[$actualKey]) === false)) {
            $currentPosition[$actualKey][] = $value;
        }
    }

    public function __unset($name)
    {
        self::unsetItem($name);
    }

    public static function unsetItem(int|string $key): void
    {
        $matches = [];
        preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        if (count($matches[1]) > 0) {
            $match = [];
            preg_match("/^[^\\[]*/", $key, $match);
            self::unsetItemRecursive($_SESSION[$match[0]], $matches[1]);
        } else {
            unset($_SESSION[$key]);
        }
    }

    private static function unsetItemRecursive(mixed &$currentPosition, array $keys): void
    {
        $actualKey = array_shift($keys);
        if (count($keys) > 0) {
            self::unsetItemRecursive($currentPosition[$actualKey], $keys);
        } else {
            unset($currentPosition[$actualKey]);
        }
    }

    public function __get($name)
    {
        return self::getItem($name);
    }

    public static function getItem(int|string $key, $unserialize = false): mixed
    {
        preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        if ($unserialize) {
            return unserialize($_SESSION[$key]);
        } elseif (count($matches[1]) > 0) {
            preg_match("/^[^\\[]*/", $key, $match);
            return self::getItemRecursive($_SESSION[$match[0]], $matches[1]);
        } else {
            return $_SESSION[$key];
        }
    }

    private static function getItemRecursive(mixed &$currentPosition, array $keys): mixed
    {
        $actualKey = array_shift($keys);
        if (count($keys) > 0) {
            return self::getItemRecursive($currentPosition[$actualKey], $keys);
        } else {
            return $currentPosition[$actualKey];
        }
    }

    public function __isset($name)
    {
        return self::hasItem($name);
    }

    public static function hasItem($key): bool
    {
        preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        if (count($matches[1]) > 0) {
            preg_match("/^[^\\[]*/", $key, $match);
            return self::hasItemRecursive($_SESSION[$match[0]], $matches[1]);
        } else {
            return isset($_SESSION[$key]);
        }
    }

    private static function hasItemRecursive(mixed &$currentPosition, array $keys): bool
    {
        $actualKey = array_shift($keys);
        if (count($keys) > 0) {
            return self::hasItemRecursive($currentPosition[$actualKey], $keys);
        } else {
            return isset($currentPosition[$actualKey]);
        }
    }

    public static function isValidSession(): bool
    {
        $request = new Request();
        $value = hash("sha512", $request->server['HTTP_USER_AGENT'] . $request->server['REMOTE_ADDR']);
        return (self::getItem('token') === $value);
    }

    public static function end(): void
    {
        session_destroy();
        $_SESSION = [];
    }
}

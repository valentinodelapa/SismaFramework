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

use SismaFramework\Core\Enumerations\CommunicationProtocol;
use SismaFramework\Core\HttpClasses\Communication;
use SismaFramework\Core\HttpClasses\Request;

/**
 *
 * @author Valentino de Lapa
 */
class Session
{

    public function __construct(Request $request = new Request())
    {
        if (session_status() === PHP_SESSION_NONE) {
            self::start($request);
        }
    }

    public static function start(Request $request = new Request()): void
    {
        $config = Config::getInstance();
        ini_set('session.gc_maxlifetime', $config->sessionLifetime);
        session_set_cookie_params([
            "lifetime" => $config->sessionLifetime,
            "path" => $config->sessionCookiePath,
            "domain" => $config->sessionCookieDomain,
            "secure" => Communication::getCommunicationProtocol($request) === CommunicationProtocol::https,
            "httponly" => $config->sessionCookieHttpOnly,
            "samesite" => $config->sessionCookieSameSite,
        ]);
        session_start();
        if (self::followRotationChainOfPresentedId() === false) {
            self::rotateIdKeepingPreviousSessionDuringGracePeriod();
        }
        if (self::hasItem($config->sessionTokenKey) === false) {
            self::setItem($config->sessionTokenKey, self::getClientFingerprint($request, $config));
        }
    }

    private static function getClientFingerprint(Request $request, Config $config): string
    {
        $remoteAddress = $config->sessionTokenIncludesRemoteAddress ? ($request->server['REMOTE_ADDR'] ?? null) : null;
        return hash($config->sessionTokenHashAlgorithm, ($request->server['HTTP_USER_AGENT'] ?? null) . $remoteAddress);
    }

    private static function followRotationChainOfPresentedId(): bool
    {
        $hasFollowedRotation = false;
        $maximumRotationHops = Config::getInstance()->sessionMaximumRotationHops;
        for ($hop = 0; ($hop < $maximumRotationHops) && self::isCurrentSessionRotated(); $hop++) {
            if (self::isRotationStillFollowable() === false) {
                self::replaceWithEmptySession();
                return true;
            }
            self::switchToSession($_SESSION[Config::getInstance()->sessionNextSessionIdKey]);
            $hasFollowedRotation = true;
        }
        return $hasFollowedRotation;
    }

    private static function isCurrentSessionRotated(): bool
    {
        return isset($_SESSION[Config::getInstance()->sessionRotatedAtKey]);
    }

    private static function isRotationStillFollowable(): bool
    {
        $config = Config::getInstance();
        $isWithinGracePeriod = $_SESSION[$config->sessionRotatedAtKey] >= time() - $config->sessionRotationGraceSeconds;
        return $isWithinGracePeriod && isset($_SESSION[$config->sessionNextSessionIdKey]);
    }

    private static function replaceWithEmptySession(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    private static function rotateIdKeepingPreviousSessionDuringGracePeriod(): void
    {
        $previousSessionId = session_id();
        session_regenerate_id(false);
        $nextSessionId = session_id();
        self::switchToSession($previousSessionId);
        self::markCurrentSessionAsRotatedTo($nextSessionId);
        self::switchToSession($nextSessionId);
    }

    private static function markCurrentSessionAsRotatedTo(string $nextSessionId): void
    {
        $config = Config::getInstance();
        $_SESSION[$config->sessionRotatedAtKey] = time();
        $_SESSION[$config->sessionNextSessionIdKey] = $nextSessionId;
    }

    private static function switchToSession(string $sessionId): void
    {
        session_write_close();
        session_id($sessionId);
        session_start();
    }

    public function __set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public static function setItem(int|string $key, mixed $value, bool $serialize = false): void
    {
        $matches = [[], []];
        if (is_string($key) && str_contains($key, '[')) {
            preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        }
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
        $matches = [[], []];
        if (is_string($key) && str_contains($key, '[')) {
            preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        }
        if ($serialize) {
            $_SESSION[$key][] = serialize($value);
        } elseif (count($matches[1]) > 0) {
            preg_match("/^[^\\[]*/", $key, $match);
            self::appendItemRecursive($_SESSION[$match[0]], $matches[1], $value);
        } elseif ((isset($_SESSION[$key]) && is_array($_SESSION[$key]) && (in_array($value, $_SESSION[$key]) === false)) || (isset($_SESSION[$key]) === false)) {
            $_SESSION[$key][] = $value;
        }
    }

    private static function appendItemRecursive(mixed &$currentPosition, array $keys, mixed $value): void
    {
        $actualKey = array_shift($keys);
        if (count($keys) > 0) {
            self::appendItemRecursive($currentPosition[$actualKey], $keys, $value);
        } elseif ((isset($currentPosition[$actualKey]) && is_array($currentPosition[$actualKey]) && (in_array($value, $currentPosition[$actualKey]) === false)) || (isset($currentPosition[$actualKey]) === false)) {
            $currentPosition[$actualKey][] = $value;
        }
    }

    public function __unset($name)
    {
        unset($_SESSION[$name]);
    }

    public static function unsetItem(int|string $key): void
    {
        $matches = [[], []];
        if (is_string($key) && str_contains($key, '[')) {
            preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        }
        if (count($matches[1]) > 0) {
            $match = [];
            preg_match("/^[^\\[]*/", $key, $match);
            self::unsetItemRecursive($_SESSION, [$match[0], ...$matches[1]]);
        } else {
            unset($_SESSION[$key]);
        }
    }

    private static function unsetItemRecursive(array &$currentPosition, array $keys): void
    {
        $actualKey = array_shift($keys);
        if (count($keys) === 0) {
            unset($currentPosition[$actualKey]);
        } elseif (isset($currentPosition[$actualKey]) && is_array($currentPosition[$actualKey])) {
            self::unsetItemRecursive($currentPosition[$actualKey], $keys);
        }
    }

    public function __get($name)
    {
        return $_SESSION[$name];
    }

    public static function getItem(int|string $key, $unserialize = false): mixed
    {
        $matches = [[], []];
        if (is_string($key) && str_contains($key, '[')) {
            preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        }
        if ($unserialize) {
            return unserialize($_SESSION[$key]);
        } elseif (count($matches[1]) > 0) {
            preg_match("/^[^\\[]*/", $key, $match);
            return self::getItemRecursive($_SESSION, [$match[0], ...$matches[1]]);
        } else {
            return $_SESSION[$key];
        }
    }

    private static function getItemRecursive(mixed $currentPosition, array $keys): mixed
    {
        $actualKey = array_shift($keys);
        if (count($keys) > 0) {
            return self::getItemRecursive($currentPosition[$actualKey] ?? null, $keys);
        } else {
            return $currentPosition[$actualKey];
        }
    }

    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }

    public static function hasItem($key): bool
    {
        $matches = [[], []];
        if (is_string($key) && str_contains($key, '[')) {
            preg_match_all("/\\[([^\\]]*)\\]/", $key, $matches);
        }
        if (count($matches[1]) > 0) {
            preg_match("/^[^\\[]*/", $key, $match);
            return self::hasItemRecursive($_SESSION, [$match[0], ...$matches[1]]);
        } else {
            return isset($_SESSION[$key]);
        }
    }

    private static function hasItemRecursive(mixed $currentPosition, array $keys): bool
    {
        $actualKey = array_shift($keys);
        if (count($keys) > 0) {
            return self::hasItemRecursive($currentPosition[$actualKey] ?? null, $keys);
        } else {
            return isset($currentPosition[$actualKey]);
        }
    }

    public static function isValidSession(Request $request = new Request()): bool
    {
        $config = Config::getInstance();
        return hash_equals((string) self::getItem($config->sessionTokenKey), self::getClientFingerprint($request, $config));
    }

    public static function end(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

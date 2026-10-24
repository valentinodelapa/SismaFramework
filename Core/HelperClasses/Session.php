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

    public const ROTATION_GRACE_SECONDS = 30;
    public const ROTATED_AT_KEY = '__sismaSessionRotatedAt';
    public const NEXT_SESSION_ID_KEY = '__sismaNextSessionId';
    private const MAXIMUM_ROTATION_HOPS = 10;

    private \stdClass $sessionProperties;

    public function __construct(Request $request = new Request())
    {
        if (session_status() === PHP_SESSION_NONE) {
            self::start($request);
        }
    }

    public static function start(Request $request = new Request()): void
    {
        ini_set('session.gc_maxlifetime', 3600);
        session_set_cookie_params([
            "lifetime" => 3600,
            "path" => "/",
            "secure" => Communication::getCommunicationProtocol($request) === CommunicationProtocol::https,
            "httponly" => true,
            "samesite" => "Lax",
        ]);
        session_start();
        if (self::followRotation() === false) {
            self::rotate();
        }
        if (self::hasItem('token') === false) {
            self::setItem('token', hash("sha512", ($request->server['HTTP_USER_AGENT'] ?? null) . ($request->server['REMOTE_ADDR'] ?? null)));
        }
    }

    /**
     * Se l'ID presentato è già stato ruotato da una richiesta precedente, aggancia la richiesta alla sessione che lo
     * ha sostituito (seguendo eventuali rotazioni successive) purché la rotazione sia avvenuta entro
     * ROTATION_GRACE_SECONDS; altrimenti la sessione è scaduta e viene sostituita da una vuota. Restituisce true se la
     * richiesta ha seguito una rotazione: in quel caso non si ruota di nuovo.
     */
    private static function followRotation(): bool
    {
        $followed = false;
        for ($hop = 0; ($hop < self::MAXIMUM_ROTATION_HOPS) && isset($_SESSION[self::ROTATED_AT_KEY]); $hop++) {
            if (($_SESSION[self::ROTATED_AT_KEY] < time() - self::ROTATION_GRACE_SECONDS) || (isset($_SESSION[self::NEXT_SESSION_ID_KEY]) === false)) {
                $_SESSION = [];
                session_regenerate_id(true);
                return true;
            }
            $nextSessionId = $_SESSION[self::NEXT_SESSION_ID_KEY];
            session_write_close();
            session_id($nextSessionId);
            session_start();
            $followed = true;
        }
        return $followed;
    }

    /**
     * Sostituisce l'ID di sessione con uno nuovo che ne eredita i dati. La vecchia sessione non viene cancellata
     * subito: resta marcata con l'istante della rotazione e il nuovo ID, così le richieste concorrenti partite con il
     * vecchio cookie (fogli di stile, script, chiamate fetch della stessa pagina) ritrovano la sessione corrente invece
     * di riceverne una vuota, e dopo il periodo di grazia il vecchio ID non è più utilizzabile. Entrambe le sessioni
     * esistono quando vengono riaperte, quindi la rotazione funziona anche con session.use_strict_mode attivo.
     */
    private static function rotate(): void
    {
        $previousSessionId = session_id();
        session_regenerate_id(false);
        $nextSessionId = session_id();
        session_write_close();
        session_id($previousSessionId);
        session_start();
        $_SESSION[self::ROTATED_AT_KEY] = time();
        $_SESSION[self::NEXT_SESSION_ID_KEY] = $nextSessionId;
        session_write_close();
        session_id($nextSessionId);
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
        $value = hash("sha512", ($request->server['HTTP_USER_AGENT'] ?? null) . ($request->server['REMOTE_ADDR'] ?? null));
        return hash_equals((string) self::getItem('token'), $value);
    }

    public static function end(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

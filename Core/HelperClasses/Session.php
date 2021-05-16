<?php

namespace Sisma\Core\HelperClasses;

class Session
{

    public static function start(): void
    {
        //session_id(uniqid());
        session_start();
        self::setItem('token', md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']));
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
        $value = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
        return (self::getItem('token') === $value);
    }

    public static function end(): void
    {
        session_destroy();
        $_SESSION = [];
    }

}

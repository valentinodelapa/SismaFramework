<?php

namespace Sisma\Core\HelperClasses;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\BaseClasses\BaseEnumerator;
use Sisma\Core\Exceptions\FilterException;

class Filter
{

    public static function noFilter($value): bool
    {
        return true;
    }

    public static function isNotNull($value): bool
    {
        return is_null($value) ? false : true;
    }

    public static function isNotFalse($value): bool
    {
        return ($value === false) ? false : true;
    }

    public static function isNotEmpty($value): bool
    {
        return empty($value) ? false : true;
    }

    public static function isString($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = (is_string($value)) ? $result : false;
        return $result;
    }

    public static function isMinLimitString($value, int $minLimit): bool
    {
        $result = true;
        $result = (self::isString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }

    public static function isMaxLimitString($value, int $maxLimit): bool
    {
        $result = true;
        $result = (self::isString($value)) ? $result : false;
        $result = (strlen($value) <= $maxLimit) ? $result : false;
        return $result;
    }

    public static function isLimitString($value, int $minLimit, int $maxLimit): bool
    {
        $result = true;
        $result = (self::isMinLimitString($value, $minLimit)) ? $result : false;
        $result = (self::isMaxLimitString($value, $maxLimit)) ? $result : false;
        return $result;
    }

    public static function isAlphabeticString($value): bool
    {
        $result = true;
        $result = (self::isString($value)) ? $result : false;
        $result = (ctype_alpha($variabile)) ? $result : false;
        return $result;
    }

    public static function isMinLimitAlphabeticString($value, int $minLimit): bool
    {
        $result = true;
        $result = (self::isAlphabeticString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }

    public static function isMaxLimitAlphabeticString($value, int $maxLimit): bool
    {
        $result = true;
        $result = (self::isAlphabeticString($value)) ? $result : false;
        $result = (strlen($value) <= $maxLimit) ? $result : false;
        return $result;
    }

    public static function isLimitAlphabeticString($value, int $minLimit, int $maxLimit): bool
    {
        $result = true;
        $result = (self::isMinLimitAlphabeticString($value, $minLimit)) ? $result : false;
        $result = (self::isMaxLimitAlphabeticString($value, $maxLimit)) ? $result : false;
        return $result;
    }

    public static function isAlphanumericString($value): bool
    {
        $result = true;
        $result = (self::isString($value)) ? $result : false;
        $result = (ctype_alnum($variabile)) ? $result : false;
        return $result;
    }

    public static function isMinLimitAlphanumericString($value, int $minLimit): bool
    {
        $result = true;
        $result = (self::isAlphanumericString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }

    public static function isMaxLimitAlphanumericString($value, int $maxLimit): bool
    {
        $result = true;
        $result = (self::isAlphanumericString($value)) ? $result : false;
        $result = (strlen($value) <= $maxLimit) ? $result : false;
        return $result;
    }

    public static function isLimitAlphanumericString($value, int $minLimit, int $maxLimit): bool
    {
        $result = true;
        $result = (self::isMinLimitAlphanumericString($value, $minLimit)) ? $result : false;
        $result = (self::isMaxLimitAlphanumericString($value, $maxLimit)) ? $result : false;
        return $result;
    }

    public static function isSecurePassword($value): bool
    {
        $result = true;
        $result = (self::isString($value)) ? $result : false;
        $result = (preg_match('/^(?=.*\d)(?=.*[@#\-_$%^&+=§!\?])(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z@#\-_$%^&+=§!\?]{8,}$/', $value)) ? $result : false;
        return $result;
    }

    public static function isEmail($value): bool
    {
        $result = true;
        $result = (self::isString($value)) ? $result : false;
        $result = (preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $value)) ? $result : false;
        return $result;
    }

    public static function isNumeric($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = (is_numeric($value)) ? $result : false;
        return $result;
    }

    public static function isInteger($value): bool
    {
        $result = true;
        $result = (self::isNumeric($value)) ? $result : false;
        $result = (is_integer($value)) ? $result : false;
        return $result;
    }

    public static function isFloat($value): bool
    {
        $result = true;
        $result = (self::isNumeric($value)) ? $result : false;
        $result = (is_float($value)) ? $result : false;
        return $result;
    }
    
    public static function isBoolean($value):bool
    {
        return is_bool($value);
    }

    public static function isArray($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = (is_array($value)) ? $result : false;
        return $result;
    }
    
    public static function isUploadedFile($value) : bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = (is_uploaded_file($value)) ? $result : false;
        return $result;
    }
    
    public static function isEntity($value):bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = ($value instanceof BaseEntity) ? $result : false;
        return $result;
    }
    
    public static function isEnumerator($value):bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = ($value instanceof BaseEnumerator) ? $result : false;
        return $result;
    }

}

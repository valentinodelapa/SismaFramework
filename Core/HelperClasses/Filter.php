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

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaTime;

/**
 *
 * @author Valentino de Lapa
 */
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
        if ($value === 0) {
            return true;
        } else {
            return empty($value) ? false : true;
        }
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
        $result = (ctype_alpha($value)) ? $result : false;
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
        $result = (ctype_alnum($value)) ? $result : false;
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

    public static function isStrictAlphanumericString($value): bool
    {
        $result = true;
        $result = (self::isAlphanumericString($value)) ? $result : false;
        $result = (ctype_alpha($value)) ? false : $result;
        $result = (ctype_digit($value)) ? false : $result;
        return $result;
    }

    public static function isMinLimitStrictAlphanumericString($value, int $minLimit): bool
    {
        $result = true;
        $result = (self::isStrictAlphanumericString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }

    public static function isMaxLimitStrictAlphanumericString($value, int $maxLimit): bool
    {
        $result = true;
        $result = (self::isStrictAlphanumericString($value)) ? $result : false;
        $result = (strlen($value) <= $maxLimit) ? $result : false;
        return $result;
    }

    public static function isLimitStrictAlphanumericString($value, int $minLimit, int $maxLimit): bool
    {
        $result = true;
        $result = (self::isMinLimitStrictAlphanumericString($value, $minLimit)) ? $result : false;
        $result = (self::isMaxLimitStrictAlphanumericString($value, $maxLimit)) ? $result : false;
        return $result;
    }

    public static function isSecurePassword($value): bool
    {
        $result = true;
        $result = (self::isString($value)) ? $result : false;
        if ($result) {
            $result = (preg_match('/^(?=.*\d)(?=.*[@#\-_$%^&+=§!\?])(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z@#\-_$%^&+=§!\?]{8,}$/', $value)) ? $result : false;
        }
        return $result;
    }

    public static function isEmail($value): bool
    {
        $result = true;
        $result = (self::isString($value)) ? $result : false;
        if ($result) {
            $result = (preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $value)) ? $result : false;
        }
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

    public static function isBoolean($value): bool
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

    public static function isDate($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        if ($result) {
            $result = $value instanceof SismaDate;
        }
        return $result;
    }

    public static function isDatetime($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        if ($result) {
            $result = $value instanceof SismaDateTime;
        }
        return $result;
    }

    public static function isTime($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        if ($result) {
            $result = $value instanceof SismaTime;
        }
        return $result;
    }

    public static function isUploadedFile($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = (self::isArray($value)) ? $result : false;
        $result = (array_key_exists('error', $value)) ? $result : false;
        $result = ($value['error'] === 0) ? $result : false;
        $result = (array_key_exists('tmp_name', $value)) ? $result : false;
        $result = (is_uploaded_file($value['tmp_name'])) ? $result : false;
        $result = (mime_content_type($value['tmp_name']) !== false) ? $result : false;
        return $result;
    }

    public static function isEntity($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = ($value instanceof BaseEntity) ? $result : false;
        return $result;
    }

    public static function isEnumeration($value): bool
    {
        $result = true;
        $result = (self::isNotEmpty($value)) ? $result : false;
        $result = is_subclass_of($value, \UnitEnum::class) ? $result : false;
        return $result;
    }
}

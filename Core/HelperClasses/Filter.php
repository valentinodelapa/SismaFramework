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

    public function noFilter($value): bool
    {
        return true;
    }

    public function isNotNull($value): bool
    {
        return is_null($value) ? false : true;
    }

    public function isNotFalse($value): bool
    {
        return ($value === false) ? false : true;
    }

    public function isNotEmpty($value): bool
    {
        if ((is_object($value) === false) && ($value == 0)) {
            return true;
        } else {
            return empty($value) ? false : true;
        }
    }

    public function isString($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        $result = (is_string($value)) ? $result : false;
        return $result;
    }

    public function isMinLimitString($value, int $minLimit): bool
    {
        $result = true;
        $result = ($this->isString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }

    public function isMaxLimitString($value, int $maxLimit): bool
    {
        $result = true;
        $result = ($this->isString($value)) ? $result : false;
        $result = (strlen($value) <= $maxLimit) ? $result : false;
        return $result;
    }

    public function isLimitString($value, int $minLimit, int $maxLimit): bool
    {
        $result = true;
        $result = ($this->isMinLimitString($value, $minLimit)) ? $result : false;
        $result = ($this->isMaxLimitString($value, $maxLimit)) ? $result : false;
        return $result;
    }

    public function isAlphabeticString($value): bool
    {
        $result = true;
        $result = ($this->isString($value)) ? $result : false;
        $result = (ctype_alpha($value)) ? $result : false;
        return $result;
    }

    public function isMinLimitAlphabeticString($value, int $minLimit): bool
    {
        $result = true;
        $result = ($this->isAlphabeticString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }

    public function isMaxLimitAlphabeticString($value, int $maxLimit): bool
    {
        $result = true;
        $result = ($this->isAlphabeticString($value)) ? $result : false;
        $result = (strlen($value) <= $maxLimit) ? $result : false;
        return $result;
    }

    public function isLimitAlphabeticString($value, int $minLimit, int $maxLimit): bool
    {
        $result = true;
        $result = ($this->isMinLimitAlphabeticString($value, $minLimit)) ? $result : false;
        $result = ($this->isMaxLimitAlphabeticString($value, $maxLimit)) ? $result : false;
        return $result;
    }

    public function isAlphanumericString($value): bool
    {
        $result = true;
        $result = ($this->isString($value)) ? $result : false;
        $result = (ctype_alnum($value)) ? $result : false;
        return $result;
    }

    public function isMinLimitAlphanumericString($value, int $minLimit): bool
    {
        $result = true;
        $result = ($this->isAlphanumericString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }

    public function isMaxLimitAlphanumericString($value, int $maxLimit): bool
    {
        $result = true;
        $result = ($this->isAlphanumericString($value)) ? $result : false;
        $result = (strlen($value) <= $maxLimit) ? $result : false;
        return $result;
    }

    public function isLimitAlphanumericString($value, int $minLimit, int $maxLimit): bool
    {
        $result = true;
        $result = ($this->isMinLimitAlphanumericString($value, $minLimit)) ? $result : false;
        $result = ($this->isMaxLimitAlphanumericString($value, $maxLimit)) ? $result : false;
        return $result;
    }

    public function isStrictAlphanumericString($value): bool
    {
        $result = true;
        $result = ($this->isAlphanumericString($value)) ? $result : false;
        $result = (ctype_alpha($value)) ? false : $result;
        $result = (ctype_digit($value)) ? false : $result;
        return $result;
    }

    public function isMinLimitStrictAlphanumericString($value, int $minLimit): bool
    {
        $result = true;
        $result = ($this->isStrictAlphanumericString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }

    public function isMaxLimitStrictAlphanumericString($value, int $maxLimit): bool
    {
        $result = true;
        $result = ($this->isStrictAlphanumericString($value)) ? $result : false;
        $result = (strlen($value) <= $maxLimit) ? $result : false;
        return $result;
    }

    public function isLimitStrictAlphanumericString($value, int $minLimit, int $maxLimit): bool
    {
        $result = true;
        $result = ($this->isMinLimitStrictAlphanumericString($value, $minLimit)) ? $result : false;
        $result = ($this->isMaxLimitStrictAlphanumericString($value, $maxLimit)) ? $result : false;
        return $result;
    }

    public function isSecurePassword($value): bool
    {
        $result = true;
        $result = ($this->isString($value)) ? $result : false;
        if ($result) {
            $result = (preg_match('/^(?=.*\d)(?=.*[@#\-_$%^&+=§!\?])(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z@#\-_$%^&+=§!\?]{8,}$/', $value)) ? $result : false;
        }
        return $result;
    }

    public function isEmail($value): bool
    {
        $result = true;
        $result = ($this->isString($value)) ? $result : false;
        if ($result) {
            $result = (preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $value)) ? $result : false;
        }
        return $result;
    }

    public function isNumeric($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        $result = (is_numeric($value)) ? $result : false;
        return $result;
    }

    public function isInteger($value): bool
    {
        $result = true;
        $result = ($this->isNumeric($value)) ? $result : false;
        $result = (is_integer($value)) ? $result : false;
        return $result;
    }

    public function isFloat($value): bool
    {
        $result = true;
        $result = ($this->isNumeric($value)) ? $result : false;
        $result = (is_float($value)) ? $result : false;
        return $result;
    }

    public function isBoolean($value): bool
    {
        return is_bool($value);
    }

    public function isArray($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        $result = (is_array($value)) ? $result : false;
        return $result;
    }

    public function isDate($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        if ($result) {
            $result = $value instanceof SismaDate;
        }
        return $result;
    }

    public function isDatetime($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        if ($result) {
            $result = $value instanceof SismaDateTime;
        }
        return $result;
    }

    public function isTime($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        if ($result) {
            $result = $value instanceof SismaTime;
        }
        return $result;
    }

    public function isUploadedFile($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        $result = ($this->isArray($value)) ? $result : false;
        $result = (array_key_exists('error', $value)) ? $result : false;
        $result = ($value['error'] === 0) ? $result : false;
        $result = (array_key_exists('tmp_name', $value)) ? $result : false;
        $result = (is_uploaded_file($value['tmp_name'])) ? $result : false;
        $result = (mime_content_type($value['tmp_name']) !== false) ? $result : false;
        return $result;
    }

    public function isEntity($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        $result = ($value instanceof BaseEntity) ? $result : false;
        return $result;
    }

    public function isEnumeration($value): bool
    {
        $result = true;
        $result = ($this->isNotEmpty($value)) ? $result : false;
        $result = is_subclass_of($value, \UnitEnum::class) ? $result : false;
        return $result;
    }

    public function customFilter(mixed $value, string $regularExpression): bool
    {
        return preg_match($regularExpression, $value);
    }
}

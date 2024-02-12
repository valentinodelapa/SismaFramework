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

namespace SismaFramework\Core\Exceptions;

/**
 * @author Valentino de Lapa
 */
class PhpVersionException extends \Exception
{

    private static int $minimumMajorPhpVersion = \Config\MINIMUM_MAJOR_PHP_VERSION;
    private static int $minimumMinorPhpVersion = \Config\MINIMUM_MINOR_PHP_VERSION;
    private static int $minimumReleasePhpVersion = \Config\MINIMUM_RELEASE_PHP_VERSION;

    public function __construct()
    {
        $phpMinimumVersionRequired = self::$minimumMajorPhpVersion . '.' . self::$minimumMinorPhpVersion . '.' . self::$minimumReleasePhpVersion;
        $phpActualVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        $message = "The minimum required version of PHP is " . $phpMinimumVersionRequired . ". Your version of PHP is " . $phpActualVersion . ".<br />Please update your PHP version to " . $phpMinimumVersionRequired . " or higher in order to use this application.";
        return parent::__construct($message);
    }
}

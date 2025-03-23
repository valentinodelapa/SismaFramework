<?php

/*
 * The MIT License
 *
 * Copyright (c) 2023-present Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\HelperClasses\PhpVersionChecker;
use SismaFramework\Core\Exceptions\PhpVersionException;

/**
 * Description of PhpVersionCheckerTest
 *
 * @author Valentino de Lapa
 */
class PhpVersionCheckerTest extends TestCase
{

    private PhpVersionCheckerConfigTest $phpVersionCheckerConfigTest;

    #[\Override]
    public function setUp():void
    {
        $this->phpVersionCheckerConfigTest = new PhpVersionCheckerConfigTest();
        BaseConfig::setInstance($this->phpVersionCheckerConfigTest);
    }

    #[RunInSeparateProcess]
    public function testMinimumMajorVersionNotPassed()
    {
        $this->expectException(PhpVersionException::class);
        PhpVersionChecker::forceCurrentMajorVersionValue(7);
        PhpVersionChecker::checkPhpVersion($this->phpVersionCheckerConfigTest);
    }

    #[RunInSeparateProcess]
    public function testMinimumMinorVersionNotPassed()
    {
        $this->expectException(PhpVersionException::class);
        PhpVersionChecker::forceCurrentMajorVersionValue(8);
        PhpVersionChecker::forceCurrentMinorVersionValue(0);
        PhpVersionChecker::checkPhpVersion($this->phpVersionCheckerConfigTest);
    }

    #[RunInSeparateProcess]
    public function testMinimumReleaseVersionNotPassed()
    {
        $this->expectException(PhpVersionException::class);
        PhpVersionChecker::forceCurrentMajorVersionValue(8);
        PhpVersionChecker::forceCurrentMinorVersionValue(1);
        PhpVersionChecker::forceCurrentReleaseVersionValue(0);
        PhpVersionChecker::checkPhpVersion($this->phpVersionCheckerConfigTest);
    }
}

class PhpVersionCheckerConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        return false;
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->minimumMajorPhpVersion = 8;
        $this->minimumMinorPhpVersion = 1;
        $this->minimumReleasePhpVersion = 1;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        
    }
}

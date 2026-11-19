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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\PhpVersionChecker;
use SismaFramework\Core\Exceptions\PhpVersionException;

/**
 * Description of PhpVersionCheckerTest
 *
 * @author Valentino de Lapa
 */
class PhpVersionCheckerTest extends TestCase
{

    private string $composerJsonPath;

    #[\Override]
    protected function setUp(): void
    {
        $this->composerJsonPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sisma_composer_' . uniqid() . '.json';
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (is_file($this->composerJsonPath)) {
            unlink($this->composerJsonPath);
        }
    }

    private function writeComposerJson(?string $phpRequirement): void
    {
        $require = $phpRequirement === null ? ['psr/log' => '^3.0'] : ['php' => $phpRequirement];
        file_put_contents($this->composerJsonPath, json_encode(['name' => 'test/test', 'require' => $require]));
    }

    private function assertMinimumVersionNotPassed(string $minimumPhpVersion): void
    {
        $this->writeComposerJson('>=' . $minimumPhpVersion);
        $this->expectException(PhpVersionException::class);
        $this->expectExceptionMessage('The minimum required version of PHP is ' . $minimumPhpVersion . '.');
        PhpVersionChecker::checkPhpVersion($this->composerJsonPath);
    }

    public function testMinimumMajorVersionNotPassed(): void
    {
        $this->assertMinimumVersionNotPassed((PHP_MAJOR_VERSION + 1) . '.0.0');
    }

    public function testMinimumMinorVersionNotPassed(): void
    {
        $this->assertMinimumVersionNotPassed(PHP_MAJOR_VERSION . '.' . (PHP_MINOR_VERSION + 1) . '.0');
    }

    public function testMinimumReleaseVersionNotPassed(): void
    {
        $this->assertMinimumVersionNotPassed(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . (PHP_RELEASE_VERSION + 1));
    }

    public function testMinimumVersionPassed(): void
    {
        $this->expectNotToPerformAssertions();
        $this->writeComposerJson('>=' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION);
        PhpVersionChecker::checkPhpVersion($this->composerJsonPath);
    }

    public function testCheckSkippedWithoutComposerJson(): void
    {
        $this->expectNotToPerformAssertions();
        PhpVersionChecker::checkPhpVersion($this->composerJsonPath);
    }

    #[DataProvider('phpRequirementProvider')]
    public function testGetMinimumPhpVersion(?string $phpRequirement, ?string $expectedVersion): void
    {
        $this->writeComposerJson($phpRequirement);
        $this->assertSame($expectedVersion, PhpVersionChecker::getMinimumPhpVersion($this->composerJsonPath));
    }

    public static function phpRequirementProvider(): array
    {
        return [
            'greater or equal with release' => ['>=8.3.0', '8.3.0'],
            'caret without release' => ['^8.4', '8.4.0'],
            'tilde with release' => ['~8.3.5', '8.3.5'],
            'alternative constraints' => ['^8.3 || ^9.0', '8.3.0'],
            'missing requirement' => [null, null],
        ];
    }

    public function testGetMinimumPhpVersionWithoutComposerJson(): void
    {
        $this->assertNull(PhpVersionChecker::getMinimumPhpVersion($this->composerJsonPath));
    }

    public function testGetMinimumPhpVersionFromFrameworkComposerJson(): void
    {
        $this->assertTrue(version_compare(PhpVersionChecker::getMinimumPhpVersion(), '8.3.0', '>='));
    }
}

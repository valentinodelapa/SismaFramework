<?php

/*
 * The MIT License
 *
 * Copyright 2024 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Tests\Core\Traits;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\TestsApplication\Enumerations\SampleType;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SelectableEnumerationTest extends TestCase
{

    #[\Override]
    public function setUp(): void
    {
        BaseConfig::setInstance(new SelectableEnumerationConfigTest());
    }

    public function testGetFriendlyLabel()
    {
        $this->assertEquals('uno', SampleType::one->getFriendlyLabel(Language::italian));
        $this->assertEquals('due', SampleType::two->getFriendlyLabel(Language::italian));
    }

    public function testGetChoiceFromEnumerations()
    {
        $choices = SampleType::getChoiceFromEnumerations(Language::italian);
        $this->assertIsArray($choices);
        $this->assertEquals([
            "uno" => "O",
            "due" => "T"
                ], $choices);
    }
}

class SelectableEnumerationConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        switch ($name) {
            case 'rootPath':
                return true;
            default:
                return false;
        }
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->language = Language::italian;
        $this->localesPath = 'TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        $this->rootPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    }
}

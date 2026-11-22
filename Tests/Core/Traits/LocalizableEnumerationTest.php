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

namespace SismaFramework\Tests\Core\Traits;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Exceptions\LocalizatorException;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Localizator;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\TestsApplication\Enumerations\SampleRank;
use SismaFramework\TestsApplication\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */
class LocalizableEnumerationTest extends TestCase
{

    private Config $configStub;

    #[\Override]
    public function setUp(): void
    {
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['language', Language::italian],
                    ['localesPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
        ]);
        Config::setInstance($this->configStub);
        Localizator::unsetLanguage();
        ModuleManager::setApplicationModule('SismaFramework');
        ModuleManager::unsetCustomVisualizationModule();
        ModuleManager::getExistingFilePath('TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . Language::italian->value, Resource::json, $this->configStub);
    }

    public function testGetFriendlyLabelUsesContextualLanguage()
    {
        $this->assertEquals('uno', SampleType::one->getFriendlyLabel());
    }

    public function testGetFriendlyLabelWithLocalizator()
    {
        $this->assertEquals('due', SampleType::two->getFriendlyLabel([], new Localizator(Language::italian, $this->configStub)));
    }

    public function testGetFriendlyLabelWithDeprecatedLanguage()
    {
        $this->assertEquals('uno', SampleType::one->getFriendlyLabel(Language::italian));
    }

    public function testGetFriendlyLabelOfComposedValue()
    {
        $this->assertEquals('Cavaliere', SampleRank::knight->getFriendlyLabel());
    }

    public function testGetFriendlyLabelWithPathToString()
    {
        $this->assertEquals('Dama', SampleRank::knight->getFriendlyLabel('female'));
    }

    public function testGetFriendlyLabelWithPathToComposedValue()
    {
        $this->assertEquals('Cavaliere', SampleRank::knight->getFriendlyLabel(['male']));
    }

    public function testGetFriendlyLabelWithoutLabelThrowsException()
    {
        $this->expectException(LocalizatorException::class);
        SampleRank::officer->getFriendlyLabel();
    }

    public function testGetFriendlyLabelWithMissingPathThrowsException()
    {
        $this->expectException(LocalizatorException::class);
        SampleRank::knight->getFriendlyLabel('neutral');
    }

    public function testGetLocalizedAttribute()
    {
        $this->assertEquals('Primo grado', SampleRank::knight->getLocalizedAttribute('description'));
    }

    public function testGetLocalizedAttributeWithNestedPath()
    {
        $this->assertEquals('Cav.', SampleRank::knight->getLocalizedAttribute(['male', 'initial']));
    }

    public function testGetLocalizedAttributeWithNullValue()
    {
        $this->assertNull(SampleRank::knight->getLocalizedAttribute('initials'));
    }

    public function testGetLocalizedAttributeReturnsComposedValue()
    {
        $this->assertEquals(['male' => 'Ufficiale', 'female' => 'Ufficiale'], SampleRank::officer->getLocalizedAttribute([]));
    }

    public function testGetLocalizedAttributeWithMissingAttributeThrowsException()
    {
        $this->expectException(LocalizatorException::class);
        SampleRank::knight->getLocalizedAttribute('missing');
    }

    public function testGetLocalizedAttributeOnSimpleValueThrowsException()
    {
        $this->expectException(LocalizatorException::class);
        SampleType::one->getLocalizedAttribute('description');
    }
}

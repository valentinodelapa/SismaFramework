<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\Enumerations;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Enumerations\Language;

/**
 * Test for Language enumeration
 * @author Valentino de Lapa
 */
class LanguageTest extends TestCase
{
    public function testEnumValuesAreCorrect()
    {
        $this->assertEquals('en_US', Language::usEnglish->value);
        $this->assertEquals('en_GB', Language::english->value);
        $this->assertEquals('it_IT', Language::italian->value);
        $this->assertEquals('fr_FR', Language::french->value);
        $this->assertEquals('de_DE', Language::german->value);
        $this->assertEquals('es_ES', Language::spanish->value);
    }

    public function testEnumIsBackedByString()
    {
        $this->assertInstanceOf(\BackedEnum::class, Language::italian);
        $this->assertIsString(Language::italian->value);
    }

    public function testFromMethodWorks()
    {
        $this->assertEquals(Language::italian, Language::from('it_IT'));
        $this->assertEquals(Language::english, Language::from('en_GB'));
        $this->assertEquals(Language::usEnglish, Language::from('en_US'));
    }

    public function testTryFromMethodWorks()
    {
        $this->assertEquals(Language::italian, Language::tryFrom('it_IT'));
        $this->assertEquals(Language::french, Language::tryFrom('fr_FR'));
        $this->assertNull(Language::tryFrom('invalid_locale'));
    }

    public function testGetFriendlyLabelMethod()
    {
        $this->assertEquals('Italiano', Language::italian->getFriendlyLabel(Language::italian));
        $this->assertEquals('English', Language::english->getFriendlyLabel(Language::english));
        $this->assertEquals('US English', Language::usEnglish->getFriendlyLabel(Language::usEnglish));
        $this->assertEquals('Français', Language::french->getFriendlyLabel(Language::french));
        $this->assertEquals('Deutsch', Language::german->getFriendlyLabel(Language::german));
        $this->assertEquals('Español', Language::spanish->getFriendlyLabel(Language::spanish));
    }

    public function testGetFriendlyLabelWithUnicodeCharacters()
    {
        // Test languages with Unicode characters
        $this->assertEquals('العربية', Language::arabic->getFriendlyLabel(Language::arabic));
        $this->assertEquals('中文', Language::chinese->getFriendlyLabel(Language::chinese));
        $this->assertEquals('日本語', Language::japanese->getFriendlyLabel(Language::japanese));
        $this->assertEquals('한국어', Language::korean->getFriendlyLabel(Language::korean));
        $this->assertEquals('Русский', Language::russian->getFriendlyLabel(Language::russian));
        $this->assertEquals('हिंदी', Language::hindi->getFriendlyLabel(Language::hindi));
        $this->assertEquals('ภาษาไทย', Language::thai->getFriendlyLabel(Language::thai));
    }

    public function testGetISO6391LabelMethod()
    {
        $this->assertEquals('IT', Language::italian->getISO6391Label());
        $this->assertEquals('GB', Language::english->getISO6391Label());
        $this->assertEquals('US', Language::usEnglish->getISO6391Label());
        $this->assertEquals('FR', Language::french->getISO6391Label());
        $this->assertEquals('DE', Language::german->getISO6391Label());
        $this->assertEquals('ES', Language::spanish->getISO6391Label());
        $this->assertEquals('CN', Language::chinese->getISO6391Label());
        $this->assertEquals('JP', Language::japanese->getISO6391Label());
    }

    public function testAllLanguagesHaveValidLocaleFormat()
    {
        $cases = Language::cases();

        foreach ($cases as $language) {
            // Test that each locale follows the pattern xx_XX
            $this->assertMatchesRegularExpression('/^[a-z]{2}_[A-Z]{2}$/', $language->value);
        }
    }

    public function testSelectableEnumerationTrait()
    {
        // Test that the trait is used
        $reflection = new \ReflectionClass(Language::class);
        $traitNames = $reflection->getTraitNames();
        $this->assertContains('SismaFramework\Core\Traits\SelectableEnumeration', $traitNames);
    }

    public function testSpecificLanguagePairs()
    {
        // Test Portuguese variants
        $this->assertEquals('pt_PT', Language::portuguese->value);
        $this->assertEquals('pt_BR', Language::brazilianPortuguese->value);
        $this->assertEquals('Português', Language::portuguese->getFriendlyLabel(Language::portuguese));
        $this->assertEquals('Português', Language::brazilianPortuguese->getFriendlyLabel(Language::brazilianPortuguese));

        // Test English variants
        $this->assertEquals('en_GB', Language::english->value);
        $this->assertEquals('en_US', Language::usEnglish->value);
        $this->assertEquals('English', Language::english->getFriendlyLabel(Language::english));
        $this->assertEquals('US English', Language::usEnglish->getFriendlyLabel(Language::usEnglish));
    }

    public function testCasesMethodReturnsAllLanguages()
    {
        $cases = Language::cases();
        $this->assertIsArray($cases);
        $this->assertGreaterThan(30, count($cases)); // Should have many languages

        // Verify some key languages are present
        $values = array_map(fn($case) => $case->value, $cases);
        $expectedLanguages = ['it_IT', 'en_US', 'en_GB', 'fr_FR', 'de_DE', 'es_ES', 'zh_CN', 'ja_JP', 'ar_SA'];

        foreach ($expectedLanguages as $lang) {
            $this->assertContains($lang, $values);
        }
    }
}
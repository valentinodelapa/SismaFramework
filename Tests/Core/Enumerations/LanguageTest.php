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

    public function testGetFriendlyLabelMethodExists()
    {
        $this->assertTrue(method_exists(Language::italian, 'getFriendlyLabel'));
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
        $this->assertEquals('TW', Language::chineseTraditional->getISO6391Label());
    }

    public function testAllLanguagesHaveValidLocaleFormat()
    {
        $cases = Language::cases();

        foreach ($cases as $language) {
            $this->assertMatchesRegularExpression('/^[a-z]{2}_[A-Z]{2}$/', $language->value);
        }
    }

    public function testSelectableEnumerationTrait()
    {
        $reflection = new \ReflectionClass(Language::class);
        $traitNames = $reflection->getTraitNames();
        $this->assertContains('SismaFramework\Core\Traits\SelectableEnumeration', $traitNames);
    }

    public function testNewLanguagesArePresent()
    {
        $this->assertEquals('am_ET', Language::amharic->value);
        $this->assertEquals('eu_ES', Language::basque->value);
        $this->assertEquals('my_MM', Language::burmese->value);
        $this->assertEquals('ca_ES', Language::catalan->value);
        $this->assertEquals('gu_IN', Language::gujarati->value);
        $this->assertEquals('ha_NG', Language::hausa->value);
        $this->assertEquals('is_IS', Language::icelandic->value);
        $this->assertEquals('kn_IN', Language::kannada->value);
        $this->assertEquals('ms_MY', Language::malay->value);
        $this->assertEquals('mr_IN', Language::marathi->value);
        $this->assertEquals('pa_IN', Language::punjabi->value);
        $this->assertEquals('qu_PE', Language::quechua->value);
        $this->assertEquals('sw_KE', Language::swahili->value);
        $this->assertEquals('ta_IN', Language::tamil->value);
        $this->assertEquals('te_IN', Language::telugu->value);
        $this->assertEquals('ur_PK', Language::urdu->value);
    }

    public function testRegionalVariants()
    {
        $this->assertEquals('en_GB', Language::english->value);
        $this->assertEquals('en_US', Language::usEnglish->value);
        $this->assertEquals('en_AU', Language::australianEnglish->value);
        $this->assertEquals('en_CA', Language::canadianEnglish->value);
        $this->assertEquals('en_IN', Language::indianEnglish->value);

        $this->assertEquals('es_ES', Language::spanish->value);
        $this->assertEquals('es_MX', Language::mexicanSpanish->value);
        $this->assertEquals('es_AR', Language::argentinianSpanish->value);
        $this->assertEquals('es_CO', Language::colombianSpanish->value);

        $this->assertEquals('pt_PT', Language::portuguese->value);
        $this->assertEquals('pt_BR', Language::brazilianPortuguese->value);
        $this->assertEquals('pt_AO', Language::angolanPortuguese->value);

        $this->assertEquals('fr_FR', Language::french->value);
        $this->assertEquals('fr_CA', Language::canadianFrench->value);

        $this->assertEquals('de_DE', Language::german->value);
        $this->assertEquals('de_AT', Language::austrianGerman->value);
        $this->assertEquals('de_CH', Language::swissGerman->value);

        $this->assertEquals('zh_CN', Language::chinese->value);
        $this->assertEquals('zh_TW', Language::chineseTraditional->value);

        $this->assertEquals('ar_SA', Language::arabic->value);
        $this->assertEquals('ar_EG', Language::egyptianArabic->value);
    }

    public function testCasesMethodReturnsAllLanguages()
    {
        $cases = Language::cases();
        $this->assertIsArray($cases);
        $this->assertGreaterThan(60, count($cases));
        $values = array_map(fn($case) => $case->value, $cases);
        $expectedLanguages = [
            'it_IT', 'en_US', 'en_GB', 'fr_FR', 'de_DE', 'es_ES',
            'zh_CN', 'ja_JP', 'ar_SA', 'hi_IN', 'ru_RU'
        ];

        foreach ($expectedLanguages as $lang) {
            $this->assertContains($lang, $values);
        }
    }

    public function testGetChoiceFromEnumerationsMethodExists()
    {
        $this->assertTrue(method_exists(Language::class, 'getChoiceFromEnumerations'));
    }

    public function testIndianLanguages()
    {
        $indianLanguages = [
            Language::hindi->value => 'hi_IN',
            Language::gujarati->value => 'gu_IN',
            Language::kannada->value => 'kn_IN',
            Language::marathi->value => 'mr_IN',
            Language::punjabi->value => 'pa_IN',
            Language::tamil->value => 'ta_IN',
            Language::telugu->value => 'te_IN',
            Language::indianEnglish->value => 'en_IN',
        ];

        foreach ($indianLanguages as $actual => $expected) {
            $this->assertEquals($expected, $actual);
        }
    }

    public function testLessCommonLanguages()
    {
        $lessCommon = [
            Language::amharic->value => 'am_ET',
            Language::basque->value => 'eu_ES',
            Language::burmese->value => 'my_MM',
            Language::hausa->value => 'ha_NG',
            Language::icelandic->value => 'is_IS',
            Language::quechua->value => 'qu_PE',
            Language::swahili->value => 'sw_KE',
            Language::urdu->value => 'ur_PK',
        ];

        foreach ($lessCommon as $actual => $expected) {
            $this->assertEquals($expected, $actual);
        }
    }
}
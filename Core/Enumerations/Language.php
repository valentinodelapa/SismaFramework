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

namespace SismaFramework\Core\Enumerations;

/**
 *
 * @author Valentino de Lapa
 */
enum Language: string
{

    use \SismaFramework\Core\Traits\SelectableEnumeration;

    case arabic = 'ar_SA';
    case bengali = 'bn_BD';
    case bulgarian = 'bg_BG';
    case chinese = 'zh_CN';
    case croatian = 'hr_HR';
    case czech = 'cs_CZ';
    case danish = 'da_DK';
    case german = 'de_DE';
    case greek = 'el_GR';
    case usEnglish = 'en_US';
    case english = 'en_GB';
    case spanish = 'es_ES';
    case estonian = 'et_EE';
    case persian = 'fa_IR';
    case finnish = 'fi_FI';
    case french = 'fr_FR';
    case hebrew = 'he_IL';
    case hindi = 'hi_IN';
    case hungarian = 'hu_HU';
    case indonesian = 'id_ID';
    case italian = 'it_IT';
    case japanese = 'ja_JP';
    case kazakh = 'kk_KZ';
    case korean = 'ko_KR';
    case latvian = 'lv_LV';
    case lithuanian = 'lt_LT';
    case dutch = 'nl_NL';
    case norwegian = 'no_NO';
    case polish = 'pl_PL';
    case portuguese = 'pt_PT';
    case brazilianPortuguese = 'pt_BR';
    case romanian = 'ro_RO';
    case russian = 'ru_RU';
    case serbian = 'sr_RS';
    case slovak = 'sk_SK';
    case slovenian = 'sl_SI';
    case swedish = 'sv_SE';
    case thai = 'th_TH';
    case turkish = 'tr_TR';
    case ukrainian = 'uk_UA';
    case vietnamese = 'vi_VN';

    public function getFriendlyLabel(self $language): string
    {
        return match ($this) {
            self::usEnglish => "US English",
            self::arabic => "العربية",
            self::bengali => "বাংলা",
            self::bulgarian => "Български", 
            self::chinese => "中文",
            self::croatian => "Hrvatski",
            self::czech => "Čeština",
            self::danish => "Dansk",
            self::dutch => "Nederlands",
            self::english => "English",
            self::estonian => "Eesti",
            self::finnish => "Suomi",
            self::french => "Français",
            self::german => "Deutsch",
            self::greek => "Ελληνικά",
            self::hebrew => "עברית",
            self::hindi => "हिंदी",
            self::hungarian => "Magyar",
            self::indonesian => "Bahasa Indonesia",
            self::italian => "Italiano",
            self::japanese => "日本語",
            self::kazakh => "Қазақ",
            self::korean => "한국어",
            self::latvian => "Latviešu",
            self::lithuanian => "Lietuvių",
            self::norwegian => "Norsk",
            self::persian => "فارسی",
            self::polish => "Polski",
            self::portuguese => "Português",
            self::brazilianPortuguese => "Português",
            self::romanian => "Română",
            self::russian => "Русский",
            self::serbian => "Српски",
            self::slovak => "Slovenčina",
            self::slovenian => "Slovenščina",
            self::spanish => "Español",
            self::swedish => "Svenska",
            self::thai => "ภาษาไทย",
            self::turkish => "Türkçe",
            self::ukrainian => "Українська",
            self::vietnamese => "Tiếng Việt",
        };
    }

    public function getISO6391Label(): string
    {
        return explode('_', $this->value)[1];
    }
}

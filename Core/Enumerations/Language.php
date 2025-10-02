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

    case amharic = 'am_ET';
    case arabic = 'ar_SA';
    case egyptianArabic = 'ar_EG';
    case basque = 'eu_ES';
    case bengali = 'bn_BD';
    case bulgarian = 'bg_BG';
    case burmese = 'my_MM';
    case catalan = 'ca_ES';
    case chinese = 'zh_CN';
    case chineseTraditional = 'zh_TW';
    case croatian = 'hr_HR';
    case czech = 'cs_CZ';
    case danish = 'da_DK';
    case german = 'de_DE';
    case austrianGerman = 'de_AT';
    case swissGerman = 'de_CH';
    case greek = 'el_GR';
    case usEnglish = 'en_US';
    case english = 'en_GB';
    case australianEnglish = 'en_AU';
    case canadianEnglish = 'en_CA';
    case indianEnglish = 'en_IN';
    case spanish = 'es_ES';
    case mexicanSpanish = 'es_MX';
    case argentinianSpanish = 'es_AR';
    case colombianSpanish = 'es_CO';
    case estonian = 'et_EE';
    case persian = 'fa_IR';
    case finnish = 'fi_FI';
    case french = 'fr_FR';
    case canadianFrench = 'fr_CA';
    case gujarati = 'gu_IN';
    case hausa = 'ha_NG';
    case hebrew = 'he_IL';
    case hindi = 'hi_IN';
    case hungarian = 'hu_HU';
    case icelandic = 'is_IS';
    case indonesian = 'id_ID';
    case italian = 'it_IT';
    case japanese = 'ja_JP';
    case kannada = 'kn_IN';
    case kazakh = 'kk_KZ';
    case korean = 'ko_KR';
    case latvian = 'lv_LV';
    case lithuanian = 'lt_LT';
    case malay = 'ms_MY';
    case marathi = 'mr_IN';
    case dutch = 'nl_NL';
    case norwegian = 'no_NO';
    case punjabi = 'pa_IN';
    case polish = 'pl_PL';
    case portuguese = 'pt_PT';
    case brazilianPortuguese = 'pt_BR';
    case angolanPortuguese = 'pt_AO';
    case quechua = 'qu_PE';
    case romanian = 'ro_RO';
    case russian = 'ru_RU';
    case serbian = 'sr_RS';
    case slovak = 'sk_SK';
    case slovenian = 'sl_SI';
    case swahili = 'sw_KE';
    case swedish = 'sv_SE';
    case tamil = 'ta_IN';
    case telugu = 'te_IN';
    case thai = 'th_TH';
    case turkish = 'tr_TR';
    case ukrainian = 'uk_UA';
    case urdu = 'ur_PK';
    case vietnamese = 'vi_VN';


    public function getISO6391Label(): string
    {
        return explode('_', $this->value)[1];
    }
}

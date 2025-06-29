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
enum ContentType
{

    case applicationDocx;
    case applicationFormUrlencoded;
    case applicationGeoJson;
    case applicationJavascript;
    case applicationJsm;
    case applicationJson;
    case applicationPdf;
    case applicationPhp;
    case applicationPpt;
    case applicationPptx;
    case applicationRar;
    case applicationXls;
    case applicationXlsx;
    case applicationXml;
    case applicationZip;
    case audioMp3;
    case fontOtf;
    case fontTtf;
    case fontWoff;
    case fontWoff2;
    case imageIcon;
    case imageJpeg;
    case imagePng;
    case imageSvgXml;
    case multipartFormData;
    case textCss;
    case textHtml;
    case textPlain;
    case textTpl;
    case videoMp4;

    public function getMime(): string
    {
        return match ($this) {
            self::applicationDocx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::applicationFormUrlencoded => 'application/x-www-form-urlencoded',
            self::applicationGeoJson => 'application/geo+json',
            self::applicationJavascript, self::applicationJsm => 'application/javascript',
            self::applicationJson => 'application/json',
            self::applicationPdf => 'application/pdf',
            self::applicationPhp => 'application/x-httpd-php',
            self::applicationPpt => 'application/vnd.ms-powerpoint',
            self::applicationPptx => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            self::applicationRar => 'application/x-rar-compressed',
            self::applicationXls => 'application/vnd.ms-excel',
            self::applicationXlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::applicationXml => 'application/xml',
            self::applicationZip => 'application/x-zip-compressed',
            self::audioMp3 => 'audio/mp3',
            self::fontOtf => 'font/otf',
            self::fontTtf => 'font/ttf',
            self::fontWoff => 'font/woff',
            self::fontWoff2 => 'font/woff2',
            self::imageIcon => 'image/x-icon',
            self::imageJpeg => 'image/jpeg',
            self::imagePng => 'image/png',
            self::imageSvgXml => 'image/svg+xml',
            self::multipartFormData => 'multipart/form-data',
            self::textCss => 'text/css',
            self::textHtml => 'text/html',
            self::textPlain, self::textTpl => 'text/plain',
            self::applicationMsword => 'application/msword',
            self::videoMp4 => 'video/mp4',
        };
    }

    public static function getByMime(string $mime): self
    {
        return match ($mime) {
            'application/msword' => self::applicationMsword,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => self::applicationDocx,
            'application/x-www-form-urlencoded' => self::applicationFormUrlencoded,
            'application/geo+json' => self::applicationGeoJson,
            'application/javascript', 'text/javascript' => self::applicationJavascript,
            'application/json' => self::applicationJson,
            'application/pdf' => self::applicationPdf,
            'application/x-httpd-php' => self::applicationPhp,
            'application/vnd.ms-powerpoint' => self::applicationPpt,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => self::applicationPptx,
            'application/x-rar-compressed' => self::applicationRar,
            'application/vnd.ms-excel' => self::applicationXls,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => self::applicationXlsx,
            'application/xml' => self::applicationXml,
            'application/x-zip-compressed' => self::applicationZip,
            'audio/mp3' => self::audioMp3,
            'font/otf' => self::fontOtf,
            'font/ttf' => self::fontTtf,
            'font/woff' => self::fontWoff,
            'font/woff2' => self::fontWoff2,
            'image/x-icon' => self::imageIcon,
            'image/jpeg' => self::imageJpeg,
            'image/png' => self::imagePng,
            'image/svg+xml' => self::imageSvgXml,
            'multipart/form-data' => self::multipartFormData,
            'text/css' => self::textCss,
            'text/html' => self::textHtml,
            'text/plain' => self::textPlain,
            'applicationMsword' => self::applicationMsword,
            'video/mp4' => self::videoMp4,
        };
    }

    public function toResource(): ?Resource
    {
        return match ($this) {
            self::applicationDocx => Resource::docx,
            self::applicationGeoJson => Resource::geojson,
            self::applicationJavascript, self::applicationJsm => Resource::js,
            self::applicationJson => Resource::json,
            self::applicationMsword => Resource::doc,
            self::applicationPdf => Resource::pdf,
            self::applicationPhp => Resource::php,
            self::applicationPpt => Resource::ppt,
            self::applicationPptx => Resource::pptx,
            self::applicationRar => Resource::rar,
            self::applicationXls => Resource::xls,
            self::applicationXlsx => Resource::xlsx,
            self::applicationXml => Resource::xml,
            self::applicationZip => Resource::zip,
            self::audioMp3 => Resource::mp3,
            self::fontOtf => Resource::otf,
            self::fontTtf => Resource::ttf,
            self::fontWoff => Resource::woff,
            self::fontWoff2 => Resource::woff2,
            self::imageIcon => Resource::ico,
            self::imageJpeg => Resource::jpg,
            self::imagePng => Resource::png,
            self::imageSvgXml => Resource::svg,
            self::multipartFormData => null,
            self::textCss => Resource::css,
            self::textHtml => Resource::html,
            self::textPlain => Resource::txt,
            self::textTpl => Resource::tpl,
            self::videoMp4 => Resource::mp4,
        };
    }
}

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

    case textCss;
    case applicationMsword;
    case applicationDocx;
    case applicationFormUrlencoded;
    case applicationGeoJson;
    case textHtml;
    case imageIcon;
    case imageJpeg;
    case applicationJavascript;
    case applicationJsm;
    case applicationJson;
    case audioMp3;
    case videoMp4;
    case multipartFormData;
    case fontOtf;
    case applicationPdf;
    case applicationPhp;
    case imagePng;
    case applicationPpt;
    case applicationPptx;
    case applicationRar;
    case imageSvgXml;
    case textTpl;
    case fontTtf;
    case textPlain;
    case fontWoff;
    case fontWoff2;
    case applicationXls;
    case applicationXlsx;
    case applicationXml;
    case applicationZip;

    public function getMime(): string
    {
        return match ($this) {
            self::textCss => 'text/css',
            self::applicationMsword => 'application/msword',
            self::applicationDocx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::applicationFormUrlencoded => 'application/x-www-form-urlencoded',
            self::multipartFormData => 'multipart/form-data',
            self::applicationGeoJson => 'application/geo+json',
            self::textHtml => 'text/html',
            self::imageIcon => 'image/x-icon',
            self::imageJpeg => 'image/jpeg',
            self::applicationJavascript, self::applicationJsm => 'application/javascript',
            self::applicationJson => 'application/json',
            self::audioMp3 => 'audio/mp3',
            self::videoMp4 => 'video/mp4',
            self::fontOtf => 'font/otf',
            self::applicationPdf => 'application/pdf',
            self::applicationPhp => 'application/x-httpd-php',
            self::imagePng => 'image/png',
            self::applicationPpt => 'application/vnd.ms-powerpoint',
            self::applicationPptx => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            self::applicationRar => 'application/x-rar-compressed',
            self::imageSvgXml => 'image/svg+xml',
            self::textTpl, self::textPlain => 'text/plain',
            self::fontTtf => 'font/ttf',
            self::fontWoff => 'font/woff',
            self::fontWoff2 => 'font/woff2',
            self::applicationXls => 'application/vnd.ms-excel',
            self::applicationXlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::applicationXml => 'application/xml',
            self::applicationZip => 'application/x-zip-compressed',
        };
    }

    public static function getByMime(string $mime): self
    {
        return match ($mime) {
            'text/css' => self::textCss,
            'application/msword' => self::applicationMsword,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => self::applicationDocx,
            'application/x-www-form-urlencoded' => self::applicationFormUrlencoded,
            'multipart/form-data' => self::multipartFormData,
            'application/geo+json' => self::applicationGeoJson,
            'text/html' => self::textHtml,
            'image/x-icon' => self::imageIcon,
            'image/jpeg' => self::imageJpeg,
            'application/javascript', 'text/javascript' => self::applicationJavascript,
            'application/json' => self::applicationJson,
            'audio/mp3' => self::audioMp3,
            'video/mp4' => self::videoMp4,
            'font/otf' => self::fontOtf,
            'application/pdf' => self::applicationPdf,
            'application/x-httpd-php' => self::applicationPhp,
            'image/png' => self::imagePng,
            'application/vnd.ms-powerpoint' => self::applicationPpt,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => self::applicationPptx,
            'application/x-rar-compressed' => self::applicationRar,
            'image/svg+xml' => self::imageSvgXml,
            'font/ttf' => self::fontTtf,
            'text/plain' => self::textPlain,
            'font/woff' => self::fontWoff,
            'font/woff2' => self::fontWoff2,
            'application/vnd.ms-excel' => self::applicationXls,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => self::applicationXlsx,
            'application/xml' => self::applicationXml,
            'application/x-zip-compressed' => self::applicationZip,
        };
    }
}

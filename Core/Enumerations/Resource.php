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
enum Resource: string
{

    case css = 'css';
    case doc = 'doc';
    case docx = 'docx';
    case geojson = 'geojson';
    case htm = 'htm';
    case html = 'html';
    case ico = 'ico';
    case jpg = 'jpg';
    case jpeg = 'jpeg';
    case js = 'js';
    case jsm = 'jsm';
    case json = 'json';
    case map = 'map';
    case mp3 = 'mp3';
    case mp4 = 'mp4';
    case otf = 'otf';
    case pdf = 'pdf';
    case php = 'php';
    case png = 'png';
    case ppt = 'ppt';
    case pptx = 'pptx';
    case rar = 'rar';
    case svg = 'svg';
    case ttf = 'ttf';
    case woff = 'woff';
    case woff2 = 'woff2';
    case xls = 'xls';
    case xlsx = 'xlsx';
    case xml = 'xml';
    case zip = 'zip';

    public function getMime(): string
    {
        return match ($this) {
            self::css => 'text/css',
            self::doc => 'application/msword',
            self::docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::geojson => 'application/geo+json',
            self::htm, self::html => 'text/html',
            self::ico => 'image/x-icon',
            self::jpg, self::jpeg => 'image/jpeg',
            self::js, self::jsm => 'application/javascript',
            self::json, self::map => 'application/json',
            self::mp3 => 'audio/mp3',
            self::mp4 => 'video/mp4',
            self::otf => 'font/otf',
            self::pdf => 'application/pdf',
            self::php => 'application/x-httpd-php',
            self::png => 'image/png',
            self::ppt => 'application/vnd.ms-powerpoint',
            self::pptx => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            self::rar => 'application/x-rar-compressed',
            self::svg => 'image/svg+xml',
            self::ttf => 'font/ttf',
            self::woff => 'font/woff',
            self::woff2 => 'font/woff2',
            self::xls => 'application/vnd.ms-excel',
            self::xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::xml => 'application/xml',
            self::zip => 'application/x-zip-compressed',
        };
    }

    public function isRenderable(): bool
    {
        return match ($this) {
            self::css, self::doc, self::docx, self::geojson, self::htm, self::html,
            self::ico, self::jpg, self::jpeg, self::js, self::jsm, self::json, self::map,
            self::mp3, self::mp4, self::otf, self::pdf, self::png, self::ppt, self::pptx,
            self::rar, self::svg, self::ttf, self::woff, self::woff2, self::xls,
            self::xlsx, self::xml => true,
            self::php, self::zip => false,
        };
    }

    public function isDownloadable(): bool
    {
        return match ($this) {
            self::css, self::doc, self::docx, self::geojson, self::htm, self::html,
            self::ico, self::jpg, self::jpeg, self::js, self::jsm, self::json, self::map,
            self::mp3, self::mp4, self::otf, self::pdf, self::png, self::ppt, self::pptx,
            self::rar, self::svg, self::ttf, self::woff, self::woff2, self::xls,
            self::xlsx, self::xml, self::zip => true,
            self::php => false,
        };
    }
}

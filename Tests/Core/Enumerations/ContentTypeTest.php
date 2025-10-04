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
use SismaFramework\Core\Enumerations\ContentType;
use SismaFramework\Core\Enumerations\Resource;

/**
 * Test for ContentType enumeration
 * @author Valentino de Lapa
 */
class ContentTypeTest extends TestCase
{
    public function testGetMimeMethod()
    {
        // Test common MIME types
        $this->assertEquals('application/json', ContentType::applicationJson->getMime());
        $this->assertEquals('text/html', ContentType::textHtml->getMime());
        $this->assertEquals('text/css', ContentType::textCss->getMime());
        $this->assertEquals('application/javascript', ContentType::applicationJavascript->getMime());
        $this->assertEquals('image/png', ContentType::imagePng->getMime());
        $this->assertEquals('image/jpeg', ContentType::imageJpeg->getMime());
        $this->assertEquals('application/pdf', ContentType::applicationPdf->getMime());
    }

    public function testApplicationMimeTypes()
    {
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ContentType::applicationDocx->getMime());
        $this->assertEquals('application/x-www-form-urlencoded',
            ContentType::applicationFormUrlencoded->getMime());
        $this->assertEquals('application/geo+json', ContentType::applicationGeoJson->getMime());
        $this->assertEquals('application/vnd.ms-excel', ContentType::applicationXls->getMime());
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ContentType::applicationXlsx->getMime());
        $this->assertEquals('application/xml', ContentType::applicationXml->getMime());
        $this->assertEquals('application/x-zip-compressed', ContentType::applicationZip->getMime());
    }

    public function testImageMimeTypes()
    {
        $this->assertEquals('image/x-icon', ContentType::imageIcon->getMime());
        $this->assertEquals('image/jpeg', ContentType::imageJpeg->getMime());
        $this->assertEquals('image/png', ContentType::imagePng->getMime());
        $this->assertEquals('image/svg+xml', ContentType::imageSvgXml->getMime());
    }

    public function testFontMimeTypes()
    {
        $this->assertEquals('font/otf', ContentType::fontOtf->getMime());
        $this->assertEquals('font/ttf', ContentType::fontTtf->getMime());
        $this->assertEquals('font/woff', ContentType::fontWoff->getMime());
        $this->assertEquals('font/woff2', ContentType::fontWoff2->getMime());
    }

    public function testTextMimeTypes()
    {
        $this->assertEquals('text/css', ContentType::textCss->getMime());
        $this->assertEquals('text/html', ContentType::textHtml->getMime());
        $this->assertEquals('text/plain', ContentType::textPlain->getMime());
    }

    public function testMultipartAndOtherTypes()
    {
        $this->assertEquals('multipart/form-data', ContentType::multipartFormData->getMime());
        $this->assertEquals('audio/mp3', ContentType::audioMp3->getMime());
        $this->assertEquals('video/mp4', ContentType::videoMp4->getMime());
    }

    public function testGetByMimeMethod()
    {
        // Test successful lookups
        $this->assertEquals(ContentType::applicationJson, ContentType::getByMime('application/json'));
        $this->assertEquals(ContentType::textHtml, ContentType::getByMime('text/html'));
        $this->assertEquals(ContentType::imagePng, ContentType::getByMime('image/png'));
        $this->assertEquals(ContentType::applicationPdf, ContentType::getByMime('application/pdf'));

        // Test JavaScript alias
        $this->assertEquals(ContentType::applicationJavascript, ContentType::getByMime('text/javascript'));

        // Test null for unknown MIME type
        $this->assertNull(ContentType::getByMime('unknown/mime-type'));
        $this->assertNull(ContentType::getByMime('invalid'));
    }

    public function testGetByMimeSpecialCases()
    {
        // Test document types
        $this->assertEquals(ContentType::applicationMsword,
            ContentType::getByMime('application/msword'));
        $this->assertEquals(ContentType::applicationDocx,
            ContentType::getByMime('application/vnd.openxmlformats-officedocument.wordprocessingml.document'));

        // Test form data
        $this->assertEquals(ContentType::applicationFormUrlencoded,
            ContentType::getByMime('application/x-www-form-urlencoded'));
        $this->assertEquals(ContentType::multipartFormData,
            ContentType::getByMime('multipart/form-data'));
    }

    public function testToResourceMethod()
    {
        // Test valid resource conversions
        $this->assertEquals(Resource::json, ContentType::applicationJson->toResource());
        $this->assertEquals(Resource::html, ContentType::textHtml->toResource());
        $this->assertEquals(Resource::css, ContentType::textCss->toResource());
        $this->assertEquals(Resource::js, ContentType::applicationJavascript->toResource());
        $this->assertEquals(Resource::png, ContentType::imagePng->toResource());
        $this->assertEquals(Resource::jpg, ContentType::imageJpeg->toResource());
        $this->assertEquals(Resource::pdf, ContentType::applicationPdf->toResource());

        // Test null for multipart form data
        $this->assertNull(ContentType::multipartFormData->toResource());
    }

    public function testToResourceDocumentTypes()
    {
        $this->assertEquals(Resource::doc, ContentType::applicationMsword->toResource());
        $this->assertEquals(Resource::docx, ContentType::applicationDocx->toResource());
        $this->assertEquals(Resource::xls, ContentType::applicationXls->toResource());
        $this->assertEquals(Resource::xlsx, ContentType::applicationXlsx->toResource());
        $this->assertEquals(Resource::ppt, ContentType::applicationPpt->toResource());
        $this->assertEquals(Resource::pptx, ContentType::applicationPptx->toResource());
    }

    public function testToResourceSpecialTypes()
    {
        $this->assertEquals(Resource::geojson, ContentType::applicationGeoJson->toResource());
        $this->assertEquals(Resource::zip, ContentType::applicationZip->toResource());
        $this->assertEquals(Resource::rar, ContentType::applicationRar->toResource());
        $this->assertEquals(Resource::svg, ContentType::imageSvgXml->toResource());
        $this->assertEquals(Resource::ico, ContentType::imageIcon->toResource());
        $this->assertEquals(Resource::txt, ContentType::textPlain->toResource());
        $this->assertEquals(Resource::tpl, ContentType::textTpl->toResource());
    }

    public function testEnumHasAllExpectedCases()
    {
        $cases = ContentType::cases();
        $this->assertIsArray($cases);
        $this->assertGreaterThan(25, count($cases)); // Should have many content types

        // Verify some key cases are present
        $caseNames = array_map(fn($case) => $case->name, $cases);
        $expectedCases = [
            'applicationJson', 'textHtml', 'textCss', 'applicationJavascript',
            'imagePng', 'imageJpeg', 'applicationPdf', 'multipartFormData'
        ];

        foreach ($expectedCases as $expectedCase) {
            $this->assertContains($expectedCase, $caseNames);
        }
    }

    public function testMimeToContentTypeRoundTrip()
    {
        // Test that getMime() and getByMime() are consistent
        $testCases = [
            ContentType::applicationJson,
            ContentType::textHtml,
            ContentType::textCss,
            ContentType::imagePng,
            ContentType::applicationPdf
        ];

        foreach ($testCases as $contentType) {
            $mime = $contentType->getMime();
            $retrievedContentType = ContentType::getByMime($mime);
            $this->assertEquals($contentType, $retrievedContentType);
        }
    }
}
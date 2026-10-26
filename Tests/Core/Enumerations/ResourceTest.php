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

namespace SismaFramework\Tests\Core\Enumerations;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Enumerations\ContentType;
use SismaFramework\Core\Enumerations\Resource;

/**
 * @author Valentino de Lapa
 */
class ResourceTest extends TestCase
{

    public function testJavascriptModuleExtensionIsResource()
    {
        $this->assertEquals(Resource::mjs, Resource::tryFrom('mjs'));
    }

    public function testJavascriptModuleIsRenderableAndDownloadable()
    {
        $this->assertTrue(Resource::mjs->isRenderable());
        $this->assertTrue(Resource::mjs->isDownloadable());
    }

    public function testJavascriptModuleContentType()
    {
        $this->assertEquals(ContentType::textJavascript, Resource::mjs->getContentType());
        $this->assertEquals('text/javascript', Resource::mjs->getContentType()->getMime());
    }

    public function testEveryResourceHasContentType()
    {
        foreach (Resource::cases() as $resource) {
            $this->assertInstanceOf(ContentType::class, $resource->getContentType());
            $this->assertIsBool($resource->isRenderable());
            $this->assertIsBool($resource->isDownloadable());
        }
    }
}

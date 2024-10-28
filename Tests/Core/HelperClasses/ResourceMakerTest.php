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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\Exceptions\AccessDeniedException;
use SismaFramework\Core\HelperClasses\ResourceMaker;

/**
 * Description of ResourceMakerTest
 *
 * @author Valentino de Lapa
 */
class ResourceMakerTest extends TestCase
{

    /**
     * @runInSeparateProcess
     */
    public function testIsAcceptedResourceFile()
    {
        $resourceMaker = new ResourceMaker();
        $this->assertTrue($resourceMaker->isAcceptedResourceFile('/sample.js'));
        $this->assertFalse($resourceMaker->isAcceptedResourceFile('/notify/'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testMakeResourceNotRenderable()
    {
        $resourceMaker = new ResourceMaker();
        $this->expectException(AccessDeniedException::class);
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Controllers/SampleController.php');
    }

    /**
     * @runInSeparateProcess
     */
    public function testMakeResourceNotDownloadable()
    {
        $resourceMaker = new ResourceMaker();
        $this->expectException(AccessDeniedException::class);
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Controllers/SampleController.php', true);
    }

    /**
     * @runInSeparateProcess
     */
    public function testMakeResourceWithFolderNotAccessible()
    {
        $resourceMaker = new ResourceMaker();
        $this->expectException(AccessDeniedException::class);
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Cache/referencedCache.json');
    }

    /**
     * @runInSeparateProcess
     */
    public function testMakeResourceViaFileGetContent()
    {
        $this->expectOutputString(file_get_contents(__DIR__ . '/../../../Sample/Assets/css/sample.css'));
        $resourceMaker = new ResourceMaker();
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Assets/css/sample.css');
    }

    /**
     * @runInSeparateProcess
     */
    public function testMakeResourceViaReadFile()
    {
        $this->expectOutputString(file_get_contents(__DIR__ . '/../../../Sample/Assets/css/sample.css'));
        $resourceMaker = new ResourceMaker();
        $resourceMaker->setFileGetContentMaxBytesLimit(4);
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Assets/css/sample.css');
    }

    /**
     * @runInSeparateProcess
     */
    public function testMakeResourceViaFopen()
    {
        $this->expectOutputString(file_get_contents(__DIR__ . '/../../../Sample/Assets/css/sample.css'));
        $resourceMaker = new ResourceMaker();
        $resourceMaker->setFileGetContentMaxBytesLimit(4);
        $resourceMaker->setReadfileMaxBytesLimit(4);
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Assets/css/sample.css');
    }

    /**
     * @runInSeparateProcess
     */
    public function testFileWithStreamContent()
    {
        $this->expectOutputString(file_get_contents(__DIR__ . '/../../../Sample/Assets/javascript/sample.js'));
        $_SERVER['QUERY_STRING'] = 'resource=resource';
        $resourceMaker = new ResourceMaker();
        $resourceMaker->setStreamContex();
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Assets/javascript/sample.js');
    }
}

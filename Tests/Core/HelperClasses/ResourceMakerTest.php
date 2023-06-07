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
    public function testMakeResourceViaFileGetContent()
    {
        \ob_start();
        $resourceMaker = new ResourceMaker();
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Assets/css/sample.css');
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Sample/Assets/css/sample.css'), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testMakeResourceViaReadFile()
    {
        \ob_start();
        $resourceMaker = new ResourceMaker();
        $resourceMaker->setFileGetContentMaxBytesLimit(4);
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Assets/css/sample.css');
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Sample/Assets/css/sample.css'), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testMakeResourceViaFopen()
    {
        \ob_start();
        $resourceMaker = new ResourceMaker();
        $resourceMaker->setFileGetContentMaxBytesLimit(4);
        $resourceMaker->setReadfileMaxBytesLimit(4);
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Assets/css/sample.css');
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Sample/Assets/css/sample.css'), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFileWithStreamContent()
    {
        $_SERVER['QUERY_STRING'] = 'resource=resource';
        \ob_start();
        $resourceMaker = new ResourceMaker();
        $resourceMaker->setStreamContex(__DIR__ . '/../../../Sample/Assets/javascript/sample.js');
        $resourceMaker->makeResource(__DIR__ . '/../../../Sample/Assets/javascript/sample.js');
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals(file_get_contents(__DIR__ . '/../../../Sample/Assets/javascript/sample.js'), $result);
    }

}
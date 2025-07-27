<?php

/*
 * The MIT License
 *
 * Copyright 2025 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Tests\Core\HttpClasses;

use SismaFramework\Core\HttpClasses\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
        $_SERVER = [];
    }

    public function testRequestConstructor()
    {
        $_GET['foo'] = 'bar';
        $_POST['baz'] = 'qux';
        $_COOKIE['cookie'] = 'value';
        $_FILES['file'] = ['name' => 'file.txt', 'tmp_name' => '/tmp/file.txt'];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = new Request();

        $this->assertEquals(['foo' => 'bar'], $request->query);
        $this->assertEquals(['baz' => 'qux'], $request->request);
        $this->assertEquals(['cookie' => 'value'], $request->cookie);
        $this->assertEquals(['file' => ['name' => 'file.txt', 'tmp_name' => '/tmp/file.txt']], $request->files);
        $this->assertArrayHasKey('REQUEST_METHOD', $request->server);
        $this->assertIsArray($request->headers);
    }

    public function testRequestHeadersWithServer()
    {
        $_SERVER['HTTP_TEST_HEADER'] = 'Test Value';
        $request = new Request();
        $this->assertArrayHasKey('Test-Header', $request->headers);
        $this->assertEquals('Test Value', $request->headers['Test-Header']);
    }

    public function testRequestHeadersWithMultipleHeaders()
    {
        $_SERVER['HTTP_TEST_HEADER'] = 'Test Value';
        $_SERVER['HTTP_TEST_HEADER_2'] = 'Test Value 2';
        $request = new Request();
        $this->assertArrayHasKey('Test-Header', $request->headers);
        $this->assertArrayHasKey('Test-Header-2', $request->headers);
        $this->assertEquals('Test Value 2', $request->headers['Test-Header-2']);
    }

    public function testRequestHeadersWithoutHeader()
    {
        $request = new Request();
        $this->assertIsArray($request->headers);
        $this->assertArrayNotHasKey('Test-Header', $request->headers);
    }

    public function testRequestParseRequestBody()
    {
        $_POST['test_key'] = 'test_value';
        $request = new Request();
        $this->assertEquals(['test_key' => 'test_value'], $request->request);

        $_FILES['file'] = [
            'tmp_name' => 'path/to/file.txt',
            'name' => 'file.txt'
        ];
        $request = new Request();
        $this->assertArrayHasKey('file', $request->files);
        $this->assertEquals('file.txt', $request->files['file']['name']);
    }

    public function testRequestParseJsonBody()
    {
        $jsonData = ['foo' => 'bar', 'baz' => 123];
        $jsonString = json_encode($jsonData);
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', MockPhpStream::class);
        MockPhpStream::registerContent($jsonString);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $request = new Request();
        $this->assertEquals($jsonData, $request->data);
        stream_wrapper_restore('php');
    }

    public function testRequestGetStreamContentResource()
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'content' => http_build_query(['test_key' => 'test_value'])
            ]
        ];
        $resource = stream_context_create($opts);
        $this->assertTrue(is_resource($resource));
    }
}

class MockPhpStream
{

    public $context;
    protected static $content;
    protected $position;

    public static function registerContent($string)
    {
        self::$content = $string;
    }

    public function stream_open()
    {
        $this->position = 0;
        return true;
    }

    public function stream_read($count)
    {
        $result = substr(self::$content, $this->position, $count);
        $this->position += strlen($result);
        return $result;
    }

    public function stream_eof()
    {
        return $this->position >= strlen(self::$content);
    }

    public function stream_stat()
    {
        return [];
    }

    public function stream_seek($offset, $whence)
    {
        if ($whence === SEEK_SET) {
            $this->position = $offset;
            return true;
        }
        return false;
    }
}

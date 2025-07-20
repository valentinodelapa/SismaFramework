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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\AccessDeniedException;
use SismaFramework\Core\HelperClasses\Locker;
use SismaFramework\Core\HelperClasses\ResourceMaker;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

/**
 * Description of ResourceMakerTest
 *
 * @author Valentino de Lapa
 */
class ResourceMakerTest extends TestCase
{

    private Locker $lockerMock;
    private Request $requestMock;
    private Config $configMock;

    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->requestMock = $this->createMock(Request::class);
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['fileGetContentMaxBytesLimit', 10],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
                    ['readfileMaxBytesLimit', 1000],
                    ['customRenderableResourceTypes', ['md' => 'text/markdown']],
                    ['customDownloadableResourceTypes', []],
        ]);
        Config::setInstance($this->configMock);
        $this->lockerMock = $this->createMock(Locker::class);
    }

    public function testIsAcceptedResourceFile()
    {
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configMock);
        $this->assertTrue($resourceMaker->isAcceptedResourceFile('/sample.js'));
        $this->assertFalse($resourceMaker->isAcceptedResourceFile('/notify/'));
    }

    public function testMakeResourceNotRenderable()
    {
        $filePath = __DIR__ . '/../../../TestsApplication/Controllers/SampleController.php';
        $this->lockerMock->expects($this->once())
                ->method('fileIsLocked')
                ->with($filePath)
                ->willReturn(false);
        $this->lockerMock->expects($this->once())
                ->method('folderIsLocked')
                ->with(dirname($filePath))
                ->willReturn(false);
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configMock);
        $this->expectException(AccessDeniedException::class);
        $this->assertInstanceOf(Response::class, $resourceMaker->makeResource($filePath, false, $this->lockerMock));
    }

    public function testMakeResourceNotDownloadable()
    {
        $filePath = __DIR__ . '/../../../TestsApplication/Controllers/SampleController.php';
        $this->lockerMock->expects($this->once())
                ->method('fileIsLocked')
                ->with($filePath)
                ->willReturn(false);
        $this->lockerMock->expects($this->once())
                ->method('folderIsLocked')
                ->with(dirname($filePath))
                ->willReturn(false);
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configMock);
        $this->expectException(AccessDeniedException::class);
        $this->assertInstanceOf(Response::class, $resourceMaker->makeResource($filePath, true, $this->lockerMock));
    }

    public function testMakeResourceWithFileNotAccessible()
    {
        $filePath = __DIR__ . '/../../../TestsApplication/Assets/css/notAccessible.css';
        $this->lockerMock->expects($this->once())
                ->method('fileIsLocked')
                ->with($filePath)
                ->willReturn(true);
        $resourceMaker = new ResourceMaker();
        $this->expectException(AccessDeniedException::class);
        $this->assertInstanceOf(Response::class, $resourceMaker->makeResource($filePath, false, $this->lockerMock));
    }

    public function testMakeResourceWithFolderNotAccessible()
    {
        $filePath = __DIR__ . '/../../../TestsApplication/Cache/referencedCache.json';
        $this->lockerMock->expects($this->once())
                ->method('fileIsLocked')
                ->with($filePath)
                ->willReturn(false);
        $this->lockerMock->expects($this->once())
                ->method('folderIsLocked')
                ->with(dirname($filePath))
                ->willReturn(true);
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configMock);
        $this->expectException(AccessDeniedException::class);
        $this->assertInstanceOf(Response::class, $resourceMaker->makeResource($filePath, false, $this->lockerMock));
    }

    public function testMakeResourceViaFileGetContent()
    {
        $filePath = __DIR__ . '/../../../TestsApplication/Assets/css/sample.css';
        $this->lockerMock->expects($this->once())
                ->method('fileIsLocked')
                ->with($filePath)
                ->willReturn(false);
        $this->lockerMock->expects($this->once())
                ->method('folderIsLocked')
                ->with(dirname($filePath))
                ->willReturn(false);
        $this->expectOutputString(file_get_contents($filePath));
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configMock);
        $this->assertInstanceOf(Response::class, $resourceMaker->makeResource($filePath, false, $this->lockerMock));
    }

    public function testMakeResourceViaReadFile()
    {
        $filePath = __DIR__ . '/../../../TestsApplication/Assets/vendor/sample-vendor/sample-vendor.css';
        $this->lockerMock->expects($this->once())
                ->method('fileIsLocked')
                ->with($filePath)
                ->willReturn(false);
        $this->lockerMock->expects($this->once())
                ->method('folderIsLocked')
                ->with(dirname($filePath))
                ->willReturn(false);
        $this->expectOutputString(file_get_contents($filePath));
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configMock);
        $this->assertInstanceOf(Response::class, $resourceMaker->makeResource($filePath, false, $this->lockerMock));
    }

    public function testMakeResourceViaFopen()
    {
        $filePath = __DIR__ . '/../../../TestsApplication/Assets/javascript/sample.js';
        $this->lockerMock->expects($this->once())
                ->method('fileIsLocked')
                ->with($filePath)
                ->willReturn(false);
        $this->lockerMock->expects($this->once())
                ->method('folderIsLocked')
                ->with(dirname($filePath))
                ->willReturn(false);
        $this->expectOutputString(file_get_contents($filePath));
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configMock);
        $this->assertInstanceOf(Response::class, $resourceMaker->makeResource($filePath, false, $this->lockerMock));
    }
    
    public function testMakeResourceWithCustomResourceType()
    {
        $filePath = __DIR__ . '/../../../TestsApplication/Assets/markdown/sample.md';
        $this->lockerMock->expects($this->once())
                ->method('fileIsLocked')
                ->with($filePath)
                ->willReturn(false);
        $this->lockerMock->expects($this->once())
                ->method('folderIsLocked')
                ->with(dirname($filePath))
                ->willReturn(false);
        $this->expectOutputString(file_get_contents($filePath));
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configMock);
        $this->assertInstanceOf(Response::class, $resourceMaker->makeResource($filePath, false, $this->lockerMock));
    }
}

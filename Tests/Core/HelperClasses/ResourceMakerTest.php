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

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\Exceptions\AccessDeniedException;
use SismaFramework\Core\HelperClasses\ResourceMaker;
use SismaFramework\Core\HttpClasses\Request;

/**
 * Description of ResourceMakerTest
 *
 * @author Valentino de Lapa
 */
class ResourceMakerTest extends TestCase
{

    private Request $requestMock;
    private ResourceMakerConfigTest $configTest;

    public function setUp(): void
    {
        $this->requestMock = $this->createMock(Request::class);
        $this->configTest = new ResourceMakerConfigTest();
        BaseConfig::setInstance($this->configTest);
    }

    #[RunInSeparateProcess]
    public function testIsAcceptedResourceFile()
    {
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configTest);
        $this->assertTrue($resourceMaker->isAcceptedResourceFile('/sample.js'));
        $this->assertFalse($resourceMaker->isAcceptedResourceFile('/notify/'));
    }

    #[RunInSeparateProcess]
    public function testMakeResourceNotRenderable()
    {
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configTest);
        $this->expectException(AccessDeniedException::class);
        $resourceMaker->makeResource(__DIR__ . '/../../../TestsApplication/Controllers/SampleController.php');
    }

    #[RunInSeparateProcess]
    public function testMakeResourceNotDownloadable()
    {
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configTest);
        $this->expectException(AccessDeniedException::class);
        $resourceMaker->makeResource(__DIR__ . '/../../../TestsApplication/Controllers/SampleController.php', true);
    }

    #[RunInSeparateProcess]
    public function testMakeResourceWithFolderNotAccessible()
    {
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configTest);
        $this->expectException(AccessDeniedException::class);
        $resourceMaker->makeResource(__DIR__ . '/../../../TestsApplication/Cache/referencedCache.json');
    }

    #[RunInSeparateProcess]
    public function testMakeResourceViaFileGetContent()
    {
        $this->expectOutputString(file_get_contents(__DIR__ . '/../../../TestsApplication/Assets/css/sample.css'));
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configTest);
        $resourceMaker->makeResource(__DIR__ . '/../../../TestsApplication/Assets/css/sample.css');
    }

    #[RunInSeparateProcess]
    public function testMakeResourceViaReadFile()
    {
        $this->expectOutputString(file_get_contents(__DIR__ . '/../../../TestsApplication/Assets/vendor/sample-vendor/sample-vendor.css'));
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configTest);
        $resourceMaker->makeResource(__DIR__ . '/../../../TestsApplication/Assets/vendor/sample-vendor/sample-vendor.css');
    }

    #[RunInSeparateProcess]
    public function testMakeResourceViaFopen()
    {
        $this->expectOutputString(file_get_contents(__DIR__ . '/../../../TestsApplication/Assets/javascript/sample.js'));
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configTest);
        $resourceMaker->makeResource(__DIR__ . '/../../../TestsApplication/Assets/javascript/sample.js');
    }

    #[RunInSeparateProcess]
    public function testFileWithStreamContent()
    {
        $this->expectOutputString(file_get_contents(__DIR__ . '/../../../TestsApplication/Assets/javascript/sample.js'));
        $_SERVER['QUERY_STRING'] = 'resource=resource';
        $resourceMaker = new ResourceMaker($this->requestMock, $this->configTest);
        $resourceMaker->setStreamContex();
        $resourceMaker->makeResource(__DIR__ . '/../../../TestsApplication/Assets/javascript/sample.js');
    }
}

class ResourceMakerConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        return false;
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->developmentEnvironment = false;
        $this->fileGetContentMaxBytesLimit = 10;
        $this->logDevelopmentMaxRow = 100;
        $this->logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->logPath = $this->logDirectoryPath . 'log.txt';
        $this->logProductionMaxRow = 2;
        $this->logVerboseActive = true;
        $this->readfileMaxBytesLimit = 1000;
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        
    }
}

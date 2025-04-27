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
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Exceptions\ModuleException;

/**
 * @author Valentino de Lapa
 */
class ModuleManagerTest extends TestCase
{

    private BaseConfig $configMock;

    #[\Override]
    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->configMock = $this->createMock(BaseConfig::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 100],
                    ['logVerboseActive', true],
                    ['moduleFolders', ['SismaFramework']],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
        ]);
        BaseConfig::setInstance($this->configMock);
    }

    public function testGetModuleList()
    {
        $this->assertContains('SismaFramework', ModuleManager::getModuleList());
    }

    public function testInizializeApplication()
    {
        ModuleManager::initializeApplicationModule();
        $this->assertEmpty(ModuleManager::getApplicationModule());
    }

    public function testSetApplicationModule()
    {
        ModuleManager::setApplicationModule('TestsApplicationModule');
        $this->assertEquals('TestsApplicationModule', ModuleManager::getApplicationModule());
    }

    public function testSetCustomVisualizationModule()
    {
        $this->assertNull(ModuleManager::getCustomVisualizationModule());
        ModuleManager::setCustomVisualizationModule('TestsApplicationModule');
        $this->assertEquals('TestsApplicationModule', ModuleManager::getCustomVisualizationModule());
    }

    public function testUnsetCustomVisualizationModule()
    {
        $this->assertEquals('TestsApplicationModule', ModuleManager::getCustomVisualizationModule());
        ModuleManager::unsetCustomVisualizationModule();
        $this->assertNull(ModuleManager::getCustomVisualizationModule());
    }

    public function testGetExistingFilePathWithCustomModule()
    {
        ModuleManager::setCustomVisualizationModule('SismaFramework');
        $locales = ModuleManager::getExistingFilePath('TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index', Resource::php);
        $this->assertStringContainsString('TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index.php', $locales);
    }

    public function testGetConsequentFilePathWithCustomModule()
    {
        $locales = ModuleManager::getConsequentFilePath('TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT', Resource::json);
        $this->assertStringContainsString('TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT.json', $locales);
    }

    public function testGetExistingFilePathWithApplicationModule()
    {
        ModuleManager::unsetCustomVisualizationModule();
        ModuleManager::setApplicationModule('SismaFramework');
        $locales = ModuleManager::getExistingFilePath('TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index', Resource::php);
        $this->assertStringContainsString('TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index.php', $locales);
    }

    public function testGetConsequentFilePathWithApplicationModule()
    {
        $locales = ModuleManager::getConsequentFilePath('TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT', Resource::json);
        $this->assertStringContainsString('TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT.json', $locales);
    }

    #[RunInSeparateProcess]
    public function testGetExistingFilePathWithException()
    {
        $this->expectException(ModuleException::class);
        ModuleManager::getExistingFilePath('TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'fake', Resource::php, $this->configMock);
    }

    #[RunInSeparateProcess]
    public function testGetConsequentFilePathWithException()
    {
        $this->expectException(ModuleException::class);
        ModuleManager::setApplicationModule('SismaFramework');
        ModuleManager::getExistingFilePath('TestsApplication' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index', Resource::php, $this->configMock);
        ModuleManager::getConsequentFilePath('TestsApplication' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'en_EN', Resource::json, $this->configMock);
    }
}

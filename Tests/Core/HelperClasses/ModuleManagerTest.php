<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa <valentino.delapa@gmail.com>.
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
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Exceptions\ModuleException;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ModuleManagerTest extends TestCase
{

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
        ModuleManager::setApplicationModule('SampleModule');
        $this->assertEquals('SampleModule', ModuleManager::getApplicationModule());
    }

    public function testSetCustomVisualizationModule()
    {
        $this->assertNull(ModuleManager::getCustomVisualizationModule());
        ModuleManager::setCustomVisualizationModule('SampleModule');
        $this->assertEquals('SampleModule', ModuleManager::getCustomVisualizationModule());
    }

    public function testUnsetCustomVisualizationModule()
    {
        $this->assertEquals('SampleModule', ModuleManager::getCustomVisualizationModule());
        ModuleManager::unsetCustomVisualizationModule();
        $this->assertNull(ModuleManager::getCustomVisualizationModule());
    }

    public function testGetExistingFilePathWithCustomModule()
    {
        ModuleManager::setCustomVisualizationModule('SismaFramework');
        $locales = ModuleManager::getExistingFilePath('Sample' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index', Resource::php);
        $this->assertStringContainsString('Sample' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index.php', $locales);
    }

    public function testGetConsequentFilePathWithCustomModule()
    {
        $locales = ModuleManager::getConsequentFilePath('Sample' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT', Resource::json);
        $this->assertStringContainsString('Sample' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT.json', $locales);
    }

    public function testGetExistingFilePathWithApplicationModule()
    {
        ModuleManager::unsetCustomVisualizationModule();
        ModuleManager::setApplicationModule('SismaFramework');
        $locales = ModuleManager::getExistingFilePath('Sample' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index', Resource::php);
        $this->assertStringContainsString('Sample' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index.php', $locales);
    }

    public function testGetConsequentFilePathWithApplicationModule()
    {
        $locales = ModuleManager::getConsequentFilePath('Sample' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT', Resource::json);
        $this->assertStringContainsString('Sample' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT.json', $locales);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetExistingFilePathWithException()
    {
        $this->expectException(ModuleException::class);
        ModuleManager::getExistingFilePath('Sample' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'fake', Resource::php);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetConsequentFilePathWithException()
    {
        $this->expectException(ModuleException::class);
        ModuleManager::setApplicationModule('SismaFramework');
        ModuleManager::getExistingFilePath('Sample' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'index', Resource::php);
        ModuleManager::getConsequentFilePath('Sample' . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'en_EN', Resource::json);
    }

}

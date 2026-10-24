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
use SismaFramework\Core\HelperClasses\Localizator;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\Enumerations\Resource;

/**
 * @author Valentino de Lapa
 */
class LocalizatorTest extends TestCase
{

    public function testClassExists()
    {
        $this->assertTrue(class_exists('SismaFramework\Core\HelperClasses\Localizator'));
    }

    public function testConstructorExists()
    {
        $reflection = new \ReflectionClass(Localizator::class);
        $this->assertTrue($reflection->hasMethod('__construct'));
        $this->assertTrue($reflection->getMethod('__construct')->isPublic());
    }

    public function testConstructorWithDefaults()
    {
        $localizator = new Localizator();
        $this->assertInstanceOf(Localizator::class, $localizator);
    }

    public function testConstructorWithCustomLanguage()
    {
        $localizator = new Localizator(Language::english);
        $this->assertInstanceOf(Localizator::class, $localizator);
    }

    public function testGetPageLocaleArrayMethodExists()
    {
        $reflection = new \ReflectionClass(Localizator::class);
        $this->assertTrue($reflection->hasMethod('getPageLocaleArray'));
        $this->assertTrue($reflection->getMethod('getPageLocaleArray')->isPublic());
    }

    public function testGetTemplateLocaleArrayMethodExists()
    {
        $reflection = new \ReflectionClass(Localizator::class);
        $this->assertTrue($reflection->hasMethod('getTemplateLocaleArray'));
        $this->assertTrue($reflection->getMethod('getTemplateLocaleArray')->isPublic());
    }

    public function testGetEnumerationLocaleArrayMethodExists()
    {
        $reflection = new \ReflectionClass(Localizator::class);
        $this->assertTrue($reflection->hasMethod('getEnumerationLocaleArray'));
        $this->assertTrue($reflection->getMethod('getEnumerationLocaleArray')->isPublic());
    }

    public function testSetLanguageMethodExists()
    {
        $reflection = new \ReflectionClass(Localizator::class);
        $this->assertTrue($reflection->hasMethod('setLanguage'));
        $this->assertTrue($reflection->getMethod('setLanguage')->isStatic());
        $this->assertTrue($reflection->getMethod('setLanguage')->isPublic());
    }

    public function testUnsetLanguageMethodExists()
    {
        $reflection = new \ReflectionClass(Localizator::class);
        $this->assertTrue($reflection->hasMethod('unsetLanguage'));
        $this->assertTrue($reflection->getMethod('unsetLanguage')->isStatic());
        $this->assertTrue($reflection->getMethod('unsetLanguage')->isPublic());
    }

    public function testSetAndUnsetLanguage()
    {
        Localizator::setLanguage(Language::english);
        Localizator::unsetLanguage();
        $this->assertTrue(true);
    }

    public function testGetPageLocaleArrayWithoutRootCommon()
    {
        $localizator = $this->getLocalizatorWithLocale(['pages' => ['sample' => ['common' => ['section' => 'Esempi'], 'index' => ['title' => 'Homepage']]]]);
        $this->assertEquals(['section' => 'Esempi', 'title' => 'Homepage'], $localizator->getPageLocaleArray('sample/index'));
    }

    public function testGetPageLocaleArrayWithoutPagesSection()
    {
        $localizator = $this->getLocalizatorWithLocale(['templates' => []]);
        $this->assertEquals([], $localizator->getPageLocaleArray('sample/index'));
    }

    public function testGetTemplateLocaleArrayWithoutTemplatesSection()
    {
        $localizator = $this->getLocalizatorWithLocale(['pages' => []]);
        $this->assertEquals([], $localizator->getTemplateLocaleArray('welcome'));
    }

    protected function tearDown(): void
    {
        $moduleDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'LocalizatorTestModule';
        if (is_dir($moduleDirectory)) {
            unlink($moduleDirectory . DIRECTORY_SEPARATOR . 'Locales' . DIRECTORY_SEPARATOR . 'it_IT.json');
            rmdir($moduleDirectory . DIRECTORY_SEPARATOR . 'Locales');
            rmdir($moduleDirectory);
        }
    }

    private function getLocalizatorWithLocale(array $locale): Localizator
    {
        $localesDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'LocalizatorTestModule' . DIRECTORY_SEPARATOR . 'Locales';
        mkdir($localesDirectory, 0777, true);
        file_put_contents($localesDirectory . DIRECTORY_SEPARATOR . 'it_IT.json', json_encode($locale));
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['rootPath', sys_get_temp_dir() . DIRECTORY_SEPARATOR],
                    ['localesPath', 'Locales' . DIRECTORY_SEPARATOR],
        ]);
        Config::setInstance($configStub);
        ModuleManager::setApplicationModule('LocalizatorTestModule');
        ModuleManager::unsetCustomVisualizationModule();
        ModuleManager::getExistingFilePath($configStub->localesPath . Language::italian->value, Resource::json, $configStub);
        return new Localizator(Language::italian, $configStub);
    }
}
<?php

/*
 * The MIT License
 *
 * Copyright (c) 2023-present Valentino de Lapa.
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

namespace SismaFramework\Tests\Orm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Locker;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Exceptions\CacheException;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\ExtendedReferencedSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;

/**
 * @author Valentino de Lapa
 */
class CacheTest extends TestCase
{

    private Config $configMock;
    private Locker $lockerMock;

    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $referenceCacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('cache_', true) . DIRECTORY_SEPARATOR;
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['entityNamespace', 'TestsApplication\\Entities\\'],
                    ['entityPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
                    ['moduleFolders', ['SismaFramework']],
                    ['ormCache', true],
                    ['referenceCacheDirectory', $referenceCacheDirectory],
                    ['referenceCachePath', $referenceCacheDirectory . 'referenceCache.json'],
                    ['rootPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR],
        ]);
        Config::setInstance($this->configMock);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->lockerMock = $this->createMock(Locker::class);
    }

    public function testSetCheckGetEntityInCache()
    {
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        $this->assertFalse(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
        Cache::setEntity($baseSample);
        $this->assertTrue(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
        $this->assertInstanceOf(BaseSample::class, Cache::getEntityById(BaseSample::class, 1));
        $this->assertEquals(1, $baseSample->id);
    }

    public function testCheckExceptionWithBaseEntity()
    {
        $this->expectException(CacheException::class);
        Cache::getForeignKeyData(BaseSample::class, null, $this->lockerMock, $this->configMock);
    }

    public function checkUnsetEntity()
    {
        $baseSample = new BaseSample();
        $baseSample->id = 1;
        $this->assertFalse(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
        Cache::setEntity($baseSample);
        $this->assertTrue(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
        $this->assertInstanceOf(BaseSample::class, Cache::getEntityById(BaseSample::class, 1));
        Cache::clearEntityCache();
        $this->assertFalse(Cache::checkEntityPresenceInCache(BaseSample::class, 1));
    }

    public function testGetForeignKeyData()
    {
        $cacheInformationsOne = Cache::getForeignKeyData(ReferencedSample::class, null, $this->lockerMock, $this->configMock);
        $this->assertIsArray($cacheInformationsOne);
        $this->assertArrayHasKey('baseSample', $cacheInformationsOne);
        $this->assertIsArray($cacheInformationsOne['baseSample']);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsOne['baseSample']);
        $cacheInformationsTwo = Cache::getForeignKeyData(ReferencedSample::class, 'baseSample', $this->lockerMock, $this->configMock);
        $this->assertIsArray($cacheInformationsTwo);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsTwo);
        $this->assertSame($cacheInformationsTwo, $cacheInformationsOne['baseSample']);

        $cacheInformationsThree = Cache::getForeignKeyData(ExtendedReferencedSample::class, null, $this->lockerMock, $this->configMock);
        $this->assertIsArray($cacheInformationsThree);
        $this->assertArrayHasKey('otherBaseSample', $cacheInformationsThree);
        $this->assertIsArray($cacheInformationsThree['otherBaseSample']);
        $cacheInformationsFour = Cache::getForeignKeyData(ExtendedReferencedSample::class, 'otherBaseSample', $this->lockerMock, $this->configMock);
        $this->assertIsArray($cacheInformationsFour);
        $this->assertArrayHasKey('extendedReferencedSample', $cacheInformationsFour);
        $this->assertSame($cacheInformationsFour, $cacheInformationsThree['otherBaseSample']);
        $cacheInformationsFifth = Cache::getForeignKeyData(ExtendedReferencedSample::class, 'baseSample', $this->lockerMock, $this->configMock);
        $this->assertIsArray($cacheInformationsFifth);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsFifth);
        $this->assertSame($cacheInformationsFifth, $cacheInformationsThree['baseSample']);
        Cache::clearForeighKeyDataCache();
    }

    public function testGenerateReferencedCacheFile()
    {
        $this->assertFalse(file_exists($this->configMock->referenceCachePath));
        $cacheInformationsOne = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsOne);
        $this->assertArrayHasKey('baseSample', $cacheInformationsOne);
        $this->assertTrue(file_exists($this->configMock->referenceCachePath));
        Cache::clearForeighKeyDataCache();
        $cacheInformationsTwo = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsTwo);
        $this->assertArrayHasKey('baseSample', $cacheInformationsTwo);
        $this->assertTrue(file_exists($this->configMock->referenceCachePath));
        unlink($this->configMock->referenceCachePath);
        unlink($this->configMock->referenceCacheDirectory . '.lock');
        rmdir($this->configMock->referenceCacheDirectory);
        Cache::clearForeighKeyDataCache();
        $this->assertFalse(is_dir($this->configMock->referenceCacheDirectory));
        $this->assertFalse(file_exists($this->configMock->referenceCachePath));
        $cacheInformationsThree = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsThree);
        $this->assertArrayHasKey('baseSample', $cacheInformationsThree);
        $this->assertTrue(file_exists($this->configMock->referenceCachePath));
        Cache::clearForeighKeyDataCache();
    }
}

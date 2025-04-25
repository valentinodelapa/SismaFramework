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
use SismaFramework\Core\BaseClasses\BaseConfig;
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

    private CacheConfigTest $configTest;
    private Locker $lockerMock;

    public function setUp(): void
    {
        $this->configTest = new CacheConfigTest();
        BaseConfig::setInstance($this->configTest);
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
        Cache::getForeignKeyData(BaseSample::class, null, $this->lockerMock, $this->configTest);
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
        $cacheInformationsOne = Cache::getForeignKeyData(ReferencedSample::class, null, $this->lockerMock, $this->configTest);
        $this->assertIsArray($cacheInformationsOne);
        $this->assertArrayHasKey('baseSample', $cacheInformationsOne);
        $this->assertIsArray($cacheInformationsOne['baseSample']);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsOne['baseSample']);
        $cacheInformationsTwo = Cache::getForeignKeyData(ReferencedSample::class, 'baseSample', $this->lockerMock, $this->configTest);
        $this->assertIsArray($cacheInformationsTwo);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsTwo);
        $this->assertSame($cacheInformationsTwo, $cacheInformationsOne['baseSample']);

        $cacheInformationsThree = Cache::getForeignKeyData(ExtendedReferencedSample::class, null, $this->lockerMock, $this->configTest);
        $this->assertIsArray($cacheInformationsThree);
        $this->assertArrayHasKey('otherBaseSample', $cacheInformationsThree);
        $this->assertIsArray($cacheInformationsThree['otherBaseSample']);
        $cacheInformationsFour = Cache::getForeignKeyData(ExtendedReferencedSample::class, 'otherBaseSample', $this->lockerMock, $this->configTest);
        $this->assertIsArray($cacheInformationsFour);
        $this->assertArrayHasKey('extendedReferencedSample', $cacheInformationsFour);
        $this->assertSame($cacheInformationsFour, $cacheInformationsThree['otherBaseSample']);
        $cacheInformationsFifth = Cache::getForeignKeyData(ExtendedReferencedSample::class, 'baseSample', $this->lockerMock, $this->configTest);
        $this->assertIsArray($cacheInformationsFifth);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsFifth);
        $this->assertSame($cacheInformationsFifth, $cacheInformationsThree['baseSample']);
    }

    public function testGenerateReferencedCacheFile()
    {
        $cacheInformationsOne = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsOne);
        $this->assertArrayHasKey('baseSample', $cacheInformationsOne);
        $this->assertFalse(file_exists($this->configTest->referenceCachePath));
        Cache::clearForeighKeyDataCache();
        $cacheInformationsTwo = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsTwo);
        $this->assertArrayHasKey('baseSample', $cacheInformationsTwo);
        $this->assertTrue(file_exists($this->configTest->referenceCachePath));
        unlink($this->configTest->referenceCachePath);
        unlink($this->configTest->referenceCacheDirectory . '.lock');
        rmdir($this->configTest->referenceCacheDirectory);
        Cache::clearForeighKeyDataCache();
        $this->assertFalse(is_dir($this->configTest->referenceCacheDirectory));
        $this->assertFalse(file_exists($this->configTest->referenceCachePath));
        $cacheInformationsThree = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsThree);
        $this->assertArrayHasKey('baseSample', $cacheInformationsThree);
        $this->assertTrue(file_exists($this->configTest->referenceCachePath));
        file_put_contents($this->configTest->referenceCacheDirectory . '.lock', '');
    }
}

class CacheConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        switch ($name) {
            case 'rootPath':
                return true;
            default:
                return false;
        }
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->developmentEnvironment = false;
        $this->entityNamespace = 'TestsApplication\\Entities\\';
        $this->entityPath = 'TestsApplication' . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR;
        $this->logDevelopmentMaxRow = 100;
        $this->logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $this->logPath = $this->logDirectoryPath . 'log.txt';
        $this->logProductionMaxRow = 2;
        $this->logVerboseActive = true;
        $this->moduleFolders = [
            'SismaFramework',
        ];
        $this->ormCache = true;
        $this->referenceCacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('cache_', true) . DIRECTORY_SEPARATOR;
        $this->referenceCachePath = $this->referenceCacheDirectory . 'referenceCache.json';
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        $this->rootPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    }
}

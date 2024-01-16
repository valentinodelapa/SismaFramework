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
use SismaFramework\Orm\Exceptions\CacheException;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Sample\Entities\BaseSample;
use SismaFramework\Sample\Entities\ExtendedReferencedSample;
use SismaFramework\Sample\Entities\OtherBaseSample;
use SismaFramework\Sample\Entities\ReferencedSample;

/**
 * @author Valentino de Lapa
 */
class CacheTest extends TestCase
{
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
        Cache::getForeignKeyData(BaseSample::class);
    }
    
    public function testGetForeignKeyData()
    {
        $cacheInformationsOne = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsOne);
        $this->assertArrayHasKey('baseSample', $cacheInformationsOne);
        $this->assertIsArray($cacheInformationsOne['baseSample']);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsOne['baseSample']);
        $cacheInformationsTwo = Cache::getForeignKeyData(ReferencedSample::class, 'baseSample');
        $this->assertIsArray($cacheInformationsTwo);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsTwo);
        $this->assertSame($cacheInformationsTwo, $cacheInformationsOne['baseSample']);
        
        $cacheInformationsThree = Cache::getForeignKeyData(ExtendedReferencedSample::class);
        $this->assertIsArray($cacheInformationsThree);
        $this->assertArrayHasKey('otherBaseSample', $cacheInformationsThree);
        $this->assertIsArray($cacheInformationsThree['otherBaseSample']);
        $cacheInformationsFour = Cache::getForeignKeyData(ExtendedReferencedSample::class, 'otherBaseSample');
        $this->assertIsArray($cacheInformationsFour);
        $this->assertArrayHasKey('extendedReferencedSample', $cacheInformationsFour);
        $this->assertSame($cacheInformationsFour, $cacheInformationsThree['otherBaseSample']);
        $cacheInformationsFifth = Cache::getForeignKeyData(ExtendedReferencedSample::class, 'baseSample');
        $this->assertIsArray($cacheInformationsFifth);
        $this->assertArrayHasKey('referencedEntityWithoutInitialization', $cacheInformationsFifth);
        $this->assertSame($cacheInformationsFifth, $cacheInformationsThree['baseSample']);
    }
    
    public function testGenerateReferencedCacheFile()
    {
        $this->assertTrue(file_exists(\Config\REFERENCE_CACHE_PATH));
        file_put_contents(\Config\REFERENCE_CACHE_PATH, json_encode([]));
        Cache::clearForeighKeyDataCache();
        $cacheInformationsOne = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsOne);
        $this->assertArrayHasKey('baseSample', $cacheInformationsOne);
        unlink(\Config\REFERENCE_CACHE_PATH);
        $this->assertFalse(file_exists(\Config\REFERENCE_CACHE_PATH));
        Cache::clearForeighKeyDataCache();
        $cacheInformationsTwo = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsTwo);
        $this->assertArrayHasKey('baseSample', $cacheInformationsTwo);
        $this->assertTrue(file_exists(\Config\REFERENCE_CACHE_PATH));
        unlink(\Config\REFERENCE_CACHE_PATH);
        unlink(\Config\REFERENCE_CACHE_DIRECTORY.'.lock');
        rmdir(\Config\REFERENCE_CACHE_DIRECTORY);
        Cache::clearForeighKeyDataCache();
        $this->assertFalse(is_dir(\Config\REFERENCE_CACHE_DIRECTORY));
        $this->assertFalse(file_exists(\Config\REFERENCE_CACHE_PATH));
        $cacheInformationsThree = Cache::getForeignKeyData(ReferencedSample::class);
        $this->assertIsArray($cacheInformationsThree);
        $this->assertArrayHasKey('baseSample', $cacheInformationsThree);
        $this->assertTrue(file_exists(\Config\REFERENCE_CACHE_PATH));
        file_put_contents(\Config\REFERENCE_CACHE_DIRECTORY.'.lock', '');
    }
}

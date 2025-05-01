<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\BaseClasses;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\FixtureException;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Entities\FakeReferencedSample;
use SismaFramework\TestsApplication\Entities\OtherReferencedSample;
use SismaFramework\TestsApplication\Entities\ReferencedSample;
use SismaFramework\TestsApplication\FakeFixtures\FakeBaseSampleFixture;
use SismaFramework\TestsApplication\FakeFixtures\FakeReferencedSampleFixture;
use SismaFramework\TestsApplication\Fixtures\BaseSampleFixture;
use SismaFramework\TestsApplication\Fixtures\OtherReferencedSampleFixture;
use SismaFramework\TestsApplication\Fixtures\ReferencedSampleFixture;

/**
 * Description of BaseFixtureTest
 *
 * Copyright 2022 Valentino de Lapa.
 */
class BaseFixtureTest extends TestCase
{

    private DataMapper $dataMapperMock;

    #[\Override]
    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['developmentEnvironment', false],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 100],
                    ['logVerboseActive', true],
        ]);
        Config::setInstance($configMock);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
    }

    public function testBaseSampleFixture()
    {
        $referencedSample = new ReferencedSample($this->dataMapperMock);
        $referencedSample->text = 'referenced sample text';
        $otherReferencedSample = new OtherReferencedSample($this->dataMapperMock);
        $otherReferencedSample->text = 'other referenced sample text';
        $entitesArray = [
            ReferencedSampleFixture::class => $referencedSample,
            OtherReferencedSampleFixture::class => $otherReferencedSample,
        ];
        $baseSampleFixture = new BaseSampleFixture($this->dataMapperMock);
        $baseSample = $baseSampleFixture->execute($entitesArray);
        $this->assertInstanceOf(BaseSample::class, $baseSample);
        $this->assertInstanceOf(ReferencedSample::class, $baseSample->referencedEntityWithoutInitialization);
        $this->assertInstanceOf(ReferencedSample::class, $baseSample->referencedEntityWithInitialization);
        $this->assertInstanceOf(OtherReferencedSample::class, $baseSample->otherReferencedSample);
    }

    #[RunInSeparateProcess]
    public function testFakeBaseSampleFixtureWithException()
    {
        $this->expectException(FixtureException::class);
        $entitesArray = [
            FakeReferencedSampleFixture::class => new FakeReferencedSample($this->dataMapperMock),
        ];
        $fakeBaseSampleFixture = new FakeBaseSampleFixture($this->dataMapperMock);
        $fakeBaseSampleFixture->execute($entitesArray);
    }
}

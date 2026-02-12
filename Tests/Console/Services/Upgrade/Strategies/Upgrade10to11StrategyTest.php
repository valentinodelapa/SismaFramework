<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Strategies;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Strategies\Upgrade10to11Strategy;
use SismaFramework\Console\Services\Upgrade\Strategies\UpgradeStrategyInterface;
use SismaFramework\Console\Services\Upgrade\Transformers\MethodRenameTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\ResponseConstructorTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\ReturnTypeTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\StaticToInstanceTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\TransformerInterface;

class Upgrade10to11StrategyTest extends TestCase
{
    private Upgrade10to11Strategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new Upgrade10to11Strategy();
    }

    public function testImplementsUpgradeStrategyInterface(): void
    {
        $this->assertInstanceOf(UpgradeStrategyInterface::class, $this->strategy);
    }

    public function testGetSourceVersion(): void
    {
        $this->assertEquals('10.1.7', $this->strategy->getSourceVersion());
    }

    public function testGetTargetVersion(): void
    {
        $this->assertEquals('11.0.0', $this->strategy->getTargetVersion());
    }

    public function testGetTransformersReturnsCorrectCount(): void
    {
        $transformers = $this->strategy->getTransformers();

        $this->assertCount(4, $transformers);
    }

    public function testGetTransformersReturnsCorrectTypes(): void
    {
        $transformers = $this->strategy->getTransformers();

        $this->assertInstanceOf(StaticToInstanceTransformer::class, $transformers[0]);
        $this->assertInstanceOf(ReturnTypeTransformer::class, $transformers[1]);
        $this->assertInstanceOf(ResponseConstructorTransformer::class, $transformers[2]);
        $this->assertInstanceOf(MethodRenameTransformer::class, $transformers[3]);
    }

    public function testGetTransformersAllImplementInterface(): void
    {
        $transformers = $this->strategy->getTransformers();

        foreach ($transformers as $transformer) {
            $this->assertInstanceOf(TransformerInterface::class, $transformer);
        }
    }

    public function testGetBreakingChangesReturnsNonEmptyArray(): void
    {
        $breakingChanges = $this->strategy->getBreakingChanges();

        $this->assertNotEmpty($breakingChanges);
        $this->assertCount(5, $breakingChanges);
    }

    public function testGetBreakingChangesContainsExpectedItems(): void
    {
        $breakingChanges = $this->strategy->getBreakingChanges();

        $joined = implode(' ', $breakingChanges);
        $this->assertStringContainsString('ErrorHandler', $joined);
        $this->assertStringContainsString('customFilter', $joined);
        $this->assertStringContainsString('setResponseType', $joined);
        $this->assertStringContainsString('handleNonThrowableError', $joined);
        $this->assertStringContainsString('index.php', $joined);
    }

    public function testRequiresManualIntervention(): void
    {
        $this->assertTrue($this->strategy->requiresManualIntervention());
    }
}

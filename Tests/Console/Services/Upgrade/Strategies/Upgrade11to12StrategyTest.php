<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Strategies;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Strategies\Upgrade11to12Strategy;
use SismaFramework\Console\Services\Upgrade\Strategies\UpgradeStrategyInterface;
use SismaFramework\Console\Services\Upgrade\Transformers\ClassRenameTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\DeprecatedMethodUsageTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\ExceptionBaseClassTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\FulltextIndexColumnTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\TransformerInterface;

class Upgrade11to12StrategyTest extends TestCase
{

    private Upgrade11to12Strategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new Upgrade11to12Strategy();
    }

    public function testImplementsUpgradeStrategyInterface(): void
    {
        $this->assertInstanceOf(UpgradeStrategyInterface::class, $this->strategy);
    }

    public function testGetSourceVersion(): void
    {
        $this->assertEquals('11.0.0', $this->strategy->getSourceVersion());
    }

    public function testGetTargetVersion(): void
    {
        $this->assertEquals('12.0.0', $this->strategy->getTargetVersion());
    }

    public function testGetTransformersReturnsCorrectCount(): void
    {
        $transformers = $this->strategy->getTransformers();

        $this->assertCount(4, $transformers);
    }

    public function testGetTransformersReturnsCorrectTypes(): void
    {
        $transformers = $this->strategy->getTransformers();

        $this->assertInstanceOf(ClassRenameTransformer::class, $transformers[0]);
        $this->assertInstanceOf(FulltextIndexColumnTransformer::class, $transformers[1]);
        $this->assertInstanceOf(ExceptionBaseClassTransformer::class, $transformers[2]);
        $this->assertInstanceOf(DeprecatedMethodUsageTransformer::class, $transformers[3]);
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
        $this->assertCount(7, $breakingChanges);
    }

    public function testGetBreakingChangesContainsExpectedItems(): void
    {
        $breakingChanges = $this->strategy->getBreakingChanges();

        $joined = implode(' ', $breakingChanges);
        $this->assertStringContainsString('SelfReferencedModel', $joined);
        $this->assertStringContainsString('SelfDependentModel', $joined);
        $this->assertStringContainsString('selfReferencedModel', $joined);
        $this->assertStringContainsString('selfDependentModel', $joined);
        $this->assertStringContainsString('setFulltextIndexColumn', $joined);
        $this->assertStringContainsString('countEntityCollectionByEntity', $joined);
        $this->assertStringContainsString('countEntityCollectionByParentAndEntity', $joined);
        $this->assertStringContainsString('LogException', $joined);
        $this->assertStringContainsString('NoLogException', $joined);
        $this->assertStringContainsString('Config/config.php', $joined);
    }

    public function testRequiresManualIntervention(): void
    {
        $this->assertTrue($this->strategy->requiresManualIntervention());
    }
}

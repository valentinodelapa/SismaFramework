<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Strategies;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Strategies\Upgrade12to13Strategy;
use SismaFramework\Console\Services\Upgrade\Strategies\UpgradeStrategyInterface;
use SismaFramework\Console\Services\Upgrade\Transformers\ClassRenameTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\TransformerInterface;

class Upgrade12to13StrategyTest extends TestCase
{

    private Upgrade12to13Strategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new Upgrade12to13Strategy();
    }

    public function testImplementsUpgradeStrategyInterface(): void
    {
        $this->assertInstanceOf(UpgradeStrategyInterface::class, $this->strategy);
    }

    public function testGetSourceVersion(): void
    {
        $this->assertEquals('12.0.0', $this->strategy->getSourceVersion());
    }

    public function testGetTargetVersion(): void
    {
        $this->assertEquals('13.0.0', $this->strategy->getTargetVersion());
    }

    public function testGetTransformersReturnsCorrectCount(): void
    {
        $transformers = $this->strategy->getTransformers();

        $this->assertCount(1, $transformers);
    }

    public function testGetTransformersReturnsCorrectTypes(): void
    {
        $transformers = $this->strategy->getTransformers();

        $this->assertInstanceOf(ClassRenameTransformer::class, $transformers[0]);
    }

    public function testGetTransformersAllImplementInterface(): void
    {
        $transformers = $this->strategy->getTransformers();

        foreach ($transformers as $transformer) {
            $this->assertInstanceOf(TransformerInterface::class, $transformer);
        }
    }

    public function testClassRenameTransformerRewritesBaseFormNamespace(): void
    {
        $transformer = $this->strategy->getTransformers()[0];
        $content = "use SismaFramework\Core\BaseClasses\BaseForm;\n"
                . "use SismaFramework\Core\BaseClasses\BaseForm\EntityResolver;\n"
                . "use SismaFramework\Core\BaseClasses\BaseForm\FilterManager;\n"
                . "use SismaFramework\Core\BaseClasses\BaseForm\FormValidator;\n"
                . "use SismaFramework\Core\HelperClasses\Filter;\n"
                . "use SismaFramework\Core\Enumerations\FilterType;\n";

        $this->assertTrue($transformer->canTransform('SampleForm.php', $content));

        $result = $transformer->transform($content, 'SampleForm.php');

        $this->assertStringContainsString('SismaFramework\Orm\BaseClasses\BaseForm;', $result->transformedContent);
        $this->assertStringContainsString('SismaFramework\Orm\BaseClasses\BaseForm\EntityResolver;', $result->transformedContent);
        $this->assertStringContainsString('SismaFramework\Orm\BaseClasses\BaseForm\FilterManager;', $result->transformedContent);
        $this->assertStringContainsString('SismaFramework\Orm\BaseClasses\BaseForm\FormValidator;', $result->transformedContent);
        $this->assertStringContainsString('SismaFramework\Orm\HelperClasses\Filter;', $result->transformedContent);
        $this->assertStringContainsString('SismaFramework\Orm\Enumerations\FilterType;', $result->transformedContent);
        $this->assertStringNotContainsString('SismaFramework\Core\BaseClasses\BaseForm', $result->transformedContent);
        $this->assertStringNotContainsString('SismaFramework\Core\HelperClasses\Filter', $result->transformedContent);
        $this->assertStringNotContainsString('SismaFramework\Core\Enumerations\FilterType', $result->transformedContent);
    }

    public function testGetBreakingChangesReturnsNonEmptyArray(): void
    {
        $breakingChanges = $this->strategy->getBreakingChanges();

        $this->assertNotEmpty($breakingChanges);
        $this->assertCount(4, $breakingChanges);
    }

    public function testGetBreakingChangesContainsExpectedItems(): void
    {
        $breakingChanges = $this->strategy->getBreakingChanges();

        $joined = implode(' ', $breakingChanges);
        $this->assertStringContainsString('BaseForm', $joined);
        $this->assertStringContainsString('EntityResolver', $joined);
        $this->assertStringContainsString('FilterManager', $joined);
        $this->assertStringContainsString('FormValidator', $joined);
        $this->assertStringContainsString('Filter', $joined);
        $this->assertStringContainsString('FilterType', $joined);
        $this->assertStringContainsString('SismaFramework\\Core\\BaseClasses', $joined);
        $this->assertStringContainsString('SismaFramework\\Orm\\BaseClasses', $joined);
    }

    public function testRequiresManualIntervention(): void
    {
        $this->assertTrue($this->strategy->requiresManualIntervention());
    }
}

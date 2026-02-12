<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Transformers;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Transformers\ReturnTypeTransformer;

class ReturnTypeTransformerTest extends TestCase
{
    private ReturnTypeTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ReturnTypeTransformer();
    }

    public function testCanTransformReturnsTrueForFormWithVoidCustomFilter(): void
    {
        $content = <<<'PHP'
<?php
class SampleForm extends BaseForm
{
    protected function customFilter(): void
    {
    }
}
PHP;

        $this->assertTrue($this->transformer->canTransform('/path/to/Forms/SampleForm.php', $content));
    }

    public function testCanTransformReturnsFalseForNonFormFile(): void
    {
        $content = <<<'PHP'
<?php
class SampleController
{
    protected function customFilter(): void
    {
    }
}
PHP;

        $this->assertFalse($this->transformer->canTransform('/path/to/Controllers/SampleController.php', $content));
    }

    public function testCanTransformReturnsFalseWhenAlreadyBool(): void
    {
        $content = <<<'PHP'
<?php
class SampleForm extends BaseForm
{
    protected function customFilter(): bool
    {
        return true;
    }
}
PHP;

        $this->assertFalse($this->transformer->canTransform('/path/to/Forms/SampleForm.php', $content));
    }

    public function testTransformChangesReturnTypeFromVoidToBool(): void
    {
        $content = <<<'PHP'
<?php
class SampleForm extends BaseForm
{
    protected function customFilter(): void
    {
    }
}
PHP;

        $result = $this->transformer->transform($content, '/path/to/Forms/SampleForm.php');

        $this->assertStringContainsString('protected function customFilter(): bool', $result->transformedContent);
        $this->assertStringNotContainsString('customFilter(): void', $result->transformedContent);
        $this->assertGreaterThan(0, $result->changesCount);
    }

    public function testTransformAddsReturnTrueAtEnd(): void
    {
        $content = <<<'PHP'
<?php
class SampleForm extends BaseForm
{
    protected function customFilter(): void
    {
        $this->someValidation();
    }
}
PHP;

        $result = $this->transformer->transform($content, '/path/to/Forms/SampleForm.php');

        $this->assertStringContainsString('return true;', $result->transformedContent);
    }

    public function testTransformAddsReturnFalseAfterErrorAssignment(): void
    {
        $content = <<<'PHP'
<?php
class SampleForm extends BaseForm
{
    protected function customFilter(): void
    {
        $this->formFilterError->startDateError = true;
    }
}
PHP;

        $result = $this->transformer->transform($content, '/path/to/Forms/SampleForm.php');

        $this->assertStringContainsString('return false;', $result->transformedContent);
        $this->assertStringContainsString('return true;', $result->transformedContent);
    }

    public function testTransformPreservesExistingReturnStatements(): void
    {
        $content = <<<'PHP'
<?php
class SampleForm extends BaseForm
{
    protected function customFilter(): void
    {
        if ($condition) {
            return true;
        }
        return false;
    }
}
PHP;

        $result = $this->transformer->transform($content, '/path/to/Forms/SampleForm.php');

        $this->assertStringContainsString('customFilter(): bool', $result->transformedContent);
    }

    public function testGetConfidence(): void
    {
        $this->assertEquals(85, $this->transformer->getConfidence());
    }

    public function testGetDescription(): void
    {
        $this->assertNotEmpty($this->transformer->getDescription());
    }
}

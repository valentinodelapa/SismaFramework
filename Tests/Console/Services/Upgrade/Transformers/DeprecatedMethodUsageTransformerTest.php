<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Transformers;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Transformers\DeprecatedMethodUsageTransformer;

class DeprecatedMethodUsageTransformerTest extends TestCase
{

    public function testCanTransformReturnsTrueWhenMethodFound(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([
            'countEntityCollectionByEntity' => 'Use countBy{PropertyName}() instead',
        ]);

        $content = '<?php $this->countEntityCollectionByEntity([\'foo\' => $bar]);';

        $this->assertTrue($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWhenMethodNotFound(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([
            'countEntityCollectionByEntity' => 'Use countBy{PropertyName}() instead',
        ]);

        $content = '<?php $this->countByFoo($bar);';

        $this->assertFalse($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWithEmptyDeprecations(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([]);

        $content = '<?php $this->countEntityCollectionByEntity([\'foo\' => $bar]);';

        $this->assertFalse($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testTransformDoesNotModifyContent(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([
            'countEntityCollectionByEntity' => 'Use countBy{PropertyName}() instead',
        ]);

        $content = '<?php $this->countEntityCollectionByEntity([\'foo\' => $bar]);';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertEquals($content, $result->transformedContent);
        $this->assertEquals(0, $result->changesCount);
    }

    public function testTransformAddsWarningAndRequiresManualReview(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([
            'countEntityCollectionByEntity' => 'Use countBy{PropertyName}() instead',
        ]);

        $content = '<?php $this->countEntityCollectionByEntity([\'foo\' => $bar]);';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertTrue($result->requiresManualReview);
        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('countEntityCollectionByEntity', $result->warnings[0]);
        $this->assertStringContainsString('Use countBy{PropertyName}() instead', $result->warnings[0]);
    }

    public function testTransformCountsMultipleOccurrences(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([
            'getEntityCollectionByEntity' => 'Use getBy{PropertyName}() instead',
        ]);

        $content = <<<'PHP'
<?php
$this->getEntityCollectionByEntity(['foo' => $bar]);
$this->getEntityCollectionByEntity(['baz' => $qux]);
PHP;
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('2', $result->warnings[0]);
    }

    public function testTransformWithNoMatchesReturnsNoWarnings(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([
            'countEntityCollectionByEntity' => 'Use countBy{PropertyName}() instead',
        ]);

        $content = '<?php $this->countByFoo($bar);';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertEmpty($result->warnings);
        $this->assertFalse($result->requiresManualReview);
    }

    public function testGetConfidence(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([]);

        $this->assertEquals(100, $transformer->getConfidence());
    }

    public function testGetDescription(): void
    {
        $transformer = new DeprecatedMethodUsageTransformer([]);

        $this->assertNotEmpty($transformer->getDescription());
    }
}

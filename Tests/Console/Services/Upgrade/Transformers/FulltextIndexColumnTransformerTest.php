<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Transformers;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Transformers\FulltextIndexColumnTransformer;

class FulltextIndexColumnTransformerTest extends TestCase
{

    private FulltextIndexColumnTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new FulltextIndexColumnTransformer();
    }

    public function testCanTransformReturnsTrueWhenMethodFound(): void
    {
        $content = '<?php $query->setFulltextIndexColumn([\'col\'], Placeholder::placeholder);';

        $this->assertTrue($this->transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWhenMethodNotFound(): void
    {
        $content = '<?php $query->setWhere();';

        $this->assertFalse($this->transformer->canTransform('/path/to/file.php', $content));
    }

    public function testTransformIgnoresCallsWithTwoOrFewerArgs(): void
    {
        $content = '$query->setFulltextIndexColumn([\'title\'], Placeholder::placeholder);';
        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals($content, $result->transformedContent);
        $this->assertEquals(0, $result->changesCount);
        $this->assertFalse($result->requiresManualReview);
    }

    public function testTransformIgnoresCallWithOneArg(): void
    {
        $content = '$query->setFulltextIndexColumn([\'title\']);';
        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals($content, $result->transformedContent);
        $this->assertEquals(0, $result->changesCount);
    }

    public function testTransformReordersFiveArgs(): void
    {
        // Old: (columns, value, columnAlias, append, textSearchMode)
        // New: (columns, value, textSearchMode, columnAlias, append)
        $content = '$query->setFulltextIndexColumn([\'title\', \'body\'], Placeholder::placeholder, \'relevance\', false, TextSearchMode::inBooleanMode);';
        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(1, $result->changesCount);
        $this->assertStringContainsString(
            'setFulltextIndexColumn([\'title\', \'body\'], Placeholder::placeholder, TextSearchMode::inBooleanMode, \'relevance\', false)',
            $result->transformedContent
        );
    }

    public function testTransformReordersFiveArgsWithNullAlias(): void
    {
        $content = '$query->setFulltextIndexColumn($cols, $value, null, false, TextSearchMode::inNaturaLanguageMode);';
        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(1, $result->changesCount);
        $this->assertStringContainsString(
            'setFulltextIndexColumn($cols, $value, TextSearchMode::inNaturaLanguageMode, null, false)',
            $result->transformedContent
        );
    }

    public function testTransformFlagsThreeArgCallForManualReview(): void
    {
        // 3rd arg meaning changed: was columnAlias, now textSearchMode
        $content = '$query->setFulltextIndexColumn([\'col\'], $value, \'my_alias\');';
        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(0, $result->changesCount);
        $this->assertTrue($result->requiresManualReview);
        $this->assertNotEmpty($result->warnings);
    }

    public function testTransformFlagsFourArgCallForManualReview(): void
    {
        $content = '$query->setFulltextIndexColumn([\'col\'], $value, \'my_alias\', true);';
        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(0, $result->changesCount);
        $this->assertTrue($result->requiresManualReview);
        $this->assertNotEmpty($result->warnings);
    }

    public function testTransformHandlesMultipleFiveArgCalls(): void
    {
        $content = <<<'PHP'
$q1->setFulltextIndexColumn(['a'], $v, 'alias1', false, TextSearchMode::inBooleanMode);
$q2->setFulltextIndexColumn(['b'], $v, null, true, TextSearchMode::inNaturaLanguageMode);
PHP;

        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(2, $result->changesCount);
        $this->assertStringContainsString(
            "setFulltextIndexColumn(['a'], \$v, TextSearchMode::inBooleanMode, 'alias1', false)",
            $result->transformedContent
        );
        $this->assertStringContainsString(
            "setFulltextIndexColumn(['b'], \$v, TextSearchMode::inNaturaLanguageMode, null, true)",
            $result->transformedContent
        );
    }

    public function testTransformHandlesNestedParenthesesInArgs(): void
    {
        // Arg with nested function call: setFulltextIndexColumn(getColumns(), getValue(), null, false, getMode())
        $content = '$query->setFulltextIndexColumn(getColumns(), getValue(), null, false, getMode());';
        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(1, $result->changesCount);
        $this->assertStringContainsString(
            'setFulltextIndexColumn(getColumns(), getValue(), getMode(), null, false)',
            $result->transformedContent
        );
    }

    public function testTransformMixedCallsAutoFixAndManualReview(): void
    {
        $content = <<<'PHP'
$q1->setFulltextIndexColumn(['a'], $v, 'alias', false, TextSearchMode::inBooleanMode);
$q2->setFulltextIndexColumn(['b'], $v, 'alias2');
PHP;

        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(1, $result->changesCount);
        $this->assertTrue($result->requiresManualReview);
        $this->assertNotEmpty($result->warnings);
    }

    public function testGetConfidence(): void
    {
        $this->assertEquals(70, $this->transformer->getConfidence());
    }

    public function testGetDescription(): void
    {
        $this->assertNotEmpty($this->transformer->getDescription());
    }

    public function testTransformWithNoMethodCalls(): void
    {
        $content = '<?php echo "hello world";';
        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertEquals($content, $result->transformedContent);
        $this->assertEquals(0, $result->changesCount);
        $this->assertFalse($result->requiresManualReview);
    }
}

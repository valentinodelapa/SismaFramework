<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Transformers;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Transformers\ExceptionBaseClassTransformer;

class ExceptionBaseClassTransformerTest extends TestCase
{

    public function testCanTransformReturnsTrueForLogException(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $content = '<?php class FooException extends LogException {}';

        $this->assertTrue($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsTrueForNoLogException(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $content = '<?php class FooException extends NoLogException {}';

        $this->assertTrue($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWhenNoMatch(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $content = '<?php class FooException extends BaseException {}';

        $this->assertFalse($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testTransformReplacesLogExceptionWithBaseExceptionAndInterface(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $content = <<<'PHP'
<?php

use SismaFramework\Security\ExtendedClasses\LogException;

class FooException extends LogException
{
}
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('extends BaseException implements ShouldBeLoggedException', $result->transformedContent);
        $this->assertStringContainsString('use SismaFramework\Security\BaseClasses\BaseException;', $result->transformedContent);
        $this->assertStringContainsString('use SismaFramework\Security\Interfaces\Exceptions\ShouldBeLoggedException;', $result->transformedContent);
        $this->assertStringNotContainsString('SismaFramework\Security\ExtendedClasses\LogException', $result->transformedContent);
        $this->assertFalse($result->requiresManualReview);
    }

    public function testTransformReplacesNoLogExceptionWithBaseExceptionOnly(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $content = <<<'PHP'
<?php

use SismaFramework\Security\ExtendedClasses\NoLogException;

class FooException extends NoLogException
{
}
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('extends BaseException', $result->transformedContent);
        $this->assertStringNotContainsString('ShouldBeLoggedException', $result->transformedContent);
        $this->assertStringContainsString('use SismaFramework\Security\BaseClasses\BaseException;', $result->transformedContent);
        $this->assertStringNotContainsString('SismaFramework\Security\ExtendedClasses\NoLogException', $result->transformedContent);
        $this->assertFalse($result->requiresManualReview);
    }

    public function testTransformPreservesExistingImplementsClause(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $content = <<<'PHP'
<?php

use SismaFramework\Security\ExtendedClasses\LogException;
use My\Module\SomeInterface;

class FooException extends LogException implements SomeInterface
{
}
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('extends BaseException implements ShouldBeLoggedException, SomeInterface', $result->transformedContent);
    }

    public function testTransformFlagsManualReviewWhenUseStatementMissing(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $content = <<<'PHP'
<?php

class FooException extends LogException
{
}
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertTrue($result->requiresManualReview);
        $this->assertNotEmpty($result->warnings);
    }

    public function testTransformFlagsManualReviewWhenExtendsClauseMissing(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $content = <<<'PHP'
<?php

use SismaFramework\Security\ExtendedClasses\LogException;

// LogException referenced but not extended, e.g. via instanceof or docblock
if ($exception instanceof LogException) {
}
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertTrue($result->requiresManualReview);
        $this->assertNotEmpty($result->warnings);
    }

    public function testGetConfidence(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $this->assertEquals(80, $transformer->getConfidence());
    }

    public function testGetDescription(): void
    {
        $transformer = new ExceptionBaseClassTransformer();

        $this->assertNotEmpty($transformer->getDescription());
    }
}

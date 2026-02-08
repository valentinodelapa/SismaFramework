<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Transformers;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Transformers\MethodRenameTransformer;

class MethodRenameTransformerTest extends TestCase
{
    public function testCanTransformReturnsTrueWhenMethodFound(): void
    {
        $transformer = new MethodRenameTransformer([
            'handleNonThrowableError' => 'registerNonThrowableErrorHandler'
        ]);

        $content = '<?php $handler->handleNonThrowableError();';

        $this->assertTrue($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWhenMethodNotFound(): void
    {
        $transformer = new MethodRenameTransformer([
            'handleNonThrowableError' => 'registerNonThrowableErrorHandler'
        ]);

        $content = '<?php $handler->someOtherMethod();';

        $this->assertFalse($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWithEmptyRenames(): void
    {
        $transformer = new MethodRenameTransformer([]);

        $content = '<?php $handler->handleNonThrowableError();';

        $this->assertFalse($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testTransformRenamesMethod(): void
    {
        $transformer = new MethodRenameTransformer([
            'handleNonThrowableError' => 'registerNonThrowableErrorHandler'
        ]);

        $content = '<?php $handler->handleNonThrowableError();';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('registerNonThrowableErrorHandler', $result->transformedContent);
        $this->assertStringNotContainsString('handleNonThrowableError', $result->transformedContent);
        $this->assertEquals(1, $result->changesCount);
    }

    public function testTransformRenamesMultipleOccurrences(): void
    {
        $transformer = new MethodRenameTransformer([
            'handleNonThrowableError' => 'registerNonThrowableErrorHandler'
        ]);

        $content = <<<'PHP'
<?php
$handler->handleNonThrowableError();
$other->handleNonThrowableError();
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(2, $result->changesCount);
    }

    public function testTransformRenamesMultipleMethods(): void
    {
        $transformer = new MethodRenameTransformer([
            'handleNonThrowableError' => 'registerNonThrowableErrorHandler',
            'oldMethod' => 'newMethod'
        ]);

        $content = <<<'PHP'
<?php
$handler->handleNonThrowableError();
$obj->oldMethod();
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('registerNonThrowableErrorHandler', $result->transformedContent);
        $this->assertStringContainsString('newMethod', $result->transformedContent);
        $this->assertEquals(2, $result->changesCount);
    }

    public function testTransformUsesWordBoundaries(): void
    {
        $transformer = new MethodRenameTransformer([
            'get' => 'fetch'
        ]);

        $content = '<?php $obj->getAll(); $obj->get();';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('getAll', $result->transformedContent);
        $this->assertStringContainsString('fetch()', $result->transformedContent);
    }

    public function testTransformWithNoChanges(): void
    {
        $transformer = new MethodRenameTransformer([
            'handleNonThrowableError' => 'registerNonThrowableErrorHandler'
        ]);

        $content = '<?php $handler->someMethod();';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertEquals($content, $result->transformedContent);
        $this->assertEquals(0, $result->changesCount);
    }

    public function testGetConfidence(): void
    {
        $transformer = new MethodRenameTransformer([]);

        $this->assertEquals(90, $transformer->getConfidence());
    }

    public function testGetDescription(): void
    {
        $transformer = new MethodRenameTransformer([]);

        $this->assertNotEmpty($transformer->getDescription());
    }

    public function testTransformDoesNotRequireManualReview(): void
    {
        $transformer = new MethodRenameTransformer([
            'handleNonThrowableError' => 'registerNonThrowableErrorHandler'
        ]);

        $content = '<?php $handler->handleNonThrowableError();';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertFalse($result->requiresManualReview);
        $this->assertEmpty($result->warnings);
    }
}

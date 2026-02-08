<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Transformers;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Transformers\StaticToInstanceTransformer;

class StaticToInstanceTransformerTest extends TestCase
{
    private StaticToInstanceTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new StaticToInstanceTransformer();
    }

    public function testCanTransformReturnsTrueForErrorHandlerStaticCalls(): void
    {
        $content = '<?php ErrorHandler::disableErrorDisplay();';

        $this->assertTrue($this->transformer->canTransform('/path/to/index.php', $content));
    }

    public function testCanTransformReturnsTrueForDebuggerStaticCalls(): void
    {
        $content = '<?php Debugger::startExecutionTimeCalculation();';

        $this->assertTrue($this->transformer->canTransform('/path/to/index.php', $content));
    }

    public function testCanTransformReturnsFalseWhenNoStaticCalls(): void
    {
        $content = '<?php $handler->disableErrorDisplay();';

        $this->assertFalse($this->transformer->canTransform('/path/to/index.php', $content));
    }

    public function testTransformIndexFileReplacesErrorHandlerStaticCalls(): void
    {
        $content = "<?php\nrequire_once '/path/to/Autoload.php';\nErrorHandler::disableErrorDisplay();\nErrorHandler::handleNonThrowableError();";

        $result = $this->transformer->transform($content, '/path/to/index.php');

        $this->assertStringContainsString('$errorHandler = new ErrorHandler();', $result->transformedContent);
        $this->assertStringContainsString('$errorHandler->disableErrorDisplay', $result->transformedContent);
        $this->assertStringContainsString('$errorHandler->registerNonThrowableErrorHandler', $result->transformedContent);
        $this->assertStringNotContainsString('ErrorHandler::', $result->transformedContent);
        $this->assertGreaterThan(0, $result->changesCount);
    }

    public function testTransformIndexFileReplacesDebuggerStaticCalls(): void
    {
        $content = "<?php\nrequire_once '/path/to/Autoload.php';\nDebugger::startExecutionTimeCalculation();";

        $result = $this->transformer->transform($content, '/path/to/index.php');

        $this->assertStringContainsString('$debugger = new Debugger();', $result->transformedContent);
        $this->assertStringContainsString('$debugger->startExecutionTimeCalculation', $result->transformedContent);
        $this->assertStringNotContainsString('Debugger::', $result->transformedContent);
    }

    public function testTransformIndexFileInjectsDebuggerIntoDispatcher(): void
    {
        $content = "<?php\nrequire_once '/path/to/Autoload.php';\nDebugger::startExecutionTimeCalculation();\n\$dispatcher = new Dispatcher();";

        $result = $this->transformer->transform($content, '/path/to/index.php');

        $this->assertStringContainsString('new Dispatcher(debugger: $debugger)', $result->transformedContent);
    }

    public function testTransformIndexFileHasHigherConfidence(): void
    {
        $content = "<?php\nrequire_once '/path/to/Autoload.php';\nErrorHandler::disableErrorDisplay();";

        $result = $this->transformer->transform($content, '/path/to/index.php');

        $this->assertEquals(75, $result->confidence);
        $this->assertFalse($result->requiresManualReview);
    }

    public function testTransformIndexFileWithoutAutoloadWarns(): void
    {
        $content = "<?php\nErrorHandler::disableErrorDisplay();";

        $result = $this->transformer->transform($content, '/path/to/index.php');

        $this->assertStringContainsString('$errorHandler->disableErrorDisplay', $result->transformedContent);
        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('Could not find autoload statement', $result->warnings[0]);
    }

    public function testTransformRegularFileHasLowerConfidence(): void
    {
        $content = '<?php ErrorHandler::handleNonThrowableError();';

        $result = $this->transformer->transform($content, '/path/to/SomeService.php');

        $this->assertEquals(60, $result->confidence);
        $this->assertTrue($result->requiresManualReview);
    }

    public function testTransformRegularFileDoesNotModifyContent(): void
    {
        $content = '<?php ErrorHandler::handleNonThrowableError();';

        $result = $this->transformer->transform($content, '/path/to/SomeService.php');

        $this->assertEquals($content, $result->transformedContent);
        $this->assertNotEmpty($result->warnings);
    }

    public function testTransformIndexFileRenamesHandleNonThrowableError(): void
    {
        $content = "<?php\nrequire_once '/path/to/Autoload.php';\nErrorHandler::handleNonThrowableError();";

        $result = $this->transformer->transform($content, '/path/to/index.php');

        $this->assertStringContainsString('registerNonThrowableErrorHandler', $result->transformedContent);
        $this->assertStringNotContainsString('handleNonThrowableError', $result->transformedContent);
    }

    public function testGetConfidence(): void
    {
        $this->assertEquals(75, $this->transformer->getConfidence());
    }

    public function testGetDescription(): void
    {
        $this->assertNotEmpty($this->transformer->getDescription());
    }

    public function testTransformIndexFileBothErrorHandlerAndDebugger(): void
    {
        $content = "<?php\nrequire_once '/path/to/Autoload.php';\nErrorHandler::disableErrorDisplay();\nErrorHandler::handleNonThrowableError();\nDebugger::startExecutionTimeCalculation();\n\$dispatcher = new Dispatcher();";

        $result = $this->transformer->transform($content, '/path/to/index.php');

        $this->assertStringContainsString('$errorHandler = new ErrorHandler();', $result->transformedContent);
        $this->assertStringContainsString('$debugger = new Debugger();', $result->transformedContent);
        $this->assertStringContainsString('$errorHandler->disableErrorDisplay', $result->transformedContent);
        $this->assertStringContainsString('$errorHandler->registerNonThrowableErrorHandler', $result->transformedContent);
        $this->assertStringContainsString('$debugger->startExecutionTimeCalculation', $result->transformedContent);
        $this->assertStringContainsString('new Dispatcher(debugger: $debugger)', $result->transformedContent);
    }
}

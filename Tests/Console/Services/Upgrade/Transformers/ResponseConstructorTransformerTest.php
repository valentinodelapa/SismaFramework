<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Transformers;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Transformers\ResponseConstructorTransformer;

class ResponseConstructorTransformerTest extends TestCase
{
    private ResponseConstructorTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ResponseConstructorTransformer();
    }

    public function testCanTransformReturnsTrueWhenSetResponseTypeFound(): void
    {
        $content = '<?php $response->setResponseType(ResponseType::httpNotFound);';

        $this->assertTrue($this->transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWhenNotFound(): void
    {
        $content = '<?php $response = new Response(ResponseType::httpOk);';

        $this->assertFalse($this->transformer->canTransform('/path/to/file.php', $content));
    }

    public function testTransformConvertsSimplePattern(): void
    {
        $content = <<<'PHP'
<?php
$response = new Response();
$response->setResponseType(ResponseType::httpNotFound);
PHP;

        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('$response = new Response(ResponseType::httpNotFound);', $result->transformedContent);
        $this->assertStringNotContainsString('setResponseType', $result->transformedContent);
        $this->assertEquals(1, $result->changesCount);
        $this->assertFalse($result->requiresManualReview);
    }

    public function testTransformConvertsMultiplePatterns(): void
    {
        $content = <<<'PHP'
<?php
$responseA = new Response();
$responseA->setResponseType(ResponseType::httpOk);
$responseB = new Response();
$responseB->setResponseType(ResponseType::httpNotFound);
PHP;

        $result = $this->transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('$responseA = new Response(ResponseType::httpOk);', $result->transformedContent);
        $this->assertStringContainsString('$responseB = new Response(ResponseType::httpNotFound);', $result->transformedContent);
        $this->assertEquals(2, $result->changesCount);
    }

    public function testTransformWarnsOnComplexPattern(): void
    {
        $content = <<<'PHP'
<?php
$this->response->setResponseType(ResponseType::httpNotFound);
PHP;

        $result = $this->transformer->transform($content, '/path/to/file.php');

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
}

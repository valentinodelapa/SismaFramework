<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\DTO;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\DTO\TransformationResult;

class TransformationResultTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $result = new TransformationResult(
            transformedContent: 'transformed code',
            changesCount: 5,
            confidence: 85,
            warnings: ['warning one', 'warning two'],
            requiresManualReview: true
        );

        $this->assertEquals('transformed code', $result->transformedContent);
        $this->assertEquals(5, $result->changesCount);
        $this->assertEquals(85, $result->confidence);
        $this->assertEquals(['warning one', 'warning two'], $result->warnings);
        $this->assertTrue($result->requiresManualReview);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $result = new TransformationResult(
            transformedContent: 'content',
            changesCount: 0,
            confidence: 90
        );

        $this->assertEquals('content', $result->transformedContent);
        $this->assertEquals(0, $result->changesCount);
        $this->assertEquals(90, $result->confidence);
        $this->assertEquals([], $result->warnings);
        $this->assertFalse($result->requiresManualReview);
    }

    public function testPropertiesAreReadonly(): void
    {
        $result = new TransformationResult(
            transformedContent: 'content',
            changesCount: 1,
            confidence: 70
        );

        $reflection = new \ReflectionClass($result);

        $this->assertTrue($reflection->getProperty('transformedContent')->isReadOnly());
        $this->assertTrue($reflection->getProperty('changesCount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('confidence')->isReadOnly());
        $this->assertTrue($reflection->getProperty('warnings')->isReadOnly());
        $this->assertTrue($reflection->getProperty('requiresManualReview')->isReadOnly());
    }
}

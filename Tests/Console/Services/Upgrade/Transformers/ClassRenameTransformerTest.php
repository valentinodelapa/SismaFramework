<?php

namespace SismaFramework\Tests\Console\Services\Upgrade\Transformers;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Upgrade\Transformers\ClassRenameTransformer;

class ClassRenameTransformerTest extends TestCase
{

    public function testCanTransformReturnsTrueWhenClassFound(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
        ]);

        $content = '<?php class CategoryModel extends SelfReferencedModel {}';

        $this->assertTrue($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWhenClassNotFound(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
        ]);

        $content = '<?php class CategoryModel extends BaseModel {}';

        $this->assertFalse($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testCanTransformReturnsFalseWithEmptyRenames(): void
    {
        $transformer = new ClassRenameTransformer([]);

        $content = '<?php class CategoryModel extends SelfReferencedModel {}';

        $this->assertFalse($transformer->canTransform('/path/to/file.php', $content));
    }

    public function testTransformRenamesClassInExtendsDeclaration(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
        ]);

        $content = '<?php class CategoryModel extends SelfReferencedModel {}';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('SelfDependentModel', $result->transformedContent);
        $this->assertStringNotContainsString('SelfReferencedModel', $result->transformedContent);
        $this->assertEquals(1, $result->changesCount);
    }

    public function testTransformRenamesClassInUseStatement(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
        ]);

        $content = 'use SismaFramework\Orm\ExtendedClasses\SelfReferencedModel;';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('SelfDependentModel', $result->transformedContent);
        $this->assertStringNotContainsString('SelfReferencedModel', $result->transformedContent);
        $this->assertEquals(1, $result->changesCount);
    }

    public function testTransformRenamesEnumCase(): void
    {
        $transformer = new ClassRenameTransformer([
            'selfReferencedModel' => 'selfDependentModel',
        ]);

        $content = '<?php $type = ModelType::selfReferencedModel;';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('selfDependentModel', $result->transformedContent);
        $this->assertStringNotContainsString('selfReferencedModel', $result->transformedContent);
        $this->assertEquals(1, $result->changesCount);
    }

    public function testTransformRenamesStringLiteral(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
        ]);

        $content = '<?php $type = "SelfReferencedModel";';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('"SelfDependentModel"', $result->transformedContent);
        $this->assertEquals(1, $result->changesCount);
    }

    public function testTransformRenamesMultipleOccurrences(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
        ]);

        $content = <<<'PHP'
<?php
use SismaFramework\Orm\ExtendedClasses\SelfReferencedModel;

class CategoryModel extends SelfReferencedModel
{
}
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertEquals(2, $result->changesCount);
        $this->assertStringNotContainsString('SelfReferencedModel', $result->transformedContent);
    }

    public function testTransformHandlesMultipleRenames(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
            'selfReferencedModel' => 'selfDependentModel',
        ]);

        $content = <<<'PHP'
<?php
use SismaFramework\Orm\ExtendedClasses\SelfReferencedModel;

class CategoryModel extends SelfReferencedModel
{
    public function getType(): string
    {
        return ModelType::selfReferencedModel->value;
    }
}
PHP;

        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertStringContainsString('SelfDependentModel', $result->transformedContent);
        $this->assertStringContainsString('selfDependentModel', $result->transformedContent);
        $this->assertStringNotContainsString('SelfReferencedModel', $result->transformedContent);
        $this->assertStringNotContainsString('selfReferencedModel', $result->transformedContent);
        $this->assertEquals(3, $result->changesCount);
    }

    public function testTransformUsesWordBoundaries(): void
    {
        $transformer = new ClassRenameTransformer([
            'Model' => 'NewModel',
        ]);

        $content = '<?php class CategoryModel extends BaseModel {}';
        $result = $transformer->transform($content, '/path/to/file.php');

        // "CategoryModel" and "BaseModel" should not be changed because "Model" is not a standalone word there
        $this->assertStringNotContainsString('CategoryNewModel', $result->transformedContent);
        $this->assertStringNotContainsString('BaseNewModel', $result->transformedContent);
        $this->assertEquals(0, $result->changesCount);
    }

    public function testTransformWithNoChanges(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
        ]);

        $content = '<?php class CategoryModel extends BaseModel {}';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertEquals($content, $result->transformedContent);
        $this->assertEquals(0, $result->changesCount);
    }

    public function testGetConfidence(): void
    {
        $transformer = new ClassRenameTransformer([]);

        $this->assertEquals(95, $transformer->getConfidence());
    }

    public function testGetDescription(): void
    {
        $transformer = new ClassRenameTransformer([]);

        $this->assertNotEmpty($transformer->getDescription());
    }

    public function testTransformDoesNotRequireManualReview(): void
    {
        $transformer = new ClassRenameTransformer([
            'SelfReferencedModel' => 'SelfDependentModel',
        ]);

        $content = '<?php class CategoryModel extends SelfReferencedModel {}';
        $result = $transformer->transform($content, '/path/to/file.php');

        $this->assertFalse($result->requiresManualReview);
        $this->assertEmpty($result->warnings);
    }
}

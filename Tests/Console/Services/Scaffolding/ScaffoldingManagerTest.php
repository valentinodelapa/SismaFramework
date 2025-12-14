<?php

namespace SismaFramework\Tests\Console\Services\Scaffolding;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Scaffolding\ScaffoldingManager;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Templater;

class ScaffoldingManagerTest extends TestCase
{

    private ScaffoldingManager $scaffoldingManager;
    private string $tempDir;
    private string $templatesPath;

    #[\Override]
    protected function setUp(): void
    {
        $this->templatesPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'Scaffolding' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR;
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scaffolding_test_' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir($this->tempDir, 0777, true);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['application', 'Application'],
                    ['controllers', 'Controllers'],
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['entities', 'Entities'],
                    ['forms', 'Forms'],
                    ['models', 'Models'],
                    ['structuralTemplatesPath', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'Structural' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR],
                    ['rootPath', $this->tempDir],
        ]);
        Config::setInstance($configStub);
        $this->scaffoldingManager = new ScaffoldingManager($configStub);
        mkdir($this->tempDir . 'TestModule/Application/Entities', 0777, true);
        mkdir($this->tempDir . 'TestModule/Application/Models', 0777, true);
        mkdir($this->tempDir . 'TestModule/Application/Controllers', 0777, true);
        mkdir($this->tempDir . 'TestModule/Application/Forms', 0777, true);
    }

    public function testGenerateScaffoldingWithBaseEntity(): void
    {
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/SimpleEntity.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class SimpleEntity extends BaseEntity
{
        protected int $id;
	protected string $name;
	protected ?int $age;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
}
PHP);
        require_once $entityFile;
        $result = $this->scaffoldingManager->generateScaffolding('SimpleEntity', 'TestModule');
        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Models/SimpleEntityModel.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Forms/SimpleEntityForm.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Controllers/SimpleEntityController.php');
        $modelContent = file_get_contents($this->tempDir . 'TestModule/Application/Models/SimpleEntityModel.php');
        $formContent = file_get_contents($this->tempDir . 'TestModule/Application/Forms/SimpleEntityForm.php');
        $controllerContent = file_get_contents($this->tempDir . 'TestModule/Application/Controllers/SimpleEntityController.php');
        $vars = [
            'controllerNamespace' => 'TestModule\Application\Controllers',
            'controllerRoute' => 'simple-entity',
            'entityShortName' => 'SimpleEntity',
            'entityShortNameLower' => 'simpleEntity',
            'entityNamespace' => 'TestModule\Application\Entities',
            'filters' => '        $this->addFilterFieldMode("name", FilterType::isString)
            ->addFilterFieldMode("age", FilterType::isInteger, [], true);',
            'formNamespace' => 'TestModule\Application\Forms',
            'modelNamespace' => 'TestModule\Application\Models',
            'modelType' => 'BaseModel',
            'modelTypeNamespace' => 'SismaFramework\Orm\BaseClasses',
        ];
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Model.tpl', $vars), $modelContent);
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Form.tpl', $vars), $formContent);
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Controller.tpl', $vars), $controllerContent);
    }

    public function testGenerateScaffoldingWithSelfReferencedEntity(): void
    {
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/CategoryEntity.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;

class CategoryEntity extends SelfReferencedEntity
{
        protected int $id;
	protected string $name;
	protected ?CategoryEntity $parentCategoryEntity;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
}
PHP);
        require_once $entityFile;
        $result = $this->scaffoldingManager->generateScaffolding('CategoryEntity', 'TestModule');
        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Models/CategoryEntityModel.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Forms/CategoryEntityForm.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Controllers/CategoryEntityController.php');
        $modelContent = file_get_contents($this->tempDir . 'TestModule/Application/Models/CategoryEntityModel.php');
        $formContent = file_get_contents($this->tempDir . 'TestModule/Application/Forms/CategoryEntityForm.php');
        $controllerContent = file_get_contents($this->tempDir . 'TestModule/Application/Controllers/CategoryEntityController.php');
        $vars = [
            'controllerNamespace' => 'TestModule\Application\Controllers',
            'controllerRoute' => 'category-entity',
            'entityShortName' => 'CategoryEntity',
            'entityShortNameLower' => 'categoryEntity',
            'entityNamespace' => 'TestModule\Application\Entities',
            'filters' => '        $this->addFilterFieldMode("name", FilterType::isString)
            ->addFilterFieldMode("parentCategoryEntity", FilterType::isEntity, [], true);',
            'formNamespace' => 'TestModule\Application\Forms',
            'modelNamespace' => 'TestModule\Application\Models',
            'modelType' => 'SelfReferencedModel',
            'modelTypeNamespace' => 'SismaFramework\Orm\ExtendedClasses',
        ];
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Model.tpl', $vars), $modelContent);
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Form.tpl', $vars), $formContent);
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Controller.tpl', $vars), $controllerContent);
    }

    public function testGenerateScaffoldingWithDependentEntity(): void
    {
        $baseEntityFile = $this->tempDir . '/TestModule/Application/Entities/UserEntity.php';
        file_put_contents($baseEntityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;

class UserEntity extends ReferencedEntity
{
        protected int $id;
	protected string $name;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
}
PHP);
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/PostEntity.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class PostEntity extends BaseEntity
{
        protected int $id;
	protected string $title;
	protected UserEntity $author;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
}
PHP);
        require_once $baseEntityFile;
        require_once $entityFile;
        $result = $this->scaffoldingManager->generateScaffolding('PostEntity', 'TestModule');
        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Models/PostEntityModel.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Forms/PostEntityForm.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Controllers/PostEntityController.php');
        $modelContent = file_get_contents($this->tempDir . 'TestModule/Application/Models/PostEntityModel.php');
        $formContent = file_get_contents($this->tempDir . 'TestModule/Application/Forms/PostEntityForm.php');
        $controllerContent = file_get_contents($this->tempDir . 'TestModule/Application/Controllers/PostEntityController.php');
        $vars = [
            'controllerNamespace' => 'TestModule\Application\Controllers',
            'controllerRoute' => 'post-entity',
            'entityShortName' => 'PostEntity',
            'entityShortNameLower' => 'postEntity',
            'entityNamespace' => 'TestModule\Application\Entities',
            'filters' => '        $this->addFilterFieldMode("title", FilterType::isString)
            ->addFilterFieldMode("author", FilterType::isEntity);',
            'formNamespace' => 'TestModule\Application\Forms',
            'modelNamespace' => 'TestModule\Application\Models',
            'modelType' => 'DependentModel',
            'modelTypeNamespace' => 'SismaFramework\Orm\ExtendedClasses',
        ];
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Model.tpl', $vars), $modelContent);
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Form.tpl', $vars), $formContent);
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Controller.tpl', $vars), $controllerContent);
    }

    public function testGenerateScaffoldingWithBaseEntityWithCustomModelType(): void
    {
        $this->scaffoldingManager->setCustomType('SelfReferencedModel');
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/CustomEntity.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class CustomEntity extends BaseEntity
{
        protected int $id;
	protected string $name;
	protected ?int $age;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
}
PHP);
        require_once $entityFile;
        $result = $this->scaffoldingManager->generateScaffolding('CustomEntity', 'TestModule');
        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Models/CustomEntityModel.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Forms/CustomEntityForm.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Controllers/CustomEntityController.php');
        $modelContent = file_get_contents($this->tempDir . 'TestModule/Application/Models/CustomEntityModel.php');
        $formContent = file_get_contents($this->tempDir . 'TestModule/Application/Forms/CustomEntityForm.php');
        $controllerContent = file_get_contents($this->tempDir . 'TestModule/Application/Controllers/CustomEntityController.php');
        $vars = [
            'controllerNamespace' => 'TestModule\Application\Controllers',
            'controllerRoute' => 'custom-entity',
            'entityShortName' => 'CustomEntity',
            'entityShortNameLower' => 'customEntity',
            'entityNamespace' => 'TestModule\Application\Entities',
            'filters' => '        $this->addFilterFieldMode("name", FilterType::isString)
            ->addFilterFieldMode("age", FilterType::isInteger, [], true);',
            'formNamespace' => 'TestModule\Application\Forms',
            'modelNamespace' => 'TestModule\Application\Models',
            'modelType' => 'SelfReferencedModel',
            'modelTypeNamespace' => 'SismaFramework\Orm\ExtendedClasses',
        ];
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Model.tpl', $vars), $modelContent);
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Form.tpl', $vars), $formContent);
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Controller.tpl', $vars), $controllerContent);
    }

    public function testDoubleExecution()
    {
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/OtherSimpleEntity.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class OtherSimpleEntity extends BaseEntity
{
        protected int $id;
	protected string $name;
	protected ?int $age;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
	public static function getTableName(): string { return 'simple_entities'; }
	public static function getPrimaryKeyName(): string { return 'id'; }
}
PHP);
        require_once $entityFile;
        $result = $this->scaffoldingManager->generateScaffolding('OtherSimpleEntity', 'TestModule');
        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Models/OtherSimpleEntityModel.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Forms/OtherSimpleEntityForm.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Controllers/OtherSimpleEntityController.php');
        $modelPath = $this->tempDir . 'TestModule/Application/Models/OtherSimpleEntityModel.php';
        $formPath = $this->tempDir . 'TestModule/Application/Forms/OtherSimpleEntityForm.php';
        $controllerPath = $this->tempDir . 'TestModule/Application/Controllers/OtherSimpleEntityController.php';
        $vars = [
            'controllerNamespace' => 'TestModule\Application\Controllers',
            'controllerRoute' => 'other-simple-entity',
            'entityShortName' => 'OtherSimpleEntity',
            'entityShortNameLower' => 'otherSimpleEntity',
            'entityNamespace' => 'TestModule\Application\Entities',
            'filters' => '        $this->addFilterFieldMode("name", FilterType::isString)
            ->addFilterFieldMode("age", FilterType::isInteger, [], true);',
            'formNamespace' => 'TestModule\Application\Forms',
            'modelNamespace' => 'TestModule\Application\Models',
            'modelType' => 'BaseModel',
            'modelTypeNamespace' => 'SismaFramework\Orm\BaseClasses',
        ];
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Model.tpl', $vars), file_get_contents($modelPath));
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Form.tpl', $vars), file_get_contents($formPath));
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Controller.tpl', $vars), file_get_contents($controllerPath));
        file_put_contents($modelPath, '');
        file_put_contents($formPath, '');
        file_put_contents($controllerPath, '');
        $this->assertEquals('', file_get_contents($modelPath));
        $this->assertEquals('', file_get_contents($formPath));
        $this->assertEquals('', file_get_contents($controllerPath));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("File already exists: " . $this->tempDir . "TestModule\Application\Models\OtherSimpleEntityModel.php. Use --force to overwrite.");
        $this->scaffoldingManager->setForce(true);
        $this->scaffoldingManager->generateScaffolding('OtherSimpleEntity', 'TestModule');
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Model.tpl', $vars), file_get_contents($modelPath));
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Form.tpl', $vars), file_get_contents($formPath));
        $this->assertEquals(Templater::parseTemplate($this->templatesPath . 'Controller.tpl', $vars), file_get_contents($controllerPath));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("File already exists: " . $this->tempDir . "TestModule\Application\Models\OtherSimpleEntityModel.php. Use --force to overwrite.");
        $this->scaffoldingManager->setForce(false);
        $this->scaffoldingManager->generateScaffolding('OtherSimpleEntity', 'TestModule');
    }

    public function testFakeModule()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(<<<ERROR
Expected project structure:
  YourProject/
  ├── SismaFramework/  (git submodule)
  ├── FakeModule/       (your module)\
  │   └── Application/
  │       ├── Controllers/
  │       ├── Models/
  │       ├── Forms/
  │       └── Entities/
  └── OtherModules/
ERROR);
        $this->scaffoldingManager->generateScaffolding('SimpleEntity', 'FakeModule');
    }

    public function testFakeApplication()
    {
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['application', 'FakeApplication'],
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['rootPath', $this->tempDir],
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(<<<ERROR
Application directory not found in module: TestModule
Each module in the project root must have this structure:
  TestModule/
  └── Application/
      ├── Controllers/
      ├── Models/
      ├── Forms/
      └── Entities/
  
Create this structure or use --force to create it automatically.
ERROR);
        $scaffoldingManager = new ScaffoldingManager($configStub);
        $scaffoldingManager->generateScaffolding('SimpleEntity', 'TestModule');
    }

    public function testFakeEntity()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(<<<ERROR
Entity class not found: TestModule\Application\Entities\FakeEntity
Make sure the entity exists in:
  TestModule/Application/Entities/FakeEntity.php

The entity class should be defined as:
  namespace TestModule\Application\Entities;
  class FakeEntity extends BaseEntity { ... }
ERROR);
        $this->scaffoldingManager->generateScaffolding('FakeEntity', 'TestModule');
    }

    public function testExistingCustomTemplate()
    {
        $customTemplatesPath = $this->tempDir . 'CustomTemplates';
        mkdir($customTemplatesPath);
        file_put_contents($customTemplatesPath . DIRECTORY_SEPARATOR . 'Controller.tpl', '');
        file_put_contents($customTemplatesPath . DIRECTORY_SEPARATOR . 'Form.tpl', '');
        file_put_contents($customTemplatesPath . DIRECTORY_SEPARATOR . 'Model.tpl', '');
        mkdir($customTemplatesPath . DIRECTORY_SEPARATOR . 'Views');
        file_put_contents($customTemplatesPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'create.tpl', '');
        file_put_contents($customTemplatesPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'index.tpl', '');
        file_put_contents($customTemplatesPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'update.tpl', '');
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/SimpleEntityTwo.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class SimpleEntityTwo extends BaseEntity
{
        protected int $id;
	protected string $name;
	protected ?int $age;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
}
PHP);
        require_once $entityFile;
        $this->scaffoldingManager->setCustomTemplatePath($customTemplatesPath);
        $result = $this->scaffoldingManager->generateScaffolding('SimpleEntityTwo', 'TestModule');
        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Models/SimpleEntityTwoModel.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Forms/SimpleEntityTwoForm.php');
        $this->assertFileExists($this->tempDir . 'TestModule/Application/Controllers/SimpleEntityTwoController.php');
        $modelContent = file_get_contents($this->tempDir . 'TestModule/Application/Models/SimpleEntityTwoModel.php');
        $formContent = file_get_contents($this->tempDir . 'TestModule/Application/Forms/SimpleEntityTwoForm.php');
        $controllerContent = file_get_contents($this->tempDir . 'TestModule/Application/Controllers/SimpleEntityTwoController.php');
        $this->assertEquals('', $modelContent);
        $this->assertEquals('', $formContent);
        $this->assertEquals('', $controllerContent);
    }

    public function testFakeCustomTemplate()
    {
        $FakeTemplatesPath = $this->tempDir . 'FakeTemplates';
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/SimpleEntityThree.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class SimpleEntityThree extends BaseEntity
{
        protected int $id;
	protected string $name;
	protected ?int $age;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
}
PHP);
        require_once $entityFile;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Template file not found: " . $FakeTemplatesPath . DIRECTORY_SEPARATOR . "Model.tpl");
        $this->scaffoldingManager->setCustomTemplatePath($FakeTemplatesPath);
        $this->scaffoldingManager->generateScaffolding('SimpleEntityThree', 'TestModule');
    }
}

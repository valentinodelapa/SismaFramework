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
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['rootPath', $this->tempDir],
        ]);
        $this->scaffoldingManager = new ScaffoldingManager($configMock);

        // Creiamo la struttura delle directory necessaria
        //mkdir($this->tempDir . '/SismaFramework', 0777, true);
        mkdir($this->tempDir . '/TestModule/Application/Entities', 0777, true);
        mkdir($this->tempDir . '/TestModule/Application/Models', 0777, true);
        mkdir($this->tempDir . '/TestModule/Application/Controllers', 0777, true);
        mkdir($this->tempDir . '/TestModule/Application/Forms', 0777, true);
    }

    public function testGenerateScaffoldingWithBaseEntity(): void
    {
        // Creiamo una classe mock che estende BaseEntity
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
	public static function getTableName(): string { return 'simple_entities'; }
	public static function getPrimaryKeyName(): string { return 'id'; }
}
PHP
        );

        require_once $entityFile;

        $result = $this->scaffoldingManager->generateScaffolding('SimpleEntity', 'TestModule');

        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Models/SimpleEntityModel.php');
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Forms/SimpleEntityForm.php');
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Controllers/SimpleEntityController.php');
        $modelContent = file_get_contents($this->tempDir . '/TestModule/Application/Models/SimpleEntityModel.php');
        $formContent = file_get_contents($this->tempDir . '/TestModule/Application/Forms/SimpleEntityForm.php');
        $controllerContent = file_get_contents($this->tempDir . '/TestModule/Application/Controllers/SimpleEntityController.php');
        $vars = [
            'controllerNamespace' => 'TestModule\Application\Controllers',
            'controllerRoute' => 'simple-entity',
            'entityName' => 'SimpleEntity',
            'entityNameLower' => 'simpleEntity',
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
PHP
        );
        require_once $entityFile;
        $result = $this->scaffoldingManager->generateScaffolding('CategoryEntity', 'TestModule');
        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Models/CategoryEntityModel.php');
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Forms/CategoryEntityForm.php');
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Controllers/CategoryEntityController.php');
        $modelContent = file_get_contents($this->tempDir . '/TestModule/Application/Models/CategoryEntityModel.php');
        $formContent = file_get_contents($this->tempDir . '/TestModule/Application/Forms/CategoryEntityForm.php');
        $controllerContent = file_get_contents($this->tempDir . '/TestModule/Application/Controllers/CategoryEntityController.php');
        $vars = [
            'controllerNamespace' => 'TestModule\Application\Controllers',
            'controllerRoute' => 'category-entity',
            'entityName' => 'CategoryEntity',
            'entityNameLower' => 'categoryEntity',
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
	public static function getTableName(): string { return 'users'; }
	public static function getPrimaryKeyName(): string { return 'id'; }
}
PHP
        );
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
	public static function getTableName(): string { return 'posts'; }
	public static function getPrimaryKeyName(): string { return 'id'; }
}
PHP
        );
        require_once $baseEntityFile;
        require_once $entityFile;
        $result = $this->scaffoldingManager->generateScaffolding('PostEntity', 'TestModule');
        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Models/PostEntityModel.php');
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Forms/PostEntityForm.php');
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Controllers/PostEntityController.php');
        $modelContent = file_get_contents($this->tempDir . '/TestModule/Application/Models/PostEntityModel.php');
        $formContent = file_get_contents($this->tempDir . '/TestModule/Application/Forms/PostEntityForm.php');
        $controllerContent = file_get_contents($this->tempDir . '/TestModule/Application/Controllers/PostEntityController.php');
        $vars = [
            'controllerNamespace' => 'TestModule\Application\Controllers',
            'controllerRoute' => 'post-entity',
            'entityName' => 'PostEntity',
            'entityNameLower' => 'postEntity',
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
}

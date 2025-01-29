<?php

namespace SismaFramework\Tests\Console\Services\Scaffolding;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Scaffolding\ScaffoldingManager;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;

class ScaffoldingManagerTest extends TestCase
{

    private ScaffoldingManager $scaffoldingManager;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->scaffoldingManager = new ScaffoldingManager();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scaffolding_test_' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir($this->tempDir, 0777, true);
        $this->scaffoldingManager->setRootPath($this->tempDir);

        // Creiamo la struttura delle directory necessaria
        //mkdir($this->tempDir . '/SismaFramework', 0777, true);
        mkdir($this->tempDir . '/TestModule/Application/Entities', 0777, true);
        mkdir($this->tempDir . '/TestModule/Application/Models', 0777, true);
        mkdir($this->tempDir . '/TestModule/Application/Controllers', 0777, true);
        mkdir($this->tempDir . '/TestModule/Application/Forms', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
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
        $modelContent = file_get_contents($this->tempDir . '/TestModule/Application/Models/SimpleEntityModel.php');
        $this->assertStringContainsString('extends BaseModel', $modelContent);
    }

    public function testGenerateScaffoldingWithSelfReferencedEntity(): void
    {
        // Creiamo una classe mock che estende SelfReferencedEntity
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/CategoryEntity.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;

class CategoryEntity extends SelfReferencedEntity
{
	protected string $name;
	protected ?CategoryEntity $parent;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
	public static function getTableName(): string { return 'categories'; }
	public static function getPrimaryKeyName(): string { return 'id'; }
}
PHP
        );

        require_once $entityFile;

        $result = $this->scaffoldingManager->generateScaffolding('CategoryEntity', 'TestModule');

        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . '/TestModule/Application/Models/CategoryEntityModel.php');
        $modelContent = file_get_contents($this->tempDir . '/TestModule/Application/Models/CategoryEntityModel.php');
        $this->assertStringContainsString('extends SelfReferencedModel', $modelContent);
    }

    public function testGenerateScaffoldingWithDependentEntity(): void
    {
        // Prima creiamo l'entità di base da cui dipenderà la nostra entità
        $baseEntityFile = $this->tempDir . '/TestModule/Application/Entities/UserEntity.php';
        file_put_contents($baseEntityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class UserEntity extends BaseEntity
{
	protected string $name;
	
	protected function setEncryptedProperties(): void {}
	protected function setPropertyDefaultValue(): void {}
	public static function getTableName(): string { return 'users'; }
	public static function getPrimaryKeyName(): string { return 'id'; }
}
PHP
        );

        // Poi creiamo l'entità dipendente
        $entityFile = $this->tempDir . '/TestModule/Application/Entities/PostEntity.php';
        file_put_contents($entityFile, <<<'PHP'
<?php
namespace TestModule\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class PostEntity extends BaseEntity
{
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
        $modelContent = file_get_contents($this->tempDir . '/TestModule/Application/Models/PostEntityModel.php');
        $this->assertStringContainsString('extends DependentModel', $modelContent);
    }
}

<?php

namespace SismaFramework\Tests\Console\Services\Installation;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Installation\InstallationManager;

class InstallationManagerTest extends TestCase
{
    private InstallationManager $manager;
    private string $testProjectRoot;
    private string $testFrameworkPath;

    protected function setUp(): void
    {
        $this->testProjectRoot = sys_get_temp_dir() . '/sisma_test_' . uniqid();
        $this->testFrameworkPath = $this->testProjectRoot . '/SismaFramework';

        mkdir($this->testProjectRoot, 0755, true);
        mkdir($this->testFrameworkPath, 0755, true);

        // Crea struttura minima del framework per i test
        $this->createFrameworkStructure();

        $this->manager = new InstallationManager($this->testProjectRoot);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testProjectRoot)) {
            $this->removeDirectory($this->testProjectRoot);
        }
    }

    public function testConstructorSetsProjectRoot(): void
    {
        $reflection = new \ReflectionClass($this->manager);
        $property = $reflection->getProperty('projectRoot');
        $property->setAccessible(true);

        $this->assertEquals($this->testProjectRoot, $property->getValue($this->manager));
    }

    public function testSetForce(): void
    {
        $result = $this->manager->setForce(true);

        $this->assertInstanceOf(InstallationManager::class, $result);

        $reflection = new \ReflectionClass($this->manager);
        $property = $reflection->getProperty('force');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($this->manager));
    }

    public function testInitializeModule(): void
    {
        $moduleName = 'TestModule';

        $result = $this->manager->initializeModule($moduleName);

        $this->assertTrue($result);
        $this->assertDirectoryExists($this->testProjectRoot . '/TestModule/Application');
        $this->assertDirectoryExists($this->testProjectRoot . '/TestModule/Application/Controllers');
        $this->assertDirectoryExists($this->testProjectRoot . '/TestModule/Application/Models');
        $this->assertDirectoryExists($this->testProjectRoot . '/TestModule/Application/Entities');
        $this->assertDirectoryExists($this->testProjectRoot . '/TestModule/Application/Views');
    }

    public function testInitializeModuleThrowsExceptionWhenModuleExists(): void
    {
        $moduleName = 'ExistingModule';
        mkdir($this->testProjectRoot . '/' . $moduleName, 0755, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Module {$moduleName} already exists");

        $this->manager->initializeModule($moduleName);
    }

    public function testInitializeModuleWithForceOverwritesExisting(): void
    {
        $moduleName = 'ExistingModule';
        mkdir($this->testProjectRoot . '/' . $moduleName, 0755, true);

        $this->manager->setForce(true);
        $result = $this->manager->initializeModule($moduleName);

        $this->assertTrue($result);
    }

    public function testInstallCreatesProjectStructure(): void
    {
        $projectName = 'MyTestProject';

        $result = $this->manager->install($projectName);

        $this->assertTrue($result);
        $this->assertDirectoryExists($this->testProjectRoot . '/Config');
        $this->assertDirectoryExists($this->testProjectRoot . '/Public');
        $this->assertDirectoryExists($this->testProjectRoot . '/Cache');
        $this->assertDirectoryExists($this->testProjectRoot . '/Logs');
        $this->assertDirectoryExists($this->testProjectRoot . '/filesystemMedia');
    }

    public function testInstallCopiesConfigFile(): void
    {
        $projectName = 'MyTestProject';

        $this->manager->install($projectName);

        $configFile = $this->testProjectRoot . '/Config/configFramework.php';
        $this->assertFileExists($configFile);

        $content = file_get_contents($configFile);
        $this->assertStringContainsString("const PROJECT = '{$projectName}'", $content);
    }

    public function testInstallCopiesPublicFolder(): void
    {
        $projectName = 'MyTestProject';

        $this->manager->install($projectName);

        $indexFile = $this->testProjectRoot . '/Public/index.php';
        $this->assertFileExists($indexFile);

        $content = file_get_contents($indexFile);
        $this->assertStringContainsString("'SismaFramework' . DIRECTORY_SEPARATOR . 'Autoload'", $content);
        $this->assertStringContainsString("'SismaFramework' . DIRECTORY_SEPARATOR . 'Config'", $content);
    }

    public function testInstallWithDatabaseConfig(): void
    {
        $projectName = 'MyTestProject';
        $config = [
            'DATABASE_HOST' => 'localhost',
            'DATABASE_NAME' => 'mydb',
            'DATABASE_USERNAME' => 'root',
            'DATABASE_PASSWORD' => 'secret',
            'DATABASE_PORT' => '3306'
        ];

        // Prima aggiungiamo queste costanti al file di configurazione del framework
        $configFile = $this->testFrameworkPath . '/Config/config.php';
        file_put_contents($configFile, <<<PHP
<?php
const PROJECT = 'TestProject';
const DATABASE_HOST = '127.0.0.1';
const DATABASE_NAME = 'test';
const DATABASE_USERNAME = 'user';
const DATABASE_PASSWORD = 'pass';
const DATABASE_PORT = '3306';
PHP
        );

        $this->manager->install($projectName, $config);

        $installedConfig = file_get_contents($this->testProjectRoot . '/Config/configFramework.php');
        $this->assertStringContainsString("const DATABASE_HOST = 'localhost'", $installedConfig);
        $this->assertStringContainsString("const DATABASE_NAME = 'mydb'", $installedConfig);
        $this->assertStringContainsString("const DATABASE_USERNAME = 'root'", $installedConfig);
        $this->assertStringContainsString("const DATABASE_PASSWORD = 'secret'", $installedConfig);
    }

    public function testInstallThrowsExceptionWhenConfigExists(): void
    {
        $projectName = 'MyTestProject';

        // Crea prima il config
        mkdir($this->testProjectRoot . '/Config', 0755, true);
        file_put_contents($this->testProjectRoot . '/Config/configFramework.php', '<?php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config file already exists');

        $this->manager->install($projectName);
    }

    public function testInstallWithForceOverwritesExistingConfig(): void
    {
        $projectName = 'MyTestProject';

        // Crea prima il config
        mkdir($this->testProjectRoot . '/Config', 0755, true);
        file_put_contents($this->testProjectRoot . '/Config/configFramework.php', '<?php // old');

        $this->manager->setForce(true);
        $result = $this->manager->install($projectName);

        $this->assertTrue($result);
        $content = file_get_contents($this->testProjectRoot . '/Config/configFramework.php');
        $this->assertStringNotContainsString('// old', $content);
        $this->assertStringContainsString("const PROJECT = '{$projectName}'", $content);
    }

    public function testInstallCreatesDirectoriesWithCorrectPermissions(): void
    {
        $projectName = 'MyTestProject';

        $this->manager->install($projectName);

        $dirs = ['Cache', 'Logs', 'filesystemMedia'];
        foreach ($dirs as $dir) {
            $path = $this->testProjectRoot . '/' . $dir;
            $this->assertDirectoryExists($path);

            // Verifica che sia scrivibile
            $this->assertTrue(is_writable($path));
        }
    }

    private function createFrameworkStructure(): void
    {
        // Crea Config/config.php
        $configDir = $this->testFrameworkPath . '/Config';
        mkdir($configDir, 0755, true);
        file_put_contents(
            $configDir . '/config.php',
            "<?php\nconst PROJECT = 'TestProject';\n"
        );

        // Crea Public/index.php con il formato reale del framework
        $publicDir = $this->testFrameworkPath . '/Public';
        mkdir($publicDir, 0755, true);
        file_put_contents(
            $publicDir . '/index.php',
            <<<'PHP'
<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Autoload' . DIRECTORY_SEPARATOR . 'autoload.php';
PHP
        );
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
}

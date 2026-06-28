<?php

namespace SismaFramework\Tests\Console\Commands;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Commands\InstallationCommand;
use SismaFramework\Console\Services\Installation\InstallationManager;
use SismaFramework\Console\Traits\InteractiveInputTrait;

#[AllowMockObjectsWithoutExpectations]
class InstallationCommandTest extends TestCase
{
    private InstallationCommand $command;
    private InstallationManager $installationManagerMock;

    protected function setUp(): void
    {
        $this->installationManagerMock = $this->createMock(InstallationManager::class);
        $this->command = new InstallationCommand($this->installationManagerMock);
    }

    public function testCheckCompatibility(): void
    {
        $this->assertTrue($this->command->checkCompatibility('install'));
        $this->assertFalse($this->command->checkCompatibility('other'));
        $this->assertFalse($this->command->checkCompatibility('scaffold'));
    }

    public function testConfigureShowsHelpMessage(): void
    {
        ob_start();
        $this->command->run();
        $output = ob_get_clean();

        $expectedStrings = [
            'Usage: php SismaFramework/Console/sisma install <projectName> [options]',
            'Arguments:',
            'projectName    The name of the project to install',
            'Options:',
            '--force',
            '--skip-db',
            '--db-host=HOST',
            '--db-name=NAME',
            '--db-user=USER',
            '--db-pass=PASS',
            '--db-port=PORT',
            '--odm-host=HOST',
            '--odm-name=NAME',
            '--odm-user=USER',
            '--odm-pass=PASS',
            '--odm-port=PORT',
            'Example:',
        ];

        foreach ($expectedStrings as $string) {
            $this->assertStringContainsString($string, $output);
        }
    }

    public function testConfigureShowsSkipDbOption(): void
    {
        ob_start();
        $this->command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('--skip-db', $output);
        $this->assertStringContainsString('Skip database configuration', $output);
    }

    public function testExecuteWithMissingProjectName(): void
    {
        $this->command->setArguments([]);
        $this->command->setOptions([]);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Usage: php SismaFramework/Console/sisma install', $output);
        $this->assertStringContainsString('Error: Project name is required', $output);
    }

    public function testSuccessfulInstallationWithSkipDb(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions(['skip-db' => true]);

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', [])
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('Installation completed successfully', $output);
    }

    public function testSuccessfulInstallationWithForce(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions(['force' => true, 'skip-db' => true]);

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(true)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', [])
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('Installation completed successfully', $output);
    }

    public function testInstallationWithDatabaseOptions(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions([
            'db-host' => 'localhost',
            'db-name' => 'mydb',
            'db-user' => 'root',
            'db-pass' => 'secret',
            'db-port' => '3306',
            'odm-host' => 'localhost',
            'odm-name' => 'mongodb',
            'odm-user' => 'mongoroot',
            'odm-pass' => 'mongosecret',
            'odm-port' => '27017'
        ]);

        $expectedConfig = [
            'ORM_DATABASE_HOST' => 'localhost',
            'ORM_DATABASE_NAME' => 'mydb',
            'ORM_DATABASE_USERNAME' => 'root',
            'ORM_DATABASE_PASSWORD' => 'secret',
            'ORM_DATABASE_PORT' => '3306',
            'ODM_DATABASE_HOST' => 'localhost',
            'ODM_DATABASE_NAME' => 'mongodb',
            'ODM_DATABASE_USERNAME' => 'mongoroot',
            'ODM_DATABASE_PASSWORD' => 'mongosecret',
            'ODM_DATABASE_PORT' => '27017'
        ];

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', $expectedConfig)
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        ob_get_clean();

        $this->assertTrue($result);
    }

    public function testInstallationWithPartialDatabaseOptions(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions([
            'db-host' => 'localhost',
            'db-name' => 'mydb',
            'odm-host' => 'localhost',
            'odm-name' => 'mongodb'
        ]);

        $expectedConfig = [
            'ORM_DATABASE_HOST' => 'localhost',
            'ORM_DATABASE_NAME' => 'mydb',
            'ODM_DATABASE_HOST' => 'localhost',
            'ODM_DATABASE_NAME' => 'mongodb'
        ];

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', $expectedConfig)
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        ob_get_clean();

        $this->assertTrue($result);
    }

    public function testInstallationWithOnlyOdmOptions(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions([
            'db-host' => 'localhost',
            'db-name' => 'mydb',
            'odm-host' => 'localhost',
            'odm-name' => 'mongodb',
            'odm-user' => 'mongoroot',
            'odm-pass' => 'mongosecret',
            'odm-port' => '27017'
        ]);

        $expectedConfig = [
            'ORM_DATABASE_HOST' => 'localhost',
            'ORM_DATABASE_NAME' => 'mydb',
            'ODM_DATABASE_HOST' => 'localhost',
            'ODM_DATABASE_NAME' => 'mongodb',
            'ODM_DATABASE_USERNAME' => 'mongoroot',
            'ODM_DATABASE_PASSWORD' => 'mongosecret',
            'ODM_DATABASE_PORT' => '27017'
        ];

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', $expectedConfig)
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        ob_get_clean();

        $this->assertTrue($result);
    }

    public function testInstallationSkipsOrmPromptWhenEnvironmentVariablesDetected(): void
    {
        putenv('ORM_DATABASE_HOST=localhost');
        putenv('ODM_DATABASE_HOST=localhost');

        try {
            $this->command->setArguments([
                '0' => 'MyProject'
            ]);
            $this->command->setOptions([]);

            $this->installationManagerMock
                ->expects($this->once())
                ->method('setForce')
                ->with(false)
                ->willReturnSelf();

            $this->installationManagerMock
                ->expects($this->once())
                ->method('install')
                ->with('MyProject', [])
                ->willReturn(true);

            ob_start();
            $result = $this->command->run();
            $output = ob_get_clean();

            $this->assertTrue($result);
            $this->assertStringContainsString('Relational database environment variables detected', $output);
            $this->assertStringContainsString('Non-relational database environment variables detected', $output);
        } finally {
            putenv('ORM_DATABASE_HOST');
            putenv('ODM_DATABASE_HOST');
        }
    }

    private function setSimulatedInput(string $content): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        $this->command->setInputStream($stream);
    }

    public function testInteractivePromptDeclinesBothOrmAndOdmByDefault(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions([]);
        $this->setSimulatedInput("\n\n");

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', [])
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('Is there a relational database to configure?', $output);
        $this->assertStringContainsString('Is there a non-relational database to configure?', $output);
    }

    public function testInteractivePromptConfiguresOnlyOrmWhenConfirmed(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions([]);
        $this->setSimulatedInput("y\nlocalhost\n3306\nmydb\nroot\nsecret\nn\n");

        $expectedConfig = [
            'ORM_DATABASE_HOST' => 'localhost',
            'ORM_DATABASE_PORT' => '3306',
            'ORM_DATABASE_NAME' => 'mydb',
            'ORM_DATABASE_USERNAME' => 'root',
            'ORM_DATABASE_PASSWORD' => 'secret',
        ];

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', $expectedConfig)
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        ob_get_clean();

        $this->assertTrue($result);
    }

    public function testInteractivePromptConfiguresOnlyOdmWhenConfirmed(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions([]);
        $this->setSimulatedInput("n\ny\nlocalhost\n27017\nmydocs\nroot\nsecret\n");

        $expectedConfig = [
            'ODM_DATABASE_HOST' => 'localhost',
            'ODM_DATABASE_PORT' => '27017',
            'ODM_DATABASE_NAME' => 'mydocs',
            'ODM_DATABASE_USERNAME' => 'root',
            'ODM_DATABASE_PASSWORD' => 'secret',
        ];

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', $expectedConfig)
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        ob_get_clean();

        $this->assertTrue($result);
    }

    public function testInteractivePromptConfiguresBothOrmAndOdmWhenConfirmed(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions([]);
        $this->setSimulatedInput("y\nlocalhost\n3306\nmydb\nroot\nsecret\ny\nlocalhost\n27017\nmydocs\nroot\nmongosecret\n");

        $expectedConfig = [
            'ORM_DATABASE_HOST' => 'localhost',
            'ORM_DATABASE_PORT' => '3306',
            'ORM_DATABASE_NAME' => 'mydb',
            'ORM_DATABASE_USERNAME' => 'root',
            'ORM_DATABASE_PASSWORD' => 'secret',
            'ODM_DATABASE_HOST' => 'localhost',
            'ODM_DATABASE_PORT' => '27017',
            'ODM_DATABASE_NAME' => 'mydocs',
            'ODM_DATABASE_USERNAME' => 'root',
            'ODM_DATABASE_PASSWORD' => 'mongosecret',
        ];

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->with('MyProject', $expectedConfig)
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        ob_get_clean();

        $this->assertTrue($result);
    }

    public function testInstallationFailure(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions(['skip-db' => true]);

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->willThrowException(new \RuntimeException('Installation failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Installation failed');

        ob_start();
        try {
            $this->command->run();
        } finally {
            ob_end_clean();
        }
    }

    public function testCheckCompatibilityReturnsTrueForInstallCommand(): void
    {
        $this->assertTrue($this->command->checkCompatibility('install'));
    }

    public function testCheckCompatibilityReturnsFalseForOtherCommands(): void
    {
        $this->assertFalse($this->command->checkCompatibility('uninstall'));
        $this->assertFalse($this->command->checkCompatibility('update'));
        $this->assertFalse($this->command->checkCompatibility(''));
    }

    public function testCommandUsesInteractiveInputTrait(): void
    {
        $reflection = new \ReflectionClass(InstallationCommand::class);
        $traits = $reflection->getTraitNames();
        
        $this->assertContains(InteractiveInputTrait::class, $traits);
    }

    public function testOutputContainsProjectStructureInfo(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions(['skip-db' => true]);

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->willReturn(true);

        ob_start();
        $this->command->run();
        $output = ob_get_clean();

        $expectedStructure = [
            'Config/configFramework.php',
            'Public/index.php',
            '.htaccess',
            'composer.json',
            'Cache/',
            'Logs/',
            'filesystemMedia/',
        ];

        foreach ($expectedStructure as $item) {
            $this->assertStringContainsString($item, $output);
        }
    }

    public function testOutputContainsNextSteps(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
        ]);
        $this->command->setOptions(['skip-db' => true]);

        $this->installationManagerMock
            ->expects($this->once())
            ->method('setForce')
            ->willReturnSelf();

        $this->installationManagerMock
            ->expects($this->once())
            ->method('install')
            ->willReturn(true);

        ob_start();
        $this->command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Next steps:', $output);
        $this->assertStringContainsString('composer install', $output);
    }
}

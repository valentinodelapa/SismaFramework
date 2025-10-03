<?php

namespace SismaFramework\Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Commands\InstallationCommand;
use SismaFramework\Console\Services\Installation\InstallationManager;

class InstallationCommandTest extends TestCase
{
    private InstallationCommand $command;
    private InstallationManager $mockInstallationManager;

    protected function setUp(): void
    {
        $this->mockInstallationManager = $this->createMock(InstallationManager::class);
        $this->command = new InstallationCommand($this->mockInstallationManager);
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

        $this->assertStringContainsString('projectName', $output);
        $this->assertStringContainsString('--force', $output);
        $this->assertStringContainsString('--db-host', $output);
        $this->assertStringContainsString('--db-name', $output);
    }

    public function testExecuteWithMissingProjectName(): void
    {
        $this->command->setArguments([]);
        $this->command->setOptions([]);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Error: Project name is required', $output);
    }

    public function testSuccessfulInstallation(): void
    {
        $this->command->setArguments([
            'projectName' => 'MyProject'
        ]);
        $this->command->setOptions(['force' => true]);

        $this->mockInstallationManager
            ->expects($this->once())
            ->method('setForce')
            ->with(true)
            ->willReturnSelf();

        $this->mockInstallationManager
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
            'projectName' => 'MyProject'
        ]);
        $this->command->setOptions([
            'db-host' => 'localhost',
            'db-name' => 'mydb',
            'db-user' => 'root',
            'db-pass' => 'secret',
            'db-port' => '3306'
        ]);

        $expectedConfig = [
            'DATABASE_HOST' => 'localhost',
            'DATABASE_NAME' => 'mydb',
            'DATABASE_USERNAME' => 'root',
            'DATABASE_PASSWORD' => 'secret',
            'DATABASE_PORT' => '3306'
        ];

        $this->mockInstallationManager
            ->expects($this->once())
            ->method('setForce')
            ->with(false)
            ->willReturnSelf();

        $this->mockInstallationManager
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
            'projectName' => 'MyProject'
        ]);

        $this->mockInstallationManager
            ->expects($this->once())
            ->method('setForce')
            ->willReturnSelf();

        $this->mockInstallationManager
            ->expects($this->once())
            ->method('install')
            ->willThrowException(new \RuntimeException('Installation failed'));

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Error: Installation failed', $output);
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
}

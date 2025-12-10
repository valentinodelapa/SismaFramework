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

        $expectedStrings = [
            'Usage: php SismaFramework/Console/sisma install <projectName> [options]',
            'Arguments:',
            'projectName    The name of the project to install',
            'Options:',
            '--force        Force overwrite existing files',
            '--db-host=HOST      Database host (default: 127.0.0.1)',
            '--db-name=NAME      Database name',
            '--db-user=USER      Database username',
            '--db-pass=PASS      Database password',
            '--db-port=PORT      Database port (default: 3306)',
            'Example:',
            'php SismaFramework/Console/sisma install MyProject --db-name=mydb --db-user=root'
        ];

        foreach ($expectedStrings as $string) {
            $this->assertStringContainsString($string, $output);
        }
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

    public function testSuccessfulInstallation(): void
    {
        $this->command->setArguments([
            '0' => 'MyProject'
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
            '0' => 'MyProject'
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
            '0' => 'MyProject'
        ]);

        $this->mockInstallationManager
            ->expects($this->once())
            ->method('setForce')
            ->willReturnSelf();

        $this->mockInstallationManager
            ->expects($this->once())
            ->method('install')
            ->willThrowException(new \RuntimeException('Installation failed'));

        // L'eccezione viene propagata e catturata dal dispatcher a monte
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
}

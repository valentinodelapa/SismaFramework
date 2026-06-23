<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SismaFramework\Tests\Console\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\BaseClasses\BaseCommand;
use SismaFramework\Console\HelperClasses\CommandDispatcher;
use SismaFramework\Core\HelperClasses\Config;

class CommandDispatcherTest extends TestCase
{

    private Config $configStub;

    protected function setUp(): void
    {
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')->willReturnMap([
            ['systemPath', '/nonexistent/path/'],
            ['system', 'SismaFramework'],
            ['rootPath', '/nonexistent/root/'],
            ['moduleFolders', []],
        ]);
    }

    public function testConstructorThrowsExceptionWithEmptyCommandParts(): void
    {
        $this->expectException(\RuntimeException::class);
        new CommandDispatcher([], $this->configStub);
    }

    public function testRunThrowsForUnknownCommand(): void
    {
        $dispatcher = new CommandDispatcher(['unknown-command'], $this->configStub);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unknown command/');
        $dispatcher->run();
    }

    public function testAddCommandStrategyAndRun(): void
    {
        $commandStub = $this->createStub(BaseCommand::class);
        $commandStub->method('checkCompatibility')->willReturnMap([
            ['mycommand', true],
        ]);
        $commandStub->method('run')->willReturn(true);

        $dispatcher = new CommandDispatcher(['mycommand'], $this->configStub);
        $dispatcher->addCommandStrategy($commandStub);

        $this->assertTrue($dispatcher->run());
    }

    public function testRunDispatchesArgumentsAndOptionsToCommand(): void
    {
        $commandMock = $this->createMock(BaseCommand::class);
        $commandMock->method('checkCompatibility')->willReturn(true);
        $commandMock->expects($this->once())->method('setArguments')->with(['0' => 'arg1']);
        $commandMock->expects($this->once())->method('setOptions')->with(['flag' => true, 'key' => 'value']);
        $commandMock->method('run')->willReturn(true);

        $dispatcher = new CommandDispatcher(['mycommand', 'arg1', '--flag', '--key=value'], $this->configStub);
        $dispatcher->addCommandStrategy($commandMock);
        $dispatcher->run();
    }

    public function testDiscoverySkipsNonExistentDirectories(): void
    {
        // No exception should be thrown even when paths do not exist
        $dispatcher = new CommandDispatcher(['somecommand'], $this->configStub);
        $this->assertInstanceOf(CommandDispatcher::class, $dispatcher);
    }

    public function testDiscoveryFindsCommandsInSystemPath(): void
    {
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sisma_sys_' . uniqid();
        $commandsDir = $tempRoot . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands';
        mkdir($commandsDir, 0755, true);

        $fqcn = 'SismaFakeSystem\\Console\\Commands\\SystemFakeCommand';
        file_put_contents($commandsDir . DIRECTORY_SEPARATOR . 'SystemFakeCommand.php', <<<'PHP'
<?php
namespace SismaFakeSystem\Console\Commands;
use SismaFramework\Console\BaseClasses\BaseCommand;
class SystemFakeCommand extends BaseCommand {
    public function checkCompatibility(string $command): bool { return $command === 'system-fake'; }
    protected function configure(): void {}
    protected function execute(): bool { return true; }
}
PHP);

        $autoloader = function (string $className) use ($fqcn, $commandsDir): void {
            if ($className === $fqcn) {
                require_once $commandsDir . DIRECTORY_SEPARATOR . 'SystemFakeCommand.php';
            }
        };
        spl_autoload_register($autoloader, true, true);

        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([
            ['systemPath', $tempRoot . DIRECTORY_SEPARATOR],
            ['system', 'SismaFakeSystem'],
            ['rootPath', '/nonexistent/'],
            ['moduleFolders', []],
        ]);

        $dispatcher = new CommandDispatcher(['system-fake'], $configStub);

        ob_start();
        $result = $dispatcher->run();
        ob_get_clean();

        spl_autoload_unregister($autoloader);
        unlink($commandsDir . DIRECTORY_SEPARATOR . 'SystemFakeCommand.php');
        rmdir($commandsDir);
        rmdir($tempRoot . DIRECTORY_SEPARATOR . 'Console');
        rmdir($tempRoot);

        $this->assertTrue($result);
    }

    public function testDiscoveryFindsCommandsInModuleFolders(): void
    {
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sisma_mod_' . uniqid();
        $moduleName = 'FakeModule';
        $commandsDir = $tempRoot . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands';
        mkdir($commandsDir, 0755, true);

        $fqcn = 'FakeModule\\Console\\Commands\\ModuleFakeCommand';
        file_put_contents($commandsDir . DIRECTORY_SEPARATOR . 'ModuleFakeCommand.php', <<<'PHP'
<?php
namespace FakeModule\Console\Commands;
use SismaFramework\Console\BaseClasses\BaseCommand;
class ModuleFakeCommand extends BaseCommand {
    public function checkCompatibility(string $command): bool { return $command === 'module-fake'; }
    protected function configure(): void {}
    protected function execute(): bool { return true; }
}
PHP);

        $autoloader = function (string $className) use ($fqcn, $commandsDir): void {
            if ($className === $fqcn) {
                require_once $commandsDir . DIRECTORY_SEPARATOR . 'ModuleFakeCommand.php';
            }
        };
        spl_autoload_register($autoloader, true, true);

        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([
            ['systemPath', '/nonexistent/'],
            ['system', 'SismaFakeSystem'],
            ['rootPath', $tempRoot . DIRECTORY_SEPARATOR],
            ['moduleFolders', [$moduleName]],
        ]);

        $dispatcher = new CommandDispatcher(['module-fake'], $configStub);

        ob_start();
        $result = $dispatcher->run();
        ob_get_clean();

        spl_autoload_unregister($autoloader);
        unlink($commandsDir . DIRECTORY_SEPARATOR . 'ModuleFakeCommand.php');
        rmdir($commandsDir);
        rmdir($tempRoot . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Console');
        rmdir($tempRoot . DIRECTORY_SEPARATOR . $moduleName);
        rmdir($tempRoot);

        $this->assertTrue($result);
    }

    public function testFirstMatchingCommandIsUsed(): void
    {
        $firstCommandMock = $this->createMock(BaseCommand::class);
        $firstCommandMock->method('checkCompatibility')->willReturn(true);
        $firstCommandMock->expects($this->once())->method('run')->willReturn(true);

        $secondCommandMock = $this->createMock(BaseCommand::class);
        $secondCommandMock->method('checkCompatibility')->willReturn(true);
        $secondCommandMock->expects($this->never())->method('run');

        $dispatcher = new CommandDispatcher(['cmd'], $this->configStub);
        $dispatcher->addCommandStrategy($firstCommandMock);
        $dispatcher->addCommandStrategy($secondCommandMock);
        $dispatcher->run();
    }

    public function testAddCommandStrategyWithModuleRunsNormally(): void
    {
        $commandMock = $this->createMock(BaseCommand::class);
        $commandMock->method('checkCompatibility')->willReturn(true);
        $commandMock->expects($this->once())->method('run')->willReturn(true);

        $dispatcher = new CommandDispatcher(['cmd'], $this->configStub);
        $dispatcher->addCommandStrategy($commandMock, 'SomeModule');
        $this->assertTrue($dispatcher->run());
    }

    public function testModuleFilterRunsOnlyMatchingModuleCommand(): void
    {
        $commandA = $this->createMock(BaseCommand::class);
        $commandA->method('checkCompatibility')->willReturn(true);
        $commandA->expects($this->once())->method('run')->willReturn(true);

        $commandB = $this->createMock(BaseCommand::class);
        $commandB->method('checkCompatibility')->willReturn(true);
        $commandB->expects($this->never())->method('run');

        $dispatcher = new CommandDispatcher(['cmd', '--module=ModuleA'], $this->configStub);
        $dispatcher->addCommandStrategy($commandA, 'ModuleA');
        $dispatcher->addCommandStrategy($commandB, 'ModuleB');
        $dispatcher->run();
    }

    public function testModuleFilterSkipsAllCommandsWhenModuleNotFound(): void
    {
        $commandMock = $this->createMock(BaseCommand::class);
        $commandMock->method('checkCompatibility')->willReturn(true);
        $commandMock->expects($this->never())->method('run');

        $dispatcher = new CommandDispatcher(['cmd', '--module=NonExistent'], $this->configStub);
        $dispatcher->addCommandStrategy($commandMock, 'ModuleA');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unknown command/');
        $dispatcher->run();
    }

    public function testModuleFilterWithoutModuleOptionIgnoresModuleOwnership(): void
    {
        $commandA = $this->createMock(BaseCommand::class);
        $commandA->method('checkCompatibility')->willReturn(true);
        $commandA->expects($this->once())->method('run')->willReturn(true);

        $commandB = $this->createMock(BaseCommand::class);
        $commandB->method('checkCompatibility')->willReturn(true);
        $commandB->expects($this->never())->method('run');

        $dispatcher = new CommandDispatcher(['cmd'], $this->configStub);
        $dispatcher->addCommandStrategy($commandA, 'ModuleA');
        $dispatcher->addCommandStrategy($commandB, 'ModuleB');
        $dispatcher->run();
    }

    public function testDiscoveryFindsCommandsInUnconfiguredModuleFolders(): void
    {
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sisma_uncfg_' . uniqid();
        $moduleName = 'UnconfiguredModule';
        $commandsDir = $tempRoot . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands';
        mkdir($commandsDir, 0755, true);

        $fqcn = 'UnconfiguredModule\\Console\\Commands\\UnconfiguredFakeCommand';
        file_put_contents($commandsDir . DIRECTORY_SEPARATOR . 'UnconfiguredFakeCommand.php', <<<'PHP'
<?php
namespace UnconfiguredModule\Console\Commands;
use SismaFramework\Console\BaseClasses\BaseCommand;
class UnconfiguredFakeCommand extends BaseCommand {
    public function checkCompatibility(string $command): bool { return $command === 'unconfigured-fake'; }
    protected function configure(): void {}
    protected function execute(): bool { return true; }
}
PHP);

        $autoloader = function (string $className) use ($fqcn, $commandsDir): void {
            if ($className === $fqcn) {
                require_once $commandsDir . DIRECTORY_SEPARATOR . 'UnconfiguredFakeCommand.php';
            }
        };
        spl_autoload_register($autoloader, true, true);

        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([
            ['systemPath', '/nonexistent/'],
            ['system', 'SismaFakeSystem'],
            ['rootPath', $tempRoot . DIRECTORY_SEPARATOR],
            ['moduleFolders', []],
        ]);

        $dispatcher = new CommandDispatcher(['unconfigured-fake'], $configStub);

        ob_start();
        $result = $dispatcher->run();
        ob_get_clean();

        spl_autoload_unregister($autoloader);
        unlink($commandsDir . DIRECTORY_SEPARATOR . 'UnconfiguredFakeCommand.php');
        rmdir($commandsDir);
        rmdir($tempRoot . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Console');
        rmdir($tempRoot . DIRECTORY_SEPARATOR . $moduleName);
        rmdir($tempRoot);

        $this->assertTrue($result);
    }

    public function testModuleFilterSelectsUnconfiguredModuleOverConfiguredOne(): void
    {
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sisma_prio_' . uniqid();
        $configuredModule = 'ConfiguredModule';
        $unconfiguredModule = 'UnconfiguredModule';

        foreach ([$configuredModule, $unconfiguredModule] as $module) {
            $dir = $tempRoot . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands';
            mkdir($dir, 0755, true);
            $className = $module . 'PrioCommand';
            $fqcn = $module . '\\Console\\Commands\\' . $className;
            file_put_contents($dir . DIRECTORY_SEPARATOR . $className . '.php', <<<PHP
<?php
namespace {$module}\\Console\\Commands;
use SismaFramework\\Console\\BaseClasses\\BaseCommand;
class {$className} extends BaseCommand {
    public function checkCompatibility(string \$command): bool { return \$command === 'prio-cmd'; }
    protected function configure(): void {}
    protected function execute(): bool { echo '{$module}'; return true; }
}
PHP);
            $autoloaders[$module] = function (string $cn) use ($fqcn, $dir, $className): void {
                if ($cn === $fqcn) {
                    require_once $dir . DIRECTORY_SEPARATOR . $className . '.php';
                }
            };
            spl_autoload_register($autoloaders[$module], true, true);
        }

        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')->willReturnMap([
            ['systemPath', '/nonexistent/'],
            ['system', 'SismaFakeSystem'],
            ['rootPath', $tempRoot . DIRECTORY_SEPARATOR],
            ['moduleFolders', [$configuredModule]],
        ]);

        $dispatcher = new CommandDispatcher(['prio-cmd', '--module=' . $unconfiguredModule], $configStub);

        ob_start();
        $result = $dispatcher->run();
        $output = ob_get_clean();

        foreach ($autoloaders as $autoloader) {
            spl_autoload_unregister($autoloader);
        }

        foreach ([$configuredModule, $unconfiguredModule] as $module) {
            $className = $module . 'PrioCommand';
            $dir = $tempRoot . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands';
            unlink($dir . DIRECTORY_SEPARATOR . $className . '.php');
            rmdir($dir);
            rmdir($tempRoot . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Console');
            rmdir($tempRoot . DIRECTORY_SEPARATOR . $module);
        }
        rmdir($tempRoot);

        $this->assertTrue($result);
        $this->assertStringContainsString($unconfiguredModule, $output);
    }
}

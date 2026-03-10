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
}

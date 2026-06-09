<?php

/*
 * The MIT License
 *
 * Copyright 2024 valen.
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

namespace SismaFramework\Console\HelperClasses;

use SismaFramework\Console\BaseClasses\BaseCommand;
use SismaFramework\Console\HelperClasses\Dispatcher\CommandFactory;
use SismaFramework\Core\HelperClasses\Config;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class CommandDispatcher
{

    private Config $config;
    private string $command;
    private array $commandParts;
    private array $commandList = [];
    private array $arguments = [];
    private array $options = [];

    public function __construct(array $commandParts, ?Config $config = null)
    {
        if (empty($commandParts)) {
            throw new \RuntimeException(<<<ERROR
Usage: php SismaFramework/Console/sisma <command> [arguments] [options]
Available commands:
  scaffold <entity> <module> - Generate scaffolding for an entity
Type 'php SismaFramework/Console/sisma <command>' for more information about a command
ERROR);
        }
        $this->config = $config ?? Config::getInstance();
        $this->command = array_shift($commandParts);
        $this->commandParts = $commandParts;
        $this->discoverCommands();
    }

    public function addCommandStrategy(BaseCommand $command): void
    {
        $this->commandList[] = $command;
    }

    public function run(): bool
    {
        foreach ($this->commandList as $command) {
            if ($command->checkCompatibility($this->command)) {
                $this->sortCommandParts();
                $command->setArguments($this->arguments);
                $command->setOptions($this->options);
                return $command->run();
            }
        }
        throw new \RuntimeException("Unknown command: " . $this->command . PHP_EOL);
    }

    private function discoverCommands(): void
    {
        $factory = new CommandFactory();
        foreach ($this->config->moduleFolders as $moduleFolder) {
            $this->discoverFromDirectory(
                $this->config->rootPath . $moduleFolder . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands',
                $moduleFolder . '\\Console\\Commands',
                $factory
            );
        }
        $this->discoverFromDirectory(
            $this->config->systemPath . 'Console' . DIRECTORY_SEPARATOR . 'Commands',
            $this->config->system . '\\Console\\Commands',
            $factory
        );
    }

    private function discoverFromDirectory(string $directory, string $namespace, CommandFactory $factory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*.php') as $file) {
            $fqcn = $namespace . '\\' . basename($file, '.php');
            if (class_exists($fqcn)) {
                $reflection = new \ReflectionClass($fqcn);
                if (!$reflection->isAbstract() && $reflection->isSubclassOf(BaseCommand::class)) {
                    $this->commandList[] = $factory->createCommand($fqcn);
                }
            }
        }
    }

    private function sortCommandParts(): void
    {
        $positionalIndex = 0;
        foreach ($this->commandParts as $arg) {
            if (strpos($arg, '--') === 0) {
                $this->parseOption(substr($arg, 2));
            } else {
                $this->arguments[$positionalIndex] = $arg;
                $positionalIndex++;
            }
        }
    }

    private function parseOption(string $option): void
    {
        if (strpos($option, '=') !== false) {
            list($key, $value) = explode('=', $option, 2);
            $this->options[$key] = $value;
        } else {
            $this->options[$option] = true;
        }
    }
}

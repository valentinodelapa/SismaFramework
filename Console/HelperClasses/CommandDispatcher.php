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

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class CommandDispatcher
{

    private string $command;
    private array $commandParts;
    private array $commandList = [];
    private array $arguments = [];
    private array $options = [];

    public function __construct(array $commandParts)
    {
        if (empty($commandParts)) {
            throw new \RuntimeException(<<<ERROR
Usage: php SismaFramework/Console/sisma <command> [arguments] [options]
Available commands:
  scaffold <entity> <module> - Generate scaffolding for an entity
Type 'php SismaFramework/Console/sisma <command>' for more information about a command
ERROR);
        }
        $this->command = array_shift($commandParts);
        $this->commandParts = $commandParts;
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

    private function sortCommandParts(): void
    {
        foreach ($this->commandParts as $arg) {
            if (strpos($arg, '--') === 0) {
                $this->parseOption(substr($arg, 2));
            } elseif (!isset($this->arguments['entity'])) {
                $this->arguments['entity'] = $arg;
            } elseif (!isset($this->arguments['module'])) {
                $this->arguments['module'] = $arg;
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

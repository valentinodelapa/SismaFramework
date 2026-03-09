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

namespace SismaFramework\Tests\Console\HelperClasses\Dispatcher;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\BaseClasses\BaseCommand;
use SismaFramework\Console\Commands\InstallationCommand;
use SismaFramework\Console\Commands\ScaffoldCommand;
use SismaFramework\Console\HelperClasses\Dispatcher\CommandFactory;

class NoConstructorCommand extends BaseCommand
{

    #[\Override]
    public function checkCompatibility(string $command): bool
    {
        return $command === 'no-constructor';
    }

    #[\Override]
    protected function configure(): void
    {
    }

    #[\Override]
    protected function execute(): bool
    {
        return true;
    }
}

class AllDefaultsCommand extends BaseCommand
{

    public function __construct(private string $label = 'default')
    {
    }

    #[\Override]
    public function checkCompatibility(string $command): bool
    {
        return $command === 'all-defaults';
    }

    #[\Override]
    protected function configure(): void
    {
    }

    #[\Override]
    protected function execute(): bool
    {
        return true;
    }
}

class CommandFactoryTest extends TestCase
{

    private CommandFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new CommandFactory();
    }

    public function testCreateCommandWithNoConstructor(): void
    {
        $command = $this->factory->createCommand(NoConstructorCommand::class);
        $this->assertInstanceOf(NoConstructorCommand::class, $command);
    }

    public function testCreateCommandWithAllDefaultParameters(): void
    {
        $command = $this->factory->createCommand(AllDefaultsCommand::class);
        $this->assertInstanceOf(AllDefaultsCommand::class, $command);
    }

    public function testCreateInstallationCommand(): void
    {
        $command = $this->factory->createCommand(InstallationCommand::class);
        $this->assertInstanceOf(InstallationCommand::class, $command);
    }

    public function testCreateScaffoldCommand(): void
    {
        $command = $this->factory->createCommand(ScaffoldCommand::class);
        $this->assertInstanceOf(ScaffoldCommand::class, $command);
    }

    public function testCreatedCommandIsInstanceOfBaseCommand(): void
    {
        $command = $this->factory->createCommand(NoConstructorCommand::class);
        $this->assertInstanceOf(BaseCommand::class, $command);
    }
}

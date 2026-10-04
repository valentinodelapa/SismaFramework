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

namespace SismaFramework\Console\BaseClasses;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseCommand
{

    protected array $arguments = [];
    protected array $options = [];
    
    abstract public function checkCompatibility(string $command): bool;

    /**
     * @deprecated dalla versione 12.5.0, sarà rimosso nella versione 13.0.0. Implementare help()
     */
    protected function configure(): void
    {

    }

    protected function help(): string
    {
        return '';
    }

    abstract protected function execute(): bool;

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function run(): bool
    {
        if ($this->getOption('help') !== null) {
            $this->printHelp();
            return true;
        }
        $result = $this->execute();
        if ($result === false) {
            $this->output('Use --help for usage information.');
        }
        return $result;
    }

    private function printHelp(): void
    {
        if ($this->isOverridden('configure') && !$this->isOverridden('help')) {
            $this->configure();
            $this->output('Deprecated: ' . static::class . '::configure() is deprecated since 12.5.0 and will be removed in 13.0.0, implement help(): string instead.');
        } else {
            $this->output($this->help());
        }
    }

    private function isOverridden(string $methodName): bool
    {
        return (new \ReflectionMethod($this, $methodName))->getDeclaringClass()->getName() !== self::class;
    }

    protected function getArgument(string $name): ?string
    {
        return $this->arguments[$name] ?? null;
    }

    protected function getOption(string $name): ?string
    {
        return $this->options[$name] ?? null;
    }

    protected function output(string $message): void
    {
        echo $message . PHP_EOL;
    }
}

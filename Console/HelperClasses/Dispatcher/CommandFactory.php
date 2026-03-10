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

namespace SismaFramework\Console\HelperClasses\Dispatcher;

use SismaFramework\Console\BaseClasses\BaseCommand;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class CommandFactory
{

    public function createCommand(string $commandClassName): BaseCommand
    {
        $reflection = new \ReflectionClass($commandClassName);
        $constructor = $reflection->getConstructor();
        if ($constructor === null || $this->allParametersHaveDefaults($constructor->getParameters())) {
            return new $commandClassName();
        }
        $arguments = $this->resolveConstructorArguments($constructor->getParameters());
        return $reflection->newInstanceArgs($arguments);
    }

    private function allParametersHaveDefaults(array $parameters): bool
    {
        foreach ($parameters as $parameter) {
            if (!$parameter->isOptional()) {
                return false;
            }
        }
        return true;
    }

    private function resolveConstructorArguments(array $parameters): array
    {
        $arguments = [];
        foreach ($parameters as $parameter) {
            if ($parameter->isOptional()) {
                break;
            }
            $type = $parameter->getType();
            if ($type !== null && !$type->isBuiltin()) {
                $className = $type->getName();
                $arguments[] = new $className();
            } else {
                throw new \RuntimeException("Cannot auto-inject builtin type in command: " . $type?->getName());
            }
        }
        return $arguments;
    }
}

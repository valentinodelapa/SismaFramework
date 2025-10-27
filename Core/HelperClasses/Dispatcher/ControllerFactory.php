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

namespace SismaFramework\Core\HelperClasses\Dispatcher;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Exceptions\BadRequestException;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class ControllerFactory
{

    private DataMapper $dataMapper;

    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function createController(string $controllerClassName): BaseController
    {
        $reflectionController = new \ReflectionClass($controllerClassName);
        $reflectionConstructor = $reflectionController->getConstructor();
        $reflectionConstructorArguments = $reflectionConstructor->getParameters();
        if ((count($reflectionConstructorArguments) == 0) ||
                (is_a($reflectionConstructorArguments[0]->getType()->getName(), DataMapper::class, true))) {
            return new $controllerClassName($this->dataMapper);
        } else {
            $constructorArguments = $this->resolveConstructorArguments($reflectionConstructorArguments);
            return $reflectionController->newInstanceArgs($constructorArguments);
        }
    }

    private function resolveConstructorArguments(array $reflectionArguments): array
    {
        $arguments = [];
        foreach ($reflectionArguments as $argument) {
            $argumentType = $argument->getType();
            if (is_a($argumentType->getName(), DataMapper::class, true)) {
                $arguments[] = $this->dataMapper;
            } elseif ($argumentType->isBuiltin() === false) {
                $className = $argumentType->getName();
                $arguments[] = new $className();
            } else {
                throw new BadRequestException("Cannot auto-inject builtin type: " . $argumentType->getName());
            }
        }
        return $arguments;
    }
}

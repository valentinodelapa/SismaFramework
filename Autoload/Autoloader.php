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

namespace SismaFramework\Autoload;

/**
 *
 * @author Valentino de Lapa
 */
class Autoloader
{

    private string $className;
    private string $classPath;
    private static array $autoloadClassMapper = \Config\AUTOLOAD_CLASS_MAPPER;
    private static array $autoloadNamespaceMapper = \Config\AUTOLOAD_NAMESPACE_MAPPER;
    private static string $rootPath = \Config\ROOT_PATH;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function findClass(): bool
    {
        $actualClassPath = self::$rootPath . str_replace('\\', DIRECTORY_SEPARATOR, $this->className) . '.php';
        if ($this->classExsist($actualClassPath)) {
            return true;
        } elseif ($this->mapNamespace()) {
            return true;
        } else {
            return $this->mapClass();
        }
    }

    private function classExsist(string $classPath): bool
    {
        if (file_exists($classPath)) {
            $this->classPath = $classPath;
            return true;
        } else {
            return false;
        }
    }

    private function mapNamespace(): bool
    {
        foreach (self::$autoloadNamespaceMapper as $key => $value) {
            if (str_contains($this->className, $key)) {
                $actualClassName = str_replace($key, '', $this->className);
                $actualClassPath = self::$rootPath . $value . str_replace('\\', DIRECTORY_SEPARATOR, $actualClassName) . '.php';
                if ($this->classExsist($actualClassPath)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function mapClass(): bool
    {
        if (array_key_exists($this->className, self::$autoloadClassMapper)) {
            $actualClassPath = self::$rootPath . self::$autoloadClassMapper[$this->className] . '.php';
            if ($this->classExsist($actualClassPath)) {
                return true;
            }
        }
        return false;
    }

    public function getClassPath(): string
    {
        return $this->classPath;
    }
}

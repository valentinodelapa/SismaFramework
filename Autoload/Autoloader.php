<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Autoloader
{

    private string $className;
    private string $classPath;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function findClass(): bool
    {
        $actualClassPath = \Config\ROOT_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $this->className) . '.php';
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
        foreach (\Config\AUTOLOAD_NAMESPACE_MAPPER as $key => $value) {
            if (str_contains($this->className, $key)) {
                $actualClassName = str_replace($key, '', $this->className);
                $actualClassPath = \Config\ROOT_PATH . $value . $actualClassName . '.php';
                if ($this->classExsist($actualClassPath)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function mapClass(): bool
    {
        if (array_key_exists($this->className, \Config\AUTOLOAD_CLASS_MAPPER)) {
            $actualClassPath = \Config\ROOT_PATH . \Config\AUTOLOAD_CLASS_MAPPER[$this->className] . '.php';
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

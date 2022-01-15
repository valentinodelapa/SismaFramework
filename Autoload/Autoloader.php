<?php

namespace SismaFramework\Autoload;

class Autoloader
{

    private string $className;
    private string $classPath;
    private static $classExsist = false;
    private static array $naturalNamespaceParts = [];

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function findClass(): bool
    {
        $classPath = \Config\ROOT_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $this->className) . '.php';
        if ($this->classExsist($classPath)) {
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
                $className = str_replace($key, '', $this->className);
                $classPath = \Config\ROOT_PATH . $value . $className . '.php';
                if ($this->classExsist($classPath)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function mapClass(): bool
    {
        if (array_key_exists($this->className, \Config\AUTOLOAD_CLASS_MAPPER)) {
            $classPath = \Config\ROOT_PATH . \Config\AUTOLOAD_CLASS_MAPPER[$this->className] . '.php';
            if ($this->classExsist($classPath)) {
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

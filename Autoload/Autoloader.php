<?php

namespace SismaFramework\Autoload;

class Autoloader
{

    private static $classPath;
    private static $partialClassPath;
    private static $classFound = false;
    private static array $naturalNamespaceParts = [];

    public static function injectClassName(string $className)
    {
        self::$partialClassPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    }

    public static function findClass(string $directory, int $depth = 0): void
    {
        if (is_dir($directory)) {
            $classPath = $directory . '/' . self::$partialClassPath . '.php';
            if (file_exists($classPath)) {
                self::$classPath = $classPath;
                self::$classFound = true;
            } else {
                $depth++;
                self::searchInSubdirectory($directory, $depth);
            }
        }
    }

    private static function searchInSubdirectory(string $directory, int $depth): void
    {
        $subdirectoryList = scandir($directory);
        foreach ($subdirectoryList as $subdirectory) {
            if (($subdirectory !== '.') && ($subdirectory !== '..')) {
                self::findClass($directory . '/' . $subdirectory, $depth);
                if (self::$classFound) {
                    self::$naturalNamespaceParts[$depth] = $subdirectory;
                    break;
                }
            }
        }
    }

    public static function getSearchResult(): bool
    {
        return self::$classFound;
    }

    public static function getClassPath(): string
    {
        return self::$classPath;
    }

    public static function getNaturalNamespace(): string
    {
        $naturalNamespace = implode('\\', self::$naturalNamespaceParts);
        return $naturalNamespace;
    }

    public static function getRelativePath(): string
    {
        $naturalNamespace = implode('/', self::$naturalNamespaceParts);
        return $naturalNamespace;
    }

}

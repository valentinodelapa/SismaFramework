<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa.
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

namespace SismaFramework\Orm\HelperClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\Exceptions\CacheException;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;
use SismaFramework\Core\HelperClasses\ModuleManager;

/**
 * Description of EntityCache
 *
 * @author Valentino de Lapa
 */
class Cache
{

    private static array $entityCache = [];
    private static string $entityNamespace = \Config\ENTITY_NAMESPACE;
    private static string $entityPath = \Config\ENTITY_PATH;
    private static array $foreighKeyDataCache = [];
    private static string $referencedCacheDirectory = \Config\REFERENCE_CACHE_DIRECTORY;
    private static string $referencedCachePath = \Config\REFERENCE_CACHE_PATH;
    private static string $rootPath = \Config\ROOT_PATH;

    public static function setEntity(BaseEntity $entity): void
    {
        static::$entityCache[get_class($entity)][$entity->id] = $entity;
    }

    public static function checkEntityPresenceInCache(string $entityName, int $entityId): bool
    {
        if (isset(static::$entityCache[$entityName])) {
            return array_key_exists($entityId, static::$entityCache[$entityName]);
        } else {
            return false;
        }
    }

    public static function getEntityById(string $entityName, int $entityId): BaseEntity
    {
        return static::$entityCache[$entityName][$entityId];
    }

    public static function getForeignKeyData(string $referencedEntityName, ?string $propertyName = null): array
    {
        if (is_subclass_of($referencedEntityName, ReferencedEntity::class)) {
            if (self::checkEntityPresence($referencedEntityName, $propertyName) === false) {
                if (is_dir(self::$referencedCacheDirectory) === false) {
                    mkdir(self::$referencedCacheDirectory);
                }
                if (file_exists(self::$referencedCachePath)) {
                    static::getForeignKeyDataFromCacheFile($referencedEntityName, $propertyName);
                } else {
                    static::setForeignKeyDataFromEntities();
                }
            }
            return self::getForeignKeyDataWithParents($referencedEntityName, $propertyName);
        } else {
            throw new CacheException($referencedEntityName . (($propertyName !== null) ? ' - ' . $propertyName : ''));
        }
    }

    private static function getForeignKeyDataWithParents(string $referencedEntityName, ?string $propertyName = null): array
    {
        $parentReferencedEntityName = get_parent_class($referencedEntityName);
        $parentReflectionClass = new \ReflectionClass($parentReferencedEntityName);
        if ($parentReflectionClass->isAbstract()) {
            return ($propertyName === null) ? static::$foreighKeyDataCache[$referencedEntityName] : static::$foreighKeyDataCache[$referencedEntityName][$propertyName];
        } elseif ($propertyName === null) {
            return array_merge(static::$foreighKeyDataCache[$referencedEntityName], self::getForeignKeyDataWithParents($parentReferencedEntityName));
        } elseif (array_key_exists($propertyName, static::$foreighKeyDataCache[$referencedEntityName])) {
            return static::$foreighKeyDataCache[$referencedEntityName][$propertyName];
        } else {
            return self::getForeignKeyDataWithParents($parentReferencedEntityName, $propertyName);
        }
    }

    private static function checkEntityPresence(string $referencedEntityName, ?string $propertyName): bool
    {
        if (array_key_exists($referencedEntityName, static::$foreighKeyDataCache)) {
            return self::checkForeignKeyPresence($referencedEntityName, $propertyName);
        } else {
            return false;
        }
    }

    private static function checkForeignKeyPresence(string $referencedEntityName, ?string $propertyName): bool
    {
        if ($propertyName === null) {
            return true;
        } else {
            $parentEntityName = get_parent_class($referencedEntityName);
            $parentReflectionClass = new \ReflectionClass($parentEntityName);
            if ($parentReflectionClass->isAbstract()) {
                return array_key_exists($propertyName, static::$foreighKeyDataCache[$referencedEntityName]);
            } elseif (array_key_exists($propertyName, static::$foreighKeyDataCache[$referencedEntityName])) {
                return true;
            } else {
                return self::checkEntityPresence($parentEntityName, $propertyName);
            }
        }
    }

    private static function getForeignKeyDataFromCacheFile(string $referencedEntityName, ?string $propertyName = null): void
    {
        static::$foreighKeyDataCache = json_decode(file_get_contents(self::$referencedCachePath), true) ?? [];
        if (self::checkEntityPresence($referencedEntityName, $propertyName) === false) {
            static::setForeignKeyDataFromEntities();
        }
    }

    private static function setForeignKeyDataFromEntities(): void
    {
        foreach (ModuleManager::getModuleList() as $module) {
            $entitiesDirectory = self::$rootPath . $module . DIRECTORY_SEPARATOR . self::$entityPath;
            if (is_dir($entitiesDirectory)) {
                static::scanModuleEntities($module, $entitiesDirectory);
            }
        }
        file_put_contents(self::$referencedCachePath, json_encode(static::$foreighKeyDataCache));
    }

    private static function scanModuleEntities($module, $directory): void
    {
        foreach (array_diff(scandir($directory), ['.', '..']) as $entityFileName) {
            $entitySimpleName = str_replace('.php', '', $entityFileName);
            $entityName = $module . '\\' . self::$entityNamespace . $entitySimpleName;
            $reflectionEntity = new \ReflectionClass($entityName);
            foreach ($reflectionEntity->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
                if (BaseEntity::checkFinalClassReflectionProperty($property) && (is_subclass_of($property->getType()->getName(), BaseEntity::class, true))) {
                    static::$foreighKeyDataCache[$property->getType()->getName()][lcfirst($entitySimpleName)][$property->getName()] = $entityName;
                }
            }
        }
    }

    public static function clearForeighKeyDataCache()
    {
        self::$foreighKeyDataCache = [];
    }
}

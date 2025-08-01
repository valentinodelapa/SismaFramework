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
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Locker;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\Exceptions\CacheException;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;

/**
 * Description of EntityCache
 *
 * @author Valentino de Lapa
 */
class Cache
{

    private static Locker $locker;
    private static array $entityCache = [];
    private static array $foreighKeyDataCache = [];

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
	
    public static function clearEntityCache(): void
    {
        static::$entityCache = [];
    }
	
    public static function getForeignKeyData(string $referencedEntityName, ?string $propertyName = null, Locker $locker = new Locker(), ?Config $customConfig = null): array
    {
        self::$locker = $locker;
        $config = $customConfig ?? Config::getInstance();
        if (is_subclass_of($referencedEntityName, ReferencedEntity::class)) {
            if (self::checkEntityPresence($referencedEntityName, $propertyName) === false) {
                if (is_dir($config->referenceCacheDirectory) === false) {
                    mkdir($config->referenceCacheDirectory);
                }
                if (file_exists($config->referenceCachePath)) {
                    static::getForeignKeyDataFromCacheFile($referencedEntityName, $propertyName, $config);
                } else {
                    static::setForeignKeyDataFromEntities($config);
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
            return self::getForeignKeyDataWithAbstractParent($referencedEntityName, $propertyName);
        } elseif ($propertyName === null) {
            return array_merge(static::$foreighKeyDataCache[$referencedEntityName], self::getForeignKeyDataWithParents($parentReferencedEntityName));
        } elseif (array_key_exists($propertyName, static::$foreighKeyDataCache[$referencedEntityName])) {
            return static::$foreighKeyDataCache[$referencedEntityName][$propertyName];
        } else {
            return self::getForeignKeyDataWithParents($parentReferencedEntityName, $propertyName);
        }
    }

    private static function getForeignKeyDataWithAbstractParent(string $referencedEntityName, ?string $propertyName = null): array
    {
        if ($propertyName === null) {
            return static::$foreighKeyDataCache[$referencedEntityName];
        } elseif (array_key_exists($propertyName, static::$foreighKeyDataCache[$referencedEntityName])) {
            return static::$foreighKeyDataCache[$referencedEntityName][$propertyName];
        } else {
            throw new CacheException($referencedEntityName . (($propertyName !== null) ? ' - ' . $propertyName : ''));
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

    private static function getForeignKeyDataFromCacheFile(string $referencedEntityName, ?string $propertyName, Config $config): void
    {
        static::$foreighKeyDataCache = json_decode(file_get_contents($config->referenceCachePath), true) ?? [];
        if (self::checkEntityPresence($referencedEntityName, $propertyName) === false) {
            static::setForeignKeyDataFromEntities($config);
        }
    }

    private static function setForeignKeyDataFromEntities(Config $config): void
    {
        foreach (ModuleManager::getModuleList() as $module) {
            $entitiesDirectory = $config->rootPath . $module . DIRECTORY_SEPARATOR . $config->entityPath;
            if (is_dir($entitiesDirectory)) {
                static::scanModuleEntities($module, $entitiesDirectory, $config);
            }
        }
        file_put_contents($config->referenceCachePath, json_encode(static::$foreighKeyDataCache));
        self::$locker->lockFolder($config->referenceCacheDirectory);
    }

    private static function scanModuleEntities($module, $directory, Config $config): void
    {
        foreach (array_diff(scandir($directory), ['.', '..']) as $entityFileName) {
            $entitySimpleName = str_replace('.php', '', $entityFileName);
            $entityName = $module . '\\' . $config->entityNamespace . $entitySimpleName;
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

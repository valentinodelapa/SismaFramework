<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa <valentino.delapa@gmail.com>.
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
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Core\HelperClasses\ModuleManager;

/**
 * Description of EntityCache
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Cache
{

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

    public static function getForeignKeyData(ReferencedEntity $referencedEntity, ?string $propertyName = null): array
    {
        $referencedEntityName = get_class($referencedEntity);
        if (self::checkEntityPresence($referencedEntityName, $propertyName) === false) {
            if(is_dir(\Config\REFERENCE_CACHE_DIRECTORY) === false){
                mkdir(\Config\REFERENCE_CACHE_DIRECTORY);
            }
            if (file_exists(\Config\REFERENCE_CACHE_PATH)) {
                static::getForeignKeyDataFromCacheFile($referencedEntityName, $propertyName);
            } else {
                static::setForeignKeyDataFromEntities();
            }
        }
        return ($propertyName === null) ? static::$foreighKeyDataCache[$referencedEntityName] : static::$foreighKeyDataCache[$referencedEntityName][$propertyName];
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
            return array_key_exists($propertyName, static::$foreighKeyDataCache[$referencedEntityName]);
        }
    }

    private static function getForeignKeyDataFromCacheFile(string $referencedEntityName, ?string $propertyName = null): void
    {
        static::$foreighKeyDataCache = json_decode(file_get_contents(\Config\REFERENCE_CACHE_PATH), true) ?? [];
        if (self::checkEntityPresence($referencedEntityName, $propertyName) === false) {
            static::setForeignKeyDataFromEntities();
        }
    }

    private static function setForeignKeyDataFromEntities(): void
    {
        foreach (ModuleManager::getModuleList() as $module) {
            $entitiesDirectory = \Config\ROOT_PATH . $module . DIRECTORY_SEPARATOR . \Config\ENTITY_PATH;
            if (is_dir($entitiesDirectory)) {
                static::scanModuleEntities($module, $entitiesDirectory);
            }
        }
        file_put_contents(\Config\REFERENCE_CACHE_PATH, json_encode(static::$foreighKeyDataCache));
    }

    private static function scanModuleEntities($module, $directory): void
    {
        foreach (array_diff(scandir($directory), ['.', '..']) as $entityFileName) {
            $entitySimpleName = str_replace('.php', '', $entityFileName);
            $entityName = $module . '\\' . \Config\ENTITY_NAMESPACE . $entitySimpleName;
            $reflectionEntity = new \ReflectionClass($entityName);
            foreach ($reflectionEntity->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
                if (is_subclass_of($property->getType()->getName(), BaseEntity::class, true)) {
                    static::$foreighKeyDataCache[$property->getType()->getName()][lcfirst($entitySimpleName)][$property->getName()] = $entityName;
                }
            }
        }
    }

}

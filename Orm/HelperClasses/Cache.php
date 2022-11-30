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
    private static array $entityPropertyCache = [];
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

    private static function setForeignKeyData(string $referencedEntityName): void
    {
        if (count(static::$entityPropertyCache) > 0) {
            foreach (static::$entityPropertyCache as $entityName => $entity) {
                foreach ($entity as $propertyName => $propertyData) {
                    if (is_a($propertyData['propertyType'], $referencedEntityName, true)) {
                        static::$foreighKeyDataCache[$referencedEntityName][lcfirst($propertyData['entityName'])][$propertyName] = $entityName;
                    }
                }
            }
        } else {
            foreach (ModuleManager::getModuleList() as $module) {
                $entitiesDirectory = \Config\ROOT_PATH . $module . DIRECTORY_SEPARATOR . \Config\ENTITY_PATH;
                if (is_dir($entitiesDirectory)) {
                    foreach (array_diff(scandir($entitiesDirectory), ['.', '..']) as $entityFileName) {
                        $entitySimpleName = str_replace('.php', '', $entityFileName);
                        $entityName = $module . '\\' . \Config\ENTITY_NAMESPACE . $entitySimpleName;
                        $reflectionEntity = new \ReflectionClass($entityName);
                        foreach ($reflectionEntity->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
                            if (is_subclass_of($property->getType()->getName(), BaseEntity::class, true)) {
                                static::$entityPropertyCache[$entityName][$property->getName()] = [
                                    'entityName' => $entitySimpleName,
                                    'propertyType' => $property->getType()->getName(),
                                ];
                                if (is_a($property->getType()->getName(), $referencedEntityName, true)) {
                                    static::$foreighKeyDataCache[$referencedEntityName][lcfirst($entitySimpleName)][$property->getName()] = $entityName;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function getForeignKeyData(ReferencedEntity $referencedEntity): array
    {
        $referencedEntityName = get_class($referencedEntity);
        if (array_key_exists($referencedEntityName, static::$foreighKeyDataCache) === false) {
            static::setForeignKeyData($referencedEntityName);
        }
        return static::$foreighKeyDataCache[$referencedEntityName];
    }

}

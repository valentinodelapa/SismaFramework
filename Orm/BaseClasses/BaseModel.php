<?php

/*
 * Questo file è ispirato concettualmente alla classe Model della libreria SimpleORM
 * (https://github.com/davideairaghi/php) rilasciata sotto licenza Apache License 2.0
 * (fare riferimento alla licenza in third-party-licenses/SimpleOrm/LICENSE).
 *
 * Copyright (c) 2015-present Davide Airaghi.
 *
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
 *
 * CAMBIAMENTI ARCHITETTURALI RISPETTO ALLA CLASSE `MODEL` DI SIMPLEORM:
 * - Completa riscrittura seguendo il pattern Data Mapper invece di Active Record.
 * - La classe Model di SimpleORM combinava rappresentazione dati e logica di persistenza (Active Record).
 * - BaseModel è ora un repository/service layer separato che delega la persistenza a DataMapper.
 * - I metodi find(), findFirst(), save(), insert(), delete() di SimpleORM Model sono stati completamente ridisegnati.
 * - Introduzione di getEntityCollection(), getEntityById(), deleteEntityById() come astrazione su DataMapper.
 * - Rimozione completa della logica di persistenza diretta: ora delegata a DataMapper.
 * - Introduzione di dependency injection per DataMapper e Config invece di singleton.
 * - Aggiunta di metodo astratto appendSearchCondition() per logica di ricerca specifica per entità.
 * - Integrazione con sistema di cache ORM del framework.
 * - Supporto per SismaCollection tipizzata invece di array.
 * - Sistema di query dinamiche con metaprogrammazione via __call() (v10.1.0):
 *   * Estensione del sistema esistente di query dinamiche a tutte le proprietà (non solo entità referenziate)
 *   * Type safety tramite Reflection API con validazione automatica dei tipi
 *   * Supporto per tipi builtin, oggetti custom, enum PHP 8.1+ e proprietà nullable
 *   * Pattern {action}By{Property}And{Property2}() per query con condizioni AND multiple
 *   * Metodi: buildPropertiesArray(), isVariableOfType(), buildPropertiesConditions()
 *   * Metodi: countEntityCollectionByProperties(), getEntityCollectionByProperties(), deleteEntityCollectionByProperties()
 * - Introduzione del supporto JOIN SQL con eager loading (v10.1.0):
 *   * Aggiunta di getEntityCollectionWithRelations() per eager loading tramite JOIN espliciti
 *   * Supporto per relazioni nested a più livelli con dot notation e sintassi array nested
 *   * Aggiunta di flattenRelations() per normalizzazione delle sintassi di relazione
 *   * Aggiunta di appendNestedRelationJoin() per costruzione ricorsiva di JOIN multi-livello
 *   * Validazione automatica delle proprietà foreign key tramite Reflection API
 *   * Integrazione con sistema di cache esistente per evitare duplicazione di entità
 */

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\Exceptions\ModelException;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\JoinType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @author Valentino de Lapa
 */
abstract class BaseModel
{

    protected DataMapper $dataMapper;
    protected Config $config;
    protected readonly string $entityName;

    public function __construct(DataMapper $dataMapper = new DataMapper(), ?Config $config = null)
    {
        $this->dataMapper = $dataMapper;
        $this->config = $config ?? Config::getInstance();
        $this->entityName = $this->getEntityName();
        $this->checkEntityName();
    }

    private function checkEntityName()
    {
        if (is_subclass_of($this->entityName, BaseEntity::class) === false) {
            throw new ModelException();
        }
    }

    abstract protected function getEntityName(): string;

    public function countEntityCollection(?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $bindValues = $bindTypes = [];
        if ($searchKey !== null) {
            $query->setWhere();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    protected function initQuery(): Query
    {
        $query = $this->dataMapper->initQuery($this->entityName);
        return $query;
    }

    public function getEntityCollection(?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $bindValues = $bindTypes = [];
        if ($searchKey !== null) {
            $query->setWhere();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->setOrderBy($order);
        if ($offset !== null) {
            $query->setOffset($offset);
        }
        if ($limit != null) {
            $query->setLimit($limit);
        }
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    public function getEntityCollectionWithRelations(array $relations, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null, JoinType $joinType = JoinType::left): SismaCollection
    {
        $query = $this->initQuery();
        $bindValues = $bindTypes = [];
        $collectionsToLoad = [];
        $flattenedRelations = $this->flattenRelations($relations);
        foreach ($flattenedRelations as $relationPath) {
            $firstSegment = explode('.', $relationPath)[0];
            if ($this->isCollectionRelation($firstSegment)) {
                $collectionsToLoad[] = $relationPath;
            } else {
                $this->appendNestedRelationJoin($query, $relationPath, $joinType);
            }
        }
        if ($searchKey !== null) {
            $query->setWhere();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->setOrderBy($order);
        if ($offset !== null) {
            $query->setOffset($offset);
        }
        if ($limit != null) {
            $query->setLimit($limit);
        }
        $query->close();
        $collection = $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
        if (!empty($collectionsToLoad)) {
            $this->eagerLoadCollections($collection, $collectionsToLoad);
        }
        return $collection;
    }

    protected function flattenRelations(array $relations, string $prefix = ''): array
    {
        $flattened = [];
        foreach ($relations as $key => $value) {
            if (is_int($key)) {
                if (is_string($value)) {
                    $flattened[] = $prefix . $value;
                } elseif (is_array($value)) {
                    $flattened = array_merge($flattened, $this->flattenRelations($value, $prefix));
                }
            } else {
                $currentPath = $prefix . $key;
                $flattened[] = $currentPath;
                if (is_array($value)) {
                    $flattened = array_merge($flattened, $this->flattenRelations($value, $currentPath . '.'));
                } elseif (is_string($value)) {
                    $flattened[] = $currentPath . '.' . $value;
                }
            }
        }
        return array_unique($flattened);
    }

    protected function appendNestedRelationJoin(Query &$query, string $relationPath, JoinType $joinType): void
    {
        $segments = explode('.', $relationPath);
        $currentEntityClass = $this->entityName;
        $parentAlias = null;
        foreach ($segments as $index => $segment) {
            $entityReflection = new \ReflectionClass($currentEntityClass);
            if (!$entityReflection->hasProperty($segment)) {
                throw new InvalidArgumentException("Property '{$segment}' does not exist in entity '{$currentEntityClass}'");
            }
            $property = $entityReflection->getProperty($segment);
            $propertyType = $property->getType();
            if ($propertyType === null || !is_subclass_of($propertyType->getName(), BaseEntity::class)) {
                throw new InvalidArgumentException("Property '{$segment}' in '{$currentEntityClass}' is not a foreign key relationship");
            }
            $relatedEntityClass = $propertyType->getName();
            $alias = implode('_', array_slice($segments, 0, $index + 1));
            if ($parentAlias === null) {
                $query->appendJoinOnForeignKey($joinType, $segment, $relatedEntityClass);
            } else {
                $foreignKeyColumn = $query->getAdapter()->escapeColumn($segment, true);
                $onCondition = $parentAlias . '.' . $foreignKeyColumn . ' = ' . $alias . '.id';
                $query->appendJoin(
                    $joinType,
                    $relatedEntityClass,
                    $alias,
                    $onCondition,
                    $relatedEntityClass
                );
            }
            $joinedColumns = $query->getAdapter()->buildJoinedColumns($alias, $relatedEntityClass);
            foreach ($joinedColumns as $column) {
                $query->appendColumn($column);
            }
            $currentEntityClass = $relatedEntityClass;
            $parentAlias = $alias;
        }
    }

    protected function isCollectionRelation(string $relationName): bool
    {
        if (!is_subclass_of($this->entityName, ReferencedEntity::class)) {
            return false;
        }

        $tempEntity = new $this->entityName();
        if ($tempEntity instanceof ReferencedEntity) {
            return $tempEntity->checkCollectionExists($relationName);
        }

        return false;
    }

    protected function eagerLoadCollections(SismaCollection $entities, array $collectionNames): void
    {
        if ($entities->count() === 0) {
            return;
        }

        $entityIds = [];
        foreach ($entities as $entity) {
            if (isset($entity->id)) {
                $entityIds[] = $entity->id;
            }
        }

        if (empty($entityIds)) {
            return;
        }

        foreach ($collectionNames as $collectionName) {
            $this->loadCollectionForEntities($entities, $collectionName, $entityIds);
        }
    }

    protected function loadCollectionForEntities(SismaCollection $entities, string $collectionName, array $entityIds): void
    {
        $firstEntity = $entities[0];
        $collectionDataClass = $firstEntity->getCollectionDataInformation($collectionName);
        $foreignKeyName = $firstEntity->getForeignKeyName($collectionName);

        $childModelName = str_replace('Entities', 'Models', $collectionDataClass) . 'Model';
        $childModel = new $childModelName($this->dataMapper);

        $query = $childModel->initQuery();
        $query->setWhere();
        $query->appendCondition($foreignKeyName, ComparisonOperator::in, array_fill(0, count($entityIds), Placeholder::placeholder), true);

        $bindValues = $entityIds;
        $bindTypes = array_fill(0, count($entityIds), DataType::typeInteger);
        $query->close();

        $allChildren = $this->dataMapper->find($collectionDataClass, $query, $bindValues, $bindTypes);

        $groupedChildren = [];
        foreach ($allChildren as $child) {
            $parentId = $child->$foreignKeyName;
            if ($parentId instanceof BaseEntity && isset($parentId->id)) {
                $parentId = $parentId->id;
            }

            if (!isset($groupedChildren[$parentId])) {
                $groupedChildren[$parentId] = new SismaCollection($collectionDataClass);
            }
            $groupedChildren[$parentId]->append($child);
        }

        foreach ($entities as $entity) {
            if (isset($entity->id) && isset($groupedChildren[$entity->id])) {
                $entity->setEntityCollection($collectionName, $groupedChildren[$entity->id]);
            } else {
                $entity->setEntityCollection($collectionName, new SismaCollection($collectionDataClass));
            }
        }
    }

    public function getEntityByIdWithRelations(int $id, array $relations, JoinType $joinType = JoinType::left): ?BaseEntity
    {
        if (Cache::checkEntityPresenceInCache($this->entityName, $id)) {
            $entity = Cache::getEntityById($this->entityName, $id);

            foreach ($relations as $relation) {
                if (!isset($entity->$relation) || $entity->$relation === null) {
                    return $this->fetchEntityByIdWithRelations($id, $relations, $joinType);
                }
            }

            return $entity;
        }

        return $this->fetchEntityByIdWithRelations($id, $relations, $joinType);
    }

    protected function fetchEntityByIdWithRelations(int $id, array $relations, JoinType $joinType): ?BaseEntity
    {
        $query = $this->initQuery();

        foreach ($relations as $relation) {
            $this->appendRelationJoin($query, $relation, $joinType);
        }

        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::equal, Placeholder::placeholder);
        $bindValues = [$id];
        $bindTypes = [DataType::typeInteger];
        $query->close();

        $results = $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);

        return $results->count() > 0 ? $results[0] : null;
    }

    protected function appendRelationJoin(Query &$query, string $foreignKeyProperty, JoinType $joinType): void
    {
        $entityReflection = new \ReflectionClass($this->entityName);

        if (!$entityReflection->hasProperty($foreignKeyProperty)) {
            throw new InvalidArgumentException("Property '{$foreignKeyProperty}' does not exist in entity '{$this->entityName}'");
        }

        $property = $entityReflection->getProperty($foreignKeyProperty);
        $propertyType = $property->getType();

        if ($propertyType === null || !is_subclass_of($propertyType->getName(), BaseEntity::class)) {
            throw new InvalidArgumentException("Property '{$foreignKeyProperty}' is not a foreign key relationship");
        }

        $relatedEntityClass = $propertyType->getName();

        if ($relatedEntityClass === $this->entityName) {
            $this->appendSelfReferencedJoin($query, $foreignKeyProperty, $joinType);
        } else {
            $query->appendJoinOnForeignKey($joinType, $foreignKeyProperty, $relatedEntityClass);

            $joinedColumns = $query->getAdapter()->buildJoinedColumns($foreignKeyProperty, $relatedEntityClass);
            foreach ($joinedColumns as $column) {
                $query->appendColumn($column);
            }
        }
    }

    protected function appendSelfReferencedJoin(Query &$query, string $foreignKeyProperty, JoinType $joinType): void
    {
        $query->appendJoinOnForeignKey($joinType, $foreignKeyProperty, $this->entityName);

        $joinedColumns = $query->getAdapter()->buildJoinedColumns($foreignKeyProperty, $this->entityName);
        foreach ($joinedColumns as $column) {
            $query->appendColumn($column);
        }
    }

    abstract protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void;

    public function getOtherEntityCollection(BaseEntity $excludedEntity): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::notEqual, Placeholder::placeholder);
        $bindValues = [
            $excludedEntity,
        ];
        $bindTypes = [
            DataType::typeEntity,
        ];
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    public function convertArrayIntoEntityCollection(array $entitiesId): SismaCollection
    {
        $collection = new SismaCollection($this->entityName);
        foreach ($entitiesId as $entityId) {
            $collection->append($this->getEntityById($entityId));
        }
        return $collection;
    }

    public function getEntityById(int $id): ?BaseEntity
    {
        if ($this->config->ormCache && Cache::checkEntityPresenceInCache($this->entityName, $id)) {
            return Cache::getEntityById($this->entityName, $id);
        } else {
            $query = $this->initQuery();
            $query->setWhere();
            $query->appendCondition('id', ComparisonOperator::equal, Placeholder::placeholder);
            $query->close();
            $entity = $this->dataMapper->findFirst($this->entityName, $query, [
                $id,
                    ], [
                DataType::typeInteger,
            ]);
            return $entity;
        }
    }

    public function deleteEntityById(int $id): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $query->appendCondition('id', ComparisonOperator::equal, Placeholder::placeholder);
        $query->close();
        return $this->dataMapper->deleteBatch($query, [
                    $id,
                        ], [
                    DataType::typeInteger,
        ]);
    }

    public function findSingleColumn(string $propertyName, string $columnName, bool $isForeignKey): ?BaseEntity
    {
        $query = $this->initQuery();
        $query->setColumn($columnName . (($isForeignKey) ? 'Id' : null))
                ->setLimit(1);
        $this->dataMapper->setOrmCacheStatus(false);
        $result = $this->dataMapper->findFirst($propertyName, $query);
        $this->dataMapper->setOrmCacheStatus($this->config->ormCache);
        return $result;
    }

    public function __call($name, $arguments): SismaCollection|int|bool
    {
        $nameParts = explode('By', $name);
        $sismaCollectionParts = array_filter(preg_split('/(?=[A-Z])/', $nameParts[0]));
        $action = array_shift($sismaCollectionParts);
        $properties = [];
        $propertyNames = array_map('lcfirst', explode('And', $nameParts[1]));
        $this->buildPropertiesArray($propertyNames, $arguments, $properties);
        switch ($action) {
            case 'count':
                return $this->countEntityCollectionByProperties($properties, ...$arguments);
            case 'get':
                return $this->getEntityCollectionByProperties($properties, ...$arguments);
            case 'delete':
                return $this->deleteEntityCollectionByProperties($properties, ...$arguments);
            default:
                throw new ModelException($name);
        }
    }

    protected function buildPropertiesArray(array $propertyNames, array &$arguments, array &$properties): void
    {
        foreach ($propertyNames as $propertyName) {
            $propertyValue = array_shift($arguments);
            $reflectionProperty = new \ReflectionProperty($this->entityName, $propertyName);
            $propertyType = $reflectionProperty->getType();
            if (($propertyType->allowsNull() && ($propertyValue === null)) || ($this->isVariableOfType($propertyValue, $propertyType))) {
                $properties[$propertyName] = $propertyValue;
            } else {
                throw new InvalidArgumentException($propertyName);
            }
        }
    }

    protected function isVariableOfType(mixed $propertyValue, \ReflectionNamedType $reflectionType): bool
    {
        $typeName = $reflectionType->getName();
        if ($reflectionType->isBuiltin()) {
            return match ($typeName) {
                'bool' => is_bool($propertyValue),
                'int' => is_int($propertyValue),
                'float' => is_float($propertyValue),
                'string' => is_string($propertyValue),
                default => false,
            };
        } elseif (is_object($propertyValue)) {
            return $propertyValue instanceof $typeName;
        } else {
            return false;
        }
    }

    protected function countEntityCollectionByProperties(array $properties, ?string $searchKey = null): int
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $properties, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    protected function buildPropertiesConditions(Query $query, array $properties, array &$bindValues, array &$bindTypes): void
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if ($propertyValue === null) {
                $query->appendCondition($propertyName, ComparisonOperator::isNull, '');
            } else {
                $query->appendCondition($propertyName, ComparisonOperator::equal, Placeholder::placeholder);
                $reflectionNamedType = new \ReflectionProperty($this->entityName, $propertyName);
                $bindValues[] = $propertyValue;
                $bindTypes[] = DataType::fromReflection($reflectionNamedType->getType(), $propertyValue);
            }
            if ($propertyName !== array_key_last($properties)) {
                $query->appendAnd();
            }
        }
    }

    public function getEntityCollectionByProperties(array $properties, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $properties, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->setOrderBy($order);
        if ($offset !== null) {
            $query->setOffset($offset);
        }
        if ($limit != null) {
            $query->setLimit($limit);
        }
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    public function deleteEntityCollectionByProperties(array $properties, ?string $searchKey = null): bool
    {
        $query = $this->initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        $this->buildPropertiesConditions($query, $properties, $bindValues, $bindTypes);
        if ($searchKey !== null) {
            $query->appendAnd();
            $this->appendSearchCondition($query, $searchKey, $bindValues, $bindTypes);
        }
        $query->close();
        return $this->dataMapper->deleteBatch($query, $bindValues, $bindTypes);
    }
}

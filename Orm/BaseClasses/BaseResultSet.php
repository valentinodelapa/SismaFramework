<?php

/*
 * Questo file contiene codice derivato dalla libreria SimpleORM
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
 * MODIFICHE APPORTATE A QUESTO FILE RISPETTO AL CODICE ORIGINALE DI SIMPLEORM:
 * - Significativa riorganizzazione e rifattorizzazione per l'applicazione della tipizzazione forte.
 * - Sostituzione delle costanti di classe con enum (PHP 8.1+).
 * - Modifica del namespace per l'integrazione nel SismaFramework.
 * - Miglioramento della nomenclatura di metodi, parametri e variabili per maggiore chiarezza.
 * - Implementazione dell'interfaccia `\Iterator` per consentire l'iterazione tramite `foreach`.
 * - Introduzione del metodo `convertToBaseEntity()` per convertire una riga di dati in un oggetto `BaseEntity`.
 * - Introduzione del supporto JOIN SQL con hydration gerarchica multi-entità (v10.1.0):
 *   * Aggiunta di proprietà $joinMetadata per tracciare i metadati delle tabelle joined
 *   * Modifica di convertToHierarchicalEntity() per separazione dati entità principali/nested
 *   * Aggiunta di hydrateNestedEntities() per hydration ricorsiva di relazioni multi-livello
 *   * Aggiunta di getEntityClassForAlias() per risoluzione entity class da alias JOIN
 *   * Integrazione con Cache per Identity Map pattern su entità nested
 *   * Supporto per navigazione gerarchica delle relazioni tramite alias path
 */

namespace SismaFramework\Orm\BaseClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;

/**
 * @author Valentino de Lapa
 */
abstract class BaseResultSet implements \Iterator
{
    protected const COLUMN_SEPARATOR = '__';

    protected string $returnType = StandardEntity::class;
    protected int $currentRecord = 0;
    protected int $maxRecord = -1;
    protected array $joinMetadata = [];

    public function __construct()
    {
        $this->maxRecord = $this->numRows() - 1;
    }

    abstract public function numRows(): int;

    public function setReturnType(string $type): void
    {
        $this->returnType = strval($type);
    }

    public function setJoinMetadata(array $joinMetadata): void
    {
        $this->joinMetadata = $joinMetadata;
    }

    public function release(): void
    {
        $this->maxRecord = -1;
        $this->currentRecord = 0;
    }

    abstract public function fetch(bool $autoNext = true): null|StandardEntity|BaseEntity;

    public function seek(int $recordIndex): void
    {
        if ($recordIndex < 0) {
            $recordIndex = 0;
        } elseif ($recordIndex > $this->maxRecord) {
            $recordIndex = $this->maxRecord;
        }
        $this->currentRecord = $recordIndex;
    }

    public function current(): null|StandardEntity|BaseEntity
    {
        return $this->fetch(false);
    }

    public function next(): void
    {
        $this->currentRecord++;
    }

    public function key(): int
    {
        return $this->currentRecord;
    }

    public function rewind(): void
    {
        $this->currentRecord = 0;
    }

    public function valid(): bool
    {
        if (($this->currentRecord >= 0) && ($this->currentRecord <= $this->maxRecord)) {
            return true;
        } else {
            return false;
        }
    }

    protected function hydrate(\stdClass &$result): StandardEntity|BaseEntity
    {
        if ($this->returnType == StandardEntity::class) {
            return $this->convertToStandardEntity($result);
        } elseif (!empty($this->joinMetadata)) {
            return $this->convertToHierarchicalEntity($result);
        } else {
            return $this->convertToBaseEntity($result);
        }
    }

    private function convertToHierarchicalEntity(\stdClass $result): BaseEntity
    {
        $mainEntityData = new \stdClass();
        $nestedEntitiesData = [];
        foreach ($result as $columnName => $value) {
            if (str_contains($columnName, static::COLUMN_SEPARATOR)) {
                $parts = explode(static::COLUMN_SEPARATOR, $columnName, 2);
                $aliasPath = $parts[0];
                $childProperty = $parts[1];
                if (!isset($nestedEntitiesData[$aliasPath])) {
                    $nestedEntitiesData[$aliasPath] = new \stdClass();
                }
                $nestedEntitiesData[$aliasPath]->$childProperty = $value;
            } else {
                $mainEntityData->$columnName = $value;
            }
        }
        $mainEntity = $this->convertToBaseEntity($mainEntityData);
        $this->hydrateNestedEntities($mainEntity, $nestedEntitiesData);
        return $mainEntity;
    }

    private function hydrateNestedEntities(BaseEntity $parentEntity, array $nestedEntitiesData): void
    {
        $hydratedEntities = [];
        foreach ($nestedEntitiesData as $aliasPath => $childData) {
            if (!isset($childData->id) || $childData->id === null) {
                continue;
            }
            $segments = explode('_', $aliasPath);
            $entityClass = $this->getEntityClassForAlias($aliasPath);
            if ($entityClass === null) {
                continue;
            }
            $entityId = (int) $childData->id;
            if (Cache::checkEntityPresenceInCache($entityClass, $entityId)) {
                $relatedEntity = Cache::getEntityById($entityClass, $entityId);
            } else {
                $relatedEntity = $this->hydrateRelatedEntity($childData, $entityClass);
                Cache::setEntity($relatedEntity);
            }
            $hydratedEntities[$aliasPath] = $relatedEntity;
        }
        foreach ($hydratedEntities as $aliasPath => $relatedEntity) {
            $segments = explode('_', $aliasPath);
            if (count($segments) === 1) {
                $parentEntity->{$segments[0]} = $relatedEntity;
            } else {
                $currentEntity = $parentEntity;
                for ($i = 0; $i < count($segments) - 1; $i++) {
                    $propertyName = $segments[$i];
                    if (!isset($currentEntity->$propertyName)) {
                        break;
                    }
                    $currentEntity = $currentEntity->$propertyName;
                }
                if ($currentEntity !== null) {
                    $lastSegment = $segments[count($segments) - 1];
                    $currentEntity->$lastSegment = $relatedEntity;
                }
            }
        }
    }
    private function getEntityClassForAlias(string $alias): ?string
    {
        foreach ($this->joinMetadata as $join) {
            if (isset($join['alias']) && $join['alias'] === $alias) {
                return $join['relatedEntityClass'] ?? $join['entityClass'] ?? null;
            }
        }
        return null;
    }

    private function hydrateRelatedEntity(\stdClass $data, string $entityClass): BaseEntity
    {
        $entity = new $entityClass();

        foreach ($data as $property => $value) {
            $property = NotationManager::convertColumnNameToPropertyName($property);
            if (property_exists($entity, $property)) {
                $reflectionProperty = new \ReflectionProperty($entityClass, $property);
                $reflectionType = $reflectionProperty->getType();

                $initializationVectorColumnName = NotationManager::convertToSnakeCase($entity->getInitializationVectorPropertyName());
                if ($entity->isEncryptedProperty($property) && isset($data->$initializationVectorColumnName) && ($data->$initializationVectorColumnName !== null)) {
                    $value = Encryptor::decryptString($value, $data->$initializationVectorColumnName) ?: $value;
                }

                $entity->$property = Parser::parseValue($reflectionType, $value, false);
            }
        }

        $entity->modified = false;
        return $entity;
    }

    private function getJoinInfoByProperty(string $foreignKeyProperty): ?array
    {
        foreach ($this->joinMetadata as $join) {
            if ($join['foreignKeyProperty'] === $foreignKeyProperty) {
                return $join;
            }
        }

        return null;
    }

    private function convertToStandardEntity(\stdClass $standardClass): StandardEntity
    {
        $standardEntity = new StandardEntity();
        foreach ($standardClass as $property => $value) {
            $standardEntity->$property = $value;
        }
        return $standardEntity;
    }

    private function convertToBaseEntity(\stdClass $standardClass): BaseEntity
    {
        $class = $this->returnType;
        $entity = new $class();
        foreach ($standardClass as $property => $value) {
            $property = NotationManager::convertColumnNameToPropertyName($property);
            if (property_exists($entity, $property)) {
                $reflectionProperty = new \ReflectionProperty($class, $property);
                $reflectionType = $reflectionProperty->getType();
                $initializationVectorColumnName = NotationManager::convertToSnakeCase($entity->getInitializationVectorPropertyName());
                if ($entity->isEncryptedProperty($property) && ($standardClass->$initializationVectorColumnName !== null)) {
                    $value = Encryptor::decryptString($value, $standardClass->$initializationVectorColumnName) ?: $value;
                }
                $entity->$property = Parser::parseValue($reflectionType, $value, false);
            }
        }
        $entity->modified = false;
        return $entity;
    }
}

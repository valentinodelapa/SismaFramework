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

namespace SismaFramework\Core\BaseClasses\BaseForm;

use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\CustomTypes\FormFilterError;
use SismaFramework\Core\CustomTypes\FormFilterErrorCollection;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class FormValidator
{

    private Config $config;
    private DataMapper $dataMapper;
    private FilterManager $filterManager;

    public function __construct(DataMapper $dataMapper, FilterManager $filterManager)
    {
        $this->dataMapper = $dataMapper;
        $this->filterManager = $filterManager;
        $this->config = Config::getInstance();
    }

    public function validate(
        BaseEntity $entity,
        Request $request,
        array $entityFromForm,
        FormFilterError $formFilterError,
        array &$entityToResolve,
        array &$sismaCollectionToResolve
    ): array {
        $entityData = new StandardEntity();
        $filterResult = true;

        if ($entity instanceof ReferencedEntity) {
            $this->parseCollectionProperties($entity, $request, $entityFromForm, $entityData, $formFilterError, $entityToResolve, $sismaCollectionToResolve, $filterResult);
        }

        $this->parseStandardProperties($entity, $request, $entityFromForm, $entityData, $formFilterError, $entityToResolve, $sismaCollectionToResolve, $filterResult);

        return [
            'entityData' => $entityData,
            'filterResult' => $filterResult
        ];
    }

    private function parseCollectionProperties(
        BaseEntity $entity,
        Request $request,
        array $entityFromForm,
        StandardEntity $entityData,
        FormFilterError $formFilterError,
        array &$entityToResolve,
        array &$sismaCollectionToResolve,
        bool &$filterResult
    ): void {
        $entityName = get_class($entity);
        foreach (Cache::getForeignKeyData($entityName) as $propertyName => $propertyData) {
            if (array_key_exists($propertyName . $this->config->foreignKeySuffix, $request->input)) {
                $this->switchFormPropertyType($entity, $propertyName . $this->config->foreignKeySuffix, $request, $entityFromForm, $entityData, $formFilterError, $entityToResolve, $sismaCollectionToResolve, $filterResult);
            } elseif (count($propertyData) > 1) {
                $this->parseCollectionWithMultipleReferencedForeignKey($entity, $propertyName, $propertyData, $request, $entityFromForm, $entityData, $formFilterError, $entityToResolve, $sismaCollectionToResolve, $filterResult);
            } elseif ($this->isSelfReferencedProperty($entity, $propertyData)) {
                $this->switchFormPropertyType($entity, $this->config->sonCollectionPropertyName, $request, $entityFromForm, $entityData, $formFilterError, $entityToResolve, $sismaCollectionToResolve, $filterResult);
            }
        }
    }

    private function switchFormPropertyType(
        BaseEntity $entity,
        string $propertyName,
        Request $request,
        array $entityFromForm,
        StandardEntity $entityData,
        FormFilterError $formFilterError,
        array &$entityToResolve,
        array &$sismaCollectionToResolve,
        bool &$filterResult
    ): void {
        $entityData->{$propertyName} = new StandardEntity();
        if (($entity instanceof ReferencedEntity) && ($entity->checkCollectionExists($propertyName))) {
            $this->switchFormOfCollection($propertyName, $request, $entityFromForm, $entityData, $formFilterError, $sismaCollectionToResolve, $filterResult);
        } else {
            $currentForm = $entityFromForm[$propertyName];
            $entityData->{$propertyName} = $this->switchForm($currentForm, $filterResult);
            $formFilterError->$propertyName = $currentForm->getFilterErrors();
            array_push($entityToResolve, $propertyName);
        }
    }

    private function switchFormOfCollection(
        string $propertyName,
        Request $request,
        array $entityFromForm,
        StandardEntity $entityData,
        FormFilterError $formFilterError,
        array &$sismaCollectionToResolve,
        bool &$filterResult
    ): void {
        $entityData->{$propertyName} = new SismaCollection(StandardEntity::class);
        if (isset($request->input[$propertyName])) {
            $formFilterError->$propertyName = new FormFilterErrorCollection();
            foreach (array_keys($request->input[$propertyName]) as $key) {
                $currentForm = $entityFromForm[$propertyName][$key];
                $entityData->{$propertyName}[$key] = $this->switchForm($currentForm, $filterResult);
                $formFilterError->$propertyName[$key] = $currentForm->getFilterErrors();
                array_push($sismaCollectionToResolve, $propertyName);
            }
        }
    }

    private function switchForm(BaseForm $entityFromForm, bool &$filterResult): StandardEntity
    {
        if ($entityFromForm->isValid() === false) {
            $filterResult = false;
        }
        return $entityFromForm->getEntityDataToStandardEntity();
    }

    private function parseCollectionWithMultipleReferencedForeignKey(
        BaseEntity $entity,
        string $propertyName,
        array $propertyData,
        Request $request,
        array $entityFromForm,
        StandardEntity $entityData,
        FormFilterError $formFilterError,
        array &$entityToResolve,
        array &$sismaCollectionToResolve,
        bool &$filterResult
    ): void {
        foreach (array_keys($propertyData) as $foreignKeyPropertyName) {
            $parsedForeignKeyPropertyName = ucfirst($foreignKeyPropertyName);
            if (array_key_exists($propertyName . $this->config->foreignKeySuffix . $parsedForeignKeyPropertyName, $request->input)) {
                $this->switchFormPropertyType($entity, $propertyName . $this->config->foreignKeySuffix . $parsedForeignKeyPropertyName, $request, $entityFromForm, $entityData, $formFilterError, $entityToResolve, $sismaCollectionToResolve, $filterResult);
            }
        }
    }

    private function parseStandardProperties(
        BaseEntity $entity,
        Request $request,
        array $entityFromForm,
        StandardEntity $entityData,
        FormFilterError $formFilterError,
        array &$entityToResolve,
        array &$sismaCollectionToResolve,
        bool &$filterResult
    ): void {
        $reflectionEntity = new \ReflectionClass($entity);
        $reflectionProperties = $reflectionEntity->getProperties();
        foreach ($reflectionProperties as $property) {
            if (array_key_exists($property->name, $entityFromForm)) {
                $this->switchFormPropertyType($entity, $property->name, $request, $entityFromForm, $entityData, $formFilterError, $entityToResolve, $sismaCollectionToResolve, $filterResult);
            } elseif (BaseEntity::checkFinalClassReflectionProperty($property) && $this->isNotPrimaryKeyOrPassIsActive($entity, $property) && $this->filterManager->hasFilter($property->name)) {
                $this->parseSingleStandardProperty($property, $request, $entityData);
                $this->applyFilter($property->name, $entityData, $formFilterError, $filterResult);
            }
        }
    }

    private function isNotPrimaryKeyOrPassIsActive(BaseEntity $entity, \ReflectionProperty $property): bool
    {
        return (($entity->isPrimaryKey($property->name) === false) || ($this->config->primaryKeyPassAccepted));
    }

    private function parseSingleStandardProperty(\ReflectionProperty $property, Request $request, StandardEntity $entityData): void
    {
        if (array_key_exists($property->name, $request->input) && ($request->input[$property->name] !== '')) {
            $reflectionType = $property->getType();
            $entityData->{$property->name} = Parser::parseValue($reflectionType, $request->input[$property->name], true, $this->dataMapper);
        } elseif (array_key_exists($property->name, $request->files)) {
            $entityData->{$property->name} = $request->files[$property->name];
        } elseif ($this->filterManager->hasFilter($property->name)) {
            if (($property->getType()->getName() === 'bool') && ($property->getType()->allowsNull()) === false) {
                $entityData->{$property->name} = false;
            } elseif ($property->hasDefaultValue()) {
                $entityData->{$property->name} = $property->getDefaultValue();
            } else {
                $entityData->{$property->name} = null;
            }
        }
    }

    private function applyFilter(string $propertyName, StandardEntity $entityData, FormFilterError $formFilterError, bool &$filterResult): void
    {
        if ($this->filterManager->hasFilter($propertyName) && property_exists($entityData, $propertyName)) {
            $customMessagePropertyName = $propertyName . "CustomMessage";
            $formFilterError->$customMessagePropertyName = false;
            $errorPropertyName = $propertyName . "Error";
            if ($this->filterHasFailed($propertyName, $entityData->$propertyName) && ($this->isNullButNotNullable($propertyName, $entityData->$propertyName))) {
                $filterResult = false;
                $formFilterError->$errorPropertyName = true;
            } else {
                $formFilterError->$errorPropertyName = false;
            }
        }
    }

    private function filterHasFailed(string $propertyName, mixed $value): bool
    {
        return !$this->filterManager->applyFilter($propertyName, $value);
    }

    private function isNullButNotNullable(string $propertyName, mixed $value): bool
    {
        if ($value === null) {
            return !$this->filterManager->isNullable($propertyName);
        } else {
            return true;
        }
    }

    private function isSelfReferencedProperty(BaseEntity $entity, array $propertyData): bool
    {
        $foreignKeyPropertyName = array_key_first($propertyData);
        $referentEntity = $propertyData[$foreignKeyPropertyName];
        if (str_contains($foreignKeyPropertyName, $this->config->parentPrefixPropertyName) && ($referentEntity === get_class($entity))) {
            return true;
        } else {
            return false;
        }
    }
}

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
    private BaseEntity $entity;
    private Request $request;
    private array $entityFromForm;
    private StandardEntity $entityData;
    private FormFilterError $formFilterError;
    private array $entityToResolve;
    private array $sismaCollectionToResolve;
    private bool $filterResult;

    public function __construct(DataMapper $dataMapper, FilterManager $filterManager, ?Config $config = null)
    {
        $this->dataMapper = $dataMapper;
        $this->filterManager = $filterManager;
        $this->config = $config ?? Config::getInstance();
    }

    public function validate(
        BaseEntity $entity,
        Request $request,
        array $entityFromForm,
        FormFilterError $formFilterError,
        array &$entityToResolve,
        array &$sismaCollectionToResolve
    ): array {
        $this->entity = $entity;
        $this->request = $request;
        $this->entityFromForm = $entityFromForm;
        $this->formFilterError = $formFilterError;
        $this->entityToResolve = &$entityToResolve;
        $this->sismaCollectionToResolve = &$sismaCollectionToResolve;
        $this->entityData = new StandardEntity();
        $this->filterResult = true;
        if ($this->entity instanceof ReferencedEntity) {
            $this->parseCollectionProperties();
        }
        $this->parseStandardProperties();
        return [
            'entityData' => $this->entityData,
            'filterResult' => $this->filterResult
        ];
    }

    private function parseCollectionProperties(): void
    {
        $entityName = get_class($this->entity);
        foreach (Cache::getForeignKeyData($entityName) as $propertyName => $propertyData) {
            if (array_key_exists($propertyName . $this->config->foreignKeySuffix, $this->request->input)) {
                $this->switchFormPropertyType($propertyName . $this->config->foreignKeySuffix);
            } elseif (count($propertyData) > 1) {
                $this->parseCollectionWithMultipleReferencedForeignKey($propertyName, $propertyData);
            } elseif ($this->isSelfReferencedProperty($propertyData)) {
                $this->switchFormPropertyType($this->config->sonCollectionPropertyName);
            }
        }
    }

    private function switchFormPropertyType(string $propertyName): void
    {
        $this->entityData->{$propertyName} = new StandardEntity();
        if (($this->entity instanceof ReferencedEntity) && ($this->entity->checkCollectionExists($propertyName))) {
            $this->switchFormOfCollection($propertyName);
        } else {
            $currentForm = $this->entityFromForm[$propertyName];
            $this->entityData->{$propertyName} = $this->switchForm($currentForm);
            $this->formFilterError->$propertyName = $currentForm->getFilterErrors();
            array_push($this->entityToResolve, $propertyName);
        }
    }

    private function switchFormOfCollection(string $propertyName): void
    {
        $this->entityData->{$propertyName} = new SismaCollection(StandardEntity::class);
        if (isset($this->request->input[$propertyName])) {
            $this->formFilterError->$propertyName = new FormFilterErrorCollection();
            foreach (array_keys($this->request->input[$propertyName]) as $key) {
                $currentForm = $this->entityFromForm[$propertyName][$key];
                $this->entityData->{$propertyName}[$key] = $this->switchForm($currentForm);
                $this->formFilterError->$propertyName[$key] = $currentForm->getFilterErrors();
                array_push($this->sismaCollectionToResolve, $propertyName);
            }
        }
    }

    private function switchForm(BaseForm $entityFromForm): StandardEntity
    {
        if ($entityFromForm->isValid() === false) {
            $this->filterResult = false;
        }
        return $entityFromForm->getEntityDataToStandardEntity();
    }

    private function parseCollectionWithMultipleReferencedForeignKey(string $propertyName, array $propertyData): void
    {
        foreach (array_keys($propertyData) as $foreignKeyPropertyName) {
            $parsedForeignKeyPropertyName = ucfirst($foreignKeyPropertyName);
            if (array_key_exists($propertyName . $this->config->foreignKeySuffix . $parsedForeignKeyPropertyName, $this->request->input)) {
                $this->switchFormPropertyType($propertyName . $this->config->foreignKeySuffix . $parsedForeignKeyPropertyName);
            }
        }
    }

    private function isSelfReferencedProperty(array $propertyData): bool
    {
        $foreignKeyPropertyName = array_key_first($propertyData);
        $referentEntity = $propertyData[$foreignKeyPropertyName];
        if (str_contains($foreignKeyPropertyName, $this->config->parentPrefixPropertyName) && ($referentEntity === get_class($this->entity))) {
            return true;
        } else {
            return false;
        }
    }

    private function parseStandardProperties(): void
    {
        $reflectionEntity = new \ReflectionClass($this->entity);
        $reflectionProperties = $reflectionEntity->getProperties();
        foreach ($reflectionProperties as $property) {
            if (array_key_exists($property->name, $this->entityFromForm)) {
                $this->switchFormPropertyType($property->name);
            } elseif (BaseEntity::checkFinalClassReflectionProperty($property) && $this->isNotPrimaryKeyOrPassIsActive($property) && $this->filterManager->hasFilter($property->name)) {
                $this->parseSingleStandardProperty($property);
                $this->applyFilter($property->name);
            }
        }
    }

    private function isNotPrimaryKeyOrPassIsActive(\ReflectionProperty $property): bool
    {
        return (($this->entity->isPrimaryKey($property->name) === false) || ($this->config->primaryKeyPassAccepted));
    }

    private function parseSingleStandardProperty(\ReflectionProperty $property): void
    {
        if (array_key_exists($property->name, $this->request->input) && ($this->request->input[$property->name] !== '')) {
            $reflectionType = $property->getType();
            $this->entityData->{$property->name} = Parser::parseValue($reflectionType, $this->request->input[$property->name], true, $this->dataMapper);
        } elseif (array_key_exists($property->name, $this->request->files)) {
            $this->entityData->{$property->name} = $this->request->files[$property->name];
        } elseif ($this->filterManager->hasFilter($property->name)) {
            if (($property->getType()->getName() === 'bool') && ($property->getType()->allowsNull()) === false) {
                $this->entityData->{$property->name} = false;
            } elseif ($property->hasDefaultValue()) {
                $this->entityData->{$property->name} = $property->getDefaultValue();
            } else {
                $this->entityData->{$property->name} = null;
            }
        }
    }

    private function applyFilter(string $propertyName): void
    {
        if ($this->filterManager->hasFilter($propertyName) && property_exists($this->entityData, $propertyName)) {
            $customMessagePropertyName = $propertyName . "CustomMessage";
            $this->formFilterError->$customMessagePropertyName = false;
            $errorPropertyName = $propertyName . "Error";
            if ($this->filterHasFailed($propertyName) && ($this->isNullButNotNullable($propertyName))) {
                $this->filterResult = false;
                $this->formFilterError->$errorPropertyName = true;
            } else {
                $this->formFilterError->$errorPropertyName = false;
            }
        }
    }

    private function filterHasFailed(string $propertyName): bool
    {
        return !$this->filterManager->applyFilter($propertyName, $this->entityData->$propertyName);
    }

    private function isNullButNotNullable(string $propertyName): bool
    {
        if ($this->entityData->$propertyName === null) {
            return !$this->filterManager->isNullable($propertyName);
        } else {
            return true;
        }
    }
}

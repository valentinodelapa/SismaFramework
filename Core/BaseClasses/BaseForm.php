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

namespace SismaFramework\Core\BaseClasses;

use SismaFramework\Core\AbstractClasses\Submittable;
use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\CustomTypes\FormFilterErrorCollection;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Core\Exceptions\FormException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;
use SismaFramework\Orm\HelperClasses\Cache;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 *
 * @author Valentino de Lapa
 */
abstract class BaseForm extends Submittable
{

    protected bool $filterResult = true;
    protected BaseEntity $entity;
    protected StandardEntity $entityData;
    protected array $entityFromForm = [];
    protected array $filterFiledsMode = [];
    private DataMapper $dataMapper;
    private array $entityToResolve = [];
    private array $sismaCollectionToResolve = [];
    private static bool $primaryKeyPassAccepted = \Config\PRIMARY_KEY_PASS_ACCEPTED;

    public function __construct(?BaseEntity $baseEntity = null, DataMapper $dataMapper = new DataMapper())
    {
        parent::__construct();
        $this->dataMapper = $dataMapper;
        $this->checkEntityName();
        $this->embedEntity($baseEntity);
    }

    private function checkEntityName()
    {
        if (is_subclass_of(static::getEntityName(), BaseEntity::class) === false) {
            throw new FormException();
        }
    }

    abstract protected static function getEntityName(): string;

    private function embedEntity(?BaseEntity $baseEntity): void
    {
        $entityClassName = static::getEntityName();
        if ($baseEntity instanceof $entityClassName) {
            $this->entity = $baseEntity;
        } elseif ($baseEntity === null) {
            $this->entity = new $entityClassName($this->dataMapper);
        } else {
            throw new InvalidArgumentException();
        }
    }

    public function handleRequest(Request $request): void
    {
        $this->request = $request;
        $this->injectRequest();
        $this->setFilterFieldsMode();
        $this->setEntityFromForm();
        $this->formFilterError->generateFormFilterErrorFromForm($this->entityFromForm);
    }

    abstract protected function injectRequest(): void;

    abstract protected function setFilterFieldsMode(): void;

    protected function addFilterFieldMode(string $propertyName, FilterType $filterType, array $parameters = [], bool $allowNull = false): self
    {
        $this->filterFiledsMode[$propertyName] = [
            'filterType' => $filterType,
            'parameters' => $parameters,
            'allowNull' => $allowNull,
        ];
        return $this;
    }

    abstract protected function setEntityFromForm(): void;

    protected function addEntityFromForm(string $propertyName, string $formPropertyClass, bool $embedEntity = true): self
    {
        if (($this->entity instanceof ReferencedEntity) && ($this->entity->checkCollectionExists($propertyName))) {
            $this->addEntityCollectionFromForm($propertyName, $formPropertyClass, $embedEntity);
        } elseif (property_exists($this->entity, $propertyName)) {
            $this->addEntityFromFormViaForeignKey($propertyName, $formPropertyClass, $embedEntity);
        } else {
            throw new FormException($propertyName . ' - ' . $formPropertyClass);
        }
        return $this;
    }

    private function addEntityCollectionFromForm(string $propertyName, string $formPropertyClass, bool $embedEntity = true): void
    {
        if ($this->entity->getCollectionDataInformation($propertyName) === $formPropertyClass::getEntityName()) {
            if ($embedEntity) {
                $entityCollectonToEmbed = $this->entity->$propertyName ?? null;
            } else {
                $entityCollectonToEmbed = null;
            }
            if (isset($this->request->request[$propertyName])) {
                $sismaCollectionPropertyKeys = array_keys($this->request->request[$propertyName]);
            } else {
                $sismaCollectionPropertyKeys = $this->getBaseCollectionFormKeys(count($this->entity->{$propertyName}));
            }
            $this->entityFromForm[$propertyName] = $this->generateSismaCollectionProperty($sismaCollectionPropertyKeys, $formPropertyClass, $entityCollectonToEmbed, $propertyName);
        } else {
            throw new InvalidArgumentException($propertyName);
        }
    }

    private function getBaseCollectionFormKeys(int $baseCollectionFormFromNumber): array
    {
        if ($baseCollectionFormFromNumber === 0) {
            return [];
        } else {
            return range(0, $baseCollectionFormFromNumber - 1);
        }
    }

    private function generateSismaCollectionProperty(array $sismaCollectionPropertyKeys, string $formPropertyClass, ?SismaCollection $entityCollectonToEmbed, string $propertyName): array
    {
        $entityFromForm = [];
        foreach ($sismaCollectionPropertyKeys as $key) {
            $ntityToEmbed = $entityCollectonToEmbed[$key] ?? null;
            $entityFromForm[$key] = $this->generateFormProperty($formPropertyClass, $ntityToEmbed, $this->request->request[$propertyName][$key] ?? []);
        }
        return $entityFromForm;
    }

    private function generateFormProperty(string $formPropertyClass, ?BaseEntity $entityToEmbed, array $currentRequestPart): BaseForm
    {
        $propertyForm = new $formPropertyClass($entityToEmbed, $this->dataMapper);
        $currentRequest = new Request();
        $currentRequest->request = $currentRequestPart;
        $propertyForm->handleRequest($currentRequest);
        return $propertyForm;
    }

    private function addEntityFromFormViaForeignKey(string $propertyName, string $formPropertyClass, bool $embedEntity = true): void
    {
        $reflectionEntity = new \ReflectionClass($this->entity);
        $reflectionEntityProperty = $reflectionEntity->getProperty($propertyName);
        if ($embedEntity) {
            $entityToEmbedded = $this->entity->$propertyName ?? null;
        } else {
            $entityToEmbedded = null;
        }
        if (BaseEntity::checkFinalClassReflectionProperty($reflectionEntityProperty)) {
            if ($reflectionEntityProperty->getType()->getName() === $formPropertyClass::getEntityName()) {
                $this->entityFromForm[$propertyName] = $this->generateFormProperty($formPropertyClass, $entityToEmbedded, $this->request->request[$propertyName] ?? []);
            } else {
                throw new InvalidArgumentException($propertyName);
            }
        }
    }

    public function isValid(): bool
    {
        $this->entityData = new StandardEntity();
        if (is_a($this->entity, ReferencedEntity::class)) {
            $this->parseCollectionProperties();
        }
        $this->parseStandardProperties();
        $this->customFilter();
        Debugger::setFormFilter($this->formFilterError);
        return $this->filterResult;
    }

    private function parseCollectionProperties(): void
    {
        foreach (Cache::getForeignKeyData(static::getEntityName()) as $propertyName => $propertyData) {
            if (array_key_exists($propertyName . ReferencedEntity::FOREIGN_KEY_SUFFIX, $this->request->request)) {
                $this->switchFormPropertyType($propertyName . ReferencedEntity::FOREIGN_KEY_SUFFIX);
            } elseif (count($propertyData) > 1) {
                $this->parseCollectionWithMultipleReferencedForeignKey($propertyName, $propertyData);
            } elseif ($this->isSelfReferencedProperty($propertyData)) {
                $this->switchFormPropertyType(SelfReferencedEntity::SON_COLLECTION_PROPERTY_NAME);
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
        if (isset($this->request->request[$propertyName])) {
            $this->formFilterError->$propertyName = new FormFilterErrorCollection();
            foreach (array_keys($this->request->request[$propertyName]) as $key) {
                $currentForm = $this->entityFromForm[$propertyName][$key];
                $this->entityData->{$propertyName}[$key] = $this->switchForm($currentForm);
                $this->formFilterError->$propertyName[$key] = $currentForm->getFilterErrors();
                array_push($this->sismaCollectionToResolve, $propertyName);
            }
        }
    }

    private function switchForm(self $entityFromForm): StandardEntity
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
            if (array_key_exists($propertyName . ReferencedEntity::FOREIGN_KEY_SUFFIX . $parsedForeignKeyPropertyName, $this->request->request)) {
                $this->switchFormPropertyType($propertyName . ReferencedEntity::FOREIGN_KEY_SUFFIX . $parsedForeignKeyPropertyName);
            }
        }
    }

    private function parseStandardProperties(): void
    {
        $reflectionEntity = new \ReflectionClass($this->entity);
        $reflectionProperties = $reflectionEntity->getProperties();
        foreach ($reflectionProperties as $property) {
            if (array_key_exists($property->name, $this->entityFromForm)) {
                $this->switchFormPropertyType($property->name);
            } elseif (BaseEntity::checkFinalClassReflectionProperty($property) && $this->isNotPrimaryKeyOrPassIsActive($property)) {
                $this->parseSingleStandardProperty($property);
                $this->switchFilter($property->name);
            }
        }
    }

    private function isNotPrimaryKeyOrPassIsActive(\ReflectionProperty $property): bool
    {
        return (($this->entity->isPrimaryKey($property->name) === false) || (self::$primaryKeyPassAccepted));
    }

    private function parseSingleStandardProperty(\ReflectionProperty $property): void
    {
        if (array_key_exists($property->name, $this->request->request) && ($this->request->request[$property->name] !== '')) {
            $reflectionType = $property->getType();
            $this->entityData->{$property->name} = Parser::parseValue($reflectionType, $this->request->request[$property->name], true, $this->dataMapper);
        } elseif (array_key_exists($property->name, $this->request->files)) {
            $this->entityData->{$property->name} = $this->request->files[$property->name];
        } elseif (array_key_exists($property->name, $this->filterFiledsMode)) {
            if (($property->getType()->getName() === 'bool') && ($property->getType()->allowsNull()) === false) {
                $this->entityData->{$property->name} = false;
            } elseif ($property->hasDefaultValue()) {
                $this->entityData->{$property->name} = $property->getDefaultValue();
            } else {
                $this->entityData->{$property->name} = null;
            }
        }
    }

    private function parseFile()
    {
        
    }

    private function switchFilter(string $propertyName): void
    {
        if (array_key_exists($propertyName, $this->filterFiledsMode) && property_exists($this->entityData, $propertyName)) {
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
        $filterFunction = $this->filterFiledsMode[$propertyName]['filterType']->value;
        return !Filter::$filterFunction($this->entityData->$propertyName);
    }

    private function isNullButNotNullable(string $propertyName): bool
    {
        if ($this->entityData->$propertyName === null) {
            return !$this->filterFiledsMode[$propertyName]['allowNull'];
        } else {
            return true;
        }
    }

    private function isSelfReferencedProperty(array $propertyData): bool
    {
        $foreignKeyPropertyName = array_key_first($propertyData);
        $referentEntity = $propertyData[$foreignKeyPropertyName];
        if (str_contains($foreignKeyPropertyName, SelfReferencedEntity::PARENT_PREFIX_PROPERTY_NAME) && ($referentEntity === get_class($this->entity))) {
            return true;
        } else {
            return false;
        }
    }

    abstract protected function customFilter(): void;

    public function resolveEntity(): BaseEntity
    {
        foreach ($this->entityData as $propertyName => $value) {
            if (in_array($propertyName, $this->sismaCollectionToResolve)) {
                $this->resolveSismaCollection($propertyName);
            } elseif (in_array($propertyName, $this->entityToResolve)) {
                $this->resolveEntityByForm($propertyName);
            } else {
                $this->entity->$propertyName = $value;
            }
        }
        return $this->entity;
    }

    private function resolveEntityByForm(string $propertyName): void
    {
        if (isset($this->entityFromForm[$propertyName]->entityData)) {
            $this->entity->$propertyName = $this->entityFromForm[$propertyName]->resolveEntity();
        }
    }

    private function resolveSismaCollection(string $propertyName): void
    {
        foreach ($this->entityFromForm[$propertyName] as $form) {
            if (isset($form->entityData)) {
                $this->entity->addEntityToEntityCollection($propertyName, $form->resolveEntity());
            }
        }
    }

    public function getEntityDataToStandardEntity(): StandardEntity
    {
        if (isset($this->entity->id)) {
            $this->entityData->id = $this->entity->id;
        }
        return $this->entityData ?? new StandardEntity();
    }
}

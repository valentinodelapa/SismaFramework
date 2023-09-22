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

use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\Exceptions\FormException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;
use SismaFramework\Orm\HelperClasses\Cache;

/**
 *
 * @author Valentino de Lapa
 */
abstract class BaseForm
{

    use \SismaFramework\Core\Traits\Submitted;

    protected bool $filterResult = true;
    protected BaseEntity $entity;
    protected Request $request;
    protected StandardEntity $entityData;
    protected array $entityFromForm = [];
    protected array $filterFiledsMode = [];
    protected array $filterErrors = [];
    private array $entityToResolve = [];
    private array $sismaCollectionToResolve = [];

    public function __construct(?BaseEntity $baseEntity = null)
    {
        $this->checkEntityName();
        $this->embedEntity($baseEntity);
        $this->setFilterFieldsMode();
    }
    
    private function checkEntityName()
    {
        if(is_subclass_of($this->getEntityName(), BaseEntity::class) === false){
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
            $this->entity = new $entityClassName();
        } else {
            throw new InvalidArgumentException();
        }
    }

    abstract protected function setFilterFieldsMode(): void;

    public function handleRequest(Request $request): void
    {
        $this->request = $request;
        $this->injectRequest();
        $this->setEntityFromForm();
    }

    abstract protected function injectRequest(): void;

    abstract protected function setEntityFromForm(): void;

    public function isValid(): bool
    {
        $this->entityData = new StandardEntity();
        if (is_a($this->entity, ReferencedEntity::class)) {
            $this->parseCollectionProperties();
        }
        $this->parseStandardProperties();
        $this->customFilter();
        Debugger::setFormFilter($this->filterErrors);
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
        $this->filterErrors[$propertyName . "Error"] = [];
        if ($this->entity->checkCollectionExists($propertyName)) {
            $this->switchFormOfCollection($propertyName);
        } else {
            $currentRequest = new Request();
            $currentRequest->request = $this->request->request[$propertyName] ?? [];
            $currentForm = $this->entityFromForm[$propertyName];
            $this->entityData->{$propertyName} = $this->switchForm($currentForm, $currentRequest);
            $this->filterErrors[$propertyName . "Error"] = $currentForm->returnFilterErrors();
            array_push($this->entityToResolve, $propertyName);
        }
    }

    private function switchFormOfCollection(string $propertyName): void
    {
        $this->entityData->{$propertyName} = new SismaCollection(StandardEntity::class);
        if (isset($this->request->request[$propertyName])) {
            foreach ($this->request->request[$propertyName] as $key => $value) {
                $currentRequest = new Request();
                $currentRequest->request = $value;
                $currentForm = $this->entityFromForm[$propertyName][$key];
                $this->entityData->{$propertyName}[$key] = $this->switchForm($currentForm, $currentRequest);
                $this->filterErrors[$propertyName . "Error"][$key] = $currentForm->returnFilterErrors();
                array_push($this->sismaCollectionToResolve, $propertyName);
            }
        }
    }

    private function switchForm(self $entityFromForm, Request $request): StandardEntity
    {
        $entityFromForm->handleRequest($request);
        if ($entityFromForm->isValid() === false) {
            $this->filterResult = false;
        }
        return $entityFromForm->getEntityDataToStandardEntity();
    }

    private function parseStandardProperties(): void
    {
        $reflectionEntity = new \ReflectionClass($this->entity);
        $reflectionProperties = $reflectionEntity->getProperties();
        foreach ($reflectionProperties as $property) {
            if (array_key_exists($property->name, $this->entityFromForm)) {
                $this->switchFormPropertyType($property->name);
            } elseif ($this->isFieldProperty($property) && $this->isNotPrimaryKeyOrPassIsActive($property)) {
                $this->parseSingleStandardProperty($property);
                $this->switchFilter($property->name);
            }
        }
    }

    private function isFieldProperty(\ReflectionProperty $property): bool
    {
        return ($property->class === get_class($this->entity));
    }

    private function isNotPrimaryKeyOrPassIsActive(\ReflectionProperty $property): bool
    {
        return (($this->entity->isPrimaryKey($property->name) === false) || (\Config\PRIMARY_KEY_PASS_ACCEPTED));
    }

    private function parseSingleStandardProperty(\ReflectionProperty $property): void
    {
        if (array_key_exists($property->name, $this->request->request) && ($this->request->request[$property->name] !== '')) {
            $reflectionType = $property->getType();
            $this->entityData->{$property->name} = Parser::parseValue($reflectionType, $this->request->request[$property->name]);
        } elseif (array_key_exists($property->name, $this->request->files)) {
            $this->entityData->{$property->name} = $this->request->files[$property->name];
        } elseif (array_key_exists($property->name, $this->filterFiledsMode)) {
            if (($property->getType()->getName() === 'bool') && ($property->getType()->allowsNull()) === false) {
                $this->entityData->{$property->name} = false;
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
            if ($this->filterHasFailed($propertyName) && ($this->isNullButNotNullable($propertyName))) {
                $this->filterResult = false;
                $this->filterErrors[$propertyName . "Error"] = true;
            } else {
                $this->filterErrors[$propertyName . "Error"] = false;
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

    private function parseCollectionWithMultipleReferencedForeignKey(string $propertyName, array $propertyData): void
    {
        foreach (array_keys($propertyData) as $foreignKeyPropertyName) {
            $parsedForeignKeyPropertyName = ucfirst($foreignKeyPropertyName);
            if (array_key_exists($propertyName . ReferencedEntity::FOREIGN_KEY_SUFFIX . $parsedForeignKeyPropertyName, $this->request->request)) {
                $this->switchFormPropertyType($propertyName . ReferencedEntity::FOREIGN_KEY_SUFFIX . $parsedForeignKeyPropertyName);
            }
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

    protected function addEntityFromForm(string $propertyName, string $formPropertyClass, int $baseCollectionFormFromNumber = 0): self
    {
        if ($this->entity->checkCollectionExists($propertyName)) {
            $this->addEntityCollectionFromForm($propertyName, $formPropertyClass, $baseCollectionFormFromNumber);
        } elseif (property_exists($this->entity, $propertyName)) {
            $this->addEntityFromFormViaForeignKey($propertyName, $formPropertyClass);
        }
        return $this;
    }

    private function addEntityCollectionFromForm(string $propertyName, string $formPropertyClass, int $baseCollectionFormFromNumber): void
    {
        if ($this->entity->getCollectionDataInformation($propertyName) === $formPropertyClass::getEntityName()) {
            $entityCollectonToEmbed = $this->entity->$propertyName;
            if(isset($this->request->request[$propertyName])){
                $sismaCollectionPropertyKeys = array_keys($this->request->request[$propertyName]);
            }elseif(count($this->entity->{$propertyName}) > $baseCollectionFormFromNumber){
                $sismaCollectionPropertyKeys = $this->getBaseCollectionFormKeys(count($this->entity->{$propertyName}));
            }else{
                $sismaCollectionPropertyKeys = $this->getBaseCollectionFormKeys($baseCollectionFormFromNumber);
            }
            $this->generateSismaCollectionProperty($sismaCollectionPropertyKeys, $formPropertyClass, $entityCollectonToEmbed, $this->entityFromForm[$propertyName], $this->filterErrors[$propertyName . "Error"]);
        } else {
            throw new InvalidArgumentException();
        }
    }

    private function addEntityFromFormViaForeignKey(string $propertyName, string $formPropertyClass): void
    {
        $reflectionEntity = new \ReflectionClass($this->entity);
        $reflectionEntityProperty = $reflectionEntity->getProperty($propertyName);
        if ($reflectionEntityProperty->class === get_class($this->entity)) {
            if ($reflectionEntityProperty->getType()->getName() === $formPropertyClass::getEntityName()) {
                $this->generateFormProperty($formPropertyClass, $this->entity->$propertyName ?? null, $this->entityFromForm[$propertyName], $this->filterErrors[$propertyName . "Error"]);
            } else {
                throw new InvalidArgumentException();
            }
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

    private function generateSismaCollectionProperty(array $sismaCollectionPropertyKeys, string $formPropertyClass, SismaCollection $entityCollectonToEmbed, ?array &$entityFromForm, ?array &$filerErrors): void
    {
        foreach ($sismaCollectionPropertyKeys as $key) {
            $ntityToEmbed = $entityCollectonToEmbed[$key] ?? null;
            $this->generateFormProperty($formPropertyClass, $ntityToEmbed, $entityFromForm[$key], $filerErrors[$key]);
        }
    }

    private function generateFormProperty(string $formPropertyClass, ?BaseEntity $entityToEmbed, ?self &$entityFromForm, ?array &$filterErrors): void
    {
        $entityFromForm = new $formPropertyClass($entityToEmbed);
        $filterErrors = $entityFromForm->returnFilterErrors();
    }

    protected function addFilterFieldMode(string $propertyName, FilterType $filterType, array $parameters = [], bool $allowNull = false): self
    {
        $this->filterFiledsMode[$propertyName] = [
            'filterType' => $filterType,
            'parameters' => $parameters,
            'allowNull' => $allowNull,
        ];
        $this->filterErrors[$propertyName . 'Error'] = false;
        return $this;
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
        if(isset($this->entity->id)){
            $this->entityData->id = $this->entity->id;
        }
        return $this->entityData ?? new StandardEntity();
    }

}

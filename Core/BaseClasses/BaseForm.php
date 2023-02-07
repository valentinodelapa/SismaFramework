<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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
use SismaFramework\Orm\HelperClasses\Cache;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseForm
{

    use \SismaFramework\Core\Traits\Submitted;

    protected const ENTITY_CLASS_NAME = BaseEntity::class;

    private array $sismaCollectionPropertyName = [];
    protected bool $filterResult = true;
    protected BaseEntity $entity;
    protected Request $request;
    protected StandardEntity $entityData;
    protected array $entityFromForm = [];
    protected array $filterFiledsMode = [];
    protected array $filterErrors = [];

    public function __construct(?BaseEntity $baseEntity = null)
    {
        $this->checkEntityClassNameIsOverride();
        $this->embedEntity($baseEntity);
        $this->setFilterFieldsMode();
    }

    private function checkEntityClassNameIsOverride(): void
    {
        if (static::ENTITY_CLASS_NAME === BaseEntity::class) {
            throw new FormException();
        }
    }

    private function embedEntity(?BaseEntity $baseEntity): void
    {
        $entityClassName = static::ENTITY_CLASS_NAME;
        if ($baseEntity instanceof $entityClassName) {
            $this->entity = $baseEntity;
        } elseif ($baseEntity === null) {
            $this->entity = new $entityClassName();
        } else {
            throw new InvalidArgumentException();
        }
    }

    abstract protected function setFilterFieldsMode(): void;

    abstract protected function setEntityFromForm(): void;

    public function handleRequest(Request $request): void
    {
        $this->request = $request;
        $this->injectRequest();
        $this->setEntityFromForm();
    }

    abstract protected function injectRequest(): void;

    public function isValid(): bool
    {
        $this->entityData = new StandardEntity();
        if (is_a($this->entity, ReferencedEntity::class)) {
            foreach (Cache::getForeignKeyData($this->entity) as $propertyName => $propertyType) {
                if (array_key_exists($propertyName . ReferencedEntity::FOREIGN_KEY_SUFFIX, $this->request->request)) {
                    $this->switchFormPropertyType($propertyName . ReferencedEntity::FOREIGN_KEY_SUFFIX);
                }
            }
        }
        $reflectionEntity = new \ReflectionClass($this->entity);
        $reflectionProperties = $reflectionEntity->getProperties();
        foreach ($reflectionProperties as $property) {
            if (($property->class === get_class($this->entity)) && (($this->entity->isPrimaryKey($property->name) === false) ||
                    (\Config\PRIMARY_KEY_PASS_ACCEPTED) && array_key_exists($property->name, $this->request->request) && ($this->request->request[$property->name] !== ''))) {
                $this->parseProperty($property);
                $this->switchFilter($property->name);
            }
        }
        $this->customFilter();
        Debugger::setFormFilter($this->filterErrors);
        return $this->filterResult;
    }

    private function switchFormPropertyType(string $propertyName): void
    {
        $currentRequest = clone $this->request;
        $this->entityData->{$propertyName} = new StandardEntity();
        $this->filterErrors[$propertyName . "Error"] = [];
        if ($this->entity->checkCollectionExists($propertyName)) {
            $this->entityData->{$propertyName} = new SismaCollection(StandardEntity::class);
            foreach ($this->request->request[$propertyName] as $key => $value) {
                $currentRequest->request = $value;
                $this->entityData->{$propertyName}[$key] = new StandardEntity();
                $this->filterErrors[$propertyName . "Error"][$key] = [];
                $this->switchForm($this->entityFromForm[$propertyName][$key], $currentRequest, $this->entityData->{$propertyName}[$key], $this->filterErrors[$propertyName . "Error"][$key]);
                array_push($this->sismaCollectionPropertyName, $propertyName);
            }
        } else {
            $currentRequest->request = $this->request->request[$propertyName];
            $this->switchForm($this->entityFromForm[$propertyName], $currentRequest, $this->entityData->{$propertyName}, $this->filterErrors[$propertyName . "Error"]);
        }
    }

    private function switchForm(self $entityFromForm, Request $request, StandardEntity &$entityData, array &$filterErrors): void
    {
        $entityFromForm->handleRequest($request);
        if ($entityFromForm->isValid()) {
            $entityData = $entityFromForm->resolveEntity();
        } else {
            $entityData = $entityFromForm->getEntityDataToStandardEntity();
            $this->filterResult = false;
        }
        $filterErrors = $entityFromForm->returnFilterErrors();
    }

    private function parseProperty(\ReflectionProperty $property): void
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
            $this->filterErrors[$propertyName . "Error"] = false;
            $filterFunction = $this->filterFiledsMode[$propertyName]['filterType']->value;
            $conditionOne = Filter::$filterFunction($this->entityData->$propertyName);
            $conditionTwo = (($this->filterFiledsMode[$propertyName]['allowNull'] === true) && ($this->entityData->$propertyName === null));
            if (($conditionOne === false) && ($conditionTwo === false)) {
                $this->filterResult = false;
                $this->filterErrors[$propertyName . "Error"] = true;
            }
        }
    }

    protected function addEntityFromForm(string $propertyName, string $formPropertyClass, int $baseCollectionFormFromNumber = 0): self
    {
        if ($this->entity->checkCollectionExists($propertyName)) {
            if ($this->entity->getCollectionDataInformation($propertyName) === $formPropertyClass::ENTITY_CLASS_NAME) {
                $entityCollectonToEmbed = $this->entity->$propertyName;
                $sismaCollectionPropertyKeys = isset($this->request->request[$propertyName]) ? array_keys($this->request->request[$propertyName]) : $this->getBaseCollectionFormKeys($baseCollectionFormFromNumber);
                $this->generateSismaCollectionProperty($sismaCollectionPropertyKeys, $formPropertyClass, $entityCollectonToEmbed, $this->entityFromForm[$propertyName], $this->filterErrors[$propertyName . "Error"]);
            } else {
                throw new InvalidArgumentException();
            }
        } elseif (property_exists($this->entity, $propertyName)) {
            $reflectionEntity = new \ReflectionClass($this->entity);
            $reflectionEntityProperty = $reflectionEntity->getProperty($propertyName);
            if ($reflectionEntityProperty->class === get_class($this->entity)) {
                if ($reflectionEntityProperty->getType()->getName() === $formPropertyClass::ENTITY_CLASS_NAME) {
                    $this->generateFormProperty($formPropertyClass, $this->entity->$propertyName ?? null, $this->entityFromForm[$propertyName], $this->filterErrors[$propertyName . "Error"]);
                } else {
                    throw new InvalidArgumentException();
                }
            }
        }
        return $this;
    }
    
    private function getBaseCollectionFormKeys(int $baseCollectionFormFromNumber): array{
        if($baseCollectionFormFromNumber === 0){
            return [];
        }else{
            return range(0, $baseCollectionFormFromNumber-1);
        }
    }

    private function generateSismaCollectionProperty(array $sismaCollectionPropertyKeys, string $formPropertyClass, SismaCollection $entityCollectonToEmbed, ?array &$entityFromForm, ?array &$filerErrors): void
    {
        foreach($sismaCollectionPropertyKeys as $key){
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
            if (in_array($propertyName, $this->sismaCollectionPropertyName)) {
                $this->resolveSismaCollection($propertyName);
            } else {
                $this->entity->$propertyName = $value;
            }
        }
        return $this->entity;
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
        return $this->entityData ?? new StandardEntity();
    }

}

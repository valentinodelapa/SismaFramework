<?php

namespace SismaFramework\Core\BaseClasses;

use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\Exceptions\FormException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;

abstract class BaseForm
{

    use \SismaFramework\Core\Traits\ParseValue;
    use \SismaFramework\Core\Traits\Submitted;

    protected const ENTITY_CLASS_NAME = BaseEntity::class;

    private array $sismaCollectionPropertyName = [];
    protected bool $filterResult = true;
    protected BaseEntity $entity;
    protected Request $request;
    protected array $entityData;
    protected array $entityFromForm = [];
    protected array $filterFiledsMode = [];
    protected array $filterErrors = [];
    private static bool $isFirstcalled = true;

    public function __construct(?BaseEntity $baseEntity = null)
    {
        $this->checkEntityClassNameOverride();
        $this->embedEntity($baseEntity);
        $this->setFilterFieldsMode();
        $this->setEntityFromForm();
    }

    private function checkEntityClassNameOverride() :void
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
    }

    abstract protected function injectRequest(): void;

    public function isValid(): bool
    {
        $reflectionEntity = new \ReflectionClass($this->entity);
        $reflectionProperties = $reflectionEntity->getProperties();
        foreach ($reflectionProperties as $property) {
            if (array_key_exists($property->name, $this->entityFromForm) && array_key_exists($property->name, $this->request->request)) {
                $this->switchFormPropertyType($property);
            } elseif (($property->isPublic()) && ($this->entity->isPrimaryKey($property->name) === false)) {
                $this->parseProperty($property);
                $this->switchFilter($property->name);
            }
        }
        $this->customFilter();
        Debugger::setFormFilter($this->filterErrors);
        return $this->filterResult;
    }

    private function switchFormPropertyType(\ReflectionProperty $property): void
    {
        $request = clone $this->request;
        $this->entityData[$property->name] = [];
        $this->filterErrors[$property->name . "Error"] = [];
        if (is_a($property->getType()->getName(), SismaCollection::class, true)) {
            foreach ($this->request->request[$property->name] as $key => $value) {
                $request->request = $value;
                $this->entityData[$property->name][$key] = [];
                $this->filterErrors[$property->name . "Error"][$key] = [];
                $this->switchForm($this->entityFromForm[$property->name][$key], $property->name, $request, $this->entityData[$property->name][$key], $this->filterErrors[$property->name . "Error"][$key]);
            }
        } else {
            $request->request = $this->request->request[$property->name];
            $this->switchForm($this->entityFromForm[$property->name], $property->name, $request, $this->entityData[$property->name], $this->filterErrors[$property->name . "Error"]);
        }
    }

    private function switchForm(self $entityFromForm, string $propertyName, Request $request, array &$entityData, array &$filterErrors): void
    {
        array_push($this->sismaCollectionPropertyName, $propertyName);
        $entityFromForm->handleRequest($request);
        if ($entityFromForm->isValid()) {
            $entityData[$propertyName] = $entityFromForm->resolveEntity();
        } else {
            $this->filterResult = false;
        }
        $filterErrors = $entityFromForm->returnFilterErrors();
    }

    private function parseProperty(\ReflectionProperty $property): void
    {
        if (array_key_exists($property->name, $this->request->request) && ($this->request->request[$property->name] !== '')) {
            $reflectionType = $property->getType();
            $this->entityData[$property->name] = $this->parseValue($reflectionType, $this->request->request[$property->name]);
        } elseif (array_key_exists($property->name, $this->request->files)) {
            $this->entityData[$property->name] = $this->request->files[$property->name];
        } elseif ($property->isInitialized($this->entity)) {
            $this->entityData[$property->name] = $property->getValue($this->entity);
        } elseif (array_key_exists($property->name, $this->filterFiledsMode)) {
            $this->entityData[$property->name] = null;
        }
    }

    private function parseFile()
    {
        
    }

    private function switchFilter(string $propertyName): void
    {
        if (array_key_exists($propertyName, $this->filterFiledsMode)) {
            $this->filterErrors[$propertyName . "Error"] = false;
            $filterFunction = $this->filterFiledsMode[$propertyName]['filterType']->value;
            if (Filter::$filterFunction($this->entityData[$propertyName])) {
                $this->filterResult = $this->filterResult;
            } elseif (($this->filterFiledsMode[$propertyName]['allowNull'] === true) && ($this->entityData[$propertyName] === null)) {
                $this->filterResult = $this->filterResult;
            } else {
                $this->filterResult = false;
                $this->filterErrors[$propertyName . "Error"] = true;
            }
        }
    }

    protected function addEntityFromForm(string $propertyName, string $formPropertyClass, int $sismaCollectionNumbers = 0): self
    {
        $reflectionEntity = new \ReflectionClass($this->entity);
        $reflectionEntityProperty = $reflectionEntity->getProperty($propertyName);
        if (($reflectionEntityProperty->isPublic())) {
            if ($reflectionEntityProperty->getType()->getName() === $formPropertyClass::ENTITY_CLASS_NAME) {
                $this->generateFormProperty($formPropertyClass, $this->entityFromForm[$propertyName], $this->filterErrors[$propertyName . "Error"]);
            } else {
                throw new InvalidArgumentException();
            }
        } else {
            $entityClass = get_class($this->entity);
            if ($entityClass::getCollectionDataInformation($propertyName, $entityClass::FOREIGN_KEY_TYPE) === $formPropertyClass::ENTITY_CLASS_NAME) {
                $this->generateSismaCollectionProperty($sismaCollectionNumbers, $formPropertyClass, $this->entityFromForm[$propertyName], $this->filterErrors[$propertyName . "Error"]);
            } else {
                throw new InvalidArgumentException();
            }
        }
        return $this;
    }

    private function generateFormProperty(string $formPropertyClass, ?self &$entityFromForm, ?array &$filerErrors): void
    {
        $formProperty = new $formPropertyClass();
        $entityFromForm = $formProperty;
        $filerErrors = $formProperty->returnFilterErrors();
    }

    private function generateSismaCollectionProperty(int $sismaCollectionNumbers, string $formPropertyClass, ?array &$entityFromForm, ?array &$filerErrors): void
    {
        for ($i = 0; $i < $sismaCollectionNumbers; $i++) {
            $this->generateFormProperty($formPropertyClass, $entityFromForm[$i], $filerErrors[$i]);
        }
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
        foreach ($this->entityData as $key => $value) {
            if (in_array($key, $this->sismaCollectionPropertyName)) {
                $this->resolveSismaCollection($key);
            } else {
                $this->entity->$key = $value;
            }
        }
        return $this->entity;
    }

    private function resolveSismaCollection(string $propertyName): void
    {
        foreach ($this->entityFromForm[$propertyName] as $form) {
            if (isset($form->entityData)) {
                $this->entity->addEntityToSimaCollection($propertyName, $form->resolveEntity());
            }
        }
    }

}

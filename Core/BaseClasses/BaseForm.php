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
use SismaFramework\Core\BaseClasses\BaseForm\EntityResolver;
use SismaFramework\Core\BaseClasses\BaseForm\FilterManager;
use SismaFramework\Core\BaseClasses\BaseForm\FormValidator;
use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\Exceptions\FormException;
use SismaFramework\Core\Exceptions\InvalidArgumentException;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 *
 * @author Valentino de Lapa
 */
abstract class BaseForm extends Submittable
{

    protected BaseEntity $entity;
    protected StandardEntity $entityData;
    protected array $entityFromForm = [];
    private DataMapper $dataMapper;
    private FilterManager $filterManager;
    private FormValidator $formValidator;
    private EntityResolver $entityResolver;
    private array $entityToResolve = [];
    private array $sismaCollectionToResolve = [];
    private ResponseType $responseType = ResponseType::httpOk;

    public function __construct(?BaseEntity $baseEntity = null,
            DataMapper $dataMapper = new DataMapper(),
            FilterManager $filterManager = new FilterManager(),
            ?FormValidator $formValidator = null,
            EntityResolver $entityResolver = new EntityResolver())
    {
        parent::__construct();
        $this->dataMapper = $dataMapper;
        $this->filterManager = $filterManager;
        $this->formValidator = $formValidator ?? new FormValidator($dataMapper, $filterManager);
        $this->entityResolver = $entityResolver;
        $this->checkEntityName();
        $this->embedEntity($baseEntity);
    }

    private function checkEntityName()
    {
        if (is_subclass_of(static::getEntityName(), BaseEntity::class) === false) {
            throw new FormException('Entity name returned by getEntityName() must be a subclass of BaseEntity');
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
            throw new InvalidArgumentException('BaseEntity parameter must be an instance of ' . $entityClassName . ' or null');
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

    protected function addRequest(string $propertyName, string|array $value): self
    {
        $this->request->input[$propertyName] = $value;
        return $this;
    }

    abstract protected function setFilterFieldsMode(): void;

    protected function addFilterFieldMode(string $propertyName, FilterType $filterType, array $parameters = [], bool $allowNull = false): self
    {
        $this->filterManager->addFilterFieldMode($propertyName, $filterType, $parameters, $allowNull);
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
            if (isset($this->request->input[$propertyName])) {
                $sismaCollectionPropertyKeys = array_keys($this->request->input[$propertyName]);
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
            $entityFromForm[$key] = $this->generateFormProperty($formPropertyClass, $ntityToEmbed, $this->request->input[$propertyName][$key] ?? []);
        }
        return $entityFromForm;
    }

    private function generateFormProperty(string $formPropertyClass, ?BaseEntity $entityToEmbed, array $currentInputPart): BaseForm
    {
        $propertyForm = new $formPropertyClass($entityToEmbed, $this->dataMapper);
        $currentRequest = clone $this->request;
        $currentRequest->input = $currentInputPart;
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
                $this->entityFromForm[$propertyName] = $this->generateFormProperty($formPropertyClass, $entityToEmbedded, $this->request->input[$propertyName] ?? []);
            } else {
                throw new InvalidArgumentException($propertyName);
            }
        }
    }

    public function isValid(): bool
    {
        $result = $this->formValidator->validate(
            $this->entity,
            $this->request,
            $this->entityFromForm,
            $this->formFilterError,
            $this->entityToResolve,
            $this->sismaCollectionToResolve
        );

        $this->entityData = $result['entityData'];
        $filterResult = $result['filterResult'];

        $this->customFilter();
        Debugger::setFormFilter($this->formFilterError);
        if ($filterResult === false) {
            $this->responseType = ResponseType::httpBadRequest;
        }
        return $filterResult;
    }

    abstract protected function customFilter(): void;

    public function resolveEntity(): BaseEntity
    {
        return $this->entityResolver->resolveEntity(
            $this->entity,
            $this->entityData,
            $this->entityFromForm,
            $this->entityToResolve,
            $this->sismaCollectionToResolve
        );
    }

    public function getEntityDataToStandardEntity(): StandardEntity
    {
        if (isset($this->entity->id)) {
            $this->entityData->id = $this->entity->id;
        }
        return $this->entityData ?? new StandardEntity();
    }

    public function getResponseType(): ResponseType
    {
        return $this->responseType;
    }
}

<?php

namespace Sisma\Core\BaseClasses;

use Sisma\Core\ObjectRelationalMapper\Adapter;

abstract class BaseFixture extends BaseModel
{

    protected ?array $dependenciesArray = null;
    protected string $entityType;
    protected ?array $entitiesArray;
    protected int $counter = 0;

    public function __construct(?Adapter $connection = null)
    {
        parent::__construct($connection);
        $this->setDependencies();
    }

    public function execute(array &$entitiesArray): void
    {
        $data = $this->getFixtureData($entitiesArray);
        while (is_array($data)) {
            $this->entity = new $this->entityType();
            $this->saveEntityByData($data);
            $entitiesArray[$this->entityType][$this->counter] = $this->getEmbeddedEntity();
            $this->counter++;
            $data = $this->getFixtureData($entitiesArray);
        }
    }

    abstract public function getFixtureData(array $entitiesArray): ?array;

    protected function setDependencies(): void
    {
        
    }

    public function getDependencies(): ?array
    {
        return $this->dependenciesArray;
    }

}

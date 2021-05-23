<?php

namespace Sisma\Core\ExtendedClasses;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\ProprietaryTypes\SismaCollection;

abstract class ReferencedEntity extends BaseEntity
{

    public function __call($methodName, $arguments)
    {
        $methodType = substr($methodName, 0, 3);
        $propertyName = lcfirst(substr($methodName, 3));
        switch ($methodType) {
            case 'set':
                $argument = isset($arguments[0]) ? $arguments[0] : null;
                return $this->setSismaCollection($propertyName, $argument);
                break;
            case 'add':
                return $this->addEntityToSimaCollection($propertyName."s", $arguments[0]);
                break;
            case 'get':
                return $this->getSismaCollection($propertyName);
                break;
        }
    }

    protected function saveSismaCollection(): void
    {
        foreach ($this->collectionPropertiesName as $collectionName) {
            $collectionProperty = $this->$collectionName;
            foreach ($collectionProperty as $entity) {
                $entity->save();
            }
        }
    }

    public function setSismaCollection(string $propertyName, ?SismaCollection $sismaCollection = null): void
    {
        if ($sismaCollection === null){
            $modelName = str_replace('Entities', 'Models', static::COLLECTION_DATA[$propertyName]['entity']).'Model';
            $modelMethodName = 'get'.ucfirst($propertyName).'By'.ucfirst(static::COLLECTION_DATA[$propertyName]['propertyName']);
            $model = new $modelName();
            $this->$propertyName = $model->$modelMethodName($this);
        }else{
            $this->$propertyName = $sismaCollection;
            $entityPropertyName = static::COLLECTION_DATA[$propertyName]['propertyName'];
            foreach ($this->$propertyName as $entity) {
                $entity->$entityPropertyName = $this;
            }
        }
    }

    public function addEntityToSimaCollection(string $propertyName, BaseEntity $entity): void
    {
        $this->$propertyName->append($entity);
        $entityPropertyName = static::COLLECTION_DATA[$propertyName]['propertyName'];
        $entityType = static::COLLECTION_DATA[$propertyName]['entity'];
        if ($entity instanceof $entityType) {
            $entity->$entityPropertyName = $this;
        } else {
            
        }
    }

    public function getSismaCollection(string $propertyName): SismaCollection
    {
        return $this->$propertyName;
    }

}

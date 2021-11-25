<?php

namespace Sisma\Core\ExtendedClasses;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\ProprietaryTypes\SismaCollection;
use Sisma\Core\Exceptions\InvalidArgumentException;

abstract class ReferencedEntity extends BaseEntity
{

    protected static array $collectionData;
    
    
    public const FOREIGN_KEY_TYPE = 'foreignKeyType';
    public const FOREIGN_KEY_NAME = 'foreignKeyName';
    public const FOREIGN_KEY_SUFFIX = 'Collection';

    public static function getCollectionDataInformation(string $collectionName, string $information): string
    {
        static::setCollectionData();
        return self::$collectionData[$collectionName][$information];
    }

    abstract protected static function setCollectionData(): void;

    protected static function addCollectionData(string $collectionName, string $foreignKeyType, string $foreignKeyName): void
    {
        self::$collectionData[$collectionName] = [
            static::FOREIGN_KEY_TYPE => $foreignKeyType,
            static::FOREIGN_KEY_NAME => $foreignKeyName,
        ];
        self::checkCollectionDataConsistency($collectionName);
    }

    private static function checkCollectionDataConsistency(string $collectionName): void
    {
        $result = true;
        $result = (self::checkRelatedPropertyPresence($collectionName) === false) ? false : $result;
        $result = (self::checkRelatedPropertyName($collectionName) === false) ? false : $result;
        if($result === false){
            throw new InvalidArgumentException();
        }
    }

    private static function checkRelatedPropertyPresence(string $collectionName): bool
    {
        return (property_exists(self::$collectionData[$collectionName][static::FOREIGN_KEY_TYPE], self::$collectionData[$collectionName][static::FOREIGN_KEY_NAME]));
    }
    
    private static function checkRelatedPropertyName(string $collectionName): bool
    {
        $calledClassName = get_called_class();
        $reflectionRelatedProperty = new \ReflectionProperty(self::$collectionData[$collectionName][static::FOREIGN_KEY_TYPE], self::$collectionData[$collectionName][static::FOREIGN_KEY_NAME]);
        return ($reflectionRelatedProperty->getType()->getName() === $calledClassName);
    }

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
                return $this->addEntityToSimaCollection($propertyName . static::FOREIGN_KEY_SUFFIX, $arguments[0]);
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
        if ($sismaCollection === null) {
            $modelName = str_replace('Entities', 'Models', self::getCollectionDataInformation($propertyName, static::FOREIGN_KEY_TYPE)) . 'Model';
            $modelMethodName = 'get' . ucfirst($propertyName) . 'By' . ucfirst(self::getCollectionDataInformation($propertyName, static::FOREIGN_KEY_NAME));
            $model = new $modelName();
            $this->$propertyName = $model->$modelMethodName($this);
        } else {
            $this->$propertyName = $sismaCollection;
            $entityPropertyName = self::getCollectionDataInformation($propertyName, static::FOREIGN_KEY_NAME);
            foreach ($this->$propertyName as $entity) {
                $entity->$entityPropertyName = $this;
            }
        }
    }

    public function addEntityToSimaCollection(string $propertyName, BaseEntity $entity): void
    {
        $this->$propertyName->append($entity);
        $entityPropertyName = self::getCollectionDataInformation($propertyName, static::FOREIGN_KEY_NAME);
        $entityType = self::getCollectionDataInformation($propertyName, static::FOREIGN_KEY_TYPE);
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

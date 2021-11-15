<?php

namespace Sisma\Core\HttpClasses;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\ExtendedClasses\ReferencedEntity;
use Sisma\Core\Enumerators\RequestType;

class Request
{

    use \Sisma\Core\Traits\ParseValue;

    public $query;
    public $request;
    public $cookie;
    public $files;
    public $server;
    public $headers;

    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    private function parseRequest(string $className, array $request): BaseEntity
    {
        $entity = new $className();
        $reflectionClass = new \ReflectionClass($className);
        $this->parsePublicProperties($reflectionClass, $request, $entity);
        $this->parseProtectedProperties($reflectionClass, $request, $entity);
        return $entity;
    }

    private function parsePublicProperties(\ReflectionClass $reflectionClass, array $request, BaseEntity &$entity)
    {
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            if (array_key_exists($propertyName, $request)) {
                $this->switchParseFunction($reflectionProperty, $request[$propertyName], $propertyName, $entity);
            }
        }
    }

    private function switchParseFunction(\ReflectionProperty $reflectionProperty, array|string $field, string $propertyName, BaseEntity &$entity)
    {
        if (is_array($field)) {
            $entity->$propertyName = $this->parseRequest($reflectionProperty->getType()->getName(), $field);
        } elseif ($field !== '') {
            $entity->$propertyName = $this->parseValue($reflectionProperty->getType(), $field);
        }
    }

    private function parseProtectedProperties(\ReflectionClass $reflectionClass, array $request, BaseEntity &$entity)
    {
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PROTECTED) as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            if (array_key_exists($propertyName, $request)) {
                $this->iterateProtectedProperties($request[$propertyName], $propertyName, $entity);
            }
        }
    }

    private function iterateProtectedProperties(array $fields, string $propertyName, BaseEntity &$entity)
    {
        foreach ($fields as $field) {
            $methodName = "add" . ucfirst(substr($propertyName, 0, -1));
            $sismaCollectionClassName = $entity->getCollectionDataInformation($propertyName, ReferencedEntity::FOREIGN_KEY_TYPE);
            if (is_array($field)) {
                $entity->$methodName($this->parseRequest($sismaCollectionClassName, $field));
            } else {
                $entity->$methodName($this->parseEntity($sismaCollectionClassName, $field));
            }
        }
    }

    public function __call($methodName, $arguments): BaseEntity
    {
        $propertyName = lcfirst(str_replace("parseRequest", "", $methodName));
        return $this->parseRequest($arguments[0], $this->$propertyName);
    }

}

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

namespace SismaFramework\Core\HttpClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Core\Enumerations\RequestType;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Request
{

    use \SismaFramework\Core\Traits\ParseValue;

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
            $methodName = "add" . ucfirst(str_replace(ReferencedEntity::FOREIGN_KEY_SUFFIX, '', $propertyName));
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

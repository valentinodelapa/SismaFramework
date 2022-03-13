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

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\Exceptions\FixtureException;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseFixture
{

    private BaseEntity $entity;
    private array $entitiesArray;
    private array $dependenciesArray = [];

    public function __construct()
    {
        $this->setDependencies();
    }

    public function execute(array &$entitiesArray): void
    {
        $this->entitiesArray = $entitiesArray;
        $this->setEntity();
        $this->entity->save();
        $entitiesArray[get_called_class()] = $this->getEmbeddedEntity();
    }

    abstract public function setEntity(): void;

    protected function addEntity(BaseEntity $baseEntity): void
    {
        $this->entity = $baseEntity;
    }

    protected function getEntityByFixtureName(string $fixtureName): BaseEntity
    {
        if (in_array($fixtureName, $this->dependenciesArray)) {
            return $this->entitiesArray[$fixtureName];
        } else {
            throw new FixtureException('Dipendenza non settata');
        }
    }

    abstract protected function setDependencies(): void;

    protected function addDipendency(string $dependencyClassName): self
    {
        $this->dependenciesArray[] = $dependencyClassName;
        return $this;
    }

    public function getDependencies(): ?array
    {
        return $this->dependenciesArray;
    }

}

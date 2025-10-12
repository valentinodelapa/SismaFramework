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

namespace SismaFramework\Core\BaseClasses\BaseForm;

use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\StandardEntity;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class EntityResolver
{

    public function resolveEntity(BaseEntity $entity, StandardEntity $entityData, array $entityFromForm, array $entityToResolve, array $sismaCollectionToResolve): BaseEntity
    {
        // First, resolve all standard properties from entityData
        foreach ($entityData as $propertyName => $value) {
            if (!in_array($propertyName, $sismaCollectionToResolve) && !in_array($propertyName, $entityToResolve)) {
                $entity->$propertyName = $value;
            }
        }

        // Then resolve all entity references
        foreach ($entityToResolve as $propertyName) {
            $this->resolveEntityByForm($entity, $propertyName, $entityFromForm);
        }

        // Finally resolve all collections
        foreach (array_unique($sismaCollectionToResolve) as $propertyName) {
            $this->resolveSismaCollection($entity, $propertyName, $entityFromForm);
        }

        return $entity;
    }

    private function resolveEntityByForm(BaseEntity $entity, string $propertyName, array $entityFromForm): void
    {
        if (isset($entityFromForm[$propertyName])) {
            $entity->$propertyName = $entityFromForm[$propertyName]->resolveEntity();
        }
    }

    private function resolveSismaCollection(BaseEntity $entity, string $propertyName, array $entityFromForm): void
    {
        if (isset($entityFromForm[$propertyName])) {
            foreach ($entityFromForm[$propertyName] as $form) {
                $entity->addEntityToEntityCollection($propertyName, $form->resolveEntity());
            }
        }
    }
}

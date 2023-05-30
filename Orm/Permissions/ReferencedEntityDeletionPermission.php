<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa.
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

namespace SismaFramework\Orm\Permissions;

use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Core\BaseClasses\BasePermission;
use SismaFramework\Core\Enumerations\PermissionAttribute;
use SismaFramework\Orm\Exceptions\ReferencedEntityDeletionException;

/**
 *
 * @author Valentino de Lapa
 */
class ReferencedEntityDeletionPermission extends BasePermission
{

    protected function callParentPermissions(): void
    {
        
    }

    protected function checkPermmisions(): bool
    {
        switch ($this->attribute) {
            case PermissionAttribute::allow:
                return $this->canDeleteReferencedEntity($this->subject);
            default:
                return false;
        }
    }

    private function canDeleteReferencedEntity(ReferencedEntity $referencedEntity): bool
    {
        $result = true;
        foreach ($referencedEntity->getCollectionNames() as $collectionName) {
            $methodName = 'count'.ucfirst($collectionName);
            if($referencedEntity->$methodName() > 0){
                $result = false;
            }
        }
        return $result;
    }

    protected function isInstancePermitted(): bool
    {
        return $this->subject instanceof ReferencedEntity;
    }

    protected function checkResult(): void
    {
        if ($this->result === false) {
            throw new ReferencedEntityDeletionException($this->subject, "Errore vincoli integrità");
        }
    }

}

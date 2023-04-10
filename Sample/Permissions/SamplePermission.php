<?php

namespace SismaFramework\Sample\Permissions;

use SismaFramework\Core\BaseClasses\BasePermission;
use SismaFramework\Core\Enumerations\PermissionAttribute;
use SismaFramework\Sample\Entities\BaseSample;

class SamplePermission extends BasePermission
{

    protected function callParentPermissions(): void
    {
        
    }

    protected function checkPermmisions(): bool
    {
        switch ($this->attribute) {
            case PermissionAttribute::allow:
                return isset($this->subject->text);
            case PermissionAttribute::deny:
                return !isset($this->subject->text);
            default:
                return false;
        }
    }

    protected function isInstancePermitted(): bool
    {
        return $this->subject instanceof BaseSample;
    }

}

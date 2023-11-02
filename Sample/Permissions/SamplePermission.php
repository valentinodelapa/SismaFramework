<?php

namespace SismaFramework\Sample\Permissions;

use SismaFramework\Security\BaseClasses\BasePermission;
use SismaFramework\Security\Enumerations\AccessControlEntry;
use SismaFramework\Sample\Entities\BaseSample;

class SamplePermission extends BasePermission
{

    protected function callParentPermissions(): void
    {
        
    }

    protected function checkPermmisions(): bool
    {
        switch ($this->attribute) {
            case AccessControlEntry::allow:
                return isset($this->subject->text);
            case AccessControlEntry::deny:
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

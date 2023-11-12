<?php

namespace SismaFramework\Sample\Permissions;

use SismaFramework\Security\BaseClasses\BasePermission;
use SismaFramework\Security\BaseClasses\BaseVoter;
use SismaFramework\Sample\Voters\SampleVoter;

class SamplePermission extends BasePermission
{

    protected function callParentPermissions(): void
    {
        
    }

    protected function getVoter(): BaseVoter
    {
        return new SampleVoter();
    }
}

<?php

namespace SismaFramework\Sample\Voters;

use SismaFramework\Security\BaseClasses\BaseVoter;
use SismaFramework\Security\Enumerations\AccessControlEntry;
use SismaFramework\Sample\Entities\BaseSample;

class SampleVoter extends BaseVoter
{

    protected function checkVote(): bool
    {
        switch ($this->accessControlEntry) {
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

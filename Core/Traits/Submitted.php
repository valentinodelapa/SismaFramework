<?php

namespace SismaFramework\Core\Traits;

use SismaFramework\Core\HelperClasses\Debugger;

trait Submitted
{
    public function isSubmitted(): bool
    {
        if (isset($this->request->request['submitted'])) {
            return true;
        } else {
            return false;
        }
    }
    
    public function returnFilterErrors():array
    {
        return $this->filterErrors;
    }
}

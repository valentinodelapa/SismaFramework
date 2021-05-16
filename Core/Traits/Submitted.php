<?php

namespace Sisma\Core\Traits;

use Sisma\Core\Enumerators\RequestType;

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

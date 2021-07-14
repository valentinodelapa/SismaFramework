<?php

namespace Sisma\Sample\Models;

use Sisma\Core\BaseClasses\BaseModel;
use Sisma\Sample\Entities\Sample;

class SampleModel extends BaseModel
{
    
    public function implementEmbeddedEntity()
    {
        $this->entity = new Sample();
    }

}

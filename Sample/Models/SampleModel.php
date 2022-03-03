<?php

namespace SismaFramework\Sample\Models;

use SismaFramework\Core\BaseClasses\BaseModel;
use SismaFramework\Sample\Entities\Sample;

class SampleModel extends BaseModel
{
    
    public function implementEmbeddedEntity()
    {
        $this->entity = new Sample();
    }

}

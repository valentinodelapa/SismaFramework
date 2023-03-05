<?php

namespace SismaFramework\Sample\Models;

use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Sample\Entities\BaseSample;

class BaseSampleModel extends BaseModel
{
    
    public function implementEmbeddedEntity()
    {
        $this->entity = new BaseSample();
    }

    protected function appendSearchCondition(\SismaFramework\Orm\HelperClasses\Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        
    }

}

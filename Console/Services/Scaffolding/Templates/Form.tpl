<?php

namespace {{formNamespace}};

use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\Enumerations\FilterType;
use {{entityNamespace}}\{{entityName}};

class {{entityName}}Form extends BaseForm
{
    
    protected static function getEntityName(): string
    {
        return {{entityName}}::class;
    }
    
    protected function injectRequest(): void
    {
        
    }
    
    protected function setFilterFieldsMode(): void
    {
{{filters}}
    }
    
    protected function setEntityFromForm(): void
    {
        
    }
    
    protected function customFilter(): void
    {
        
    }
}

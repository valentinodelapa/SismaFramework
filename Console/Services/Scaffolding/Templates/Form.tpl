<?php

namespace {{formNamespace}};

use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\Enumerations\FilterType;
use {{entityNamespace}}\{{entityShortName}};

class {{entityShortName}}Form extends BaseForm
{
    
    protected static function getEntityName(): string
    {
        return {{entityShortName}}::class;
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
    
    protected function customFilter(): bool
    {
        return true;
    }
}

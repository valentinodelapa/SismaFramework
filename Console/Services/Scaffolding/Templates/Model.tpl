<?php

namespace {{modelNamespace}};

use {{modelTypeNamespace}}\{{modelType}};
use SismaFramework\Orm\HelperClasses\Query;
use {{entityNamespace}}\{{entityShortName}};

class {{entityShortName}}Model extends {{modelType}}
{
    
    protected function getEntityName(): string
    {
        return {{entityShortName}}::class;
    }
    
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        
    }
    
}

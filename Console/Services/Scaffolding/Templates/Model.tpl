<?php

namespace {{modelNamespace}};

use {{modelTypeNamespace}}\{{modelType}};
use SismaFramework\Orm\HelperClasses\Query;
use {{entityNamespace}}\{{entityName}};

/**
 * {{entityName}} Model
 */
class {{entityName}}Model extends {{modelType}}
{
    
    protected function getEntityName(): string
    {
        return {{entityName}}::class;
    }
    
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        
    }
    
}

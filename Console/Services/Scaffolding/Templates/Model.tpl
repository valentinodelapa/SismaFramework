<?php

namespace {{namespace}};

use {{modelTypeNamespace}}\{{modelType}};
use SismaFramework\Orm\HelperClasses\Query;
use {{namespace}}\{{className}};

/**
 * {{className}} Model
 */
class {{className}}Model extends {{modelType}}
{
    
    protected function getEntityName(): string
    {
        return {{className}}::class;
    }
    
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        
    }
    
}

<?php

namespace {{documentModelNamespace}};

use SismaFramework\Odm\BaseClasses\BaseModel;
use {{documentNamespace}}\{{documentShortName}};

class {{documentShortName}}Model extends BaseModel
{

    #[\Override]
    public function getDocumentName(): string
    {
        return {{documentShortName}}::class;
    }

}

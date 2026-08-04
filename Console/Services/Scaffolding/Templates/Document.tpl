<?php

namespace {{documentNamespace}};

use SismaFramework\Odm\BaseClasses\BaseDocument;

class {{documentShortName}} extends BaseDocument
{

    #[\Override]
    public function getCollectionName(): string
    {
        return '{{collectionName}}';
    }

    #[\Override]
    protected function setPropertyDefaultValue(): void
    {

    }

}

<?php

namespace Sisma\Core\ExtendedClasses;

use Sisma\Core\ExtendedClasses\ReferencedEntity;
use Sisma\Core\ProprietaryTypes\SismaCollection;
use Sisma\Core\ObjectRelationalMapper\Adapter;

abstract class SelfReferencedEntity extends ReferencedEntity
{

    protected SismaCollection $sons;
    
    public function __construct(Adapter &$adapter = null)
    {
        parent::__construct($adapter);
        $this->sons = new SismaCollection();
    }

}

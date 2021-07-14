<?php

namespace Sisma\Sample\Forms;

use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\BaseClasses\BaseForm;
use Sisma\Sample\Entities\Sample;

class SampleForm extends BaseForm
{

    protected const ENTITY_CLASS_NAME = Sample::class;

    protected function customFilter(): void
    {
        
    }

    protected function getEntityToEmbed(): BaseEntity
    {
        
    }

    protected function injectRequest(): void
    {
        
    }

    protected function setEntityFromForm(): void
    {
        
    }

    protected function setFilterFieldsMode(): void
    {
        
    }

}

<?php

namespace SismaFramework\Sample\Forms;

use SismaFramework\Core\BaseClasses\BaseEntity;
use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Sample\Entities\Sample;

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

<?php

namespace SismaFramework\Sample\Forms;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Sample\Entities\BaseSample;

class BaseSampleForm extends BaseForm
{

    protected const ENTITY_CLASS_NAME = BaseSample::class;

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

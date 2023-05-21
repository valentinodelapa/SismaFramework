<?php

namespace SismaFramework\Sample\Forms;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\Sample\Entities\BaseSample;

class BaseSampleFormFromOtherForm extends BaseForm
{

    protected static function getEntityName(): string
    {
        return BaseSample::class;
    }

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
        $this->addFilterFieldMode('text', FilterType::isString)
                ->addFilterFieldMode('boolean', FilterType::isBoolean);
    }

}

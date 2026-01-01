<?php

namespace SismaFramework\TestsApplication\Forms;

use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\TestsApplication\Entities\BaseSample;

class BaseSampleForm extends BaseForm
{

    protected static function getEntityName(): string
    {
        return BaseSample::class;
    }

    protected function customFilter(): bool
    {
        return true;
        
    }

    protected function injectRequest(): void
    {
        
    }

    protected function setEntityFromForm(): void
    {
        $this->addEntityFromForm('referencedEntityWithoutInitialization', ReferencedSampleFormFromOtherForm::class);
    }

    protected function setFilterFieldsMode(): void
    {
        $this->addFilterFieldMode('stringWithoutInitialization', FilterType::isString)
                ->addFilterFieldMode('boolean', FilterType::isBoolean)
                ->addFilterFieldMode('nullableSecureString', FilterType::isSecurePassword, [], true);
    }

}

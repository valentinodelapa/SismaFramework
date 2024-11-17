<?php

namespace SismaFramework\TestsApplication\Forms;

use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\TestsApplication\Entities\BaseSample;

class BaseSampleFormWithFakeEntityFromForm extends BaseForm
{

    protected static function getEntityName(): string
    {
        return BaseSample::class;
    }

    protected function customFilter(): void
    {
        
    }

    protected function injectRequest(): void
    {
        
    }

    protected function setEntityFromForm(): void
    {
        $this->addEntityFromForm('fakeReferencedEntity', ReferencedSampleFormFromOtherForm::class);
    }

    protected function setFilterFieldsMode(): void
    {
        $this->addFilterFieldMode('stringWithoutInizialization', FilterType::isString)
                ->addFilterFieldMode('boolean', FilterType::isBoolean)
                ->addFilterFieldMode('nullableSecureString', FilterType::isSecurePassword, [], true);
    }

}

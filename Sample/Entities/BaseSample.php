<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\CustomTypes\SismaTime;
use SismaFramework\Sample\Enumerations\SampleType;

/**
 * @author Valentino de Lapa
 */

class BaseSample extends BaseEntity
{

    protected int $id;
    protected ReferencedSample $referencedEntityWithoutInitialization;
    protected ReferencedSample $referencedEntityWithInitialization;
    protected ?ReferencedSample $nullableReferencedEntityWithInitialization = null;
    protected OtherReferencedSample $otherReferencedSample;
    protected SismaDateTime $datetimeWithoutInitialization;
    protected SismaDate $dateWithoutInitialization;
    protected SismaTime $timeWithoutInitialization;
    protected SismaDateTime $datetimeWithInitialization;
    protected SismaDate $dateWithInitialization;
    protected SismaTime $timeWithInitialization;
    protected ?SismaDateTime $datetimeNullableWithInitialization = null;
    protected ?SismaDate $dateNullableWithInitialization = null;
    protected ?SismaTime $timeNullableWithInitialization = null;
    protected SampleType $enumWithoutInitialization;
    protected SampleType $enumWithInitialization = SampleType::one;
    protected ?SampleType $enumNullableWithInitialization = null;
    protected string $stringWithoutInizialization;
    protected string $stringWithInizialization = 'base sample';
    protected ?string $nullableStringWithInizialization = null;
    protected ?string $nullableSecureString = null;
    protected bool $boolean;

    protected function setPropertyDefaultValue(): void
    {
        $this->referencedEntityWithInitialization = new ReferencedSample($this->dataMapper);
        $this->datetimeWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
        $this->dateWithInitialization = SismaDate::createFromFormat('Y-m-d', '2020-01-01');
        $this->timeWithInitialization = SismaTime::createFromStandardTimeFormat('10:31:25');
    }

    protected function setEncryptedProperties(): void
    {
        
    }

}

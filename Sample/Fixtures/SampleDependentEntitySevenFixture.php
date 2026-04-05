<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
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

namespace SismaFramework\Sample\Fixtures;

use SismaFramework\Core\BaseClasses\BaseFixture;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Sample\Entities\SampleDependentEntity;
use SismaFramework\Sample\Enumerations\ArticleStatus;

/**
 * @author Valentino de Lapa
 */
class SampleDependentEntitySevenFixture extends BaseFixture
{

    protected function setDependencies(): void
    {
        $this->addDependency(SampleReferencedEntityFiveFixture::class);
    }

    public function setEntity(): void
    {
        $entity = new SampleDependentEntity($this->dataMapper);
        $entity->title = 'CI/CD per PHP';
        $entity->content = 'Paolo Ferrari presenta una pipeline completa per continuous integration e deployment.';
        $entity->createdAt = new SismaDateTime('2025-01-16 13:30:00');
        $entity->status = ArticleStatus::PUBLISHED;
        $entity->views = 420;
        $entity->sampleReferencedEntity = $this->getEntityByFixtureName(SampleReferencedEntityFiveFixture::class);
        $this->addEntity($entity);
    }
}

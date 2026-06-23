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

namespace SismaFramework\Console\Services\Upgrade\Strategies;

use SismaFramework\Console\Services\Upgrade\Transformers\ClassRenameTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\FulltextIndexColumnTransformer;

/**
 * Upgrade strategy for version 11.x to 12.0.0
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Upgrade11to12Strategy implements UpgradeStrategyInterface
{

    public function getSourceVersion(): string
    {
        return '11.0.0';
    }

    public function getTargetVersion(): string
    {
        return '12.0.0';
    }

    public function getTransformers(): array
    {
        return [
            new ClassRenameTransformer([
                'SelfReferencedModel' => 'SelfDependentModel',
                'selfReferencedModel' => 'selfDependentModel',
            ]),
            new FulltextIndexColumnTransformer(),
        ];
    }

    public function getBreakingChanges(): array
    {
        return [
            'SelfReferencedModel: Renamed to SelfDependentModel (class, use statements, extends declarations)',
            'ModelType::selfReferencedModel: Enum case renamed to ModelType::selfDependentModel',
            'Query::setFulltextIndexColumn(): Parameters reordered — $textSearchMode moved before $columnAlias and $append',
        ];
    }

    public function requiresManualIntervention(): bool
    {
        return true;
    }
}

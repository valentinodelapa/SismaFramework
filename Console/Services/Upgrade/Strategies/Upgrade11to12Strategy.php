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
use SismaFramework\Console\Services\Upgrade\Transformers\DeprecatedMethodUsageTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\ExceptionBaseClassTransformer;
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
            new ExceptionBaseClassTransformer(),
            new DeprecatedMethodUsageTransformer([
                'countEntityCollectionByEntity' => 'Replace with the magic method countBy{PropertyName}($value) provided by DependentModel::__call()',
                'getEntityCollectionByEntity' => 'Replace with the magic method getBy{PropertyName}($value, $order, $offset, $limit) provided by DependentModel::__call()',
                'deleteEntityCollectionByEntity' => 'Replace with the magic method deleteBy{PropertyName}($value) provided by DependentModel::__call()',
                'countEntityCollectionByParentAndEntity' => 'Replace with the magic method countByParentAnd{PropertyName}($parent, $value) provided by SelfDependentModel::__call()',
                'getEntityCollectionByParentAndEntity' => 'Replace with the magic method getByParentAnd{PropertyName}($parent, $value, $order, $offset, $limit) provided by SelfDependentModel::__call()',
                'deleteEntityCollectionByParentAndEntity' => 'Replace with the magic method deleteByParentAnd{PropertyName}($parent, $value) provided by SelfDependentModel::__call()',
            ]),
        ];
    }

    public function getBreakingChanges(): array
    {
        return [
            'SelfReferencedModel: Renamed to SelfDependentModel (class, use statements, extends declarations)',
            'ModelType::selfReferencedModel: Enum case renamed to ModelType::selfDependentModel',
            'Query::setFulltextIndexColumn(): Parameters reordered — $textSearchMode moved before $columnAlias and $append',
            'DependentModel: countEntityCollectionByEntity(), getEntityCollectionByEntity(), deleteEntityCollectionByEntity() removed — use the magic methods countBy{PropertyName}(), getBy{PropertyName}(), deleteBy{PropertyName}()',
            'SelfDependentModel: countEntityCollectionByParentAndEntity(), getEntityCollectionByParentAndEntity(), deleteEntityCollectionByParentAndEntity() removed — use the magic methods countByParentAnd{PropertyName}(), getByParentAnd{PropertyName}(), deleteByParentAnd{PropertyName}()',
            'Security\\ExtendedClasses\\LogException and NoLogException: Classes removed — extend BaseException directly, implementing ShouldBeLoggedException if the exception must be logged',
            'Config/config.php: Unused constants removed (ADAPTERS, ADAPTER_NAMESPACE, ADAPTER_PATH, CONFIGURATION_PASSWORD, CORE, CORE_NAMESPACE, CORE_PATH, DEFAULT_CONTROLLER, DEFAULT_CONTROLLER_NAMESPACE, DEFAULT_CONTROLLER_PATH, DEFAULT_META_URL, MODEL_PATH, ORM, ORM_NAMESPACE, ORM_PATH, PUBLIC_PATH, STRUCTURAL_RESOURCES_PATH, THIS_DIRECTORY, LOG_WARNING_ROW, LOG_DANGER_ROW) — declare any constant your module still references directly via \\Config\\NAME in your own module config file',
        ];
    }

    public function requiresManualIntervention(): bool
    {
        return true;
    }
}

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

/**
 * Upgrade strategy for version 12.x to 13.0.0
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Upgrade12to13Strategy implements UpgradeStrategyInterface
{

    public function getSourceVersion(): string
    {
        return '12.0.0';
    }

    public function getTargetVersion(): string
    {
        return '13.0.0';
    }

    public function getTransformers(): array
    {
        return [
            new ClassRenameTransformer([
                'SismaFramework\Core\BaseClasses\BaseForm' => 'SismaFramework\Orm\BaseClasses\BaseForm',
                'SismaFramework\Core\HelperClasses\Filter' => 'SismaFramework\Orm\HelperClasses\Filter',
                'SismaFramework\Core\Enumerations\FilterType' => 'SismaFramework\Orm\Enumerations\FilterType',
            ]),
        ];
    }

    public function getBreakingChanges(): array
    {
        return [
            'BaseForm: Moved from SismaFramework\\Core\\BaseClasses to SismaFramework\\Orm\\BaseClasses (namespace only, no signature change)',
            'BaseForm\\EntityResolver, BaseForm\\FilterManager, BaseForm\\FormValidator: Moved along with BaseForm from SismaFramework\\Core\\BaseClasses\\BaseForm to SismaFramework\\Orm\\BaseClasses\\BaseForm',
            'Filter: Moved from SismaFramework\\Core\\HelperClasses to SismaFramework\\Orm\\HelperClasses',
            'FilterType: Moved from SismaFramework\\Core\\Enumerations to SismaFramework\\Orm\\Enumerations',
            'Localizator: Flat template keys containing "/" in the "templates" locale section are ignored, convert them to nested keys (manual)',
            'BaseCommand: configure(), deprecated in 12.5.0, removed; help(): string is now abstract and must be implemented by every command (manual, before upgrading: unmigrated commands prevent the console from starting)',
            'CustomDateTimeInterface: now extends CustomTypeInterface; classes implementing it must declare equals(CustomTypeInterface $other): bool instead of equals(CustomDateTimeInterface $other): bool (manual)',
        ];
    }

    public function requiresManualIntervention(): bool
    {
        return true;
    }
}

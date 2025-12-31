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

use SismaFramework\Console\Services\Upgrade\Transformers\StaticToInstanceTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\ReturnTypeTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\ResponseConstructorTransformer;
use SismaFramework\Console\Services\Upgrade\Transformers\MethodRenameTransformer;

/**
 * Upgrade strategy for version 10.x to 11.0.0
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Upgrade10to11Strategy implements UpgradeStrategyInterface
{
    public function getSourceVersion(): string
    {
        return '10.1.7';
    }

    public function getTargetVersion(): string
    {
        return '11.0.0';
    }

    public function getTransformers(): array
    {
        return [
            new StaticToInstanceTransformer(),
            new ReturnTypeTransformer(),
            new ResponseConstructorTransformer(),
            new MethodRenameTransformer([
                'handleNonThrowableError' => 'registerNonThrowableErrorHandler'
            ])
        ];
    }

    public function getBreakingChanges(): array
    {
        return [
            'ErrorHandler and Debugger: Static methods converted to instance methods',
            'BaseForm::customFilter(): Return type changed from void to bool',
            'Response::setResponseType(): Method removed in favor of constructor injection',
            'ErrorHandler::handleNonThrowableError(): Renamed to registerNonThrowableErrorHandler()',
            'Public/index.php: Requires update to instantiate ErrorHandler and Debugger',
        ];
    }

    public function requiresManualIntervention(): bool
    {
        return true;
    }
}

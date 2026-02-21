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

namespace SismaFramework\Console\Commands;

use SismaFramework\Console\BaseClasses\BaseCommand;
use SismaFramework\Console\Services\Fixtures\FixturesManager;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class FixturesCommand extends BaseCommand
{

    public function __construct(private FixturesManager $fixturesManager = new FixturesManager())
    {
        
    }

    #[\Override]
    public function checkCompatibility(string $command): bool
    {
        return $command === 'fixtures';
    }

    #[\Override]
    protected function configure(): void
    {
        $this->output(<<<OUTPUT
Usage: php SismaFramework/Console/sisma fixtures

Executes all fixture classes found in the configured fixture directories of each module.
Fixtures are executed respecting their declared dependencies.
OUTPUT);
    }

    #[\Override]
    protected function execute(): bool
    {
        $this->output('Loading fixtures...');
        $this->fixturesManager->run();
        if ($this->fixturesManager->extecuted()) {
            $this->output('Fixtures loaded successfully.');
            return true;
        } else {
            $this->output('Error: Some fixtures failed to execute.');
            return false;
        }
    }
}

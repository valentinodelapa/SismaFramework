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

namespace SismaFramework\Tests\Console\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\BaseClasses\BaseCommand;

class BaseCommandTest extends TestCase
{

    public function testHelpOptionPrintsHelpWithoutExecuting(): void
    {
        $command = new class extends BaseCommand {

            public bool $executed = false;

            public function checkCompatibility(string $command): bool
            {
                return true;
            }

            protected function help(): string
            {
                return 'Usage: php SismaFramework/Console/sisma test';
            }

            protected function execute(): bool
            {
                $this->executed = true;
                return true;
            }
        };
        $command->setOptions(['help' => true]);

        ob_start();
        $result = $command->run();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertFalse($command->executed);
        $this->assertSame('Usage: php SismaFramework/Console/sisma test' . PHP_EOL, $output);
    }

    public function testFailedExecutionPrintsHelpHint(): void
    {
        $command = new class extends BaseCommand {

            public function checkCompatibility(string $command): bool
            {
                return true;
            }

            protected function help(): string
            {
                return 'Usage: php SismaFramework/Console/sisma test';
            }

            protected function execute(): bool
            {
                return false;
            }
        };

        ob_start();
        $result = $command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertSame('Use --help for usage information.' . PHP_EOL, $output);
    }
}

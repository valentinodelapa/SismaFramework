<?php

/*
 * The MIT License
 *
 * Copyright 2025 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Console\Traits;

/**
 * Trait for handling interactive console input
 * 
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
trait InteractiveInputTrait
{
    private mixed $inputStream = null;

    public function setInputStream(mixed $inputStream): void
    {
        $this->inputStream = $inputStream;
    }

    private function getInputStream(): mixed
    {
        return $this->inputStream ??= fopen('php://stdin', 'r');
    }

    protected function ask(string $question, ?string $default = null): string
    {
        $defaultText = $default !== null ? " [{$default}]" : '';
        echo $question . $defaultText . ': ';

        $input = trim(fgets($this->getInputStream()));

        if ($input === '' && $default !== null) {
            return $default;
        }

        return $input;
    }

    protected function askConfirmation(string $question, bool $default = true): bool
    {
        $defaultText = $default ? '[Y/n]' : '[y/N]';
        echo $question . ' ' . $defaultText . ': ';

        $input = strtolower(trim(fgets($this->getInputStream())));

        if ($input === '') {
            return $default;
        }

        return in_array($input, ['y', 'yes', 'si', 's'], true);
    }

    protected function askSecret(string $question): string
    {
        echo $question . ': ';

        $handle = $this->getInputStream();

        if (stream_isatty($handle)) {
            system('stty -echo');
            $input = trim(fgets($handle));
            system('stty echo');
            echo PHP_EOL;
        } else {
            $input = trim(fgets($handle));
        }

        return $input;
    }
}

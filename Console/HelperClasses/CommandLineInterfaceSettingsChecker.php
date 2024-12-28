<?php

/*
 * The MIT License
 *
 * Copyright 2024 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Console\HelperClasses;

use SismaFramework\Console\Exceptions\ProjectDirectoryException;

/**
 * Description of CLIChecker
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class CommandLineInterfaceSettingsChecker
{

    public static function checkIsCommandLineInterfaceMode(string $interfaceName): void
    {
        if ($interfaceName !== 'cli') {
            throw new \RuntimeException('This script can only be run from the command line');
        }
    }

    public static function checkIsProjectDirectory(string $directory): void
    {
        if (is_dir($directory) === false) {
            throw new \RuntimeException(<<<ERROR
Error: Invalid project structure. SismaFramework should be a subdirectory of your project root.
Current framework path: $directory
Expected project structure:
  YourProject/
  ├── SismaFramework/  (git submodule)
  └── YourModules/
ERROR);
        }
    }
}

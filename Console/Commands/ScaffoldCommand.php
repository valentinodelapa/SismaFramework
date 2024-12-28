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
use SismaFramework\Console\Services\Scaffolding\ScaffoldingManager;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ScaffoldCommand extends BaseCommand
{

    private ScaffoldingManager $scaffoldingManager;
    private array $validTypes = ['BaseModel', 'DependentModel', 'SelfReferencedModel'];

    public function __construct(ScaffoldingManager $scaffoldingManager = new ScaffoldingManager())
    {
        $this->scaffoldingManager = $scaffoldingManager;
    }

    #[\Override]
    public function checkCompatibility(string $command): bool
    {
        return $command === 'scaffold';
    }

    protected function configure(): void
    {
        $this->output(<<<OUTPUT
Usage: php SismaFramework/Console/sisma scaffold <entity> <module> [options]

Arguments:
  entity    The entity name (e.g. User, Product)
  module    The module name where to generate the files

Options:
  --force         Force overwrite of existing files
  --type=TYPE     Force a specific model type (BaseModel, DependentModel, SelfReferencedModel)
  --template=PATH  Use custom templates from the specified path

Example:
  php SismaFramework/Console/sisma scaffold User Blog
OUTPUT);
    }

    protected function execute(): bool
    {
        $entityName = $this->getArgument('entity');
        $module = $this->getArgument('module');

        if (!$entityName) {
            $this->output('Error: Entity name is required');
            return false;
        }

        if (!$module) {
            $this->output('Error: Module name is required');
            return false;
        }

        // Handle options
        $force = $this->getOption('force') !== null;
        $type = $this->getOption('type');
        $templatePath = $this->getOption('template');

        // Validate type if specified
        if ($type && !in_array($type, $this->validTypes)) {
            $this->output('Error: Invalid model type. Valid types are: ' . implode(', ', $this->validTypes));
            return false;
        }

        // Validate template path if specified
        if ($templatePath && !is_dir($templatePath)) {
            $this->output('Error: Template path does not exist: ' . $templatePath);
            return false;
        }
        $this->scaffoldingManager
                ->setForce($force)
                ->setCustomType($type)
                ->setCustomTemplatePath($templatePath)
                ->generateScaffolding($entityName, $module);

        $this->output('Scaffolding generated successfully!');
        $this->output('Generated files:');
        $this->output("  - {$module}/Application/Models/{$entityName}Model.php");
        $this->output("  - {$module}/Application/Controllers/{$entityName}Controller.php");
        $this->output("  - {$module}/Application/Forms/{$entityName}Form.php");
        return true;
    }
}

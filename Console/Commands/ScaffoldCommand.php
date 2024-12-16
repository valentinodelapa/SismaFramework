<?php

namespace SismaFramework\Console\Commands;

use SismaFramework\Console\BaseClasses\BaseCommand;
use SismaFramework\Console\Services\Scaffolding\ScaffoldingManager;

/**
 * Command to generate scaffolding for entities
 */
class ScaffoldCommand extends BaseCommand {
    private ScaffoldingManager $scaffoldingManager;
    private array $validTypes = ['BaseModel', 'DependentModel', 'SelfReferencedModel'];
    
    public function __construct() {
        $this->scaffoldingManager = new ScaffoldingManager();
    }
    
    protected function configure(): void {
        $this->output("Usage: php sisma scaffold <entity> <module> [options]");
        $this->output("");
        $this->output("Arguments:");
        $this->output("  entity    The entity name (e.g. User, Product)");
        $this->output("  module    The module name where to generate the files");
        $this->output("");
        $this->output("Options:");
        $this->output("  --force         Force overwrite of existing files");
        $this->output("  --type=TYPE     Force a specific model type (BaseModel, DependentModel, SelfReferencedModel)");
        $this->output("  --template=PATH  Use custom templates from the specified path");
        $this->output("");
        $this->output("Example:");
        $this->output("  php sisma scaffold User MyModule --force --type=BaseModel");
    }
    
    protected function execute(): int {
        $entityName = $this->getArgument('entity');
        $module = $this->getArgument('module');
        
        if (!$entityName) {
            $this->output('Error: Entity name is required');
            return 1;
        }
        
        if (!$module) {
            $this->output('Error: Module name is required');
            return 1;
        }
        
        // Handle options
        $force = $this->getOption('force') !== null;
        $type = $this->getOption('type');
        $templatePath = $this->getOption('template');
        
        // Validate type if specified
        if ($type && !in_array($type, $this->validTypes)) {
            $this->output('Error: Invalid model type. Valid types are: ' . implode(', ', $this->validTypes));
            return 1;
        }
        
        // Validate template path if specified
        if ($templatePath && !is_dir($templatePath)) {
            $this->output('Error: Template path does not exist: ' . $templatePath);
            return 1;
        }
        
        try {
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
            return 0;
        } catch (\Exception $e) {
            $this->output('Error: ' . $e->getMessage());
            return 1;
        }
    }
}

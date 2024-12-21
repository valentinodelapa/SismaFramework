<?php
namespace SismaFramework\Console\Services\Scaffolding;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;
use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;

/**
 * ScaffoldingManager class handles the generation of code templates and structures
 * 
 * @package SismaFramework\Console\Services\Scaffolding
 */
class ScaffoldingManager {
    private string $templatesPath;
    private array $placeholders;
    private bool $force = false;
    private ?string $customType = null;
    private ?string $customTemplatePath = null;
    
    public function __construct() {
        $this->templatesPath = __DIR__ . '/Templates/';
        $this->placeholders = [];
    }
    
    /**
     * Set force mode to overwrite existing files
     */
    public function setForce(bool $force): self {
        $this->force = $force;
        return $this;
    }
    
    /**
     * Set custom model type
     */
    public function setCustomType(?string $type): self {
        $this->customType = $type;
        return $this;
    }
    
    /**
     * Set custom template path
     */
    public function setCustomTemplatePath(?string $path): self {
        $this->customTemplatePath = $path;
        return $this;
    }
    
    /**
     * Set a placeholder value for template generation
     * 
     * @param string $key The placeholder key
     * @param string $value The value to replace the placeholder with
     * @return self
     */
    public function setPlaceholder(string $key, string $value): self {
        $this->placeholders[$key] = $value;
        return $this;
    }
    
    /**
     * Generate code from a template
     * 
     * @param string $templateName The name of the template to use
     * @param string $outputPath The path where to save the generated code
     * @return bool True if generation was successful
     * @throws \RuntimeException If template doesn't exist or output path is not writable
     */
    public function generate(string $templateName, string $outputPath): bool {
        $templateFile = $this->getTemplatePath($templateName);
        
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template file not found: {$templateFile}");
        }
        
        if (file_exists($outputPath) && !$this->force) {
            throw new \RuntimeException("File already exists: {$outputPath}. Use --force to overwrite.");
        }
        
        $content = file_get_contents($templateFile);
        
        foreach ($this->placeholders as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        
        return file_put_contents($outputPath, $content) !== false;
    }
    
    /**
     * Clear all placeholders
     * 
     * @return self
     */
    public function clearPlaceholders(): self {
        $this->placeholders = [];
        return $this;
    }

    /**
     * Generate all scaffolding files for an entity
     * 
     * @param string $entityName The name of the entity (e.g. User, Product)
     * @param string $module The module name where to generate the files
     * @return bool True if generation was successful
     */
    public function generateScaffolding(string $entityName, string $module): bool {
        // Check if module exists in project root
        $modulePath = PROJECT_ROOT . '/' . $module;
        if (!is_dir($modulePath)) {
            throw new \RuntimeException(
                "Module directory not found: {$module}\n" .
                "Expected project structure:\n" .
                "  YourProject/\n" .
                "  ├── SismaFramework/  (git submodule)\n" .
                "  ├── {$module}/       (your module)\n" .
                "  │   └── Application/\n" .
                "  │       ├── Controllers/\n" .
                "  │       ├── Models/\n" .
                "  │       ├── Forms/\n" .
                "  │       └── Entities/\n" .
                "  └── OtherModules/\n\n" .
                "Make sure your module is in the project root directory."
            );
        }

        // Check if Application directory exists
        $applicationPath = $modulePath . '/Application';
        if (!is_dir($applicationPath)) {
            throw new \RuntimeException(
                "Application directory not found in module: {$module}\n" .
                "Each module in the project root must have this structure:\n" .
                "  {$module}/\n" .
                "  └── Application/\n" .
                "      ├── Controllers/\n" .
                "      ├── Models/\n" .
                "      ├── Forms/\n" .
                "      └── Entities/\n\n" .
                "Create this structure or use --force to create it automatically."
            );
        }

        // Ensure required directories exist
        $requiredDirs = ['Controllers', 'Models', 'Forms', 'Entities'];
        foreach ($requiredDirs as $dir) {
            $dirPath = $applicationPath . '/' . $dir;
            if (!is_dir($dirPath)) {
                if (!mkdir($dirPath, 0777, true)) {
                    throw new \RuntimeException("Failed to create directory: {$dirPath}");
                }
            }
        }

        // Build namespaces
        $moduleNamespace = str_replace('/', '\\', $module);
        $entityNamespace = $moduleNamespace . '\\Application\\Entities';
        $modelNamespace = $moduleNamespace . '\\Application\\Models';
        $controllerNamespace = $moduleNamespace . '\\Application\\Controllers';
        $formNamespace = $moduleNamespace . '\\Application\\Forms';
        
        // Check if entity exists
        $entityClass = $entityNamespace . '\\' . $entityName;
        if (!class_exists($entityClass)) {
            throw new \RuntimeException(
                "Entity class not found: {$entityClass}\n" .
                "Make sure the entity exists in:\n" .
                "  {$module}/Application/Entities/{$entityName}.php\n\n" .
                "The entity class should be defined as:\n" .
                "  namespace {$entityNamespace};\n" .
                "  class {$entityName} extends \\SismaFramework\\Orm\\BaseClasses\\BaseEntity { ... }"
            );
        }

        // Set common placeholders
        $this->setPlaceholder('className', $entityName);

        // Generate Model
        $modelType = $this->customType ?? $this->determineModelType($entityClass);
        $this->setPlaceholder('modelType', $modelType);
        $this->setPlaceholder('namespace', $modelNamespace);
        $success = $this->generate('Model', $modulePath . '/Application/Models/' . $entityName . 'Model.php');

        // Generate Controller
        $this->setPlaceholder('namespace', $controllerNamespace);
        $success &= $this->generate('Controller', $modulePath . '/Application/Controllers/' . $entityName . 'Controller.php');

        // Generate Form
        $this->setPlaceholder('namespace', $formNamespace);
        $success &= $this->generate('Form', $modulePath . '/Application/Forms/' . $entityName . 'Form.php');

        return $success;
    }

    /**
     * Determine which type of Model to use based on the entity
     */
    private function determineModelType(string $entityClass): string {
        $reflection = new \ReflectionClass($entityClass);
        
        if ($reflection->isSubclassOf(SelfReferencedEntity::class)) {
            return 'SelfReferencedModel';
        } elseif ($reflection->isSubclassOf(ReferencedEntity::class)) {
            return 'DependentModel';
        } else {
            return 'BaseModel';
        }
    }
    
    /**
     * Get the template path
     */
    private function getTemplatePath(string $templateName): string {
        if ($this->customTemplatePath) {
            $path = rtrim($this->customTemplatePath, '/') . '/';
            return $path . $templateName . '.php.template';
        }
        
        return $this->templatesPath . $templateName . '.php.template';
    }
}

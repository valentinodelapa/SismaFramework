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
        // Build namespaces
        $moduleNamespace = str_replace('/', '\\', $module);
        $entityNamespace = $moduleNamespace . '\\Application\\Entities';
        $modelNamespace = $moduleNamespace . '\\Application\\Models';
        $controllerNamespace = $moduleNamespace . '\\Application\\Controllers';
        $formNamespace = $moduleNamespace . '\\Application\\Forms';
        
        // Check if entity exists
        $entityClass = $entityNamespace . '\\' . $entityName . 'Entity';
        if (!class_exists($entityClass)) {
            throw new \RuntimeException("Entity class not found: {$entityClass}");
        }

        // Set common placeholders
        $this->setPlaceholder('className', $entityName);

        // Generate Model
        $modelType = $this->customType ?? $this->determineModelType($entityClass);
        $this->setPlaceholder('modelType', $modelType);
        $this->setPlaceholder('namespace', $modelNamespace);
        $success = $this->generate('Model', $this->getOutputPath($module . '/Application/Models', $entityName . 'Model'));

        // Generate Controller
        $this->setPlaceholder('namespace', $controllerNamespace);
        $success &= $this->generate('Controller', $this->getOutputPath($module . '/Application/Controllers', $entityName . 'Controller'));

        // Generate Form
        $this->setPlaceholder('namespace', $formNamespace);
        $success &= $this->generate('Form', $this->getOutputPath($module . '/Application/Forms', $entityName . 'Form'));

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
     * Get the output path for a generated file
     */
    private function getOutputPath(string $path, string $className): string {
        return $path . '/' . $className . '.php';
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

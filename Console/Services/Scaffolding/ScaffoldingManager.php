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

namespace SismaFramework\Console\Services\Scaffolding;

use SismaFramework\Console\Enumerations\ModelType;
use SismaFramework\Core\Enumerations\FilterType;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Templater;
use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ScaffoldingManager
{

    private string $templatesPath;
    private array $placeholders;
    private bool $force = false;
    private ?string $customType = null;
    private ?string $customTemplatePath = null;
    private Config $config;

    public function __construct(Config $config = new Config())
    {
        $this->templatesPath = __DIR__ . '/Templates/';
        $this->placeholders = [];
        $this->config = $config;
    }

    public function setForce(bool $force): self
    {
        $this->force = $force;
        return $this;
    }

    public function setCustomType(?string $type): self
    {
        $this->customType = $type;
        return $this;
    }

    public function setCustomTemplatePath(?string $path): self
    {
        $this->customTemplatePath = $path;
        return $this;
    }

    public function generateScaffolding(string $entityName, string $module): bool
    {
        // Check if module exists in project root
        $modulePath = $this->config->rootPath . $module;
        if (!is_dir($modulePath)) {
            throw new \RuntimeException(<<<ERROR
Module directory not found: {$module} .
Expected project structure:
  YourProject/
  ├── SismaFramework/  (git submodule)
  ├── {$module}/       (your module)\
  │   └── Application/
  │       ├── Controllers/
  │       ├── Models/
  │       ├── Forms/
  │       └── Entities/
  └── OtherModules/

Make sure your module is in the project root directory.
ERROR);
        }

        // Check if Application directory exists
        $applicationPath = $modulePath . '/Application';
        if (!is_dir($applicationPath)) {
            throw new \RuntimeException(<<<ERROR
Application directory not found in module: {$module}
Each module in the project root must have this structure:
  {$module}/
  └── Application/
      ├── Controllers/
      ├── Models/
      ├── Forms/
      └── Entities/
  
Create this structure or use --force to create it automatically.
ERROR);
        }

        // Ensure required directories exist
        $requiredDirs = ['Controllers', 'Models', 'Forms', 'Entities', 'Views'];
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
            throw new \RuntimeException(<<<ERROR
Entity class not found: {$entityClass}
Make sure the entity exists in:
  {$module}/Application/Entities/{$entityName}.php

The entity class should be defined as:
  namespace {$entityNamespace};
  class {$entityName} extends \\SismaFramework\\Orm\\BaseClasses\\BaseEntity { ... }
ERROR);
        }

        $this->setPlaceholder('entityName', $entityName);
        $this->setPlaceholder('entityNameLower', lcfirst($entityName));
        $this->setPlaceholder('entityNamespace', $entityNamespace);
        $this->setPlaceholder('modelNamespace', $modelNamespace);
        $this->setPlaceholder('controllerNamespace', $controllerNamespace);
        $this->setPlaceholder('formNamespace', $formNamespace);
        
        $controllerName = str_replace('Controller', '', $entityName . 'Controller');
        $this->setPlaceholder('controllerRoute', NotationManager::convertToKebabCase($controllerName));

        $modelType = $this->customType ? ModelType::from($this->customType) : $this->determineModelType($entityClass);
        $this->setPlaceholder('modelType', $modelType->value);
        $this->setPlaceholder('modelTypeNamespace', $modelType->getNamespace());
        $success = $this->generate('Model', $modulePath . '/Application/Models/' . $entityName . 'Model.php');

        $entityReflection = new \ReflectionClass($entityClass);
        $properties = $this->analyzeEntityProperties($entityReflection);
        $filtersCode = $this->generateFiltersCode($properties);
        $this->setPlaceholder('filters', $filtersCode);
        $success &= $this->generate('Form', $modulePath . '/Application/Forms/' . $entityName . 'Form.php');
        
        $success &= $this->generate('Controller', $modulePath . '/Application/Controllers/' . $entityName . 'Controller.php');

        $viewsPath = $applicationPath . '/Views/' . $entityName;
        if (!is_dir($viewsPath)) {
            if (!mkdir($viewsPath, 0777, true)) {
                throw new \RuntimeException("Failed to create directory: {$viewsPath}");
            }
        }
        $success &= $this->generate('Views/index', $viewsPath . '/index.php');
        $success &= $this->generate('Views/create', $viewsPath . '/create.php');
        $success &= $this->generate('Views/update', $viewsPath . '/update.php');
        return $success;
    }

    private function setPlaceholder(string $key, string $value): self
    {
        $this->placeholders[$key] = $value;
        return $this;
    }

    private function generate(string $templateName, string $outputPath): bool
    {
        $templateFile = $this->getTemplatePath($templateName);
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template file not found: {$templateFile}");
        }
        if (file_exists($outputPath) && !$this->force) {
            throw new \RuntimeException("File already exists: {$outputPath}. Use --force to overwrite.");
        }
        $content = Templater::parseTemplate($templateFile, $this->placeholders);
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        return file_put_contents($outputPath, $content) !== false;
    }

    private function determineModelType(string $entityClass): ModelType
    {
        $reflection = new \ReflectionClass($entityClass);
        if ($reflection->isSubclassOf(SelfReferencedEntity::class)) {
            return ModelType::selfReferencedModel;
        } elseif ($this->checkDependencies($reflection)) {
            return ModelType::dependentModel;
        } else {
            return ModelType::baseModel;
        }
    }

    public function checkDependencies(\ReflectionClass $entityReflection): bool
    {
        foreach ($entityReflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            if (BaseEntity::checkFinalClassReflectionProperty($property) &&
                    is_subclass_of($property->getType()->getName(), BaseEntity::class)) {
                return true;
            }
        }
        return false;
    }

    private function getTemplatePath(string $templateName): string
    {
        if ($this->customTemplatePath) {
            $path = rtrim($this->customTemplatePath, '/') . '/';
            return $path . $templateName . '.tpl';
        }
        return $this->templatesPath . $templateName . '.tpl';
    }

    private function analyzeEntityProperties(\ReflectionClass $entityReflection): array
    {
        $properties = [];
        foreach ($entityReflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            if (!BaseEntity::checkFinalClassReflectionProperty($property)) {
                continue;
            }
            $properties[] = [
                'name' => $property->getName(),
                'type' => $property->getType(),
            ];
        }

        return $properties;
    }

    private function generateFiltersCode(array $properties): string
    {
        $prefix = '$this';
        $lastProperty = end($properties);
        foreach ($properties as $property) {
            if ($this->config->defaultPrimaryKeyPropertyName !== $property['name']) {
                $filterType = FilterType::fromPhpType($property['type'])->name;
                $code[] = sprintf(
                        '        ' . $prefix . '->addFilterFieldMode("%s", FilterType::%s%s)%s',
                        $property['name'],
                        $filterType,
                        $property['type']->allowsNull() ? ', [], true' : '',
                        $property === $lastProperty ? ';' : ''
                );
                $prefix = '    ';
            }
        }
        return implode("\n", $code);
    }
}

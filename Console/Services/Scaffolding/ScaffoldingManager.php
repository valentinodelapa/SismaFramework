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
    private string $module;
    private string $entityShortName;
    private string $entityNamespace;
    private \ReflectionClass $entityReflection;
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

    public function generateScaffolding(string $entityShortName, string $module): bool
    {
        $this->module = $module;
        $this->entityShortName = $entityShortName;
        $modulePath = $this->getModulePath();
        $applicationPath = $this->getApplicationPath($modulePath);
        $this->checkRequiredDirectoriesExists($applicationPath);
        $this->setCommonPlaceholders();
        $this->initializeEntityReflection();
        $success = $this->generateModel($applicationPath);
        $success &= $this->generateForm($applicationPath);
        $success &= $this->generateController($applicationPath);
        $success &= $this->generateViews($applicationPath);
        return $success;
    }

    private function getModulePath(): string
    {
        $modulePath = $this->config->rootPath . $this->module;
        if (!is_dir($modulePath)) {
            throw new \RuntimeException(Templater::parseTemplate(__DIR__ . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'Errors' . DIRECTORY_SEPARATOR . 'modulePathError.tpl', ['module' => $this->module]));
        }
        return $modulePath;
    }

    private function getApplicationPath(string $modulePath): string
    {
        $applicationPath = $modulePath . DIRECTORY_SEPARATOR . $this->config->application;
        if (!is_dir($applicationPath)) {
            throw new \RuntimeException(Templater::parseTemplate(__DIR__ . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'Errors' . DIRECTORY_SEPARATOR . 'applicationPathError.tpl', ['module' => $this->module]));
        }
        return $applicationPath;
    }

    private function checkRequiredDirectoriesExists(string $applicationPath): void
    {
        $requiredDirs = [$this->config->controllers, $this->config->models, $this->config->forms, $this->config->entities, $this->config->views];
        foreach ($requiredDirs as $dir) {
            $dirPath = $applicationPath . '/' . $dir;
            if (!is_dir($dirPath)) {
                if (!mkdir($dirPath, 0777, true)) {
                    throw new \RuntimeException("Failed to create directory: " . $dirPath);
                }
            }
        }
    }

    private function setCommonPlaceholders(): void
    {
        $applicationNamespace = $this->module . '\\' . $this->config->application;
        $this->entityNamespace = $applicationNamespace . '\\' . $this->config->entities;
        $this->setPlaceholder('entityShortName', $this->entityShortName);
        $this->setPlaceholder('entityShortNameLower', lcfirst($this->entityShortName));
        $this->setPlaceholder('entityNamespace', $this->entityNamespace);
        $this->setPlaceholder('modelNamespace', $applicationNamespace . '\\' . $this->config->models);
        $this->setPlaceholder('controllerNamespace', $applicationNamespace . '\\' . $this->config->controllers);
        $this->setPlaceholder('formNamespace', $applicationNamespace . '\\' . $this->config->forms);
    }

    private function setPlaceholder(string $key, string $value): self
    {
        $this->placeholders[$key] = $value;
        return $this;
    }

    private function initializeEntityReflection(): void
    {
        $entityClass = $this->entityNamespace . '\\' . $this->entityShortName;
        if (!class_exists($entityClass)) {
            throw new \RuntimeException(Templater::parseTemplate(__DIR__ . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'Errors' . DIRECTORY_SEPARATOR . 'entityPathError.tpl', [
                                'entityClass' => $entityClass,
                                'entityNamespace' => $this->entityNamespace,
                                'entityShortName' => $this->entityShortName,
                                'module' => $this->module
            ]));
        }
        $this->entityReflection = new \ReflectionClass($entityClass);
    }

    private function generateModel(string $applicationPath): bool
    {
        $modelType = $this->customType ? ModelType::from($this->customType) : $this->determineModelType();
        $this->setPlaceholder('modelType', $modelType->value);
        $this->setPlaceholder('modelTypeNamespace', $modelType->getNamespace());
        return $this->generate('Model', $applicationPath . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $this->entityShortName . 'Model.php');
    }

    private function determineModelType(): ModelType
    {
        if ($this->entityReflection->isSubclassOf(SelfReferencedEntity::class)) {
            return ModelType::selfReferencedModel;
        } elseif ($this->checkDependencies($this->entityReflection)) {
            return ModelType::dependentModel;
        } else {
            return ModelType::baseModel;
        }
    }

    public function checkDependencies(): bool
    {
        foreach ($this->entityReflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            if (BaseEntity::checkFinalClassReflectionProperty($property) &&
                    is_subclass_of($property->getType()->getName(), BaseEntity::class)) {
                return true;
            }
        }
        return false;
    }

    private function generate(string $templateName, string $outputPath): bool
    {
        $templateFile = $this->getTemplatePath($templateName);
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template file not found: {$templateFile}");
        }
        if (file_exists($outputPath) && ($this->force === false)) {
            throw new \RuntimeException("File already exists: {$outputPath}. Use --force to overwrite.");
        }
        $content = Templater::parseTemplate($templateFile, $this->placeholders);
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        return file_put_contents($outputPath, $content) !== false;
    }

    private function getTemplatePath(string $templateName): string
    {
        if ($this->customTemplatePath) {
            $path = rtrim($this->customTemplatePath, '/'. DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            return $path . $templateName . '.tpl';
        }
        return $this->templatesPath . $templateName . '.tpl';
    }

    private function generateForm(string $applicationPath): bool
    {
        $properties = $this->analyzeEntityProperties($this->entityReflection);
        $filtersCode = $this->generateFiltersCode($properties);
        $this->setPlaceholder('filters', $filtersCode);
        return $this->generate('Form', $applicationPath . DIRECTORY_SEPARATOR . 'Forms' . DIRECTORY_SEPARATOR . $this->entityShortName . 'Form.php');
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

    private function generateController(string $applicationPath): bool
    {
        $controllerName = str_replace('Controller', '', $this->entityShortName . 'Controller');
        $this->setPlaceholder('controllerRoute', NotationManager::convertToKebabCase($controllerName));
        return $this->generate('Controller', $applicationPath . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $this->entityShortName . 'Controller.php');
    }

    private function generateViews(string $applicationPath): bool
    {
        $viewsPath = $applicationPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $this->entityShortName . DIRECTORY_SEPARATOR;
        if (!is_dir($viewsPath)) {
            if (!mkdir($viewsPath, 0777, true)) {
                throw new \RuntimeException("Failed to create directory: {$viewsPath}");
            }
        }
        $success = $this->generate('Views' . DIRECTORY_SEPARATOR . 'index', $viewsPath . 'index.php');
        $success &= $this->generate('Views' . DIRECTORY_SEPARATOR . 'create', $viewsPath . 'create.php');
        $success &= $this->generate('Views' . DIRECTORY_SEPARATOR . 'update', $viewsPath . 'update.php');
        return $success;
    }
}

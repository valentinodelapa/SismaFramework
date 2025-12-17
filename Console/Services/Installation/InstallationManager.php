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

namespace SismaFramework\Console\Services\Installation;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class InstallationManager
{

    private string $projectRoot;
    private string $frameworkPath;
    private bool $force = false;

    public function __construct(?string $projectRoot = null)
    {
        $this->frameworkPath = dirname(__DIR__, 3);
        $this->projectRoot = $projectRoot ?? dirname($this->frameworkPath);
    }

    public function setForce(bool $force): self
    {
        $this->force = $force;
        return $this;
    }
    
    public function install(string $projectName, array $config = []): bool
    {
        try {
            $this->createProjectStructure();
            $this->copyConfigFolder($projectName);
            $this->copyPublicFolder();
            $this->createAdditionalFolders();
            $this->createOrUpdateComposerJson($projectName);
            if (!empty($config)) {
                $this->updateConfigFile($config);
            }
            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException("Installation failed: " . $e->getMessage());
        }
    }
    
    private function createProjectStructure(): void
    {
        $directories = ["Config", "Public", "Cache", "Logs", "filesystemMedia"];

        foreach ($directories as $dir) {
            $path = $this->projectRoot . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$dir}");
                }
            }
        }
    }
    
    private function copyConfigFolder(string $projectName): void
    {
        $sourceConfig = $this->frameworkPath . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';
        $destConfig = $this->projectRoot . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'configFramework.php';
        if (file_exists($destConfig) && !$this->force) {
            throw new \RuntimeException("Config file already exists. Use --force to overwrite.");
        }
        if (!copy($sourceConfig, $destConfig)) {
            throw new \RuntimeException("Failed to copy config file.");
        }
        $content = file_get_contents($destConfig);
        $content = preg_replace(
                "/(const\s+PROJECT\s*=\s*')[^']*(')/",
                "$1{$projectName}$2",
                $content
        );

        if (!file_put_contents($destConfig, $content)) {
            throw new \RuntimeException("Failed to update project name in config file.");
        }
    }
    
    private function copyPublicFolder(): void
    {
        $sourcePublic = $this->frameworkPath . DIRECTORY_SEPARATOR . "Public";
        $destPublic = $this->projectRoot . DIRECTORY_SEPARATOR . "Public";
        if (!is_dir($sourcePublic)) {
            throw new \RuntimeException("Source Public directory not found in framework.");
        }

        $this->recursiveCopy($sourcePublic, $destPublic);

        $indexPath = $destPublic . DIRECTORY_SEPARATOR . "index.php";
        if (file_exists($indexPath)) {
            $content = file_get_contents($indexPath);
            $patterns = [
                "dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config'",
                "dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Autoload'",
                "'Config' . DIRECTORY_SEPARATOR . 'config.php'",
            ];
            $replacements = [
                "dirname(__DIR__) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'Config'",
                "dirname(__DIR__) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'Autoload'",
                "'Config' . DIRECTORY_SEPARATOR . 'configFramework.php'",
            ];

            $content = str_replace($patterns, $replacements, $content);

            $vendorAutoload = "require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';\n";
            $autoloadLine = "require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'Autoload' . DIRECTORY_SEPARATOR . 'autoload.php';";
            $content = str_replace($autoloadLine, $vendorAutoload . $autoloadLine, $content);

            file_put_contents($indexPath, $content);
        }
    }
    
    private function createAdditionalFolders(): void
    {
        $additionalDirs = ["Cache", "Logs", "filesystemMedia"];

        foreach ($additionalDirs as $dir) {
            $path = $this->projectRoot . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
                chmod($path, 0777);
            }
        }
    }
    
    private function updateConfigFile(array $config): void
    {
        $configPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'configFramework.php';
        if (!file_exists($configPath)) {
            throw new \RuntimeException("Config file not found.");
        }
        $content = file_get_contents($configPath);
        foreach ($config as $key => $value) {
            $pattern = "/(const\s+{$key}\s*=\s*')[^']*(')/";
            $content = preg_replace($pattern, "$1{$value}$2", $content);
        }
        if (!file_put_contents($configPath, $content)) {
            throw new \RuntimeException("Failed to update config file.");
        }
    }
    
    private function recursiveCopy(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === "." || $file === "..") {
                continue;
            }
            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $dest . DIRECTORY_SEPARATOR . $file;
            if (is_dir($sourcePath)) {
                $this->recursiveCopy($sourcePath, $destPath);
            } else {
                if (file_exists($destPath) && !$this->force) {
                    continue;
                }
                copy($sourcePath, $destPath);
            }
        }
        closedir($dir);
    }
    
    public function initializeModule(string $moduleName): bool
    {
        $modulePath = $this->projectRoot . DIRECTORY_SEPARATOR . $moduleName;
        if (is_dir($modulePath) && !$this->force) {
            throw new \RuntimeException("Module {$moduleName} already exists. Use --force to overwrite.");
        }
        $moduleStructure = [
            "Application",
            "Application/Controllers",
            "Application/Models",
            "Application/Entities",
            "Application/Views",
            "Application/Forms",
            "Application/Assets",
            "Application/Locales",
            "Application/Permissions",
            "Application/Voters",
            "Application/Enumerations",
            "Application/Templates",
        ];
        foreach ($moduleStructure as $dir) {
            $path = $modulePath . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    throw new \RuntimeException("Failed to create module directory: {$dir}");
                }
            }
        }
        return true;
    }

    private function createOrUpdateComposerJson(string $projectName): void
    {
        $composerPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'composer.json';

        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
        } else {
            $composer = [
                'name' => strtolower(str_replace(' ', '-', $projectName)),
                'description' => 'Project built with SismaFramework',
                'type' => 'project',
                'require' => []
            ];
        }

        if (!isset($composer['require']['psr/log'])) {
            $composer['require']['psr/log'] = '^3.0';
        }

        $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!file_put_contents($composerPath, $json)) {
            throw new \RuntimeException('Failed to create/update composer.json');
        }
    }
}

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

namespace SismaFramework\Core\BaseClasses;

use SismaFramework\Orm\Enumerations\AdapterType;

/**
 * Description of Config
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
abstract class BaseConfig
{

    public readonly string $adapters;
    public readonly string $application;
    public readonly string $assets;
    public readonly string $cache;
    public readonly string $controllers;
    public readonly string $core;
    public readonly string $defaultPath;
    public readonly string $defaultAction;
    public readonly string $defaultController;
    public readonly string $entities;
    public readonly string $fixtures;
    public readonly string $logs;
    public readonly string $locales;
    public readonly string $models;
    public readonly string $orm;
    public readonly string $project;
    public readonly string $system;
    public readonly string $structural;
    public readonly string $templates;
    public readonly string $resources;
    public readonly string $views;
    public readonly string $thisDirectory;
    public readonly string $directoryUp;

    /* Base Constant */
    public readonly string $language;
    public readonly string $defaultMetaUrl;
    public readonly int $minimumMajorPhpVersion;
    public readonly int $minimumMinorPhpVersion;
    public readonly int $minimumReleasePhpVersion;
    public readonly int $maxReloadAttempts;
    public readonly string $configurationPassword;
    public readonly string $rootPath;
    public readonly string $applicationPath;
    public readonly string $applicationNamespace;
    public readonly string $applicationAssetsPath;
    public readonly string $systemPath;
    public readonly string $corePath;
    public readonly string $coreNamespace;
    public readonly string $structuralPath;
    public readonly string $structuralAssetsPath;
    public readonly string $publicPath;
    public readonly bool $developmentEnvironment;
    public readonly bool $httpsIsForced;
    public readonly array $moduleFolders;
    public readonly array $autoloadNamespaceMapper;
    public readonly array $autoloadClassMapper;

    /* Fixtures Constant */
    public readonly string $fixturePath;
    public readonly string $fixtureNamespace;

    /* Object Relational Mapper Constant */
    public readonly string $ormPath;
    public readonly string $ormNamespace;
    public readonly bool $ormCache;
    public readonly string $referenceCacheDirectory;
    public readonly string $referenceCachePath;

    /* Adapter Constant */
    public readonly string $adapterPath;
    public readonly string $adapterNamespace;
    public readonly AdapterType $defaultAdapterType;

    /* Entities Constant */
    public readonly string $entityPath;
    public readonly string $entityNamespace;

    /* Models Constant */
    public readonly string $modelPath;
    public readonly string $modelNamespace;

    /* Forms Constant */
    public readonly bool $primaryKeyPassAccepted;

    /* Dispatcher Constant */
    public readonly string $controllerPath;
    public readonly string $controllerNamespace;
    public readonly string $defaultControllerPath;
    public readonly string $defaultControllerNamespace;
    public readonly int $fileGetContentMaxBytesLimit;
    public readonly int $readFileMaxBytesLimit;
    public readonly string $robotsFile;

    /* Render Constant */
    public readonly string $localesPath;
    public readonly string $viewsPath;
    public readonly string $structuralViewsPath;

    /* Templater Constant */
    public readonly string $templatesPath;
    public readonly string $structuralTemplatesPath;

    /* Resource Constant */
    public readonly string $resourcesPath;
    public readonly string $structuralResourcesPath;

    /* Log Constants */
    public readonly string $logDirectoryPath;
    public readonly string $logPath;
    public readonly bool $logVerboseActive;
    public readonly int $logDevelopmentMaxRow;
    public readonly int $logProductionMaxRow;
    public readonly int $logWarningRow;
    public readonly int $logDangerRow;

    /* Encryptor Constants */
    public readonly string $simpleHashAlgorithm;
    public readonly int $blowfishHashWorkload;
    public readonly string $encryptionPassphrase;
    public readonly string $encryptionAlgorithm;
    public readonly int $initializationVectorBytes;

    /* Database Constant */
    public readonly string $databaseHost;
    public readonly string $databaseName;
    public readonly string $databasePassword;
    public readonly string $databasePort;
    public readonly string $databaseUsername;

    /* internal properties */
    private static BaseConfig $defaultConfig;

    public static function setDefault(BaseConfig $defaultConfig): void
    {
        self::$defaultConfig = $defaultConfig;
    }

    public static function getDefault(): self
    {
        return self::$defaultConfig;
    }

    public function __construct()
    {
        $this->setFrameworkConfigurations();
    }

    abstract protected function setFrameworkConfigurations(): void;
}

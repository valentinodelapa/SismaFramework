<?php

/*
 * The MIT License
 *
 * Copyright 2025 Valentino de Lapa.
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

namespace SismaFramework\Config;

use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Orm\Enumerations\AdapterType;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR. 'BaseClasses' . DIRECTORY_SEPARATOR . 'BaseConfig.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Orm' . DIRECTORY_SEPARATOR . 'Enumerations' . DIRECTORY_SEPARATOR . 'AdapterType.php';

/**
 * Description of Config
 *
 * @author Valentino de Lapa
 */
class Config extends BaseConfig
{

    #[\Override]
    public function setFrameworkConfigurations(): void
    {
        // Proprietà di base
        $this->adapters = 'Adapters';
        $this->application = 'Sample';
        $this->assets = 'Assets';
        $this->cache = 'Cache';
        $this->controllers = 'Controllers';
        $this->core = 'Core';
        $this->defaultPath = 'sample';
        $this->defaultAction = 'index';
        $this->defaultController = 'SampleController';
        $this->entities = 'Entities';
        $this->fixtures = 'Fixtures';
        $this->logs = 'Logs';
        $this->locales = 'Locales';
        $this->models = 'Models';
        $this->orm = 'Orm';
        $this->project = 'SismaFramework';
        $this->system = 'SismaFramework';
        $this->structural = 'Structural';
        $this->templates = 'Templates';
        $this->resources = 'Resources';
        $this->views = 'Views';
        $this->thisDirectory = '.';
        $this->directoryUp = '..';

        // Configurazioni di sistema
        $this->language = 'it_IT';
        $this->minimumMajorPhpVersion = 8;
        $this->minimumMinorPhpVersion = 1;
        $this->minimumReleasePhpVersion = 0;
        $this->maxReloadAttempts = 3;
        $this->configurationPassword = '';

        // Percorsi di sistema
        $this->rootPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $this->applicationPath = 'Sample' . DIRECTORY_SEPARATOR;
        $this->applicationNamespace = 'Sample\\';
        $this->applicationAssetsPath = $this->applicationPath . 'Assets' . DIRECTORY_SEPARATOR;
        $this->systemPath = $this->rootPath . 'SismaFramework' . DIRECTORY_SEPARATOR;
        $this->corePath = $this->systemPath . 'Core' . DIRECTORY_SEPARATOR;
        $this->coreNamespace = 'SismaFramework\\Core\\';
        $this->structuralPath = $this->systemPath . 'Structural';
        $this->structuralAssetsPath = $this->structuralPath . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR;
        $this->publicPath = $this->systemPath . 'Public' . DIRECTORY_SEPARATOR;

        // Configurazioni di ambiente
        $this->developmentEnvironment = true;
        $this->httpsIsForced = false;
        $this->moduleFolders = ['SismaFramework'];
        $this->autoloadNamespaceMapper = [];
        $this->autoloadClassMapper = [];

        // Configurazioni Fixtures
        $this->fixturePath = $this->applicationPath . 'Fixtures' . DIRECTORY_SEPARATOR;
        $this->fixtureNamespace = 'Sample\\Fixtures\\';

        // Configurazioni ORM
        $this->ormPath = $this->systemPath . 'Orm' . DIRECTORY_SEPARATOR;
        $this->ormNamespace = 'SismaFramework\\Orm\\';
        $this->ormCache = true;
        $this->referenceCacheDirectory = $this->systemPath . $this->applicationPath . 'Cache' . DIRECTORY_SEPARATOR;
        $this->referenceCachePath = $this->referenceCacheDirectory . 'referenceCache.json';

        // Configurazioni Adapter
        $this->adapterPath = $this->ormPath . 'Adapters' . DIRECTORY_SEPARATOR;
        $this->adapterNamespace = $this->ormNamespace . 'Adapters\\';
        $this->defaultAdapterType = AdapterType::mysql;

        // Configurazioni Entities
        $this->entityPath = $this->applicationPath . 'Entities' . DIRECTORY_SEPARATOR;
        $this->entityNamespace = $this->applicationNamespace . 'Entities\\';

        // Configurazioni Models
        $this->modelPath = $this->applicationPath . 'Models' . DIRECTORY_SEPARATOR;
        $this->modelNamespace = $this->applicationNamespace . 'Models\\';

        // Configurazioni Forms
        $this->primaryKeyPassAccepted = false;

        // Configurazioni Dispatcher
        $this->controllerPath = $this->applicationPath . 'Controllers' . DIRECTORY_SEPARATOR;
        $this->controllerNamespace = $this->applicationNamespace . 'Controllers\\';
        $this->defaultControllerPath = $this->controllerPath . $this->defaultController . '.php';
        $this->defaultControllerNamespace = $this->controllerNamespace . $this->defaultController;
        $this->fileGetContentMaxBytesLimit = 2097152;
        $this->readFileMaxBytesLimit = 104857600;
        $this->robotsFile = 'robots.txt';

        // Configurazioni Render
        $this->localesPath = $this->applicationPath . 'Locales' . DIRECTORY_SEPARATOR;
        $this->viewsPath = $this->applicationPath . 'Views' . DIRECTORY_SEPARATOR;
        $this->structuralViewsPath = $this->structuralPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR;

        // Configurazioni Templater
        $this->templatesPath = $this->applicationPath . 'Templates' . DIRECTORY_SEPARATOR;
        $this->structuralTemplatesPath = $this->structuralPath . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR;

        // Configurazioni Resource
        $this->resourcesPath = $this->applicationPath . 'Resources' . DIRECTORY_SEPARATOR;
        $this->structuralResourcesPath = $this->structuralPath . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR;

        // Configurazioni Log
        $this->logDirectoryPath = $this->systemPath . $this->applicationPath . 'Logs' . DIRECTORY_SEPARATOR;
        $this->logPath = $this->logDirectoryPath . 'log.txt';
        $this->logVerboseActive = true;
        $this->logDevelopmentMaxRow = 1000;
        $this->logProductionMaxRow = 100;
        $this->logWarningRow = 50;
        $this->logDangerRow = 10;

        // Configurazioni Encryptor
        $this->simpleHashAlgorithm = 'sha256';
        $this->blowfishHashWorkload = 10;
        $this->encryptionPassphrase = '';
        $this->encryptionAlgorithm = 'aes-256-cbc';
        $this->initializationVectorBytes = 16;

        // Configurazioni Database
        $this->databaseHost = 'localhost';
        $this->databaseName = 'sample';
        $this->databasePassword = '';
        $this->databasePort = '3306';
        $this->databaseUsername = 'root';
    }
}

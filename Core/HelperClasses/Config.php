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

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Orm\Enumerations\AdapterType;

/**
 * Description of Config
 *
 * @internal
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class Config
{

    protected readonly string $application;
    protected readonly string $controllers;
    protected readonly string $defaultPath;
    protected readonly string $defaultAction;
    protected readonly string $entities;
    protected readonly string $forms;
    protected readonly string $locales;
    protected readonly string $models;
    protected readonly string $project;
    protected readonly string $system;
    protected readonly string $structural;
    protected readonly string $templates;
    protected readonly string $views;

    /* Base Constant */
    protected readonly Language $language;
    protected readonly int $minimumMajorPhpVersion;
    protected readonly int $minimumMinorPhpVersion;
    protected readonly int $minimumReleasePhpVersion;
    protected readonly int $maxReloadAttempts;
    protected readonly string $rootPath;
    protected readonly string $applicationPath;
    protected readonly string $applicationNamespace;
    protected readonly string $applicationAssetsPath;
    protected readonly string $systemPath;
    protected readonly string $structuralPath;
    protected readonly string $structuralAssetsPath;
    protected readonly bool $developmentEnvironment;
    protected readonly bool $httpsIsForced;
    protected readonly array $moduleFolders;
    protected readonly array $autoloadNamespaceMapper;
    protected readonly array $autoloadClassMapper;

    /* Fixtures Constant */
    protected readonly string $fixturePath;
    protected readonly string $fixtureNamespace;

    /* Object Relational Mapper Constant */
    protected readonly bool $ormCache;
    protected readonly string $referenceCacheDirectory;
    protected readonly string $referenceCachePath;

    /* Adapter Constant */
    protected readonly AdapterType $defaultAdapterType;

    /* Entities Constant */
    protected readonly string $entityPath;
    protected readonly string $entityNamespace;
    protected readonly string $defaultPrimaryKeyPropertyName;
    protected readonly string $foreignKeySuffix;
    protected readonly string $parentPrefixPropertyName;
    protected readonly string $sonCollectionPropertyName;

    /* Models Constant */
    protected readonly string $modelNamespace;
    protected readonly string $sonCollectionGetterMethod;

    /* Forms Constant */
    protected readonly bool $primaryKeyPassAccepted;

    /* Dispatcher Constant */
    protected readonly string $controllerNamespace;
    protected readonly array $customRenderableResourceTypes;
    protected readonly array $customDownloadableResourceTypes;

    /* Render Constant */
    protected readonly string $localesPath;
    protected readonly string $viewsPath;
    protected readonly string $structuralViewsPath;

    /* Templater Constant */
    protected readonly string $templatesPath;
    protected readonly string $structuralTemplatesPath;

    /* Resource Constant */
    protected readonly string $resourcesPath;

    /* Log Constants */
    protected readonly string $logDirectoryPath;
    protected readonly string $logPath;
    protected readonly bool $logVerboseActive;
    protected readonly int $logDevelopmentMaxRow;
    protected readonly int $logProductionMaxRow;

    /* Encryptor Constants */
    protected readonly int $simpleRandomTokenBytes;
    protected readonly string $simpleHashAlgorithm;
    protected readonly int $blowfishHashWorkload;
    protected readonly ?string $encryptionPassphrase;
    protected readonly string $encryptionAlgorithm;
    protected readonly int $initializationVectorBytes;
    protected readonly string $encryptedPropertyInitializationVectorSuffix;
    protected readonly int $asymmetricKeyType;
    protected readonly int $asymmetricKeyBits;
    protected readonly string $asymmetricDigestAlgorithm;
    protected readonly int $certificateValidityDays;
    protected readonly string $opensslConfigPath;
    protected readonly int $passwordKeyDerivationDefaultIterations;
    protected readonly int $passwordKeyDerivationMinimumIterations;
    protected readonly int $passwordKeyDerivationMaximumIterations;
    protected readonly string $passwordKeyDerivationHashAlgorithm;
    protected readonly int $passwordKeyDerivationSaltBytes;
    protected readonly string $passwordEncryptionAlgorithm;
    protected readonly int $passwordEncryptionInitializationVectorBytes;
    protected readonly int $passwordEncryptionTagBytes;
    protected readonly int $passwordEncryptionMinimumTagBytes;
    protected readonly string $passwordEnvelopeVersion;
    protected readonly string $passwordEnvelopeSeparator;
    protected readonly string $passwordEnvelopeKeyDerivationPrefix;

    /* Session Constants */
    protected readonly int $sessionLifetime;
    protected readonly string $sessionCookiePath;
    protected readonly string $sessionCookieDomain;
    protected readonly bool $sessionCookieHttpOnly;
    protected readonly string $sessionCookieSameSite;
    protected readonly string $sessionTokenKey;
    protected readonly string $sessionTokenHashAlgorithm;
    protected readonly int $sessionRotationGraceSeconds;
    protected readonly int $sessionMaximumRotationHops;
    protected readonly string $sessionRotatedAtKey;
    protected readonly string $sessionNextSessionIdKey;

    /* Authentication Constants */
    protected readonly string $csrfTokenKey;
    protected readonly string $authenticationIdentifierField;
    protected readonly string $authenticationPasswordField;
    protected readonly string $authenticationMultiFactorCodeField;
    protected readonly string $oauthStateKey;
    protected readonly int $oauthStateBytes;

    /* Database Constant */
    protected readonly string $databaseHost;
    protected readonly string $databaseName;
    protected readonly string $databasePassword;
    protected readonly string $databasePort;
    protected readonly string $databaseUsername;

    /* internal properties */
    private static Config $instance;

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function setInstance(Config $instance): void
    {
        self::$instance = $instance;
    }

    public static function getInstance(): self
    {
        if (isset(self::$instance) === false) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public function __get(string $name): mixed
    {
        $this->forceSet($name);
        return $this->$name;
    }

    private function forceSet(string $name): void
    {
        if (isset($this->$name) === false) {
            $reflectionProperty = new \ReflectionProperty($this, $name);
            $this->$name = Parser::simpleParseValue($reflectionProperty->getType(), $this->getProjectOrFrameworkValue($name));
        }
    }

    private function getProjectOrFrameworkValue(string $name): mixed
    {
        $constantName = NotationManager::convertToUpperSnakeCase($name);
        if (defined('Config\\' . $constantName)) {
            return constant('Config\\' . $constantName);
        }
        require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';
        return constant('SismaFramework\\Config\\' . $constantName);
    }

    public function __isset(string $name): bool
    {
        $this->forceSet($name);
        return isset($this->$name);
    }
}

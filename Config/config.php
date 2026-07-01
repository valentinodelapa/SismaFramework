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

namespace Config;

/* Name Constant */

const APPLICATION = "Sample";
const ASSETS = "Assets";
const CACHE = "Cache";
const CONTROLLERS = "Controllers";
const DEFAULT_PATH = "sample";
const DEFAULT_ACTION = "index";
const ENTITIES = "Entities";
const FIXTURES = "Fixtures";
const FORMS = "Forms";
const LOGS = "Logs";
const LOCALES = "Locales";
const MODELS = "Models";
const PROJECT = "SismaFramework";
const SYSTEM = "SismaFramework";
const STRUCTURAL = "Structural";
const TEMPLATES = "Templates";
const VIEWS = "Views";
const DIRECTORY_UP = "..";

/* Base Constant */
const LANGUAGE = "it_IT";
const MINIMUM_MAJOR_PHP_VERSION = 8;
const MINIMUM_MINOR_PHP_VERSION = 1;
const MINIMUM_RELEASE_PHP_VERSION = 0;
const MAX_RELOAD_ATTEMPTS = 3;
const ROOT_PATH = __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_UP . DIRECTORY_SEPARATOR . DIRECTORY_UP . DIRECTORY_SEPARATOR;
const APPLICATION_PATH = APPLICATION . DIRECTORY_SEPARATOR;
const APPLICATION_NAMESPACE = APPLICATION . "\\";
const APPLICATION_ASSETS_PATH = APPLICATION_PATH . ASSETS . DIRECTORY_SEPARATOR;
const SYSTEM_PATH = ROOT_PATH . SYSTEM . DIRECTORY_SEPARATOR;
const STRUCTURAL_PATH = SYSTEM_PATH . STRUCTURAL;
const STRUCTURAL_ASSETS_PATH = STRUCTURAL_PATH . DIRECTORY_SEPARATOR . ASSETS . DIRECTORY_SEPARATOR;
const DEVELOPMENT_ENVIRONMENT = true;
const HTTPS_IS_FORCED = false;
const MODULE_FOLDERS = ["SismaFramework"];
const AUTOLOAD_NAMESPACE_MAPPER = [];
const AUTOLOAD_CLASS_MAPPER = [];

/* Fixtures Constant */
const FIXTURE_PATH = APPLICATION_PATH . FIXTURES . DIRECTORY_SEPARATOR;
const FIXTURE_NAMESPACE = APPLICATION_NAMESPACE . FIXTURES . "\\";

/* Object Relational Mapper Constant */
const ORM_CACHE = true;
const REFERENCE_CACHE_DIRECTORY = SYSTEM_PATH . APPLICATION_PATH . CACHE . DIRECTORY_SEPARATOR;
const REFERENCE_CACHE_PATH = REFERENCE_CACHE_DIRECTORY . "referenceCache.json";

/* Orm Adapter Constant */
const DEFAULT_ORM_ADAPTER_TYPE = "mysql";

/* Entities Constant */
const ENTITY_PATH = APPLICATION_PATH . ENTITIES . DIRECTORY_SEPARATOR;
const ENTITY_NAMESPACE = APPLICATION_NAMESPACE . ENTITIES . "\\";
const DEFAULT_PRIMARY_KEY_PROPERTY_NAME = "id";
const FOREIGN_KEY_SUFFIX = "Collection";
const PARENT_PREFIX_PROPERTY_NAME = "parent";
const SON_COLLECTION_PROPERTY_NAME = "sonCollection";

/* Models Constant */
const MODEL_NAMESPACE = APPLICATION_NAMESPACE . MODELS . "\\";
const SON_COLLECTION_GETTER_METHOD = "getSonCollection";

/* Forms Constant */
const PRIMARY_KEY_PASS_ACCEPTED = false;

/* Dispatcher Constant */
const CONTROLLER_NAMESPACE = APPLICATION_NAMESPACE . CONTROLLERS . "\\";
const CUSTOM_RENDERABLE_RESOURCE_TYPES = [];
const CUSTOM_DOWNLOADABLE_RESOURCE_TYPES = [];

/* Render Constant */
const LOCALES_PATH = APPLICATION_PATH . LOCALES . DIRECTORY_SEPARATOR;
const VIEWS_PATH = APPLICATION_PATH . VIEWS . DIRECTORY_SEPARATOR;
const STRUCTURAL_VIEWS_PATH = STRUCTURAL_PATH . DIRECTORY_SEPARATOR . VIEWS . DIRECTORY_SEPARATOR;

/* Templater Constant */
const TEMPLATES_PATH = APPLICATION_PATH . TEMPLATES . DIRECTORY_SEPARATOR;
const STRUCTURAL_TEMPLATES_PATH = STRUCTURAL_PATH . DIRECTORY_SEPARATOR . TEMPLATES . DIRECTORY_SEPARATOR;

/* Log Constants */
const LOG_DIRECTORY_PATH = SYSTEM_PATH . APPLICATION_PATH . LOGS . DIRECTORY_SEPARATOR;
const LOG_PATH = LOG_DIRECTORY_PATH . "log.txt";
const LOG_VERBOSE_ACTIVE = true;
const LOG_DEVELOPMENT_MAX_ROW = 1000;
const LOG_PRODUCTION_MAX_ROW = 100;

/* Encryptor Constants */
const SIMPLE_HASH_ALGORITHM = "sha256";
const BLOWFISH_HASH_WORKLOAD = 12;
define(__NAMESPACE__ . '\ENCRYPTION_PASSPHRASE', getenv('ENCRYPTION_PASSPHRASE') ?: "");
const ENCRYPTION_ALGORITHM = "AES-256-CBC";
const INITIALIZATION_VECTOR_BYTES = 16;

/* Database Constant */
define(__NAMESPACE__ . '\ORM_DATABASE_HOST', getenv('ORM_DATABASE_HOST') ?: "");
define(__NAMESPACE__ . '\ORM_DATABASE_NAME', getenv('ORM_DATABASE_NAME') ?: "");
define(__NAMESPACE__ . '\ORM_DATABASE_PASSWORD', getenv('ORM_DATABASE_PASSWORD') ?: "");
define(__NAMESPACE__ . '\ORM_DATABASE_PORT', getenv('ORM_DATABASE_PORT') ?: "");
define(__NAMESPACE__ . '\ORM_DATABASE_USERNAME', getenv('ORM_DATABASE_USERNAME') ?: "");

/* Object Document Mapper Constant */
const DEFAULT_ODM_ADAPTER_TYPE = "mongodb";
define(__NAMESPACE__ . '\ODM_DATABASE_HOST', getenv('ODM_DATABASE_HOST') ?: "");
define(__NAMESPACE__ . '\ODM_DATABASE_NAME', getenv('ODM_DATABASE_NAME') ?: "");
define(__NAMESPACE__ . '\ODM_DATABASE_PASSWORD', getenv('ODM_DATABASE_PASSWORD') ?: "");
define(__NAMESPACE__ . '\ODM_DATABASE_PORT', getenv('ODM_DATABASE_PORT') ?: "");
define(__NAMESPACE__ . '\ODM_DATABASE_USERNAME', getenv('ODM_DATABASE_USERNAME') ?: "");

/* Documents Constant */
const DOCUMENT_NAMESPACE = APPLICATION_NAMESPACE . "Documents\\";

/* Document Models Constant */
const DOCUMENT_MODEL_NAMESPACE = APPLICATION_NAMESPACE . "DocumentModels\\";

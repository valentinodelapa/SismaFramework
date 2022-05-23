<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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

const ADAPTERS = 'Adapters';
const APPLICATION = 'Sample';
const ASSETS = 'Assets';
const CONTROLLERS = 'Controllers';
const CORE = 'Core';
const DEFAULT_PATH = 'Sample';
const DEFAULT_ACTION = 'index';
const DEFAULT_CONTROLLER = DEFAULT_PATH . 'Controller';
const DEFAULT_LOCALE = 'it_IT';
const ENTITIES = 'Entities';
const FIXTURES = 'Fixtures';
const LOCALES = 'Locales';
const MODELS = 'Models';
const ORM = 'Orm';
const PROJECT = 'SismaFramework';
const SYSTEM = 'SismaFramework';
const STRUCTURAL = 'Structural';
const TEMPLATES = 'Templates';
const VIEWS = 'Views';

/* Base Constant */
const MAJOR = 3;
const MINOR = 1;
const PATCH = 0;
const LANGUAGE = 'it_IT';
const DEFAULT_META_URL = '';
const MINIMUM_PHP_VERSION = '8.1.0';
const MAX_RELOAD_ATTEMPTS = 3;
const CONFIGURATION_PASSWORD = '';
const ENCRYPTION_KEY = '';
const ROOT_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
const APPLICATION_PATH = APPLICATION . DIRECTORY_SEPARATOR;
const APPLICATION_NAMESPACE = APPLICATION . '\\';
const APPLICATION_ASSETS_PATH = APPLICATION_PATH . ASSETS . DIRECTORY_SEPARATOR;
const SYSTEM_PATH = ROOT_PATH . SYSTEM . DIRECTORY_SEPARATOR;
const CORE_PATH = SYSTEM_PATH . CORE . DIRECTORY_SEPARATOR;
const CORE_NAMESPACE = SYSTEM . '\\' . CORE . '\\';
const STRUCTURAL_PATH = SYSTEM_PATH . STRUCTURAL;
const STRUCTURAL_ASSETS_PATH = STRUCTURAL_PATH . DIRECTORY_SEPARATOR . ASSETS . DIRECTORY_SEPARATOR;
const LOG_PATH = SYSTEM_PATH . 'Logs' . DIRECTORY_SEPARATOR . 'log.txt';
const PUBLIC_PATH = SYSTEM_PATH . 'Public' . DIRECTORY_SEPARATOR;
const DEVELOPMENT_ENVIRONMENT = true;
const MODULE_FOLDERS = [
    'SismaFramework',
];
const AUTOLOAD_NAMESPACE_MAPPER = [
];
const AUTOLOAD_CLASS_MAPPER = [
];

/* Fixtures Constant */
const FIXTURE_PATH = APPLICATION_PATH . FIXTURES . DIRECTORY_SEPARATOR;
const FIXTURE_NAMESPACE = APPLICATION_NAMESPACE . FIXTURES . '\\';

/* Object Relational Mapper Constant */
const ORM_PATH = CORE_PATH . ORM . DIRECTORY_SEPARATOR;
const ORM_NAMESPACE = CORE_NAMESPACE . ORM . '\\';
const ORM_CACHE = true;

/* Adapter Constant */
const ADAPTER_PATH = ORM_PATH . ADAPTERS . DIRECTORY_SEPARATOR;
const ADAPTER_NAMESPACE = ORM_NAMESPACE . ADAPTERS . '\\';
const DEFAULT_ADAPTER = ADAPTER_NAMESPACE . 'AdapterMysql';

/* Entities Constant */
const ENTITY_PATH = APPLICATION_PATH . ENTITIES . DIRECTORY_SEPARATOR;
const ENTITY_NAMESPACE = APPLICATION_NAMESPACE . ENTITIES . '\\';

/* Models Constant */
const MODEL_PATH = APPLICATION_PATH . MODELS . DIRECTORY_SEPARATOR;
const MODEL_NAMESPACE = APPLICATION_NAMESPACE . MODELS . '\\';

/* Forms Constant */
const COLLECTION_FROM_FORM_NUMBER = 50;
const PRIMARY_KEY_PASS_ACCEPTED = false;

/* Dispatcher Constant */
const CONTROLLER_PATH = APPLICATION_PATH . CONTROLLERS . DIRECTORY_SEPARATOR;
const CONTROLLER_NAMESPACE = APPLICATION_NAMESPACE . CONTROLLERS . '\\';
const DEFAULT_CONTROLLER_PATH = CONTROLLER_PATH . DEFAULT_CONTROLLER;
const DEFAULT_CONTROLLER_NAMESPACE = CONTROLLER_NAMESPACE . DEFAULT_CONTROLLER;

/* Render Constant */
const LOCALES_PATH = APPLICATION_PATH . LOCALES . DIRECTORY_SEPARATOR;
const VIEWS_PATH = APPLICATION_PATH . VIEWS . DIRECTORY_SEPARATOR;

/* Templater Constant */
const TEMPLATES_PATH = APPLICATION_PATH . TEMPLATES . DIRECTORY_SEPARATOR;
const STRUCTURAL_TEMPLATES_PATH = STRUCTURAL_PATH . DIRECTORY_SEPARATOR . TEMPLATES . DIRECTORY_SEPARATOR;

/* Database Constant */
const DATABASE_ADAPTER_TYPE = '';
const DATABASE_HOST = '';
const DATABASE_NAME = '';
const DATABASE_PASSWORD = '';
const DATABASE_PORT = '';
const DATABASE_USERNAME = '';


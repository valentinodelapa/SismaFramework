<?php

namespace Sisma\Core;

/* Name Constant */
const ADAPTERS = 'Adapters';
const APPLICATION = 'Sample';
const ASSETS = 'Assets';
const CONTROLLERS = 'Controllers';
const CORE = 'Core';
const DEFAULT_PATH = 'Default';
const DEFAULT_ACTION = 'index';
const DEFAULT_CONTROLLER = DEFAULT_PATH . 'Controller';
const DEFAULT_LOCALE = 'it_IT';
const ENTITIES = 'Entities';
const FIXTURES = 'Fixtures';
const LOCALES = 'Locales';
const MODELS = 'Models';
const ORM = 'ObjectRelationalMapper';
const PROJECT = 'Sisma';
const SYSTEM = 'Sisma';
const TEMPLATES = 'Templates';
const VIEWS = 'Views';

/* Base Constant */
const MAX_RELOAD_ATTEMPTS = 3;
const CONFIGURATION_PASSWORD = '';
const ENCRYPTION_KEY = '';
const ROOT_PATH = __DIR__ . '/../';
const APPLICATION_PATH = ROOT_PATH . APPLICATION . '/';
const APPLICATION_NAMESPACE = PROJECT . '\\' . APPLICATION . '\\';
const APPLICATION_ASSETS_PATH = APPLICATION_PATH . ASSETS . '/';
const SYSTEM_PATH = ROOT_PATH;
const CORE_PATH = SYSTEM_PATH . CORE . '/';
const CORE_NAMESPACE = SYSTEM . '\\' . CORE . '\\';
const CORE_ASSETS_PATH = CORE_PATH . ASSETS . '/';
const LOG_PATH = SYSTEM_PATH . 'Logs/log.txt';
const PUBLIC_PATH = SYSTEM_PATH . 'Public/';
const DEVELOPMENT_ENVIRONMENT = true;
const ASSET_FOLDERS = [
    'text/css' => 'css',
    'image/jpeg' => 'jpeg',
    'image/png' => 'png',
    'image/svg+xml' => 'svg',
    'application/javascript' => 'javascript',
];

/* Fixtures Constant */
const FIXTURE_PATH = APPLICATION_PATH . FIXTURES . '/';
const FIXTURE_NAMESPACE = APPLICATION_NAMESPACE . FIXTURES . '\\';

/* Object Relational Mapper Constant */
const ORM_PATH = CORE_PATH . ORM . '/';
const ORM_NAMESPACE = CORE_NAMESPACE . ORM . '\\';

/* Adapter Constant */
const ADAPTER_PATH = ORM_PATH . ADAPTERS . '/';
const ADAPTER_NAMESPACE = ORM_NAMESPACE . ADAPTERS . '\\';
const DEFAULT_ADAPTER = ADAPTER_NAMESPACE . 'AdapterMysql';

/* Entities Constant */
const ENTITY_PATH = APPLICATION_PATH . ENTITIES . '/';
const ENTITY_NAMESPACE = APPLICATION_NAMESPACE . ENTITIES . '\\';

/* Models Constant */
const MODEL_PATH = APPLICATION_PATH . MODELS . '/';
const MODEL_NAMESPACE = APPLICATION_NAMESPACE . MODELS . '\\';

/* Dispatcher Constant */
const CONTROLLER_PATH = APPLICATION_PATH . CONTROLLERS . '/';
const CONTROLLER_NAMESPACE = APPLICATION_NAMESPACE . CONTROLLERS . '\\';
const DEFAULT_CONTROLLER_PATH = CONTROLLER_PATH . DEFAULT_CONTROLLER;
const DEFAULT_CONTROLLER_NAMESPACE = CONTROLLER_NAMESPACE . DEFAULT_CONTROLLER;

/* Render Constant */
const LOCALES_PATH = APPLICATION_PATH . LOCALES . '/';
const VIEWS_PATH = APPLICATION_PATH . VIEWS . '/';

/* Templater Constant */
const TEMPLATES_PATH = APPLICATION_PATH . TEMPLATES.'/';

/* Database Constant */
const DATABASE_ADAPTER_TYPE = '';
const DATABASE_HOST = '';
const DATABASE_NAME = '';
const DATABASE_PASSWORD = '';
const DATABASE_PORT = '';
const DATABASE_USERNAME = '';

/* Google Apis */
const GOOGLE_RECAPTCHA_SECRET_KEY = '';
const GOOGLE_RECAPTCHA_SITE_KEY = '';


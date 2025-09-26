# Configuration Reference

SismaFramework utilizza un sistema di configurazione basato su costanti PHP definite nel file `Config/config.php`. Questa guida fornisce una documentazione completa di tutte le opzioni di configurazione disponibili.

## Panoramica

La configurazione è organizzata in sezioni logiche:
- **Costanti dei Nomi**: Definiscono nomi di cartelle e componenti
- **Costanti Base**: Configurazione generale dell'applicazione
- **Database**: Configurazione connessione database
- **ORM**: Impostazioni Object-Relational Mapper
- **Sicurezza**: Configurazione crittografia e sicurezza
- **Logging**: Sistema di log e debug
- **Moduli**: Gestione architettura modulare

---

## Configurazione Base

### Ambiente e Debug

```php
// Abilita ambiente di sviluppo
const DEVELOPMENT_ENVIRONMENT = true;

// Forza l'uso di HTTPS
const HTTPS_IS_FORCED = false;

// Password per accesso configurazione avanzata
const CONFIGURATION_PASSWORD = '';
```

#### DEVELOPMENT_ENVIRONMENT
- **Tipo**: `bool`
- **Default**: `true`
- **Descrizione**: Controlla l'ambiente dell'applicazione
- **Effetti**:
  - `true`: Mostra errori dettagliati, abilita debug bar, permette fixtures
  - `false`: Nasconde errori, disabilita debug, ambiente produzione

**Esempio**:
```php
// Sviluppo
const DEVELOPMENT_ENVIRONMENT = true;

// Produzione
const DEVELOPMENT_ENVIRONMENT = false;
```

#### HTTPS_IS_FORCED
- **Tipo**: `bool`
- **Default**: `false`
- **Descrizione**: Forza il redirect da HTTP a HTTPS
- **Uso**: Impostare `true` in produzione per sicurezza

### Lingua e Localizzazione

```php
// Lingua predefinita dell'applicazione
const LANGUAGE = 'it_IT';

// Meta URL per l'applicazione
const DEFAULT_META_URL = '';
```

#### LANGUAGE
- **Tipo**: `string`
- **Default**: `'it_IT'`
- **Valori**: Qualsiasi locale supportato (es. `'en_US'`, `'fr_FR'`)
- **Descrizione**: Imposta la lingua predefinita per l'internazionalizzazione

**Esempio**:
```php
const LANGUAGE = 'en_US';  // Inglese americano
const LANGUAGE = 'it_IT';  // Italiano
const LANGUAGE = 'fr_FR';  // Francese
```

### Requisiti PHP

```php
// Versione minima PHP richiesta
const MINIMUM_MAJOR_PHP_VERSION = 8;
const MINIMUM_MINOR_PHP_VERSION = 1;
const MINIMUM_RELEASE_PHP_VERSION = 0;

// Numero massimo tentativi reload
const MAX_RELOAD_ATTEMPTS = 3;
```

---

## Configurazione Database

```php
// Connessione database
const DATABASE_HOST = 'localhost';
const DATABASE_NAME = 'my_database';
const DATABASE_USERNAME = 'db_user';
const DATABASE_PASSWORD = 'db_password';
const DATABASE_PORT = '3306';
```

### Parametri Database

| Costante | Descrizione | Esempio |
|----------|-------------|---------|
| `DATABASE_HOST` | Host del database | `'localhost'`, `'127.0.0.1'` |
| `DATABASE_NAME` | Nome del database | `'my_app_db'` |
| `DATABASE_USERNAME` | Username per connessione | `'db_user'` |
| `DATABASE_PASSWORD` | Password per connessione | `'secure_password'` |
| `DATABASE_PORT` | Porta del database | `'3306'` (MySQL), `'5432'` (PostgreSQL) |

**Configurazione di Esempio**:
```php
// MySQL/MariaDB
const DATABASE_HOST = 'localhost';
const DATABASE_NAME = 'sisma_app';
const DATABASE_USERNAME = 'sisma_user';
const DATABASE_PASSWORD = 'MySecurePassword123!';
const DATABASE_PORT = '3306';

// PostgreSQL
const DATABASE_HOST = 'localhost';
const DATABASE_NAME = 'sisma_app';
const DATABASE_USERNAME = 'postgres';
const DATABASE_PASSWORD = 'postgres_password';
const DATABASE_PORT = '5432';
```

---

## Configurazione ORM

### Impostazioni Base ORM

```php
// Cache ORM abilitata
const ORM_CACHE = true;

// Tipo adapter predefinito
const DEFAULT_ADAPTER_TYPE = 'mysql';

// Cache riferimenti entità
const REFERENCE_CACHE_DIRECTORY = 'path/to/cache/';
const REFERENCE_CACHE_PATH = 'path/to/cache/referenceCache.json';
```

#### ORM_CACHE
- **Tipo**: `bool`
- **Default**: `true`
- **Descrizione**: Abilita la cache delle query e metadata ORM
- **Performance**: Migliora significativamente le prestazioni in produzione

### Configurazione Entità

```php
// Chiave primaria predefinita
const DEFAULT_PRIMARY_KEY_PROPERTY_NAME = 'id';

// Suffisso per collezioni di relazioni
const FOREIGN_KEY_SUFFIX = 'Collection';

// Proprietà per relazioni parent/child
const PARENT_PREFIX_PROPERTY_NAME = 'parent';
const SON_COLLECTION_PROPERTY_NAME = 'sonCollection';
const SON_COLLECTION_GETTER_METHOD = 'getSonCollection';
```

#### DEFAULT_PRIMARY_KEY_PROPERTY_NAME
- **Tipo**: `string`
- **Default**: `'id'`
- **Descrizione**: Nome della proprietà che rappresenta la chiave primaria
- **Convenzione**: Tutte le entità dovrebbero avere questa proprietà

**Esempio Custom**:
```php
// Se preferisci usare 'uuid' come chiave primaria
const DEFAULT_PRIMARY_KEY_PROPERTY_NAME = 'uuid';

// Le entità dovranno avere:
class User extends BaseEntity {
    protected string $uuid;
    // ... altre proprietà
}
```

### Configurazione Form

```php
// Permette passaggio chiavi primarie nei form
const PRIMARY_KEY_PASS_ACCEPTED = false;
```

#### PRIMARY_KEY_PASS_ACCEPTED
- **Tipo**: `bool`
- **Default**: `false`
- **Sicurezza**: `false` per prevenire manipolazione ID via form
- **Descrizione**: Controlla se i form possono modificare chiavi primarie

---

## Configurazione Routing

### Controller Predefiniti

```php
// Percorso e azione predefiniti
const DEFAULT_PATH = 'sample';
const DEFAULT_ACTION = 'index';
const DEFAULT_CONTROLLER = 'SampleController';

// Namespace e percorsi controller
const CONTROLLER_NAMESPACE = APPLICATION_NAMESPACE . CONTROLLERS . '\\';
const CONTROLLER_PATH = APPLICATION_PATH . CONTROLLERS . DIRECTORY_SEPARATOR;
```

#### Configurazione Routing Personalizzata

**Esempio**:
```php
// Homepage personalizzata
const DEFAULT_PATH = 'home';
const DEFAULT_ACTION = 'index';
const DEFAULT_CONTROLLER = 'HomeController';

// API come default
const DEFAULT_PATH = 'api';
const DEFAULT_ACTION = 'index';
const DEFAULT_CONTROLLER = 'ApiController';
```

### Tipi di Risorsa Personalizzati

```php
// Tipi di file renderizzabili custom
const CUSTOM_RENDERABLE_RESOURCE_TYPES = ['xml', 'rss'];

// Tipi di file scaricabili custom
const CUSTOM_DOWNLOADABLE_RESOURCE_TYPES = ['pdf', 'docx'];
```

---

## Configurazione Moduli

### Registrazione Moduli

```php
// Lista moduli attivi
const MODULE_FOLDERS = [
    'SismaFramework',
    'Blog',
    'User',
    'Shop'
];

// Mapping namespace personalizzati
const AUTOLOAD_NAMESPACE_MAPPER = [
    'Legacy\\' => 'OldCode/'
];

// Mapping classi specifiche
const AUTOLOAD_CLASS_MAPPER = [
    'OldClass' => 'NewNamespace\\NewClass'
];
```

#### MODULE_FOLDERS
- **Tipo**: `array`
- **Descrizione**: Lista ordinata dei moduli da caricare
- **Ordine**: Importante per override di rotte e risorse

**Esempio Configurazione Multi-Modulo**:
```php
const MODULE_FOLDERS = [
    'SismaFramework',  // Framework core (sempre primo)
    'Core',            // Funzionalità core app
    'User',            // Gestione utenti
    'Blog',            // Sistema blog
    'Shop',            // E-commerce
    'Admin',           // Pannello admin (ultimo per override)
];
```

---

## Configurazione Sicurezza

### Crittografia

```php
// Algoritmi hash
const SIMPLE_HASH_ALGORITHM = 'sha256';
const BLOWFISH_HASH_WORKLOAD = 12;

// Crittografia simmetrica
const ENCRYPTION_PASSPHRASE = 'your-secret-key-here';
const ENCRYPTION_ALGORITHM = 'AES-256-CBC';
const INITIALIZATION_VECTOR_BYTES = 16;
```

#### Configurazione Sicura

**Esempio Produzione**:
```php
// Password hashing robusto
const BLOWFISH_HASH_WORKLOAD = 15;  // Più sicuro ma più lento

// Chiave crittografia forte (genera con random_bytes)
const ENCRYPTION_PASSPHRASE = 'kJ8k3nR9mQ7pL5xW2zB6vC4nM8qT1eY7';

// Algoritmo raccomandato
const ENCRYPTION_ALGORITHM = 'AES-256-GCM';  // Più sicuro di CBC
```

**⚠️ Avvertenze Sicurezza**:
- Mai committare `ENCRYPTION_PASSPHRASE` in repository
- Usare variabili ambiente in produzione
- Cambiare `BLOWFISH_HASH_WORKLOAD` in base alle performance server

---

## Configurazione Logging

### Impostazioni Log

```php
// Percorsi log
const LOG_DIRECTORY_PATH = 'path/to/logs/';
const LOG_PATH = 'path/to/logs/log.txt';

// Verbosità e rotazione
const LOG_VERBOSE_ACTIVE = true;
const LOG_DEVELOPMENT_MAX_ROW = 1000;
const LOG_PRODUCTION_MAX_ROW = 100;
const LOG_WARNING_ROW = 10;
const LOG_DANGER_ROW = 50;
```

#### Configurazione Log per Ambiente

**Sviluppo**:
```php
const LOG_VERBOSE_ACTIVE = true;
const LOG_DEVELOPMENT_MAX_ROW = 5000;  // Più log per debug
```

**Produzione**:
```php
const LOG_VERBOSE_ACTIVE = false;
const LOG_PRODUCTION_MAX_ROW = 500;    // Log essenziali
```

### Livelli di Log

| Costante | Descrizione | Soglia Raccomandada |
|----------|-------------|-------------------|
| `LOG_WARNING_ROW` | Soglia per warning | 10-50 |
| `LOG_DANGER_ROW` | Soglia per errori critici | 50-100 |

---

## Configurazione Avanzata

### Path e Namespace Dinamici

```php
// Path dinamici basati su APPLICATION
const APPLICATION_PATH = APPLICATION . DIRECTORY_SEPARATOR;
const APPLICATION_NAMESPACE = APPLICATION . '\\';

// Path sistema
const SYSTEM_PATH = ROOT_PATH . SYSTEM . DIRECTORY_SEPARATOR;
const CORE_PATH = SYSTEM_PATH . CORE . DIRECTORY_SEPARATOR;

// Path specifici componenti
const ENTITY_PATH = APPLICATION_PATH . ENTITIES . DIRECTORY_SEPARATOR;
const MODEL_PATH = APPLICATION_PATH . MODELS . DIRECTORY_SEPARATOR;
const VIEWS_PATH = APPLICATION_PATH . VIEWS . DIRECTORY_SEPARATOR;
```

### Configurazione per Ambiente

#### Configurazione Multi-Ambiente

**config.php (base)**:
```php
// Carica configurazione specifica ambiente
$env = $_ENV['APP_ENV'] ?? 'development';
require_once __DIR__ . "/config.{$env}.php";
```

**config.development.php**:
```php
const DEVELOPMENT_ENVIRONMENT = true;
const DATABASE_HOST = 'localhost';
const LOG_VERBOSE_ACTIVE = true;
const ORM_CACHE = false;  // Disabilita cache in sviluppo
```

**config.production.php**:
```php
const DEVELOPMENT_ENVIRONMENT = false;
const DATABASE_HOST = 'prod-db-server';
const LOG_VERBOSE_ACTIVE = false;
const ORM_CACHE = true;
const HTTPS_IS_FORCED = true;
```

---

## Template di Configurazione

### Configurazione Minimale

```php
<?php
namespace Config;

// === CONFIGURAZIONE BASE ===
const DEVELOPMENT_ENVIRONMENT = true;
const LANGUAGE = 'it_IT';
const DEFAULT_META_URL = '';

// === DATABASE ===
const DATABASE_HOST = 'localhost';
const DATABASE_NAME = 'my_app';
const DATABASE_USERNAME = 'user';
const DATABASE_PASSWORD = 'password';
const DATABASE_PORT = '3306';

// === MODULI ===
const MODULE_FOLDERS = [
    'SismaFramework',
    'MyApp'
];

// === SICUREZZA ===
const ENCRYPTION_PASSPHRASE = 'change-this-secret-key';

// Includi configurazioni standard
require_once 'config.defaults.php';
```

### Configurazione Completa per Produzione

```php
<?php
namespace Config;

// === AMBIENTE ===
const DEVELOPMENT_ENVIRONMENT = false;
const HTTPS_IS_FORCED = true;
const CONFIGURATION_PASSWORD = 'admin-access-password';

// === LOCALIZZAZIONE ===
const LANGUAGE = 'en_US';
const DEFAULT_META_URL = '/app';

// === DATABASE ===
const DATABASE_HOST = 'db.example.com';
const DATABASE_NAME = 'production_db';
const DATABASE_USERNAME = 'prod_user';
const DATABASE_PASSWORD = 'secure-password-from-env';
const DATABASE_PORT = '3306';

// === ORM ===
const ORM_CACHE = true;
const DEFAULT_ADAPTER_TYPE = 'mysql';

// === MODULI ===
const MODULE_FOLDERS = [
    'SismaFramework',
    'Core',
    'User',
    'Blog',
    'Admin'
];

// === SICUREZZA ===
const ENCRYPTION_PASSPHRASE = 'production-encryption-key-32-chars';
const BLOWFISH_HASH_WORKLOAD = 15;
const ENCRYPTION_ALGORITHM = 'AES-256-GCM';

// === LOGGING ===
const LOG_VERBOSE_ACTIVE = false;
const LOG_PRODUCTION_MAX_ROW = 200;
const LOG_WARNING_ROW = 20;
const LOG_DANGER_ROW = 50;

// Include configurazioni base
require_once 'config.defaults.php';
```

---

## Best Practices

### 1. Gestione Password e Chiavi

```php
// ❌ MAI fare questo
const DATABASE_PASSWORD = 'password123';
const ENCRYPTION_PASSPHRASE = 'secret';

// ✅ Usa variabili ambiente
const DATABASE_PASSWORD = $_ENV['DB_PASSWORD'] ?? '';
const ENCRYPTION_PASSPHRASE = $_ENV['ENCRYPTION_KEY'] ?? '';
```

### 2. Configurazione per Performance

```php
// Produzione: cache attiva, log minimi
if (!DEVELOPMENT_ENVIRONMENT) {
    const ORM_CACHE = true;
    const LOG_VERBOSE_ACTIVE = false;
    const LOG_PRODUCTION_MAX_ROW = 100;
}

// Sviluppo: debug attivo, cache disabilitata
if (DEVELOPMENT_ENVIRONMENT) {
    const ORM_CACHE = false;
    const LOG_VERBOSE_ACTIVE = true;
    const LOG_DEVELOPMENT_MAX_ROW = 2000;
}
```

### 3. Sicurezza

```php
// Forza HTTPS in produzione
const HTTPS_IS_FORCED = !DEVELOPMENT_ENVIRONMENT;

// Hash workload basato su ambiente
const BLOWFISH_HASH_WORKLOAD = DEVELOPMENT_ENVIRONMENT ? 10 : 15;

// Prevenzione manipolazione ID
const PRIMARY_KEY_PASS_ACCEPTED = false;
```

---

## Troubleshooting Configurazione

### Errori Comuni

#### 1. Database Connection Failed
```php
// Verifica configurazione database
const DATABASE_HOST = 'localhost';    // ✓ Corretto
const DATABASE_HOST = '127.0.0.1';    // ✓ Alternativo
const DATABASE_HOST = 'localhost:3306'; // ❌ Porta va in DATABASE_PORT
```

#### 2. Moduli Non Trovati
```php
// Assicurati che i moduli esistano
const MODULE_FOLDERS = [
    'SismaFramework',  // ✓ Deve esistere
    'MyModule',        // ✓ Verifica che la cartella MyModule esista
    'NonExistent'      // ❌ Causerà errori
];
```

#### 3. Path Non Corretti
```php
// I path sono auto-generati, verifica costanti base
const APPLICATION = 'MyApp';          // Nome modulo principale
const SYSTEM = 'SismaFramework';       // Nome cartella framework
```

---

[Indice](index.md) | Precedente: [Traits per Enumerazioni](traits.md) | Successivo: [Advanced ORM](advanced-orm.md)
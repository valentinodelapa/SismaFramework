# Configuration Reference

SismaFramework utilizza un sistema di configurazione basato su costanti PHP definite nel file `Config/config.php`. Questa guida fornisce una documentazione completa di tutte le opzioni di configurazione disponibili.

## Panoramica

La configurazione è organizzata in sezioni logiche:
- **Costanti dei Nomi**: Definiscono nomi di cartelle e componenti
- **Costanti Base**: Configurazione generale dell'applicazione
- **Database**: Configurazione connessione database
- **ORM**: Impostazioni Object-Relational Mapper
- **Sicurezza**: Configurazione crittografia e sicurezza
- **Sessione**: Cookie, token anti-dirottamento e rotazione dell'ID di sessione
- **Autenticazione**: Token CSRF, nomi dei campi dei form di login, chiave e lunghezza dello `state` OAuth
- **Logging**: Sistema di log e debug
- **Moduli**: Gestione architettura modulare

### Configurazione del Progetto e Valori di Default

Il file `Config/config.php` del modulo SismaFramework dichiara le costanti nel namespace `SismaFramework\Config` e contiene i valori di default. L'installazione lo copia in `Config/configFramework.php` nella root del progetto, riscrivendo il namespace in `Config`: è il file che il progetto modifica.

Dalla 12.9.0 `Config` cerca ogni costante prima in `Config\` (il file del progetto) e, se non la trova, in `SismaFramework\Config\` (il file del modulo). Un progetto che aggiorna il framework riceve quindi automaticamente le costanti introdotte dopo la sua installazione, con il valore di default, e può sovrascriverle aggiungendole al proprio `configFramework.php`. Se una costante manca in entrambi i file, la lettura solleva `Error: Undefined constant`.

Il default è il valore calcolato nel file del modulo: per le costanti derivate da altre costanti o da `__DIR__` (path, namespace) va dichiarata esplicitamente nel progetto.

---

## Configurazione Base

### Ambiente e Debug

```php
// Abilita ambiente di sviluppo
const DEVELOPMENT_ENVIRONMENT = true;

// Forza l'uso di HTTPS
const HTTPS_IS_FORCED = false;
```

#### DEVELOPMENT_ENVIRONMENT
- **Tipo**: `bool`
- **Default**: `true`
- **Descrizione**: Controlla l'ambiente dell'applicazione
- **Effetti**:
  - `true`: Mostra errori dettagliati, abilita debug bar
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

// Namespace controller
const CONTROLLER_NAMESPACE = APPLICATION_NAMESPACE . CONTROLLERS . '\\';
```

#### Configurazione Routing Personalizzata

**Esempio**:
```php
// Homepage personalizzata
const DEFAULT_PATH = 'home';
const DEFAULT_ACTION = 'index';

// API come default
const DEFAULT_PATH = 'api';
const DEFAULT_ACTION = 'index';
```

### Tipi di Risorsa Personalizzati

```php
// Tipi di file renderizzabili custom
const CUSTOM_RENDERABLE_RESOURCE_TYPES = ['xml', 'rss'];

// Tipi di file scaricabili custom
const CUSTOM_DOWNLOADABLE_RESOURCE_TYPES = ['pdf', 'docx'];
```

#### RESOURCE_CACHE_MAX_AGE
- **Tipo**: `int`
- **Default**: `86400`
- **Descrizione**: Durata in secondi per cui browser e proxy possono tenere in cache le risorse statiche servite dal framework (CSS, JavaScript, immagini, file scaricabili). Il valore viene inviato come `Cache-Control: public, max-age=…` e come `Expires`, e sostituisce gli header anti-cache che `session_start()` imposta su ogni risposta. Le pagine generate dai controller mantengono invece gli header anti-cache della sessione, perché possono contenere dati dell'utente.

### Parametri della Query String

```php
const PRESERVED_QUERY_STRING_PARAMETERS = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "utm_id", "gclid", "gbraid", "wbraid", "dclid", "fbclid", "msclkid", "mc_cid", "mc_eid", "_ga", "_gl"];
```

#### PRESERVED_QUERY_STRING_PARAMETERS
- **Tipo**: `array`
- **Descrizione**: Parametri che il dispatcher lascia nella query string. Per le richieste `GET` e `HEAD` il dispatcher riscrive gli altri parametri della query string nel percorso con un redirect (`/blog?page=2` diventa `/blog/page/2/`); i parametri di questa lista restano invece nella query string del redirect, e una richiesta che contiene solo questi parametri non viene reindirizzata. Così gli strumenti di analytics e di tracciamento delle conversioni, che leggono i parametri dalla query string lato client, continuano a riceverli. I parametri restano comunque disponibili in `$request->query` e come argomenti delle action, con il nome convertito in camelCase (`utmSource`).

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
// Token casuali
const SIMPLE_RANDOM_TOKEN_BYTES = 16;

// Algoritmi hash
const SIMPLE_HASH_ALGORITHM = 'sha256';
const BLOWFISH_HASH_WORKLOAD = 12;

// Crittografia simmetrica
const ENCRYPTION_PASSPHRASE = 'your-secret-key-here';
const ENCRYPTION_ALGORITHM = 'AES-256-CBC';
const INITIALIZATION_VECTOR_BYTES = 16;
// Suffisso della proprietà che contiene il vettore d'inizializzazione di ogni proprietà cifrata (v12.11.0+)
const ENCRYPTED_PROPERTY_INITIALIZATION_VECTOR_SUFFIX = 'InitializationVector';

// Crittografia asimmetrica (coppie di chiavi, CSR, certificati, firma/verifica — v12.3.0+)
const ASYMMETRIC_KEY_TYPE = OPENSSL_KEYTYPE_RSA;
const ASYMMETRIC_KEY_BITS = 2048;
const ASYMMETRIC_DIGEST_ALGORITHM = 'sha256';
const CERTIFICATE_VALIDITY_DAYS = 3650;
// Solo se necessario: percorso esplicito di openssl.cnf (ambienti senza default risolvibile)
define(__NAMESPACE__ . '\OPENSSL_CONFIG_PATH', getenv('OPENSSL_CONFIG_PATH') ?: '');

// Cifratura con password (v12.8.0+)
const PASSWORD_KEY_DERIVATION_DEFAULT_ITERATIONS = 600000;
const PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS = 100000;
const PASSWORD_KEY_DERIVATION_MAXIMUM_ITERATIONS = 10000000;
const PASSWORD_KEY_DERIVATION_HASH_ALGORITHM = 'sha256';
const PASSWORD_KEY_DERIVATION_SALT_BYTES = 16;
const PASSWORD_ENCRYPTION_ALGORITHM = 'aes-256-gcm';
const PASSWORD_ENCRYPTION_INITIALIZATION_VECTOR_BYTES = 12;
const PASSWORD_ENCRYPTION_TAG_BYTES = 16;
const PASSWORD_ENCRYPTION_MINIMUM_TAG_BYTES = 12;
const PASSWORD_ENVELOPE_VERSION = 'sisma-pw2';
const PASSWORD_ENVELOPE_SEPARATOR = '$';
const PASSWORD_ENVELOPE_KEY_DERIVATION_PREFIX = 'pbkdf2-';
```

#### SIMPLE_RANDOM_TOKEN_BYTES
- **Tipo**: `int`
- **Default**: `16`
- **Descrizione**: byte casuali dei token generati da `Encryptor::getSimpleRandomToken()`; il token esadecimale è lungo il doppio (32 caratteri per default)

#### ASYMMETRIC_KEY_TYPE / ASYMMETRIC_KEY_BITS
- **Tipo**: `int` / `int`
- **Default**: `OPENSSL_KEYTYPE_RSA` / `2048`
- **Descrizione**: tipo e lunghezza in bit delle coppie di chiavi generate da `Encryptor::generateAsymmetricKeyPair()`

#### ASYMMETRIC_DIGEST_ALGORITHM
- **Tipo**: `string`
- **Default**: `'sha256'`
- **Descrizione**: algoritmo di digest usato per CSR, certificati e firme (`Encryptor::signData()`/`verifySignature()`)

#### CERTIFICATE_VALIDITY_DAYS
- **Tipo**: `int`
- **Default**: `3650`
- **Descrizione**: validità in giorni dei certificati generati da `Encryptor::generateSelfSignedCertificate()`/`signCertificateSigningRequest()`

#### OPENSSL_CONFIG_PATH
- **Tipo**: `string`
- **Default**: `''` (nessun override; letta da `getenv('OPENSSL_CONFIG_PATH')`)
- **Descrizione**: percorso esplicito di un `openssl.cnf`, opzionale. Se non valorizzata, `Encryptor` usa la configurazione OpenSSL minimale `Config/openssl.cnf` inclusa nel modulo (funziona anche su ambienti, tipicamente Windows, privi di un `openssl.cnf` di sistema risolvibile). Impostarla solo per esigenze avanzate (es. un provider/engine OpenSSL specifico) — in tal caso il file indicato deve includere le sezioni `[v3_ca]`/`[v3_leaf]` se si vuole che `generateSelfSignedCertificate()`/`signCertificateSigningRequest()` applichino correttamente l'estensione `basicConstraints`

#### PASSWORD_KEY_DERIVATION_DEFAULT_ITERATIONS
- **Tipo**: `int`
- **Default**: `600000`
- **Descrizione**: iterazioni PBKDF2 usate da `Encryptor::encryptWithPassword()` quando il terzo argomento non è indicato. Il valore è scritto nell'involucro cifrato: alzarlo non rende indecifrabili gli involucri esistenti

#### PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS / PASSWORD_KEY_DERIVATION_MAXIMUM_ITERATIONS
- **Tipo**: `int` / `int`
- **Default**: `100000` / `10000000`
- **Descrizione**: intervallo di iterazioni ammesso. `encryptWithPassword()` rifiuta un costo fuori intervallo; `decryptWithPassword()` rifiuta gli involucri che ne dichiarano uno, così un involucro alterato non può imporre una derivazione troppo debole o troppo onerosa. Alzare il minimo rende indecifrabili gli involucri creati con un costo inferiore

#### PASSWORD_KEY_DERIVATION_HASH_ALGORITHM / PASSWORD_KEY_DERIVATION_SALT_BYTES
- **Tipo**: `string` / `int`
- **Default**: `'sha256'` / `16`
- **Descrizione**: algoritmo di hash di PBKDF2 (uno di `hash_hmac_algos()`) e lunghezza in byte del sale casuale

#### PASSWORD_ENCRYPTION_ALGORITHM
- **Tipo**: `string`
- **Default**: `'aes-256-gcm'`
- **Descrizione**: cifrario dei nuovi involucri. Deve essere un cifrario autenticato supportato da OpenSSL (`aes-128/192/256-gcm`, `-ccm`, `-ocb`, `chacha20-poly1305`), altrimenti `encryptWithPassword()` solleva `InvalidArgumentException`. La lunghezza della chiave derivata è quella richiesta dal cifrario

#### PASSWORD_ENCRYPTION_INITIALIZATION_VECTOR_BYTES / PASSWORD_ENCRYPTION_TAG_BYTES
- **Tipo**: `int` / `int`
- **Default**: `12` / `16`
- **Descrizione**: lunghezze in byte dell'IV e del tag di autenticazione dei nuovi involucri; devono essere ammesse dal cifrario scelto (con GCM: IV di 12 byte raccomandato, tag fino a 16)

#### PASSWORD_ENCRYPTION_MINIMUM_TAG_BYTES
- **Tipo**: `int`
- **Default**: `12`
- **Descrizione**: lunghezza minima del tag accettata. `encryptWithPassword()` rifiuta una configurazione con un tag più corto; `decryptWithPassword()` rifiuta gli involucri con un tag più corto, così un involucro alterato non può indebolire la verifica di integrità troncando il tag

#### PASSWORD_ENVELOPE_VERSION / PASSWORD_ENVELOPE_SEPARATOR / PASSWORD_ENVELOPE_KEY_DERIVATION_PREFIX
- **Tipo**: `string` / `string` / `string`
- **Default**: `'sisma-pw2'` / `'$'` / `'pbkdf2-'`
- **Descrizione**: identificatore del formato, separatore dei campi e prefisso dell'algoritmo di derivazione nell'involucro. Definiscono il formato stesso: **non vanno modificati** su un'applicazione che ha già salvato involucri, perché `decryptWithPassword()` restituirebbe `false` per tutti quelli creati con i valori precedenti (ripristinando i valori tornano leggibili). Il separatore non deve comparire nei nomi di cifrario e hash né nell'alfabeto base64 (`A-Z`, `a-z`, `0-9`, `+`, `/`, `=`)

Cifrario, hash, iterazioni, sale, IV e tag sono scritti nell'involucro e la decifratura li legge da lì: questi parametri decidono solo come vengono creati i nuovi involucri, e modificarli non rende indecifrabili quelli esistenti. Fanno eccezione i limiti (`PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS`, `PASSWORD_KEY_DERIVATION_MAXIMUM_ITERATIONS`, `PASSWORD_ENCRYPTION_MINIMUM_TAG_BYTES`), che valgono anche in decifratura, e i tre parametri del formato. La cifratura con password non usa `ENCRYPTION_PASSPHRASE`/`ENCRYPTION_ALGORITHM`. Vedi [Sicurezza](security.md#cifratura-con-password-v1280).

#### Configurazione Sicura

**Esempio Produzione**:
```php
// Password hashing robusto
const BLOWFISH_HASH_WORKLOAD = 15;  // Più sicuro ma più lento

// Chiave crittografia forte (genera con random_bytes)
const ENCRYPTION_PASSPHRASE = 'kJ8k3nR9mQ7pL5xW2zB6vC4nM8qT1eY7';
```

`ENCRYPTION_ALGORITHM` deve restare un cifrario non autenticato (default `AES-256-CBC`): `encryptString()`/`decryptString()` non gestiscono il tag di autenticazione, quindi con GCM la cifratura fallisce. Per una cifratura autenticata usare `encryptWithPassword()`.

**⚠️ Avvertenze Sicurezza**:
- Mai committare `ENCRYPTION_PASSPHRASE` in repository
- Usare variabili ambiente in produzione
- Cambiare `BLOWFISH_HASH_WORKLOAD` in base alle performance server

### Sessione

```php
const SESSION_LIFETIME = 3600;
const SESSION_COOKIE_PATH = '/';
const SESSION_COOKIE_DOMAIN = '';
const SESSION_COOKIE_HTTP_ONLY = true;
const SESSION_COOKIE_SAME_SITE = 'Lax';
const SESSION_TOKEN_KEY = 'token';
const SESSION_TOKEN_HASH_ALGORITHM = 'sha512';
const SESSION_TOKEN_INCLUDES_REMOTE_ADDRESS = true;
const SESSION_ROTATION_GRACE_SECONDS = 30;
const SESSION_MAXIMUM_ROTATION_HOPS = 10;
const SESSION_ROTATED_AT_KEY = '__sismaSessionRotatedAt';
const SESSION_NEXT_SESSION_ID_KEY = '__sismaNextSessionId';
```

#### SESSION_LIFETIME
- **Tipo**: `int`
- **Default**: `3600`
- **Descrizione**: durata in secondi del cookie di sessione; `Session::start()` vi allinea anche `session.gc_maxlifetime`, così i dati lato server non scadono prima del cookie

#### SESSION_COOKIE_DOMAIN
- **Tipo**: `string`
- **Default**: `''`
- **Descrizione**: attributo `Domain` del cookie di sessione. Vuoto, il cookie resta legato all'host esatto che l'ha emesso e non viene inviato ai sottodomini; impostato su un dominio (per esempio `example.com`), la sessione è condivisa con tutti i suoi sottodomini. Non deve contenere la porta, altrimenti i browser scartano il cookie

#### SESSION_COOKIE_PATH / SESSION_COOKIE_HTTP_ONLY / SESSION_COOKIE_SAME_SITE
- **Tipo**: `string` / `bool` / `string`
- **Default**: `'/'` / `true` / `'Lax'`
- **Descrizione**: attributi `Path`, `HttpOnly` e `SameSite` del cookie di sessione. `HttpOnly` disattivato rende il cookie leggibile da JavaScript (esposto a XSS); `SameSite` accetta `Lax`, `Strict` o `None`, e con `None` il browser richiede HTTPS. `Secure` non è configurabile: dipende dal protocollo della richiesta

#### SESSION_TOKEN_KEY / SESSION_TOKEN_HASH_ALGORITHM
- **Tipo**: `string` / `string`
- **Default**: `'token'` / `'sha512'`
- **Descrizione**: chiave di `$_SESSION` e algoritmo di hash del token anti-dirottamento (impronta di `User-Agent` e indirizzo IP) verificato da `Session::isValidSession()`. Con sessioni aperte, cambiare l'algoritmo fa risultare non valide le sessioni esistenti; cambiare la chiave fa registrare un nuovo token alla richiesta successiva, legato al client che la invia

#### SESSION_TOKEN_INCLUDES_REMOTE_ADDRESS
- **Tipo**: `bool`
- **Default**: `true`
- **Descrizione**: se `true` il token anti-dirottamento include l'indirizzo IP del client oltre allo `User-Agent`, e `Session::isValidSession()` restituisce `false` quando l'indirizzo cambia. Va impostato a `false` quando l'indirizzo non identifica il client: dietro un reverse proxy, un load balancer o una CDN `REMOTE_ADDR` è l'indirizzo del proxy, uguale per tutti gli utenti, mentre i dispositivi mobili che cambiano rete e i client dual-stack che passano da IPv4 a IPv6 cambiano indirizzo durante la navigazione. Con sessioni aperte, cambiare il valore fa risultare non valide le sessioni esistenti

#### SESSION_ROTATION_GRACE_SECONDS
- **Tipo**: `int`
- **Default**: `30`
- **Descrizione**: per quanti secondi un ID di sessione già ruotato resta agganciato alla sessione che lo ha sostituito (richieste concorrenti partite con il cookie precedente). Oltre questo periodo la richiesta riceve una sessione vuota

#### SESSION_MAXIMUM_ROTATION_HOPS
- **Tipo**: `int`
- **Default**: `10`
- **Descrizione**: numero massimo di rotazioni consecutive seguite per ricondurre un ID ruotato alla sessione corrente

#### SESSION_ROTATED_AT_KEY / SESSION_NEXT_SESSION_ID_KEY
- **Tipo**: `string` / `string`
- **Default**: `'__sismaSessionRotatedAt'` / `'__sismaNextSessionId'`
- **Descrizione**: chiavi di `$_SESSION` in cui la sessione ruotata registra l'istante della rotazione e il nuovo ID. Non devono coincidere con chiavi usate dall'applicazione. Cambiarle con sessioni aperte fa perdere l'aggancio alle richieste concorrenti per al più `SESSION_ROTATION_GRACE_SECONDS`

Vedi [Sicurezza](security.md#rotazione-dellid-di-sessione).

### Autenticazione

```php
const CSRF_TOKEN_KEY = 'csrfToken';
const AUTHENTICATION_IDENTIFIER_FIELD = 'identifier';
const AUTHENTICATION_PASSWORD_FIELD = 'password';
const AUTHENTICATION_MULTI_FACTOR_CODE_FIELD = 'code';
const OAUTH_STATE_KEY = 'oauthState';
const OAUTH_STATE_BYTES = 16;
```

#### CSRF_TOKEN_KEY
- **Tipo**: `string`
- **Default**: `'csrfToken'`
- **Descrizione**: nome del token CSRF verificato da `Authentication::checkCsrfToken()`, usato sia come chiave di `$_SESSION` sia come nome del campo del form. L'applicazione, che genera il token, deve usare lo stesso nome. Vedi [Sicurezza](security.md#protezione-csrf)

#### AUTHENTICATION_IDENTIFIER_FIELD / AUTHENTICATION_PASSWORD_FIELD / AUTHENTICATION_MULTI_FACTOR_CODE_FIELD
- **Tipo**: `string` / `string` / `string`
- **Default**: `'identifier'` / `'password'` / `'code'`
- **Descrizione**: nomi dei campi del form da cui `Authentication` legge l'identificatore dell'utente (`checkAuthenticable()`), la password (`checkPassword()`) e il codice dell'autenticazione a due fattori (`checkMultiFactor()`). I campi `name` dei form di login e di verifica devono corrispondere

#### OAUTH_STATE_KEY
- **Tipo**: `string`
- **Default**: `'oauthState'`
- **Descrizione**: chiave di `$_SESSION` in cui `OAuthAuthentication::getAuthorizationUrl()` salva lo `state` anti-CSRF, verificato da `checkCallback()`. Non deve coincidere con chiavi usate dall'applicazione. Il parametro `state` della richiesta di callback non è configurabile: il nome è definito dal protocollo OAuth 2.0

#### OAUTH_STATE_BYTES
- **Tipo**: `int`
- **Default**: `16`
- **Descrizione**: byte casuali dello `state` generato da `getAuthorizationUrl()`; lo `state` esadecimale inviato al provider è lungo il doppio. Alcuni provider limitano la lunghezza dei parametri dell'URL di autorizzazione

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

---

## Configurazione Avanzata

### Path e Namespace Dinamici

```php
// Path dinamici basati su APPLICATION
const APPLICATION_PATH = APPLICATION . DIRECTORY_SEPARATOR;
const APPLICATION_NAMESPACE = APPLICATION . '\\';

// Path sistema
const SYSTEM_PATH = ROOT_PATH . SYSTEM . DIRECTORY_SEPARATOR;

// Path specifici componenti
const ENTITY_PATH = APPLICATION_PATH . ENTITIES . DIRECTORY_SEPARATOR;
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
```

Le costanti non dichiarate prendono il valore di default dal `config.php` del modulo (vedi [Configurazione del Progetto e Valori di Default](#configurazione-del-progetto-e-valori-di-default)). Fanno eccezione i path e i namespace derivati (`ROOT_PATH`, `APPLICATION_PATH`, `ENTITY_NAMESPACE`, ...), che nel modulo sono calcolati sulla sua cartella e sull'applicazione `Sample`: conviene partire dal `configFramework.php` generato dall'installazione, che li contiene già.

### Configurazione Completa per Produzione

```php
<?php
namespace Config;

// === AMBIENTE ===
const DEVELOPMENT_ENVIRONMENT = false;
const HTTPS_IS_FORCED = true;

// === LOCALIZZAZIONE ===
const LANGUAGE = 'en_US';

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

// === LOGGING ===
const LOG_VERBOSE_ACTIVE = false;
const LOG_PRODUCTION_MAX_ROW = 200;
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
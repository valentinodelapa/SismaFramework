# API Reference

Questa sezione fornisce una documentazione completa delle API principali di SismaFramework. Per ogni classe, vengono elencati i metodi pubblici disponibili, i loro parametri e valori di ritorno.

## Indice

* [Core Classes](#core-classes)
  * [BaseController](#basecontroller)
  * [BaseForm](#baseform)
  * [BaseFixture](#basefixture)
* [Helper Classes](#helper-classes)
  * [ModuleManager](#modulemanager)
  * [NotationManager](#notationmanager)
  * [BufferManager](#buffermanager)
  * [Router](#router)
* [ORM Classes](#orm-classes)
  * [DataMapper](#datamapper)
  * [BaseModel](#basemodel)
  * [StandardEntity](#standardentity)
* [Security Classes](#security-classes)
  * [BaseVoter](#basevoter)
  * [BasePermission](#basepermission)
* [HTTP Classes](#http-classes)
  * [Response](#response)

---

## Core Classes

### BaseController

**Namespace:** `SismaFramework\Core\BaseClasses\BaseController`

Classe base per tutti i controller dell'applicazione. Fornisce funzionalità comuni e variabili di contesto.

#### Proprietà Protette

```php
protected DataMapper $dataMapper;
protected array $vars;
```

#### Costruttore

```php
public function __construct(DataMapper $dataMapper = new DataMapper())
```

**Parametri:**
- `$dataMapper` (DataMapper, opzionale): Istanza del DataMapper per l'accesso ai dati

**Variabili disponibili in `$this->vars`:**
- `controllerUrl`: URL del controller corrente
- `actionUrl`: URL dell'action corrente
- `metaUrl`: URL con metadati
- `actualCleanUrl`: URL pulito corrente
- `rootUrl`: URL root dell'applicazione

#### Metodi Ereditabili

I controller che estendono `BaseController` possono implementare qualsiasi metodo pubblico che diventerà automaticamente un'action accessibile via URL.

**Esempio:**
```php
class PostController extends BaseController
{
    public function index(): void
    {
        // Action accessibile tramite /post/index
    }

    public function show(int $id): void
    {
        // Action accessibile tramite /post/show/id/123
    }
}
```

---

### BaseForm

**Namespace:** `SismaFramework\Core\BaseClasses\BaseForm`

Classe base per la gestione dei form con validazione automatica e protezione CSRF.

#### Metodi Principali

```php
public function isSubmitted(): bool
```
Verifica se il form è stato inviato.

```php
public function isValid(): bool
```
Verifica se il form è valido (tutti i campi superano la validazione).

```php
public function getErrors(): FormFilterErrorCollection
```
Restituisce una collezione di errori di validazione.

```php
public function handleRequest(): void
```
Processa la richiesta HTTP e popola l'entità associata.

---

### BaseFixture

**Namespace:** `SismaFramework\Core\BaseClasses\BaseFixture`

Classe base per le fixtures di dati utilizzate nei test e per popolare il database.

#### Metodi Astratti

```php
abstract public function load(): void
```
Metodo da implementare per caricare i dati fixture.

---

## Helper Classes

### ModuleManager

**Namespace:** `SismaFramework\Core\HelperClasses\ModuleManager`

Gestisce la configurazione e il caricamento dei moduli dell'applicazione.

#### Metodi Statici

```php
public static function getModuleList(?Config $customConfig = null): array
```
Restituisce la lista dei moduli registrati.

**Parametri:**
- `$customConfig` (Config, opzionale): Configurazione personalizzata

**Ritorna:** Array dei nomi dei moduli

```php
public static function setApplicationModule(string $module): void
```
Imposta il modulo dell'applicazione corrente.

```php
public static function getApplicationModule(): string
```
Restituisce il modulo dell'applicazione corrente.

```php
public static function setApplicationModuleByClassName(string $className): void
```
Imposta il modulo basandosi sul nome completo della classe.

```php
public static function setCustomVisualizationModule(string $module): void
```
Imposta un modulo personalizzato per la visualizzazione.

```php
public static function unsetCustomVisualizationModule(): void
```
Rimuove il modulo personalizzato per la visualizzazione.

---

### NotationManager

**Namespace:** `SismaFramework\Core\HelperClasses\NotationManager`

Utility per la conversione tra diverse convenzioni di naming (camelCase, kebab-case, snake_case).

#### Metodi di Conversione

```php
public static function convertToStudlyCaps(string $kebabCaseOrSnakeCaseString): string
```
Converte da kebab-case o snake_case a StudlyCaps (PascalCase).

**Esempio:**
```php
NotationManager::convertToStudlyCaps('user-profile'); // "UserProfile"
NotationManager::convertToStudlyCaps('user_profile'); // "UserProfile"
```

```php
public static function convertToCamelCase(string $kebabCaseOrSnakeCaseString): string
```
Converte da kebab-case o snake_case a camelCase.

**Esempio:**
```php
NotationManager::convertToCamelCase('user-profile'); // "userProfile"
```

```php
public static function convertToKebabCase(string $studlyCapsOrCamelCaseString): string
```
Converte da StudlyCaps o camelCase a kebab-case.

**Esempio:**
```php
NotationManager::convertToKebabCase('UserProfile'); // "user-profile"
```

```php
public static function convertToSnakeCase(string $studlyCapsOrCamelCaseString): string
```
Converte da StudlyCaps o camelCase a snake_case.

**Esempio:**
```php
NotationManager::convertToSnakeCase('UserProfile'); // "user_profile"
```

```php
public static function convertToUpperSnakeCase(string $studlyCapsOrCamelCaseString): string
```
Converte da StudlyCaps o camelCase a UPPER_SNAKE_CASE.

#### Metodi per Entità

```php
public static function convertEntityToTableName(BaseEntity $entity): string
```
Converte un'istanza di entità nel nome della tabella corrispondente.

```php
public static function convertEntityNameToTableName(string $entityName): string
```
Converte il nome di una classe entità nel nome della tabella.

```php
public static function convertColumnNameToPropertyName(string $columnName): string
```
Converte il nome di una colonna del database nel nome della proprietà PHP.

---

### BufferManager

**Namespace:** `SismaFramework\Core\HelperClasses\BufferManager`

Gestisce il buffer di output PHP per il controllo dell'output.

#### Metodi Statici

```php
public static function start(): void
```
Avvia il buffering dell'output.

```php
public static function clear(): void
```
Pulisce il buffer corrente senza inviare l'output.

```php
public static function flush(): void
```
Invia l'output del buffer e lo pulisce.

```php
public static function getContents(): string
```
Restituisce il contenuto del buffer senza pulirlo.

```php
public static function end(): void
```
Termina il buffering dell'output.

---

## Utilizzo degli Helper

Gli helper di SismaFramework sono progettati per semplificare operazioni comuni:

### Esempio: Gestione dei Moduli
```php
// Ottenere la lista dei moduli
$modules = ModuleManager::getModuleList();

// Impostare il modulo corrente
ModuleManager::setApplicationModule('Blog');
```

### Esempio: Conversioni di Naming
```php
// Convertire nomi per URL
$urlSlug = NotationManager::convertToKebabCase('MyBlogPost'); // "my-blog-post"

// Convertire per nomi di tabelle
$tableName = NotationManager::convertToSnakeCase('BlogPost'); // "blog_post"
```

### Esempio: Gestione Buffer
```php
// Catturare output in una variabile
BufferManager::start();
echo "Contenuto da catturare";
$content = BufferManager::getContents();
BufferManager::end();
```

---

## Note Generali

- **Tipizzazione Forte**: Tutte le API utilizzano la tipizzazione forte di PHP 8.1+
- **Convenzioni**: I metodi seguono le convenzioni PSR-12 per il naming
- **Immutabilità**: Molti helper restituiscono nuovi valori senza modificare gli input
- **Thread Safety**: Le classi statiche sono thread-safe per l'uso in ambienti multi-processo

---

[Indice](index.md) | Successivo: [Helper Classes](helper-classes.md)
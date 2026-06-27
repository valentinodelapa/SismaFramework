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
  * [Authentication](#authentication)
  * [OAuthAuthentication](#oauthauthentication)
  * [OAuthWrapperInterface](#oauthwrapperinterface)
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

Classe base per la gestione dei form con validazione automatica e protezione CSRF. Utilizza `SubmittableTrait` per la gestione della submission e degli errori.

#### Metodi Principali

```php
public function handleRequest(Request $request): void
```
Riceve la richiesta HTTP, esegue `injectRequest()`, applica le regole di filtro e popola l'entità associata.

```php
public function isSubmitted(): bool
```
Verifica se il form è stato inviato (da `SubmittableTrait`).

```php
public function isValid(): bool
```
Verifica se il form è valido (tutti i campi superano la validazione e `customFilter()` ritorna `true`).

```php
public function getFilterErrors(): FormFilterError
```
Restituisce l'oggetto `FormFilterError` con gli errori di validazione (da `SubmittableTrait`).

```php
public function resolveEntity(): BaseEntity
```
Salva l'entità validata e restituisce l'istanza aggiornata.

```php
public function getEntityDataToStandardEntity(): StandardEntity
```
Restituisce i dati del form come `StandardEntity`, utile per ripopolare il form in caso di errori.

```php
protected function addRequest(string $propertyName, string|int|float|bool|array|null $value, bool $override = true): self
```
Aggiunge o sovrascrive un valore nella richiesta del form, tipicamente da `injectRequest()`. Con `override: true` (default) il valore passato sovrascrive sempre quello esistente; con `override: false` il valore viene impostato solo se la proprietà non è già presente nella richiesta (introdotto nella 11.8.0).

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

## ORM Classes

### DataMapper

**Namespace:** `SismaFramework\Orm\DataMapper`

Classe principale per la gestione della mappatura tra oggetti PHP e database.

#### Metodi Principali

```php
// Trova un'entità per ID
public function find(string $entityClass, int|string $id): ?StandardEntity

// Trova più entità con criteri
public function findBy(string $entityClass, array $criteria = []): array

// Salva un'entità
public function save(StandardEntity $entity): StandardEntity

// Elimina un'entità
public function delete(StandardEntity $entity): bool

// Esegue query personalizzate
public function query(string $sql, array $parameters = []): array
```

### BaseModel

**Namespace:** `SismaFramework\Orm\BaseClasses\BaseModel`

Classe base per tutti i modeli che utilizzano l'ORM.

#### Metodi Principali

```php
// Crea una nuova istanza del modello
public function create(array $data): StandardEntity

// Trova per ID
public function find(int|string $id): ?StandardEntity

// Trova con criteri
public function findBy(array $criteria): array

// Aggiorna un'entità esistente
public function update(StandardEntity $entity, array $data): StandardEntity
```

### StandardEntity

**Namespace:** `SismaFramework\Orm\BaseClasses\StandardEntity`

Classe base per tutte le entità del sistema.

#### Proprietà e Metodi

```php
// Proprietà standard
protected ?int $id;
protected ?\DateTime $createdAt;
protected ?\DateTime $updatedAt;

// Serializzazione
public function toArray(): array

// Aggiornamento automatico timestamp
public function updateTimestamps(): void

// Validazione
public function validate(): array
```

---

## Security Classes

### Authentication

**Namespace:** `SismaFramework\Security\HttpClasses\Authentication`

Gestisce il flusso di autenticazione form-based (credenziali + password + CSRF). Estende `BaseAuthentication` e usa `SubmittableTrait`.

#### Metodi Principali

```php
public function setAuthenticableModelInterface(AuthenticableModelInterface $model): void
```
Inietta il modello che recupera l'entità autenticabile dall'identificatore.

```php
public function setPasswordModelInterface(PasswordModelInterface $model): void
```
Inietta il modello che recupera la password associata all'entità.

```php
public function checkAuthenticable(bool $withCsrfToken = true): bool
```
Verifica in sequenza: CSRF token (se `$withCsrfToken = true`), identificatore utente e password. Restituisce `true` solo se tutti i controlli passano. **Chiama internamente `checkPassword()`** — non è necessario chiamarlo separatamente.

```php
public function checkCsrfToken(): bool
```
Verifica che il token CSRF nella richiesta corrisponda a quello in sessione.

```php
public function checkPassword(AuthenticableInterface $authenticable): bool
```
Verifica la password per un'entità autenticabile specifica.

```php
public function checkMultiFactor(AuthenticableInterface $authenticable, DataMapper $dataMapper): bool
```
Verifica il codice TOTP o il codice di recovery per l'autenticazione a due fattori.

```php
// Da BaseAuthentication:
public function getAuthenticableInterface(): AuthenticableInterface
```
Restituisce l'entità autenticata dopo un controllo riuscito. Lancia `AuthenticationException` se non disponibile.

```php
// Da SubmittableTrait:
public function isSubmitted(): bool
public function getFilterErrors(): FormFilterError
```

---

### OAuthAuthentication

**Namespace:** `SismaFramework\Security\HttpClasses\OAuthAuthentication`

Implementa il flusso **Authorization Code OAuth 2.0**. Estende `BaseAuthentication`; **non usa `SubmittableTrait`** perché in OAuth non esiste un form da sottomettere.

#### Metodi Principali

```php
public function setOAuthWrapperInterface(OAuthWrapperInterface $wrapper): void
```
Inietta il wrapper specifico del provider OAuth.

```php
public function setAuthenticableModelInterface(AuthenticableModelInterface $model): void
```
Inietta il modello che recupera l'utente dall'identificatore OAuth.

```php
public function getAuthorizationUrl(): string
```
Genera uno `state` anti-CSRF con `random_bytes(16)`, lo persiste in sessione e restituisce l'URL di autorizzazione del provider.

```php
public function checkCallback(): bool
```
Verifica il callback OAuth: controlla la presenza di errori dal provider, valida lo `state` in modo timing-safe (`hash_equals`), scambia il `code` per un identificatore e recupera l'entità autenticabile. Restituisce `false` se uno qualsiasi dei controlli fallisce.

```php
// Da BaseAuthentication:
public function getAuthenticableInterface(): AuthenticableInterface
```

---

### OAuthWrapperInterface

**Namespace:** `SismaFramework\Security\Interfaces\Wrappers\OAuthWrapperInterface`

Contratto per i wrapper dei provider OAuth. Ogni implementazione astrae la comunicazione con un provider specifico (Google, GitHub, ecc.).

```php
public function getAuthorizationUrl(string $state): string
```
Costruisce e restituisce l'URL di autorizzazione del provider, includendo il parametro `state`.

```php
public function getAuthenticableIdentifier(string $code): string
```
Scambia il codice di autorizzazione per un identificatore utente (es. email). In caso di errore propaga un'eccezione.

---

### BaseVoter

**Namespace:** `SismaFramework\Security\BaseClasses\BaseVoter`

Classe base per i Voter che implementano la logica di autorizzazione. Risponde alla domanda: "Questo utente può fare questa operazione su questo soggetto?".

#### Metodi Astratti da Implementare

```php
protected function isInstancePermitted(): bool
```
Restituisce `true` se il Voter è applicabile al soggetto corrente (type check).

```php
protected function checkVote(): bool
```
Contiene la logica di autorizzazione vera e propria.

---

### BasePermission

**Namespace:** `SismaFramework\Security\BaseClasses\BasePermission`

Classe base per le Permission che utilizzano un Voter. Se il Voter restituisce `false`, lancia `AccessDeniedException` (HTTP 403).

#### Metodi Astratti da Implementare

```php
protected function callParentPermissions(): void
```
Permette di concatenare controlli di permesso padre.

```php
protected function getVoter(): string
```
Restituisce il FQCN del Voter da utilizzare.

#### Utilizzo

```php
// Nel controller
PostPermission::isAllowed($post, AccessControlEntry::check, $auth->getAuthenticableInterface());
```

---

## HTTP Classes

### Response

**Namespace:** `SismaFramework\Core\HttpClasses\Response`

Oggetto risposta HTTP restituito da ogni action del controller.

#### Costruttore

```php
public function __construct(ResponseType $responseType = ResponseType::httpOk)
```

#### Proprietà Principali

```php
public string $content;       // Corpo della risposta
public array  $headers;       // Header HTTP aggiuntivi
```

---

## Note Generali

- **Tipizzazione Forte**: Tutte le API utilizzano la tipizzazione forte di PHP 8.3+
- **Convenzioni**: I metodi seguono le convenzioni PSR-12 per il naming
- **Immutabilità**: Molti helper restituiscono nuovi valori senza modificare gli input
- **Thread Safety**: Le classi statiche sono thread-safe per l'uso in ambienti multi-processo

---

[Indice](index.md) | Successivo: [Helper Classes](helper-classes.md)
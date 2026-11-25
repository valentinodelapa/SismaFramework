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
protected RouterService $router;   // redirect e URL
protected RenderService $render;   // viste, dati e JSON
protected array $vars;
protected Debugger $debugger;
```

#### Costruttore

```php
public function __construct(DataMapper $dataMapper = new DataMapper(), ?Debugger $debugger = null)
```

**Parametri:**
- `$dataMapper` (DataMapper, opzionale): Istanza del DataMapper per l'accesso ai dati
- `$debugger` (Debugger, opzionale): Istanza della barra di debug

**Variabili disponibili in `$this->vars`:**
- `controllerUrl`: URL del controller corrente
- `actionUrl`: URL dell'action corrente
- `metaUrl`: URL con metadati
- `actualCleanUrl`: URL pulito corrente
- `rootUrl`: URL root dell'applicazione

#### Metodi Ereditabili

I controller che estendono `BaseController` possono implementare qualsiasi metodo pubblico che diventerà automaticamente un'action accessibile via URL. Ogni action restituisce una `Response`.

**Esempio:**
```php
class PostController extends BaseController
{
    public function index(): Response
    {
        // Action accessibile tramite /post/index
        return $this->render->generateView('post/index', $this->vars);
    }

    public function show(int $id): Response
    {
        // Action accessibile tramite /post/show/id/123
        $this->vars['id'] = $id;
        return $this->render->generateView('post/show', $this->vars);
    }
}
```

---

### BaseForm

**Namespace:** `SismaFramework\Core\BaseClasses\BaseForm`

Classe base per la gestione dei form con validazione automatica. Utilizza `SubmittableTrait` per la gestione della submission e degli errori. La protezione CSRF non è gestita dai form: la fornisce `Authentication::checkCsrfToken()`.

#### Metodi Principali

```php
public function handleRequest(Request $request): void
```
Riceve la richiesta HTTP, esegue `injectRequest()`, applica le regole di filtro e popola l'entità associata.

```php
public function isSubmitted(): bool
```
Verifica se il form è stato inviato, cioè se la richiesta contiene il campo `submitted` (da `SubmittableTrait`).

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
Restituisce l'entità valorizzata con i dati validati, compresi quelli dei form annidati. Non la salva: il salvataggio si fa con `DataMapper::save()`.

```php
public function getResponseType(): ResponseType
```
Restituisce `httpBadRequest` dopo una validazione fallita e `httpOk` negli altri casi, da passare a `generateView()`.

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
abstract protected function setDependencies(): void
```
Dichiara con `addDependency(string $dependencyClassName)` le fixture da eseguire prima di questa.

```php
abstract public function setEntity(): void
```
Crea l'entità e la registra con `addEntity(BaseEntity $baseEntity)`. Ogni fixture salva una sola entità; quelle delle dipendenze si recuperano con `getEntityByFixtureName(string $fixtureName)`.

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
Converte il nome di una colonna del database nel nome della proprietà PHP, togliendo il suffisso `_id` delle chiavi esterne.

```php
public static function convertPropertyNameToColumnName(string $propertyName): string
```
Converte il nome di una proprietà PHP nel nome della colonna del database.

---

### BufferManager

**Namespace:** `SismaFramework\Core\HelperClasses\BufferManager`

Gestisce gli output buffer aperti dal framework durante la generazione della risposta (vedi [Helper Classes](helper-classes.md#buffermanager)).

#### Metodi Statici

```php
public static function start(): void
```
Apre un nuovo livello di output buffer.

```php
public static function clear(): void
```
Scarta e chiude tutti i livelli aperti dal framework, senza inviare l'output.

```php
public static function flush(): void
```
Invia al client e chiude tutti i livelli aperti dal framework.

```php
public static function discardAll(): void
```
Scarta e chiude tutti i livelli di output buffer, compresi quelli aperti prima del framework.

---

### Router

**Namespace:** `SismaFramework\Core\HelperClasses\Router`

Facade statica di `RouterService`: `Router::metodo()` equivale a `RouterService::getInstance()->metodo()`, disponibile nei controller come `$this->router`. Guida d'uso in [Helper Classes](helper-classes.md#router).

#### Metodi

```php
public function redirect(string $relativeUrl, Request $request = new Request()): Response
```
Imposta l'header `Location` verso un percorso dell'applicazione (es. `post/show/post/42`) e restituisce la `Response`.

```php
public function getRootUrl(Request $request = new Request()): string
public function getActualUrl(Request $request = new Request()): string
public function getActualCleanUrl(): ?string
public function getControllerUrl(): ?string
public function getActionUrl(): ?string
```
URL della root dell'applicazione (comprensivo del meta URL), percorso della richiesta relativo alla root, URL pulito corrente e sue parti.

```php
public function getMetaUrl(): string
public function setMetaUrl(string $metaUrl): void
public function concatenateMetaUrl(string $pathToConcatenate): void
public function resetMetaUrl(): void
```
Lettura e modifica del prefisso (meta URL) dell'applicazione.

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
// Scartare l'output parziale prima di generare una risposta diversa
BufferManager::clear();
```

---

## ORM Classes

### DataMapper

**Namespace:** `SismaFramework\Orm\HelperClasses\DataMapper`

Esegue le query e salva le entità. Le query di lettura si costruiscono nei model con `initQuery()` (vedi [ORM](orm.md)).

#### Metodi Principali

```php
// Esegue una query di lettura e restituisce le entità trovate
public function find(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): SismaCollection

// Come find(), ma restituisce la prima entità o null
public function findFirst(string $entityName, Query $query, array $bindValues = [], array $bindTypes = []): ?BaseEntity

// Conta le righe di una query
public function getCount(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): int

// Inserisce o aggiorna un'entità, insieme alle entità collegate modificate
public function save(BaseEntity $entity, Query $query = new Query()): bool

// Elimina un'entità, o le righe di una query
public function delete(BaseEntity $entity, Query $query = new Query()): bool
public function deleteBatch(Query $query = new Query(), array $bindValues = [], array $bindTypes = []): bool

// Transazioni (un salvataggio fallito annulla automaticamente la transazione)
public function startTransaction(): bool
public function commitTransaction(bool $checkAnnidation = true): void
```

### BaseModel

**Namespace:** `SismaFramework\Orm\BaseClasses\BaseModel`

Classe base per tutti i modelli che utilizzano l'ORM. Le sottoclassi implementano `getEntityName()` e `appendSearchCondition()`.

#### Metodi Principali

```php
public function getEntityById(int $id): ?BaseEntity
public function getEntityCollection(?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
public function countEntityCollection(?string $searchKey = null): int
public function getOtherEntityCollection(BaseEntity $excludedEntity): SismaCollection
public function deleteEntityById(int $id): bool

// Eager loading con JOIN (vedi join-eager-loading.md)
public function getEntityByIdWithRelations(int $id, array $relations, JoinType $joinType = JoinType::left): ?BaseEntity
public function getEntityCollectionWithRelations(array $relations, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null, JoinType $joinType = JoinType::left): SismaCollection

// Query dinamiche: getBy…(), countBy…(), deleteBy…() (vedi orm.md)
public function __call($name, $arguments): SismaCollection|int|bool
```

`DependentModel` aggiunge `getOtherEntityCollectionByEntity()`; `SelfReferencedModel` aggiunge i metodi per la gerarchia (`getEntityCollectionByParent()`, `countEntityCollectionByParent()`, `getEntityTree()`, `deleteEntityTree()`, …). I metodi `...EntityCollectionByEntity()` e `...EntityCollectionByParentAndEntity()` sono deprecati in favore delle query dinamiche.

### StandardEntity

**Namespace:** `SismaFramework\Orm\ExtendedClasses\StandardEntity`

Oggetto generico che rappresenta una riga non mappata su un'entità: lo restituiscono `select()` dell'adapter e `BaseForm::getEntityDataToStandardEntity()`. Ha una proprietà pubblica per ogni colonna o campo; una proprietà assente vale `null`, e una proprietà il cui nome contiene `Collection` restituisce una `SismaCollection`.

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
protected function getVoter(): BaseVoter
```
Restituisce l'istanza del Voter da utilizzare.

#### Utilizzo

```php
// Nel controller: $user è l'utente loggato (AuthenticableInterface) o null
PostPermission::isAllowed($post, AccessControlEntry::check, $user);
```

---

## HTTP Classes

### Response

**Namespace:** `SismaFramework\Core\HttpClasses\Response`

Oggetto risposta HTTP restituito da ogni action del controller.

#### Costruttore

```php
public function __construct(?ResponseType $responseType = null)
```

Con un `ResponseType` imposta il codice di stato HTTP della risposta (`http_response_code()`); senza argomenti legge quello già impostato. Il corpo della risposta è l'output prodotto dall'action (vista, dati o JSON) e gli header si impostano con `header()`: `Response` non ha proprietà pubbliche.

---

## Note Generali

- **Tipizzazione Forte**: Tutte le API utilizzano la tipizzazione forte di PHP 8.3+
- **Convenzioni**: I metodi seguono le convenzioni PSR-12 per il naming
- **Immutabilità**: Molti helper restituiscono nuovi valori senza modificare gli input

---

[Indice](index.md) | Successivo: [Helper Classes](helper-classes.md)
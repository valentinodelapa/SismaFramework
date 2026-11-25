# Enumerations

SismaFramework fa largo uso delle enumerazioni introdotte in PHP 8.1+ (e potenziate in PHP 8.3+) per garantire type safety e migliorare la leggibilità del codice. Le enumerazioni sono organizzate per namespace funzionale e coprono tutti gli aspetti principali del framework.

## Panoramica delle Enumerazioni

SismaFramework organizza le enumerazioni in tre namespace principali:

| Namespace | Scopo | Enumerazioni |
|-----------|-------|-------------|
| **Core\\Enumerations** | Funzionalità base del framework | RequestType, ResponseType, Language, FilterType, etc. |
| **Orm\\Enumerations** | Sistema ORM e database | DataType, ComparisonOperator, LogicalOperator, etc. |
| **Security\\Enumerations** | Sistema di sicurezza | AccessControlEntry |

---

## Core Enumerations

### RequestType

**Namespace:** `SismaFramework\Core\Enumerations\RequestType`
**Tipo:** `string` enum

Definisce i metodi HTTP supportati dal framework.

```php
enum RequestType: string
{
    case methodHead = 'HEAD';
    case methodGet = 'GET';
    case methodPost = 'POST';
    case methodPut = 'PUT';
    case methodPatch = 'PATCH';
    case methodDelete = 'DELETE';
    case methodPurge = 'PURGE';
    case methodOptions = 'OPTIONS';
    case methodTrace = 'TRACE';
    case methodConnect = 'CONNECT';
}
```

#### Utilizzi Comuni

```php
use SismaFramework\Core\Enumerations\RequestType;

// Il metodo HTTP si legge dai dati del server della Request
$method = RequestType::tryFrom($request->server['REQUEST_METHOD'] ?? '');

// Match con enum
return match ($method) {
    RequestType::methodGet => $this->handleGet(),
    RequestType::methodPost => $this->handlePost(),
    RequestType::methodDelete => $this->handleDelete(),
    default => new Response(ResponseType::httpMethodNotAllowed),
};

// Validazione API REST
class ApiController extends BaseController {
    public function updateResource(Request $request): Response {
        $allowedMethods = [
            RequestType::methodPut,
            RequestType::methodPatch
        ];

        if (!in_array(RequestType::tryFrom($request->server['REQUEST_METHOD'] ?? ''), $allowedMethods, true)) {
            return new Response(ResponseType::httpMethodNotAllowed);
        }

        // ...
    }
}
```

---

### ResponseType

**Namespace:** `SismaFramework\Core\Enumerations\ResponseType`
**Tipo:** `int` enum

Contiene tutti i codici di stato HTTP standard per le risposte.

```php
enum ResponseType: int
{
    // Informativi (1xx)
    case httpContinue = 100;
    case httpSwitchingProtocols = 101;

    // Successo (2xx)
    case httpOk = 200;
    case httpCreated = 201;
    case httpNoContent = 204;

    // Redirection (3xx)
    case httpMovedPermanently = 301;
    case httpFound = 302;
    case httpNotModified = 304;

    // Client Error (4xx)
    case httpBadRequest = 400;
    case httpUnauthorized = 401;
    case httpForbidden = 403;
    case httpNotFound = 404;
    case httpMethodNotAllowed = 405;

    // Server Error (5xx)
    case httpInternalServerError = 500;
    case httpNotImplemented = 501;
    case httpServiceUnavailable = 503;
}
```

#### Utilizzi Comuni

```php
use SismaFramework\Core\Enumerations\ResponseType;

// Il codice di stato si passa al costruttore di Response, che lo invia con http_response_code()
class UserController extends BaseController {
    public function delete(User $user): Response {
        $this->dataMapper->delete($user);
        return new Response(ResponseType::httpNoContent);
    }
}

// Viste e JSON accettano il codice di stato come argomento
class PostApiController extends BaseController {
    public function show(int $id): Response {
        $post = (new PostModel($this->dataMapper))->getEntityById($id);

        if ($post === null) {
            return $this->render->generateJson(['error' => 'Post not found'], ResponseType::httpNotFound);
        }

        return $this->render->generateJson(['id' => $post->id, 'title' => $post->title]);
    }
}

// Le eccezioni del framework dichiarano il proprio codice di stato
class ArticleNotPublishedException extends BaseException {
    protected function setResponseType(): ResponseType {
        return ResponseType::httpForbidden;
    }
}
```

---

### Language

**Namespace:** `SismaFramework\Core\Enumerations\Language`
**Tipo:** `string` enum
**Usa:** `SelectableEnumeration` trait

Definisce i linguaggi supportati per l'internazionalizzazione. Utilizza il sistema di localizzazione per ottenere nomi tradotti delle lingue.

```php
enum Language: string
{
    use SelectableEnumeration;

    case italian = 'it_IT';
    case english = 'en_GB';
    case usEnglish = 'en_US';
    case australianEnglish = 'en_AU';
    case canadianEnglish = 'en_CA';
    case indianEnglish = 'en_IN';
    case french = 'fr_FR';
    case canadianFrench = 'fr_CA';
    case german = 'de_DE';
    case austrianGerman = 'de_AT';
    case swissGerman = 'de_CH';
    case spanish = 'es_ES';
    case mexicanSpanish = 'es_MX';
    case argentinianSpanish = 'es_AR';
    case colombianSpanish = 'es_CO';
    case chinese = 'zh_CN';
    case chineseTraditional = 'zh_TW';
    case arabic = 'ar_SA';
    case egyptianArabic = 'ar_EG';
    case portuguese = 'pt_PT';
    case brazilianPortuguese = 'pt_BR';
    case angolanPortuguese = 'pt_AO';
    case hindi = 'hi_IN';
    case punjabi = 'pa_IN';
    case marathi = 'mr_IN';
    case gujarati = 'gu_IN';
    case tamil = 'ta_IN';
    case telugu = 'te_IN';
    case kannada = 'kn_IN';
    case catalan = 'ca_ES';
    case basque = 'eu_ES';
    case malay = 'ms_MY';
    case swahili = 'sw_KE';
    case hausa = 'ha_NG';
    case amharic = 'am_ET';
    case urdu = 'ur_PK';
    case burmese = 'my_MM';
    case quechua = 'qu_PE';
    case icelandic = 'is_IS';
    // ... e molte altre (60+ lingue e varianti supportate)

    public function getISO6391Label(): string;
}
```

#### Utilizzi Comuni

```php
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\HelperClasses\Localizator;

// Nomi delle lingue nella lingua della richiesta
$italianName = Language::italian->getFriendlyLabel();  // "Italiano" con interfaccia in italiano

// Nomi delle lingue in una lingua specifica
$english = new Localizator(Language::english);
$italianName = Language::italian->getFriendlyLabel([], $english);  // "Italian"
$frenchName = Language::french->getFriendlyLabel([], $english);    // "French"

// Language switcher per UI
class LanguageSwitcherComponent {
    public function render(): string {
        $options = '';

        foreach (Language::cases() as $language) {
            $label = $language->getFriendlyLabel();
            $value = $language->value;
            $options .= "<option value='{$value}'>{$label}</option>";
        }

        return "<select name='language'>{$options}</select>";
    }
}

// Dropdown localizzato
class LocalizationService {
    public function getAvailableLanguages(): array {
        return Language::getChoiceFromEnumerations();
        // Restituisce ['Inglese' => 'en_GB', 'Francese' => 'fr_FR', ...]
    }
}

// Content negotiation
class ContentNegotiator {
    public function negotiateLanguage(array $acceptedLanguages): Language {
        foreach ($acceptedLanguages as $accepted) {
            foreach (Language::cases() as $supported) {
                if (str_starts_with($supported->value, $accepted)) {
                    return $supported;
                }
            }
        }

        return Language::english; // Default fallback
    }
}
```

---

### FilterType

**Namespace:** `SismaFramework\Core\Enumerations\FilterType`
**Tipo:** enum puro (senza valori)

Definisce i filtri disponibili per la validazione dei form. Ogni case corrisponde al metodo omonimo di `SismaFramework\Core\HelperClasses\Filter`, che riceve il valore da validare seguito dagli eventuali parametri.

| Case | Parametri | Il valore è valido se |
|---|---|---|
| `noFilter` | — | sempre |
| `isString` | — | è una stringa non vuota |
| `isMinLimitString`, `isMaxLimitString` | `int $minLimit` / `int $maxLimit` | è una stringa non vuota con almeno / al massimo il numero di caratteri indicato |
| `isLimitString` | `int $minLimit, int $maxLimit` | è una stringa non vuota con lunghezza compresa tra i due limiti |
| `isAlphabeticString` (e le varianti `isMinLimit…`, `isMaxLimit…`, `isLimit…`) | come sopra | è una stringa di sole lettere |
| `isAlphanumericString` (e varianti) | come sopra | è una stringa di sole lettere e cifre |
| `isStrictAlphanumericString` (e varianti) | come sopra | è una stringa di lettere e cifre che contiene almeno una lettera e almeno una cifra |
| `isSecurePassword` | — | ha almeno 8 caratteri, con almeno una minuscola, una maiuscola, una cifra e un simbolo tra `@#-_$%^&+=§!?` |
| `isEmail` | — | è un indirizzo email |
| `isNumeric` | — | è un numero o una stringa numerica |
| `isInteger` | — | è un `int` |
| `isFloat` | — | è un `float` |
| `isBoolean` | — | è un `bool` |
| `isArray` | — | è un array non vuoto |
| `isDate`, `isDatetime`, `isTime` | — | è un `SismaDate`, `SismaDateTime` o `SismaTime` |
| `isUploadedFile` | — | è un file caricato senza errori |
| `isEntity` | — | è un'entità |
| `isEnumeration` | — | è un case di un'enumerazione |
| `isJson` | — | è un `SismaJson` |
| `customFilter` | `string $regularExpression` | è una stringa che corrisponde all'espressione regolare |

Le lunghezze sono misurate in byte (`strlen()`).

#### Utilizzo nei Form

I filtri si associano alle proprietà dell'entità in `setFilterFieldsMode()`, con `addFilterFieldMode()`. I parametri si passano come array, nell'ordine della firma del metodo o per nome; il quarto argomento indica se il campo può essere vuoto:

```php
use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\Enumerations\FilterType;

class UserRegistrationForm extends BaseForm
{
    protected function setFilterFieldsMode(): void
    {
        $this->addFilterFieldMode('email', FilterType::isEmail)
            ->addFilterFieldMode('password', FilterType::isSecurePassword)
            ->addFilterFieldMode('username', FilterType::isLimitAlphanumericString, ['minLimit' => 3, 'maxLimit' => 30])
            ->addFilterFieldMode('postalCode', FilterType::customFilter, ['/^[0-9]{5}$/'])
            ->addFilterFieldMode('age', FilterType::isInteger, [], true);
    }

    // ...
}
```

I filtri ricevono il valore già convertito nel tipo della proprietà (vedi [Gestione dei Form](forms.md)).

#### Validazione Manuale

`applyFilter()` applica il filtro a un valore qualunque:

```php
$isValid = FilterType::isLimitString->applyFilter($username, [3, 30]);
```

`FilterType::fromPhpType()` restituisce il filtro predefinito per un tipo: `isInteger` per `int`, `isFloat` per `float`, `isString` per `string`, `isBoolean` per `bool`, `isEntity` per le entità, `isEnumeration` per le enumerazioni, `isDate`, `isDatetime` o `isTime` per i tipi data e ora dell'ORM e `isJson` per `SismaJson`.

---

## ORM Enumerations

### DataType

**Namespace:** `SismaFramework\Orm\Enumerations\DataType`

Definisce i tipi di dati supportati dall'ORM per la mappatura automatica.

```php
enum DataType
{
    case typeBoolean;
    case typeNull;
    case typeInteger;
    case typeString;
    case typeBinary;
    case typeDecimal;
    case typeDate;
    case typeStatement;
    case typeEntity;
    case typeEnumeration;
    case typeJson;
    case typeGeneric;
}
```

`typeJson` (dalla versione 12.6.0) è il tipo delle proprietà `SismaJson`; l'adapter MySQL lo lega come `PDO::PARAM_STR`.

#### Utilizzi nell'ORM

`DataType` indica il tipo con cui legare i valori dei placeholder di una query. I valori si passano a `DataMapper::find()` (o a `getCount()`, `deleteBatch()` e a `select()` dell'adapter) in `$bindValues`, e i tipi corrispondenti in `$bindTypes`, nello stesso ordine:

```php
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;

class PostModel extends DependentModel
{
    public function getRecentPostsByAuthor(User $author, SismaDateTime $fromDate): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendCondition('author', ComparisonOperator::equal, Placeholder::placeholder, true)
            ->appendAnd()
            ->appendCondition('publishedAt', ComparisonOperator::greaterOrEqual, Placeholder::placeholder);

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, [$author, $fromDate], [DataType::typeEntity, DataType::typeDate]);
    }
}
```

| Valore | `DataType` |
|---|---|
| `bool` | `typeBoolean` |
| `int` | `typeInteger` |
| `float` | `typeDecimal` |
| `string` | `typeString` |
| `null` | `typeNull` |
| entità (chiave esterna) | `typeEntity` |
| enumerazione | `typeEnumeration` |
| `SismaDate`, `SismaDateTime`, `SismaTime` | `typeDate` |
| `SismaJson` | `typeJson` |
| dati binari | `typeBinary` |

### ComparisonOperator

**Namespace:** `SismaFramework\Orm\Enumerations\ComparisonOperator`

Operatori di confronto per le condizioni delle query. L'enumerazione non ha valori: l'operatore SQL si ottiene con `getAdapterVersion()`, in base all'adapter.

```php
enum ComparisonOperator
{
    case against;        // AGAINST (ricerca fulltext)
    case equal;          // =
    case notEqual;       // <>
    case greater;        // >
    case less;           // <
    case greaterOrEqual; // >=
    case lessOrEqual;    // <=
    case in;             // IN
    case notIn;          // NOT IN
    case like;           // LIKE
    case notLike;        // NOT LIKE
    case isNull;         // IS NULL
    case isNotNull;      // IS NOT NULL
}
```

#### Utilizzi nel Query Builder

```php
use SismaFramework\Orm\Enumerations\ComparisonOperator;

class UserModel extends BaseModel
{
    public function getActiveAdultUsers(array $statuses): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendCondition('age', ComparisonOperator::greaterOrEqual, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('status', ComparisonOperator::in, array_fill(0, count($statuses), Placeholder::placeholder))
            ->appendAnd()
            ->appendCondition('deletedAt', ComparisonOperator::isNull);

        $bindValues = [18, ...$statuses];
        $bindTypes = [DataType::typeInteger, ...array_fill(0, count($statuses), DataType::typeString)];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

- `isNull` e `isNotNull` non hanno un valore da legare
- `in` e `notIn` richiedono un placeholder per ogni valore
- `against` è usato internamente dalle condizioni fulltext (`appendFulltextCondition()`)

### LogicalOperator

**Namespace:** `SismaFramework\Orm\Enumerations\LogicalOperator`

Operatori logici per combinare le condizioni. L'enumerazione non ha valori: l'operatore SQL si ottiene con `getAdapterVersion()`.

```php
enum LogicalOperator
{
    case and; // AND
    case or;  // OR
    case not; // NOT
}
```

#### Utilizzi per Query Complesse

Nel query builder gli operatori logici si inseriscono con `appendAnd()`, `appendOr()` e `appendNot()`, che usano `LogicalOperator` internamente, e le condizioni si raggruppano con `appendOpenBlock()` e `appendCloseBlock()`:

```php
class UserModel extends BaseModel
{
    public function getEligibleUsers(): SismaCollection
    {
        $query = $this->initQuery();

        // (age >= ? AND status = ?) OR (type = ? AND verified = ?)
        $query->setWhere()
            ->appendOpenBlock()
                ->appendCondition('age', ComparisonOperator::greaterOrEqual, Placeholder::placeholder)
                ->appendAnd()
                ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendCloseBlock()
            ->appendOr()
            ->appendOpenBlock()
                ->appendCondition('type', ComparisonOperator::equal, Placeholder::placeholder)
                ->appendAnd()
                ->appendCondition('verified', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendCloseBlock();

        $bindValues = [18, 'active', 'premium', true];
        $bindTypes = [DataType::typeInteger, DataType::typeString, DataType::typeString, DataType::typeBoolean];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

### JsonValueType

**Namespace:** `SismaFramework\Orm\Enumerations\JsonValueType`

Introdotto nella 12.7.0, indica il tipo di un valore contenuto in un documento JSON, per le condizioni, la selezione e l'ordinamento sulle colonne JSON. L'adapter genera in base al tipo la conversione nell'SQL, così che i valori si leghino con il `DataType` naturale.

```php
enum JsonValueType
{
    case string;
    case integer;
    case decimal;
    case boolean;
    case json;
}
```

```php
$query->setWhere()
    ->appendJsonCondition('attributes', '$.price', ComparisonOperator::greater, Placeholder::placeholder, JsonValueType::decimal);
$bindValues = [10.5];
$bindTypes = [DataType::typeDecimal];
```

Vedi [Query su Colonne JSON](advanced-orm.md#query-su-colonne-json) per la conversione generata da ciascun caso.

---

## Security Enumerations

### AccessControlEntry

**Namespace:** `SismaFramework\Security\Enumerations\AccessControlEntry`

Indica il tipo di accesso richiesto quando si interroga una Permission o un Voter. Il chiamante lo passa a `isAllowed()`, e il Voter lo legge in `$this->accessControlEntry` per decidere quale controllo eseguire; il risultato del Voter è un booleano (vedi [Sicurezza](security.md#sistema-di-autorizzazione-voters-e-permissions)).

```php
enum AccessControlEntry
{
    case allow;
    case check;
    case deny;
}
```

Il significato di ogni case è stabilito dal Voter: il framework usa `allow` per chiedere se un'entità referenziata può essere cancellata (`ReferencedEntityDeletionVoter`).

#### Utilizzi nel Sistema di Sicurezza

```php
use SismaFramework\Security\Enumerations\AccessControlEntry;

// Un Voter che distingue il tipo di accesso richiesto
class PostVoter extends BaseVoter
{
    protected function isInstancePermitted(): bool
    {
        return $this->subject instanceof Post;
    }

    protected function checkVote(): bool
    {
        return match ($this->accessControlEntry) {
            AccessControlEntry::allow => $this->subject->isPublished,
            AccessControlEntry::check => ($this->authenticable instanceof User) && ($this->subject->author->id === $this->authenticable->id),
            default => false,
        };
    }
}

// Lettura consentita a tutti se il post è pubblicato
$canRead = PostVoter::isAllowed($post, AccessControlEntry::allow);

// Modifica consentita solo all'autore: la Permission solleva AccessDeniedException se il Voter risponde false
PostPermission::isAllowed($post, AccessControlEntry::check, $user);
```

---

## Pattern di Utilizzo Avanzati

### 1. Enumerations con Traits

```php
// Esempio dal Language enum che usa SelectableEnumeration trait
enum Status: string
{
    use \SismaFramework\Core\Traits\SelectableEnumeration;

    case active = 'active';
    case inactive = 'inactive';
    case pending = 'pending';
    case suspended = 'suspended';

    public function getLabel(): string {
        return match ($this) {
            self::active => 'Attivo',
            self::inactive => 'Inattivo',
            self::pending => 'In attesa',
            self::suspended => 'Sospeso',
        };
    }

    public function isActionable(): bool {
        return in_array($this, [self::active, self::pending]);
    }
}
```

### 2. Factory Pattern con Enumerations

```php
class ApiResponder {
    private RenderService $render;

    public function __construct() {
        $this->render = RenderService::getInstance();
    }

    public function respond(ResponseType $type, array $data = []): Response {
        return match ($type) {
            ResponseType::httpNoContent => new Response($type),
            ResponseType::httpNotFound => $this->render->generateJson(['error' => 'Resource not found'], $type),
            default => $this->render->generateJson($data, $type),
        };
    }
}
```

### 3. State Machine con Enumerations

```php
enum OrderStatus: string
{
    case pending = 'pending';
    case confirmed = 'confirmed';
    case processing = 'processing';
    case shipped = 'shipped';
    case delivered = 'delivered';
    case cancelled = 'cancelled';

    public function getAllowedTransitions(): array {
        return match ($this) {
            self::pending => [self::confirmed, self::cancelled],
            self::confirmed => [self::processing, self::cancelled],
            self::processing => [self::shipped, self::cancelled],
            self::shipped => [self::delivered],
            self::delivered => [],
            self::cancelled => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool {
        return in_array($newStatus, $this->getAllowedTransitions());
    }
}

class OrderService {
    public function __construct(private DataMapper $dataMapper = new DataMapper()) {}

    public function changeStatus(Order $order, OrderStatus $newStatus): void {
        // La proprietà dell'entità è tipizzata OrderStatus: l'ORM converte il valore della colonna nel case
        if (!$order->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Cannot transition from {$order->status->value} to {$newStatus->value}"
            );
        }

        $order->status = $newStatus;
        $this->dataMapper->save($order);
    }
}
```

---

## Best Practices

### 1. Naming Conventions

```php
// ✓ Buono: nomi descrittivi con context
enum OrderStatus: string { ... }
enum PaymentMethod: string { ... }

// ✗ Evitare: nomi troppo generici
enum Status: string { ... }
enum Type: string { ... }
```

### 2. Metodi Helper

```php
enum Priority: int
{
    case low = 1;
    case medium = 5;
    case high = 10;
    case critical = 20;

    public function isHighPriority(): bool {
        return $this->value >= 10;
    }

    public function getColor(): string {
        return match ($this) {
            self::low => 'green',
            self::medium => 'yellow',
            self::high => 'orange',
            self::critical => 'red',
        };
    }
}
```

### 3. Validation con Enumerations

```php
// Fuori dai form, tryFrom() restituisce null per un valore non valido
$status = UserStatus::tryFrom($rawValue);
if ($status === null) {
    // valore non valido
}

// Nei form, un campo di tipo enumerazione viene convertito nel case dall'ORM, e un valore che non
// corrisponde a nessun case solleva InvalidArgumentException: FilterType::isEnumeration verifica
// che il campo sia stato valorizzato
class UserForm extends BaseForm {
    protected function setFilterFieldsMode(): void {
        $this->addFilterFieldMode('status', FilterType::isEnumeration);
    }

    // ...
}
```

---

[Indice](index.md) | Precedente: [Custom Types](custom-types.md) | Successivo: [Traits per Enumerazioni](traits.md)
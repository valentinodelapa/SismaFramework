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

// Verifica del metodo HTTP
$request = new Request();
if ($request->getMethod() === RequestType::methodPost->value) {
    // Gestire richiesta POST
}

// Switch statement con enum
switch (RequestType::from($request->getMethod())) {
    case RequestType::methodGet:
        return $this->handleGet();
    case RequestType::methodPost:
        return $this->handlePost();
    case RequestType::methodDelete:
        return $this->handleDelete();
}

// Validazione API REST
class ApiController extends BaseController {
    public function updateResource(): Response {
        $allowedMethods = [
            RequestType::methodPut,
            RequestType::methodPatch
        ];

        if (!in_array(RequestType::from($_SERVER['REQUEST_METHOD']), $allowedMethods)) {
            throw new MethodNotAllowedException();
        }
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

// Impostazione status code
class UserController extends BaseController {
    public function create(): Response {
        $user = $this->userService->createUser($data);

        $response = new Response();
        $response->setStatusCode(ResponseType::httpCreated->value);
        return $response;
    }

    public function delete(int $id): Response {
        $this->userService->deleteUser($id);

        $response = new Response();
        $response->setStatusCode(ResponseType::httpNoContent->value);
        return $response;
    }
}

// Gestione errori strutturata
class ErrorHandler {
    public static function handleException(\Throwable $exception): Response {
        $response = new Response();

        $statusCode = match (true) {
            $exception instanceof ValidationException => ResponseType::httpBadRequest,
            $exception instanceof UnauthorizedException => ResponseType::httpUnauthorized,
            $exception instanceof NotFoundException => ResponseType::httpNotFound,
            default => ResponseType::httpInternalServerError,
        };

        $response->setStatusCode($statusCode->value);
        return $response;
    }
}

// API REST con status appropriati
class PostApiController extends BaseController {
    public function show(int $id): Response {
        $post = $this->postService->findById($id);

        if (!$post) {
            return $this->jsonResponse(
                ['error' => 'Post not found'],
                ResponseType::httpNotFound->value
            );
        }

        return $this->jsonResponse(
            $post->toArray(),
            ResponseType::httpOk->value
        );
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

// Ottenere nomi localizzati delle lingue
$currentLang = Language::english;

$italianName = Language::italian->getFriendlyLabel($currentLang);  // "Italian"
$frenchName = Language::french->getFriendlyLabel($currentLang);    // "French"
$germanName = Language::german->getFriendlyLabel($currentLang);    // "German"

// Con lingua italiana
$currentLang = Language::italian;
$englishName = Language::english->getFriendlyLabel($currentLang);  // "Inglese"
$frenchName = Language::french->getFriendlyLabel($currentLang);    // "Francese"

// Language switcher per UI
class LanguageSwitcherComponent {
    public function render(Language $userLanguage): string {
        $options = '';

        foreach (Language::cases() as $language) {
            $label = $language->getFriendlyLabel($userLanguage);
            $value = $language->value;
            $options .= "<option value='{$value}'>{$label}</option>";
        }

        return "<select name='language'>{$options}</select>";
    }
}

// Dropdown localizzato
class LocalizationService {
    public function getAvailableLanguages(Language $userLanguage): array {
        return Language::getChoiceFromEnumerations($userLanguage);
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
**Tipo:** `string` enum

Definisce i tipi di filtri disponibili per la validazione dei form e dei dati input.

```php
enum FilterType: string
{
    case text = 'text';
    case email = 'email';
    case integer = 'integer';
    case float = 'float';
    case boolean = 'boolean';
    case date = 'date';
    case url = 'url';
    case required = 'required';
    case minLength = 'min_length';
    case maxLength = 'max_length';
    case regex = 'regex';
}
```

#### Utilizzi Comuni

```php
use SismaFramework\Core\Enumerations\FilterType;

// Configurazione validazione form
class UserRegistrationForm extends BaseForm {
    protected function configureFilters(): array {
        return [
            'email' => [
                FilterType::required,
                FilterType::email,
                FilterType::maxLength->withValue(255)
            ],
            'password' => [
                FilterType::required,
                FilterType::minLength->withValue(8)
            ],
            'age' => [
                FilterType::integer,
                FilterType::required
            ]
        ];
    }
}

// Validazione manuale
class DataValidator {
    public function validateField(string $value, FilterType $filter): bool {
        return match ($filter) {
            FilterType::email => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            FilterType::integer => filter_var($value, FILTER_VALIDATE_INT) !== false,
            FilterType::url => filter_var($value, FILTER_VALIDATE_URL) !== false,
            FilterType::required => !empty(trim($value)),
            default => true
        };
    }
}
```

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
    case typeGeneric;
}
```

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

---

## Security Enumerations

### AccessControlEntry

**Namespace:** `SismaFramework\Security\Enumerations\AccessControlEntry`

Definisce i risultati possibili per i controlli di accesso nel sistema di sicurezza.

```php
enum AccessControlEntry
{
    case allow;    // Accesso consentito
    case check;    // Richiede ulteriori verifiche
    case deny;     // Accesso negato
}
```

#### Utilizzi nel Sistema di Sicurezza

```php
use SismaFramework\Security\Enumerations\AccessControlEntry;

// Implementazione di un Voter
class PostVoter extends BaseVoter {
    public function vote(Permission $permission, $subject = null): AccessControlEntry {
        if (!$subject instanceof Post) {
            return AccessControlEntry::deny;
        }

        return match ($permission->getAction()) {
            'view' => $this->canView($subject),
            'edit' => $this->canEdit($subject),
            'delete' => $this->canDelete($subject),
            default => AccessControlEntry::deny,
        };
    }

    private function canEdit(Post $post): AccessControlEntry {
        $user = $this->getCurrentUser();

        // Owner può sempre modificare
        if ($post->getAuthor()->getId() === $user->getId()) {
            return AccessControlEntry::allow;
        }

        // Admin può modificare tutto
        if ($user->hasRole('ADMIN')) {
            return AccessControlEntry::allow;
        }

        // Moderatori solo se il post non è pubblicato
        if ($user->hasRole('MODERATOR') && !$post->isPublished()) {
            return AccessControlEntry::check; // Richiede ulteriori verifiche
        }

        return AccessControlEntry::deny;
    }
}

// Security Manager
class SecurityManager {
    public function isGranted(Permission $permission, $subject = null): bool {
        $result = AccessControlEntry::deny;

        foreach ($this->voters as $voter) {
            $vote = $voter->vote($permission, $subject);

            // Se almeno un voter consente, permettere l'accesso
            if ($vote === AccessControlEntry::allow) {
                $result = AccessControlEntry::allow;
                break;
            }

            // Se richiede verifica, continuare con altri voter
            if ($vote === AccessControlEntry::check) {
                $result = AccessControlEntry::check;
            }
        }

        // Se nessun voter ha dato allow, verificare se c'è almeno un check
        return $result === AccessControlEntry::allow ||
               ($result === AccessControlEntry::check && $this->performAdditionalChecks($permission, $subject));
    }
}
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
class ResponseFactory {
    public static function createFromType(ResponseType $type, array $data = []): Response {
        $response = new Response();
        $response->setStatusCode($type->value);

        return match ($type) {
            ResponseType::httpOk => $response->setContent(json_encode($data)),
            ResponseType::httpCreated => $response
                ->setContent(json_encode($data))
                ->setHeader('Location', $data['location'] ?? ''),
            ResponseType::httpNoContent => $response,
            ResponseType::httpNotFound => $response
                ->setContent(json_encode(['error' => 'Resource not found'])),
            default => $response->setContent(json_encode(['error' => 'Unknown error'])),
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
    public function changeStatus(Order $order, OrderStatus $newStatus): void {
        $currentStatus = OrderStatus::from($order->getStatus());

        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidStateTransitionException(
                "Cannot transition from {$currentStatus->value} to {$newStatus->value}"
            );
        }

        $order->setStatus($newStatus->value);
        $this->entityManager->save($order);
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
class FormValidator {
    public function validateEnum(mixed $value, string $enumClass): bool {
        if (!enum_exists($enumClass)) {
            return false;
        }

        try {
            $enumClass::from($value);
            return true;
        } catch (ValueError) {
            return false;
        }
    }
}

// Utilizzo nei form
class UserForm extends BaseForm {
    public function validate(): bool {
        $validator = new FormValidator();

        if (!$validator->validateEnum($this->entity->status, UserStatus::class)) {
            $this->addError('status', 'Invalid status value');
            return false;
        }

        return true;
    }
}
```

---

[Indice](index.md) | Precedente: [Custom Types](custom-types.md) | Successivo: [Traits per Enumerazioni](traits.md)
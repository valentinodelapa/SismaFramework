# Enumerations

SismaFramework fa largo uso delle enumerazioni introdotte in PHP 8.1+ per garantire type safety e migliorare la leggibilità del codice. Le enumerazioni sono organizzate per namespace funzionale e coprono tutti gli aspetti principali del framework.

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

```php
use SismaFramework\Orm\Enumerations\DataType;

// Mappatura automatica delle proprietà
class EntityMapper {
    public function mapProperty(\ReflectionProperty $property): DataType {
        $type = $property->getType();

        return match ($type->getName()) {
            'bool' => DataType::typeBoolean,
            'int' => DataType::typeInteger,
            'string' => DataType::typeString,
            'float' => DataType::typeDecimal,
            'DateTime' => DataType::typeDate,
            default => $this->isEntityClass($type->getName())
                ? DataType::typeEntity
                : DataType::typeGeneric,
        };
    }
}

// Generazione SQL basata sui tipi
class SqlGenerator {
    public function getColumnDefinition(DataType $dataType): string {
        return match ($dataType) {
            DataType::typeBoolean => 'BOOLEAN',
            DataType::typeInteger => 'INT',
            DataType::typeString => 'VARCHAR(255)',
            DataType::typeDecimal => 'DECIMAL(10,2)',
            DataType::typeDate => 'DATETIME',
            DataType::typeBinary => 'BLOB',
            default => 'TEXT',
        };
    }
}
```

### ComparisonOperator

**Namespace:** `SismaFramework\Orm\Enumerations\ComparisonOperator`

Operatori di confronto per le query dell'ORM.

```php
enum ComparisonOperator: string
{
    case equal = '=';
    case notEqual = '!=';
    case greaterThan = '>';
    case lessThan = '<';
    case greaterThanOrEqual = '>=';
    case lessThanOrEqual = '<=';
    case like = 'LIKE';
    case notLike = 'NOT LIKE';
    case in = 'IN';
    case notIn = 'NOT IN';
    case isNull = 'IS NULL';
    case isNotNull = 'IS NOT NULL';
}
```

#### Utilizzi nel Query Builder

```php
use SismaFramework\Orm\Enumerations\ComparisonOperator;

// Query builder fluido
class QueryBuilder {
    public function where(string $column, ComparisonOperator $operator, mixed $value = null): self {
        $condition = match ($operator) {
            ComparisonOperator::isNull,
            ComparisonOperator::isNotNull => "{$column} {$operator->value}",
            ComparisonOperator::in,
            ComparisonOperator::notIn => "{$column} {$operator->value} (" . implode(',', $value) . ")",
            default => "{$column} {$operator->value} ?",
        };

        $this->conditions[] = $condition;
        if ($value !== null && !in_array($operator, [ComparisonOperator::isNull, ComparisonOperator::isNotNull])) {
            $this->parameters[] = $value;
        }

        return $this;
    }
}

// Utilizzo
$users = $queryBuilder
    ->from('user')
    ->where('age', ComparisonOperator::greaterThanOrEqual, 18)
    ->where('status', ComparisonOperator::in, ['active', 'premium'])
    ->where('deleted_at', ComparisonOperator::isNull)
    ->get();

// Search functionality
class UserSearchService {
    public function searchByName(string $searchTerm): array {
        return $this->userModel
            ->createQuery()
            ->where('first_name', ComparisonOperator::like, "%{$searchTerm}%")
            ->orWhere('last_name', ComparisonOperator::like, "%{$searchTerm}%")
            ->execute();
    }
}
```

### LogicalOperator

**Namespace:** `SismaFramework\Orm\Enumerations\LogicalOperator`

Operatori logici per combinare condizioni nelle query.

```php
enum LogicalOperator: string
{
    case and = 'AND';
    case or = 'OR';
    case not = 'NOT';
}
```

#### Utilizzi per Query Complesse

```php
use SismaFramework\Orm\Enumerations\LogicalOperator;

class AdvancedQueryBuilder {
    public function buildComplexQuery(): string {
        // (age >= 18 AND status = 'active') OR (type = 'premium' AND verified = true)
        return $this->group(function($query) {
                return $query
                    ->where('age', ComparisonOperator::greaterThanOrEqual, 18)
                    ->combine(LogicalOperator::and)
                    ->where('status', ComparisonOperator::equal, 'active');
            })
            ->combine(LogicalOperator::or)
            ->group(function($query) {
                return $query
                    ->where('type', ComparisonOperator::equal, 'premium')
                    ->combine(LogicalOperator::and)
                    ->where('verified', ComparisonOperator::equal, true);
            });
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
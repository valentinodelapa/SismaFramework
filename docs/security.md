# Sicurezza

SismaFramework fornisce un sistema di sicurezza completo che copre due aspetti fondamentali:

- **Autenticazione e Autorizzazione**: Gestione dell'identità utente e controllo degli accessi
- **Crittografia e Sicurezza**: Protezione dei dati attraverso encryption, hashing e token sicuri

Questo documento descrive come implementare funzionalità di sicurezza enterprise-grade nella tua applicazione.

## Autenticazione e Autorizzazione

Il cuore del sistema di autenticazione è la classe `SismaFramework\Security\HttpClasses\Authentication`. Questa classe, che può essere iniettata in un controller, fornisce i metodi necessari per gestire un processo di login in modo sicuro.

### Esempio: Creare una Pagina di Login

Vediamo come implementare un'action del controller che gestisce sia la visualizzazione del form di login sia l'elaborazione dei dati inviati.

```php
namespace MyModule\Application\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Security\HttpClasses\Authentication;
use MyModule\Application\Models\UserModel; // Il tuo modello utente
use MyModule\Application\Models\PasswordModel; // Il tuo modello per le password

class SecurityController extends BaseController
{
    public function login(Request $request, Authentication $auth): Response
    {
        // Se l'utente è già loggato, reindirizzalo alla dashboard
        if ($auth->isLogged()) {
            return Router::redirect('dashboard/index');
        }

        // Se il form è stato inviato (metodo POST)
        if ($request->server->get('REQUEST_METHOD') === 'POST') {
            // 1. Inietta i modelli necessari per trovare utente e password
            $auth->setAuthenticableModelInterface(new UserModel($this->dataMapper));
            $auth->setPasswordModelInterface(new PasswordModel($this->dataMapper));

            // 2. Esegui i controlli in sequenza
            if ($auth->checkAuthenticable() && $auth->checkPassword()) {
                // 3. Se i controlli passano, recupera l'utente e loggalo
                $user = $auth->getAuthenticableInterface();
                $auth->login($user);

                return Router::redirect('dashboard/index');
            } else {
                // Se i controlli falliscono, imposta un messaggio di errore
                $this->vars['error'] = 'Credenziali non valide.';
            }
        }

        // Mostra la vista del form di login
        $this->vars['pageTitle'] = 'Login';
        return Render::generateView('security/login', $this->vars);
    }
}
```

> La classe `Authentication` si occupa anche di gestire la protezione da attacchi CSRF.

### Sistema di Autorizzazione (Voters e Permissions)

Il sistema di autorizzazione si basa su due concetti: **Voters** e **Permissions**.

- **Voter**: Una classe che contiene la logica per una singola decisione di sicurezza. Risponde a una domanda con "sì" o "no" (restituisce un booleano). Ad esempio: "Questo utente è l'autore di questo post?".
- **Permission**: Una classe che usa un Voter per proteggere un'azione. Se il Voter risponde "no", la Permission lancia un'eccezione (`AccessDeniedException`), bloccando l'esecuzione.

Questo disaccoppia la logica di sicurezza (nel Voter) dal suo utilizzo (nella Permission e nel Controller).

#### Esempio: Proteggere la Modifica di un Post

**Scenario**: Solo l'autore di un `Post` può modificarlo.

##### 1. Creare il Voter

Crea un `PostVoter` nella cartella `Voters` del tuo modulo.

`MyBlog/Application/Voters/PostVoter.php`
```php
namespace MyBlog\Application\Voters;

use SismaFramework\Security\BaseClasses\BaseVoter;
use MyBlog\Application\Entities\Post;
use MyModule\Application\Entities\User; // La tua entità utente

class PostVoter extends BaseVoter
{
    // Specifica che questo Voter agisce solo su oggetti di tipo Post
    protected function isInstancePermitted(): bool
    {
        return $this->subject instanceof Post;
    }

    // Contiene la logica di autorizzazione vera e propria
    protected function checkVote(): bool
    {
        $post = $this->subject;
        $user = $this->authenticable;

        // Se l'utente non è loggato o non è un'istanza di User, nega l'accesso
        if (!$user instanceof User) {
            return false;
        }

        // L'utente è l'autore del post?
        return $post->getAuthor()->getId() === $user->getId();
    }
}
```

##### 2. Creare la Permission

Crea una `PostPermission` nella cartella `Permissions` che utilizzi il `PostVoter`.

`MyBlog/Application/Permissions/PostPermission.php`
```php
namespace MyBlog\Application\Permissions;

use SismaFramework\Security\BaseClasses\BasePermission;
use MyBlog\Application\Voters\PostVoter;

class PostPermission extends BasePermission
{
    // Non ci sono altre permission da chiamare prima
    protected function callParentPermissions(): void {}

    // Specifica quale Voter deve essere usato
    protected function getVoter(): string
    {
        return PostVoter::class;
    }
}
```

##### 3. Usare la Permission nel Controller

Ora, all'inizio dell'action `edit` del tuo `PostController`, invoca la `Permission`.

```php
use MyBlog\Application\Permissions\PostPermission;
use SismaFramework\Security\Enumerations\AccessControlEntry;

class PostController extends BaseController
{
    public function edit(Request $request, Post $post, Authentication $auth): Response
    {
        // 1. Controlla il permesso. Se fallisce, lancia un'eccezione 403.
        PostPermission::isAllowed(
            $post,                         // Il soggetto su cui decidere
            AccessControlEntry::check,     // Il tipo di controllo
            $auth->getAuthenticable()      // L'utente attualmente loggato
        );

        // 2. Se il controllo passa, prosegui con la logica del form...
        $form = new PostForm($post);
        // ...
    }
}
```

## Crittografia e Protezione Dati

### Classe `Encryptor`

La classe `Encryptor` fornisce un'API unificata per tutte le operazioni crittografiche, dalla generazione di token all'encryption completa dei dati.

#### Generazione Token Casuali

Per creare token sicuri da usare in sessioni, CSRF protection o API keys:

```php
use SismaFramework\Core\HelperClasses\Encryptor;

// Genera un token esadecimale di 32 caratteri (16 bytes)
$token = Encryptor::getSimpleRandomToken();
// Esempio output: "a1b2c3d4e5f6789012345678901234567890abcd"

// Uso tipico per CSRF token
$_SESSION['csrf_token'] = Encryptor::getSimpleRandomToken();
```

#### Hash Semplici (per checksum e integrità)

Gli hash semplici sono ideali per verifiche di integrità, checksum e confronti rapidi:

```php
// Hash con algoritmo di default (configurabile)
$hash = Encryptor::getSimpleHash('testo da hashare');

// Verifica hash
$isValid = Encryptor::verifySimpleHash('testo da hashare', $hash);

// Con configurazione personalizzata
$customConfig = Config::getInstance();
$hash = Encryptor::getSimpleHash('testo', $customConfig);
```

**Configurazione algoritmo in `Config/config.php`:**
```php
const SIMPLE_HASH_ALGORITHM = 'sha256'; // o 'md5', 'sha1', 'sha512'
```

#### Hash Password (Blowfish/BCrypt)

Per l'hashing sicuro delle password utilizza sempre Blowfish/BCrypt:

```php
// Hash password (cost configurabile)
$hashedPassword = Encryptor::getBlowfishHash('password_utente');

// Verifica password
$isCorrect = Encryptor::verifyBlowfishHash('password_utente', $hashedPassword);
```

**Configurazione workload in `Config/config.php`:**
```php
const BLOWFISH_HASH_WORKLOAD = 12; // Range: 4-31 (default: 10)
```

**Note di sicurezza:**
- **Mai** usare hash semplici per le password
- BCrypt include automaticamente il salt
- Il workload determina il tempo di calcolo (più alto = più sicuro ma più lento)

#### Crittografia Simmetrica

Per crittografare dati sensibili con chiave simmetrica:

```php
// 1. Genera Initialization Vector (IV)
$iv = Encryptor::createInizializationVector();

// 2. Cifra il testo
$plaintext = 'Dati sensibili da proteggere';
$ciphertext = Encryptor::encryptString($plaintext, $iv);

// 3. Decifra il testo
$decrypted = Encryptor::decryptString($ciphertext, $iv);

// IMPORTANTE: Salva sempre IV insieme ai dati cifrati
$dataToStore = base64_encode($iv) . '|' . $ciphertext;
```

**Configurazione crittografia in `Config/config.php`:**
```php
const ENCRYPTION_ALGORITHM = 'AES-256-CBC';
const ENCRYPTION_PASSPHRASE = 'chiave-molto-lunga-e-sicura';
const INITIALIZATION_VECTOR_BYTES = 16;
```

### Interfacce di Autenticazione

#### `AuthenticableInterface`

Definisce il contratto per entità che possono essere autenticate:

```php
use SismaFramework\Security\Interfaces\Entities\AuthenticableInterface;

class User implements AuthenticableInterface
{
    public function getAuthIdentifier(): string
    {
        return $this->email; // o $this->username
    }

    public function getAuthPassword(): string
    {
        return $this->passwordHash;
    }
}
```

#### `PasswordInterface`

Per entità che gestiscono reset password:

```php
use SismaFramework\Security\Interfaces\Entities\PasswordInterface;

class User implements PasswordInterface
{
    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setPasswordResetToken(?string $token): void
    {
        $this->resetToken = $token;
        $this->resetTokenExpiry = $token ? (new DateTime())->add(new DateInterval('P1D')) : null;
    }
}
```

#### `MultiFactorInterface`

Per autenticazione a due fattori:

```php
use SismaFramework\Security\Interfaces\Entities\MultiFactorInterface;

class User implements MultiFactorInterface
{
    public function isMfaEnabled(): bool
    {
        return $this->mfaSecret !== null;
    }

    public function getMfaSecret(): ?string
    {
        return $this->mfaSecret;
    }

    public function setMfaSecret(?string $secret): void
    {
        $this->mfaSecret = $secret;
    }
}
```

## Esempi Pratici

### 1. Sistema di Login Sicuro

```php
class AuthController extends BaseController
{
    public function login(Request $request): Response
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        $user = $this->userModel->getByEmail($email);

        if ($user && Encryptor::verifyBlowfishHash($password, $user->getAuthPassword())) {
            // Login riuscito
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['session_token'] = Encryptor::getSimpleRandomToken();

            return Router::redirect('dashboard');
        }

        $this->vars['error'] = 'Credenziali non valide';
        return Render::generateView('auth/login', $this->vars);
    }
}
```

### 2. Crittografia Dati Utente

```php
class UserProfile
{
    public function encryptSensitiveData(string $data): string
    {
        $iv = Encryptor::createInizializationVector();
        $encrypted = Encryptor::encryptString($data, $iv);

        // Combina IV e dati cifrati per storage
        return base64_encode($iv) . '|' . $encrypted;
    }

    public function decryptSensitiveData(string $encryptedData): string|false
    {
        [$ivBase64, $ciphertext] = explode('|', $encryptedData, 2);
        $iv = base64_decode($ivBase64);

        return Encryptor::decryptString($ciphertext, $iv);
    }
}
```

### 3. Reset Password Sicuro

```php
class PasswordResetController extends BaseController
{
    public function requestReset(Request $request): Response
    {
        $email = $request->request->get('email');
        $user = $this->userModel->getByEmail($email);

        if ($user) {
            // Genera token sicuro
            $token = Encryptor::getSimpleRandomToken();
            $user->setPasswordResetToken($token);
            $this->dataMapper->save($user);

            // Invia email con link di reset
            $resetLink = "https://mysite.com/reset-password?token={$token}";
            // ... codice invio email
        }

        return Render::generateView('auth/reset-sent', $this->vars);
    }

    public function resetPassword(Request $request): Response
    {
        $token = $request->query->get('token');
        $newPassword = $request->request->get('password');

        $user = $this->userModel->getByResetToken($token);

        if ($user && $this->isTokenValid($user)) {
            // Hash nuova password
            $hashedPassword = Encryptor::getBlowfishHash($newPassword);
            $user->setPassword($hashedPassword);
            $user->setPasswordResetToken(null); // Invalida token

            $this->dataMapper->save($user);

            return Router::redirect('login');
        }

        $this->vars['error'] = 'Token non valido o scaduto';
        return Render::generateView('auth/reset-error', $this->vars);
    }
}
```

## Best Practices di Sicurezza

### 1. Gestione delle Chiavi
- **Mai** hardcodare chiavi nel codice
- Usa variabili d'ambiente per configurazioni sensibili
- Ruota periodicamente le chiavi di encryption

### 2. Storage Sicuro
- Salva sempre IV insieme ai dati cifrati
- Non riutilizzare mai lo stesso IV
- Usa hash BCrypt per tutte le password

### 3. Validazione Input
- Sanifica sempre l'input utente
- Usa prepared statements per query SQL
- Implementa rate limiting per login

### 4. Gestione Errori
- Non rivelare informazioni specifiche negli errori
- Logga tentativi di accesso falliti
- Implementa temporary lockout dopo tentativi multipli

### 5. HTTPS e Trasporto
- Usa sempre HTTPS in produzione
- Configura header di sicurezza appropriati
- Implementa CSRF protection

## Configurazione Sicurezza Produzione

In `Config/config.php` per ambiente di produzione:

```php
// Hash e Encryption
const BLOWFISH_HASH_WORKLOAD = 12;
const ENCRYPTION_ALGORITHM = 'AES-256-CBC';
const SIMPLE_HASH_ALGORITHM = 'sha256';

// Session Security
const SESSION_COOKIE_SECURE = true;
const SESSION_COOKIE_HTTPONLY = true;
const SESSION_COOKIE_SAMESITE = 'Strict';

// CSRF Protection
const CSRF_TOKEN_NAME = '_token';
const CSRF_TOKEN_EXPIRY = 3600; // 1 ora
```

---

[Indice](index.md) | Precedente: [Enumerazioni](enumerations.md) | Successivo: [Barra di Debug](debug-bar.md)
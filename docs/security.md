# Sicurezza

SismaFramework fornisce un sistema di sicurezza completo che copre due aspetti fondamentali:

- **Autenticazione e Autorizzazione**: Gestione dell'identità utente e controllo degli accessi
- **Crittografia e Sicurezza**: Protezione dei dati attraverso encryption, hashing e token sicuri

Questo documento descrive come implementare funzionalità di sicurezza enterprise-grade nella tua applicazione.

## Autenticazione e Autorizzazione

### Gerarchia delle classi di autenticazione

Il sistema di autenticazione è fondato su una classe astratta base:

- **`BaseAuthentication`** (`Security/BaseClasses/`) — classe `@internal` che centralizza le dipendenze comuni a tutti i flussi (request, filter, session, authenticable interface).
- **`Authentication`** (`Security/HttpClasses/`) — flusso form-based classico; usa `SubmittableTrait` per la gestione di submission e degli errori di validazione.
- **`OAuthAuthentication`** (`Security/HttpClasses/`) — flusso OAuth 2.0 Authorization Code; non usa `SubmittableTrait` perché non esiste un form da sottomettere.

### Autenticazione Form-Based (`Authentication`)

La classe `Authentication` può essere iniettata direttamente in un controller e fornisce i metodi per validare le credenziali utente in modo sicuro (inclusa la protezione CSRF).

> **Nota sulla gestione della sessione**: La classe `Authentication` si occupa esclusivamente della *validazione* delle credenziali. La persistenza dello stato di autenticazione (login/logout) va gestita manualmente tramite la classe `Session`.

#### Esempio: Creare una Pagina di Login

```php
namespace MyModule\Application\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Security\HttpClasses\Authentication;
use MyModule\Application\Models\UserModel;
use MyModule\Application\Models\PasswordModel;

class SecurityController extends BaseController
{
    public function login(Request $request, Authentication $auth): Response
    {
        // Se l'utente è già loggato (sessione attiva), reindirizzalo
        if (Session::hasItem('userId')) {
            return $this->router->redirect('dashboard/index');
        }

        // Se il form è stato inviato (metodo POST)
        if ($request->server['REQUEST_METHOD'] === 'POST') {
            // 1. Inietta i modelli necessari
            $auth->setAuthenticableModelInterface(new UserModel($this->dataMapper));
            $auth->setPasswordModelInterface(new PasswordModel($this->dataMapper));

            // 2. checkAuthenticable() verifica in sequenza: CSRF token,
            //    identificatore utente e password. Restituisce true solo
            //    se tutti i controlli passano.
            if ($auth->checkAuthenticable()) {
                // 3. Recupera l'entità autenticata e persisti l'ID in sessione
                $user = $auth->getAuthenticableInterface();
                Session::setItem('userId', $user->getId());

                return $this->router->redirect('dashboard/index');
            }

            // Se i controlli falliscono, gli errori sono disponibili tramite getFilterErrors()
            $this->vars['errors'] = $auth->getFilterErrors();
        }

        // Genera e salva il token CSRF per il form: checkCsrfToken()/checkAuthenticable()
        // lo verificano soltanto, non lo generano — tocca all'applicazione farlo prima
        // di renderizzare il form.
        if (Session::hasItem('csrfToken') === false) {
            Session::setItem('csrfToken', Encryptor::getSimpleRandomToken());
        }
        $this->vars['csrfToken'] = Session::getItem('csrfToken');

        $this->vars['pageTitle'] = 'Login';
        return $this->render->generateView('security/login', $this->vars);
    }

    public function logout(): Response
    {
        Session::end();
        return $this->router->redirect('security/login');
    }
}
```

> La classe `Authentication` gestisce automaticamente la protezione da attacchi CSRF tramite `checkCsrfToken()`, che viene chiamato internamente da `checkAuthenticable()`.

Il form di login deve inviare i campi `identifier`, `password` e `csrfToken`; quello di verifica a due fattori il campo `code`. Sono i nomi di default e si cambiano con `AUTHENTICATION_IDENTIFIER_FIELD`, `AUTHENTICATION_PASSWORD_FIELD`, `CSRF_TOKEN_KEY` e `AUTHENTICATION_MULTI_FACTOR_CODE_FIELD` (vedi [Configuration Reference](configuration-reference.md#autenticazione)).

### Autenticazione OAuth 2.0 (`OAuthAuthentication`)

La classe `OAuthAuthentication` implementa il flusso **Authorization Code OAuth 2.0** ed è progettata per provider come Google, GitHub, ecc. Non usa `SubmittableTrait` perché non esiste un form da sottomettere: gli errori arrivano come parametri URL dal provider e vengono gestiti tramite valori di ritorno.

#### `OAuthWrapperInterface`

Per astrarre la comunicazione con il provider OAuth, il framework fornisce l'interfaccia `OAuthWrapperInterface` (`Security/Interfaces/Wrappers/`). Ogni provider deve implementare due metodi:

```php
namespace SismaFramework\Security\Interfaces\Wrappers;

interface OAuthWrapperInterface
{
    // Costruisce l'URL di autorizzazione con il parametro state anti-CSRF
    public function getAuthorizationUrl(string $state): string;

    // Scambia il codice di autorizzazione per un identificatore utente (es. email).
    // In caso di errore (token invalido, errore di rete), propaga un'eccezione.
    public function getAuthenticableIdentifier(string $code): string;
}
```

#### Esempio: Implementare un provider OAuth (Google)

```php
namespace MyModule\Application\Wrappers;

use SismaFramework\Security\Interfaces\Wrappers\OAuthWrapperInterface;

class GoogleOAuthWrapper implements OAuthWrapperInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public function getAuthenticableIdentifier(string $code): string
    {
        // Scambia il code per un access token, poi recupera l'email
        // (implementazione specifica del provider)
        $tokenResponse = $this->exchangeCodeForToken($code);
        $userInfo = $this->getUserInfo($tokenResponse['access_token']);
        return $userInfo['email'];
    }

    // ... metodi privati di supporto ...
}
```

#### Esempio: Controller con flusso OAuth

Il flusso OAuth si articola in due action: una che avvia il redirect verso il provider e una che gestisce il callback.

```php
namespace MyModule\Application\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Security\HttpClasses\OAuthAuthentication;
use MyModule\Application\Models\UserModel;
use MyModule\Application\Wrappers\GoogleOAuthWrapper;

class OAuthController extends BaseController
{
    // Fase 1: Reindirizza l'utente al provider OAuth
    public function redirectToProvider(OAuthAuthentication $auth): Response
    {
        $wrapper = new GoogleOAuthWrapper(
            clientId: 'YOUR_CLIENT_ID',
            clientSecret: 'YOUR_CLIENT_SECRET',
            redirectUri: 'https://myapp.com/oauth/callback',
        );
        $auth->setOAuthWrapperInterface($wrapper);

        // getAuthorizationUrl() genera lo state anti-CSRF, lo salva in sessione
        // e restituisce l'URL completo del provider
        $url = $auth->getAuthorizationUrl();

        return $this->router->redirectToUrl($url);
    }

    // Fase 2: Gestisce il callback del provider
    public function callback(Request $request, OAuthAuthentication $auth): Response
    {
        $wrapper = new GoogleOAuthWrapper(
            clientId: 'YOUR_CLIENT_ID',
            clientSecret: 'YOUR_CLIENT_SECRET',
            redirectUri: 'https://myapp.com/oauth/callback',
        );
        $auth->setOAuthWrapperInterface($wrapper);
        $auth->setAuthenticableModelInterface(new UserModel($this->dataMapper));

        // checkCallback() verifica lo state anti-CSRF, scambia il code per
        // un identificatore e recupera l'utente dal modello
        if ($auth->checkCallback()) {
            $user = $auth->getAuthenticableInterface();
            Session::setItem('userId', $user->getId());
            return $this->router->redirect('dashboard/index');
        }

        $this->vars['error'] = 'Autenticazione OAuth fallita.';
        return $this->render->generateView('security/oauth-error', $this->vars);
    }
}
```

> **Protezione CSRF in OAuth**: `getAuthorizationUrl()` genera automaticamente uno `state` casuale di `OAUTH_STATE_BYTES` byte (default 16) e lo persiste in sessione, nella chiave `OAUTH_STATE_KEY` (default `oauthState`). `checkCallback()` lo verifica in modo timing-safe tramite `hash_equals()`, seguendo lo stesso pattern difensivo di `checkCsrfToken()` nella classe `Authentication`.

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
            $auth->getAuthenticableInterface() // L'utente attualmente loggato
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

// Genera un token esadecimale di 32 caratteri (SIMPLE_RANDOM_TOKEN_BYTES = 16 byte per default)
$token = Encryptor::getSimpleRandomToken();
// Esempio output: "a1b2c3d4e5f60718293a4b5c6d7e8f90"

// Uso tipico per il token CSRF verificato da Authentication::checkCsrfToken()
Session::setItem('csrfToken', Encryptor::getSimpleRandomToken());
```

La lunghezza in byte si cambia con `SIMPLE_RANDOM_TOKEN_BYTES`; il token esadecimale è lungo il doppio.

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
const SIMPLE_HASH_ALGORITHM = 'sha256'; // o 'sha384', 'sha512', 'sha3-256'
```

Non usare `md5` né `sha1`: sono vulnerabili alle collisioni, quindi non garantiscono che due testi diversi abbiano hash diversi. `getSimpleHash()` è comunque un hash veloce e senza sale, adatto a impronte e confronti di integrità: per le password usa `getBlowfishHash()`.

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
$iv = Encryptor::createInitializationVector();

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

#### Crittografia Asimmetrica e Certificati

Per casi d'uso che richiedono chiavi pubbliche/private reali — firma digitale di documenti, identità verificabili, catene di certificati, dati leggibili solo da un destinatario specifico — `Encryptor` fornisce un'API basata su OpenSSL completa: generazione di chiavi e certificati, verifica della catena di fiducia, firma/verifica di dati e cifratura a busta.

Tutte le operazioni funzionano senza alcuna configurazione: se `OPENSSL_CONFIG_PATH` non è valorizzata, `Encryptor` usa la configurazione OpenSSL minimale `Config/openssl.cnf` inclusa nel modulo — non serve un `openssl.cnf` di sistema risolvibile, nemmeno su ambienti (tipicamente Windows) che ne sono privi.

**Generare una coppia di chiavi:**

```php
$keyPair = Encryptor::generateAsymmetricKeyPair();
// ['privateKey' => '-----BEGIN PRIVATE KEY-----...', 'publicKey' => '-----BEGIN PUBLIC KEY-----...']
```

**Certificato self-signed** (il soggetto è al contempo titolare e garante — es. un fondatore/root of trust iniziale). Il secondo parametro booleano (default `true`) controlla l'estensione X.509v3 `basicConstraints`: `true` produce un certificato abilitato a firmare altri certificati, `false` un certificato foglia non abilitato a farlo:

```php
$certificate = Encryptor::generateSelfSignedCertificate(
    $keyPair['privateKey'],
    ['CN' => 'Mario Rossi', 'O' => 'La Mia Organizzazione']
    // true di default: root of trust abilitata a firmare altri certificati
);
```

**Certificato emesso da una CA** (il soggetto genera la propria chiave e una CSR; la CA firma la CSR con il proprio certificato/chiave, producendo un certificato con `issuer` diverso dal `subject`). Anche qui un parametro booleano (default `false`) decide se il certificato emesso è a sua volta una CA intermedia o un certificato foglia:

```php
// Lato CA: una CA self-signed già esistente
$caKeyPair = Encryptor::generateAsymmetricKeyPair();
$caCertificate = Encryptor::generateSelfSignedCertificate($caKeyPair['privateKey'], ['CN' => 'La Mia CA']);

// Lato soggetto: genera la propria chiave e la CSR
$subjectKeyPair = Encryptor::generateAsymmetricKeyPair();
$csr = Encryptor::generateCertificateSigningRequest($subjectKeyPair['privateKey'], ['CN' => 'Mario Rossi']);

// Lato CA: firma la CSR, emette il certificato del soggetto (foglia, non CA)
$subjectCertificate = Encryptor::signCertificateSigningRequest($csr, $caCertificate, $caKeyPair['privateKey']);
```

**Verificare la catena di fiducia** ("questo certificato è stato davvero emesso da questa CA?" — diverso dal verificare la firma su un documento):

```php
$isTrusted = Encryptor::verifyCertificateSignedByIssuer($subjectCertificate, $caCertificate); // true
$isTrusted = Encryptor::verifyCertificateSignedByIssuer($subjectCertificate, $unrelatedCertificate); // false
```

**Firma e verifica di dati:**

```php
$signature = Encryptor::signData('testo del documento', $subjectKeyPair['privateKey']);

// La verifica accetta sia un certificato che una chiave pubblica
$isValid = Encryptor::verifySignature('testo del documento', $signature, $subjectCertificate); // true
$isValid = Encryptor::verifySignature('testo manomesso', $signature, $subjectCertificate); // false
```

**Cifratura a busta (envelope encryption)** — a differenza della cifratura RSA diretta, non ha limiti di dimensione sul dato cifrato: la chiave pubblica cifra una chiave simmetrica generata al volo, che a sua volta cifra i dati (stesso algoritmo di `ENCRYPTION_ALGORITHM`):

```php
$envelope = Encryptor::encryptWithPublicKey('dati riservati per il destinatario', $subjectCertificate);
// ['data' => ..., 'envelopeKey' => ..., 'initializationVector' => ...] — tutti in base64

$decrypted = Encryptor::decryptWithPrivateKey($envelope, $subjectKeyPair['privateKey']);
// 'dati riservati per il destinatario'

// Con la chiave privata sbagliata: false, non un'eccezione (stesso contratto di decryptString())
$failed = Encryptor::decryptWithPrivateKey($envelope, $altraChiavePrivata); // false
```

**Configurazione in `Config/config.php`:**
```php
const ASYMMETRIC_KEY_TYPE = OPENSSL_KEYTYPE_RSA;
const ASYMMETRIC_KEY_BITS = 2048;
const ASYMMETRIC_DIGEST_ALGORITHM = 'sha256';
const CERTIFICATE_VALIDITY_DAYS = 3650;

// Opzionale: necessaria solo per esigenze avanzate (es. un provider/engine OpenSSL
// specifico). Se il file indicato non contiene le sezioni [v3_ca]/[v3_leaf] usate da
// generateSelfSignedCertificate()/signCertificateSigningRequest(), l'estensione
// basicConstraints non verrà applicata ai certificati generati.
define(__NAMESPACE__ . '\OPENSSL_CONFIG_PATH', getenv('OPENSSL_CONFIG_PATH') ?: '');
```

> **Nota per progetti già installati**: `Config/configFramework.php` viene copiato da `Config/config.php` una sola volta, in fase di installazione, e non si aggiorna con gli upgrade del framework. Dalla 12.9.0 non serve aggiungere le costanti mancanti: se una costante non è dichiarata nel progetto, `Config` usa il valore di default del `config.php` del modulo. Aggiungile al tuo `configFramework.php` solo per cambiarne il valore. Fino alla 12.8.x una costante mancante sollevava `Error: Undefined constant`.

#### Cifratura con Password (v12.8.0+)

`encryptString()` cifra con la passphrase dell'applicazione: chi controlla il server può decifrare tutto. Quando un dato deve restare inaccessibile senza un segreto che conosce solo il suo titolare (una chiave privata custodita sul server ma utilizzabile solo con il PIN dell'utente, un documento protetto da password), si usa la cifratura con password:

```php
// Cifratura: la chiave AES viene ricavata dalla password
$envelope = Encryptor::encryptWithPassword($privateKeyPem, $pin);

// Decifratura: false se la password è errata o l'involucro è stato alterato
$privateKeyPem = Encryptor::decryptWithPassword($envelope, $pin);
if ($privateKeyPem === false) {
    // PIN errato
}
```

- **Algoritmi**: per default la chiave è ricavata dalla password con PBKDF2-HMAC-SHA256 (600.000 iterazioni, minimo 100.000) e un sale casuale di 16 byte; i dati sono cifrati con AES-256-GCM, che ne garantisce anche l'integrità. Hash, cifrario (purché autenticato) e lunghezze sono configurabili.
- **Involucro autodescrittivo**: il risultato è una stringa `sisma-pw2$<cifrario>$pbkdf2-<hash>$<iterazioni>$<sale>$<iv>$<tag>$<dati>` (campi binari in base64) che contiene tutto il necessario per decifrare, password esclusa: non serve conservare a parte sale o IV, e cambiare la configurazione non rende indecifrabili gli involucri esistenti. L'intestazione (versione, cifrario, hash, iterazioni, sale) è autenticata insieme ai dati, quindi un involucro con i parametri alterati non si decifra. Gli involucri `sisma-pw1` della 12.8.x non sono piÃ¹ supportati.
- **Configurazione**: il costo di default e l'intervallo ammesso delle iterazioni sono letti da `PASSWORD_KEY_DERIVATION_DEFAULT_ITERATIONS`, `PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS` e `PASSWORD_KEY_DERIVATION_MAXIMUM_ITERATIONS`; il terzo argomento di `encryptWithPassword()` permette di scegliere un costo diverso per il singolo involucro. Le iterazioni effettive sono scritte nell'involucro, quindi alzare il default non rende indecifrabili gli involucri già creati.
- **Errori**: `encryptWithPassword()` solleva `InvalidArgumentException` per una password vuota o un numero di iterazioni fuori dall'intervallo ammesso; `decryptWithPassword()` non solleva eccezioni e restituisce `false`, come `decryptString()`.

La robustezza dipende dalla password: un PIN numerico corto resiste a un attacco offline solo per il costo della derivazione. Per i segreti più delicati, limitare anche i tentativi lato applicativo e preferire password lunghe.

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
        $email = $request->input['email'] ?? '';
        $password = $request->input['password'] ?? '';

        $user = $this->userModel->getByEmail($email);

        if ($user && Encryptor::verifyBlowfishHash($password, $user->getAuthPassword())) {
            // Login riuscito
            Session::setItem('userId', $user->getId());
            Session::setItem('sessionToken', Encryptor::getSimpleRandomToken());

            return $this->router->redirect('dashboard');
        }

        $this->vars['error'] = 'Credenziali non valide';
        return $this->render->generateView('auth/login', $this->vars);
    }
}
```

### 2. Crittografia Dati Utente

```php
class UserProfile
{
    public function encryptSensitiveData(string $data): string
    {
        $iv = Encryptor::createInitializationVector();
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
        $email = $request->input['email'] ?? '';
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

        return $this->render->generateView('auth/reset-sent', $this->vars);
    }

    public function resetPassword(Request $request): Response
    {
        $token = $request->query['token'] ?? '';
        $newPassword = $request->input['password'] ?? '';

        $user = $this->userModel->getByResetToken($token);

        if ($user && $this->isTokenValid($user)) {
            // Hash nuova password
            $hashedPassword = Encryptor::getBlowfishHash($newPassword);
            $user->setPassword($hashedPassword);
            $user->setPasswordResetToken(null); // Invalida token

            $this->dataMapper->save($user);

            return $this->router->redirect('login');
        }

        $this->vars['error'] = 'Token non valido o scaduto';
        return $this->render->generateView('auth/reset-error', $this->vars);
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
```

### Cookie di Sessione

`Session::start()` imposta i parametri del cookie di sessione via `session_set_cookie_params()`, leggendoli dalla configurazione (v12.9.0+):

```php
[
    "lifetime"  => $config->sessionLifetime,        // SESSION_LIFETIME, default 3600
    "path"      => $config->sessionCookiePath,      // SESSION_COOKIE_PATH, default "/"
    "domain"    => $config->sessionCookieDomain,    // SESSION_COOKIE_DOMAIN, default ""
    "secure"    => Communication::getCommunicationProtocol($request) === CommunicationProtocol::https,
    "httponly"  => $config->sessionCookieHttpOnly,  // SESSION_COOKIE_HTTP_ONLY, default true
    "samesite"  => $config->sessionCookieSameSite,  // SESSION_COOKIE_SAME_SITE, default "Lax"
]
```

Per default `domain` è vuoto (v12.8.2+): il cookie resta legato all'host esatto che l'ha emesso, porta compresa, e non viene inviato ai sottodomini. Per condividere la sessione tra sottodomini si imposta `SESSION_COOKIE_DOMAIN` sul dominio comune (per esempio `example.com`, senza porta: un attributo `Domain` con la porta non è valido e il browser scarta il cookie). Fino alla 12.8.1 valeva `HTTP_HOST`, che su una porta non standard (`localhost:8080`) contiene anche la porta: i browser scartavano il cookie e il login non funzionava.

`secure` viene determinato automaticamente in base al protocollo della richiesta corrente (coerente con `HTTPS_IS_FORCED`, non un flag separato). Disattivare `httponly` rende il cookie leggibile da JavaScript, quindi esposto al furto tramite XSS; con `samesite` a `Strict` il cookie non viene inviato quando l'utente arriva da un link esterno, e con `None` serve HTTPS (i browser scartano un cookie `SameSite=None` senza `Secure`).

### Rotazione dell'ID di Sessione

`Session::start()`, chiamato dal front controller a ogni richiesta, sostituisce l'ID di sessione con uno nuovo che ne eredita i dati: un ID sottratto smette presto di essere utilizzabile. La vecchia sessione non viene cancellata subito ma marcata con l'istante della rotazione e con il nuovo ID (v12.8.2+):

- una richiesta che si presenta con un ID già ruotato **entro `SESSION_ROTATION_GRACE_SECONDS` (30 secondi per default)** viene agganciata alla sessione corrente, seguendo anche più rotazioni consecutive, e non ruota di nuovo. È il caso normale delle richieste concorrenti dello stesso browser: fogli di stile, script e chiamate `fetch` di una pagina partono tutte con lo stesso cookie, e quando passano dal front controller la prima che arriva ruota l'ID;
- oltre il periodo di grazia la vecchia sessione è considerata scaduta e la richiesta riceve una sessione vuota.

Fino alla 12.8.1 la rotazione cancellava subito la vecchia sessione (`session_regenerate_id(true)`): ogni richiesta concorrente partita con il cookie precedente riceveva una sessione vuota, e l'utente risultava disconnesso già al caricamento di una pagina con un foglio di stile e uno script serviti dal front controller.

Il token anti-dirottamento (impronta di `User-Agent` e indirizzo IP, calcolata con `SESSION_TOKEN_HASH_ALGORITHM` e registrata nella chiave `SESSION_TOKEN_KEY`) viene registrato alla creazione della sessione e non più a ogni richiesta: `Session::isValidSession($request)` restituisce `false` se la richiesta corrente proviene da un client diverso da quello che ha aperto la sessione. Il framework non chiama `isValidSession()`: la verifica spetta all'applicazione. Dietro un reverse proxy, o con utenti che cambiano rete durante la navigazione, l'indirizzo IP non identifica il client e va escluso dall'impronta impostando `SESSION_TOKEN_INCLUDES_REMOTE_ADDRESS` a `false` (v12.13.0+): il token resta legato al solo `User-Agent`.

### Protezione CSRF

Il nome del token è `CSRF_TOKEN_KEY` (default `csrfToken`, configurabile dalla v12.9.0) ed è usato sia come chiave di sessione sia come nome del campo del form; il framework non applica alcuna scadenza al token. `Authentication::checkCsrfToken()` (richiamato internamente da `checkAuthenticable()`) **verifica soltanto** che il valore in sessione corrisponda al campo omonimo inviato dal form, con un confronto a tempo costante (`hash_equals()`, dalla v12.9.0) — non lo genera. Genera e salva il token nella action che mostra il form, prima di renderizzarlo. Gli esempi usano il nome di default; se cambi `CSRF_TOKEN_KEY`, usa lo stesso nome in sessione e nel form:

```php
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\Session;

if (Session::hasItem('csrfToken') === false) {
    Session::setItem('csrfToken', Encryptor::getSimpleRandomToken());
}
$this->vars['csrfToken'] = Session::getItem('csrfToken');
```

e includilo nel form come campo nascosto:

```html
<input type="hidden" name="csrfToken" value="<?= htmlspecialchars($csrfToken) ?>">
```

---

[Indice](index.md) | Precedente: [Enumerazioni](enumerations.md) | Successivo: [Barra di Debug](debug-bar.md)
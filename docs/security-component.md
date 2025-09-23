# Sicurezza: Autenticazione e Autorizzazione

Il componente di sicurezza di SismaFramework fornisce un sistema robusto per gestire due aspetti fondamentali di ogni applicazione:

*   **Autenticazione**: Il processo di verifica dell'identità di un utente (es. tramite login con username e password).
*   **Autorizzazione**: Il processo di verifica se un utente autenticato ha il permesso di eseguire una determinata azione (es. modificare un articolo).

## Autenticazione

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

## Autorizzazione (Voters e Permissions)

Il sistema di autorizzazione si basa su due concetti: **Voters** e **Permissions**.

*   **Voter**: Una classe che contiene la logica per una singola decisione di sicurezza. Risponde a una domanda con "sì" o "no" (restituisce un booleano). Ad esempio: "Questo utente è l'autore di questo post?".
*   **Permission**: Una classe che usa un Voter per proteggere un'azione. Se il Voter risponde "no", la Permission lancia un'eccezione (`AccessDeniedException`), bloccando l'esecuzione.

Questo disaccoppia la logica di sicurezza (nel Voter) dal suo utilizzo (nella Permission e nel Controller).

### Esempio: Proteggere la Modifica di un Post

**Scenario**: Solo l'autore di un `Post` può modificarlo.

#### 1. Creare il Voter

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

#### 2. Creare la Permission

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

#### 3. Usare la Permission nel Controller

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

* * *

[Indice](index.md) | Precedente: [Funzionalità Avanzate dell'ORM](orm-additional-features.md) | Successivo: [Barra di Debug](debug-bar.md)

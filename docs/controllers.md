# # Controllori

I Controllori sono il cuore della gestione delle richieste nella tua applicazione. Il loro compito è ricevere una richiesta HTTP, interagire con il Model (se necessario) per recuperare o modificare dati, e infine restituire una risposta che verrà inviata al browser.

In SismaFramework, un controller è una classe PHP che estende `BaseController` e i suoi metodi pubblici sono chiamati **Actions**.

## Creare un Controllore

Per creare un controllore, crea un nuovo file nella cartella `Controllers` del tuo modulo (es. `MyModule/App/Controllers/PageController.php`).

Ogni action deve restituire un oggetto `Response`, che rappresenta la risposta HTTP da inviare.

Ecco un esempio di un controllore di base con una singola action che renderizza una vista:

```php
namespace MyModule\App\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;

class PageController extends BaseController
{
    /**
     * Questa action risponde all'URL /page/index
     */
    public function index(): Response
    {
        // Prepara le variabili da passare alla vista
        $this->vars['pageTitle'] = 'Pagina di Benvenuto';
        $this->vars['content'] = 'Questo è il contenuto della nostra prima pagina.';

        // Renderizza la vista 'page/index.php' e le passa le variabili
        return Render::generateView('page/index', $this->vars);
    }
} Classe Render
```

Routing e Parametri delle Action
--------------------------------

Il `Dispatcher` di SismaFramework mappa automaticamente gli URL alle actions dei controller. La convenzione è: `/nome-controller/nome-action`. I nomi sono convertiti da `kebab-case` (nell'URL) a `CamelCase` (nel codice).

* **URL:** `/user-profile/show-details`
* **Controller:** `UserProfileController`
* **Action:** `showDetails()`

### Passare Parametri dall'URL

Puoi passare parametri dall'URL direttamente come argomenti delle tue action. Il framework si occupa di mapparli e convertirli automaticamente, a patto che siano **tipizzati**.

La sintassi nell'URL è `/nome-parametro/valore/`.

php

 Show full code block 

`namespace MyModule\App\Controllers;  use SismaFramework\Core\BaseClasses\BaseController; use SismaFramework\Core\HttpClasses\Response; use SismaFramework\Core\HelperClasses\Render; use MyModule\App\Entities\Post; // Supponiamo esista un'entità Post  class PostController extends BaseController {     /**     * Questa action risponde a URL come:     * /post/show/id/42     * /post/show/post/42 (l'ORM usa il tipo per risolvere l'entità)     */     public function show(int $id, Post $post): Response     {         // $id conterrà il valore 42         // $post sarà l'oggetto Post con id=42, caricato automaticamente dall'ORM          $this->vars['post'] = $post;         return Render::generateView('post/show', $this->vars);     } }`

I tipi di parametro supportati per il binding automatico sono:

* Tipi nativi: `int`, `float`, `string`, `bool`, `array`.
* Oggetti `SismaDatetime` (formato `Y-m-d H:i:s`).
* `BackedEnum`: il valore viene usato per trovare il case corrispondente.
* **Entità**: il framework carica automaticamente l'entità dal database usando l'ID fornito.

### Autowiring di Servizi

Alcuni oggetti di servizio possono essere "iniettati" automaticamente come parametri delle action, semplicemente dichiarandoli con il loro tipo.

* `Request`: Contiene tutte le informazioni della richiesta HTTP (`$_GET`, `$_POST`, `$_FILES`, ecc.).
* `Authentication`: Fornisce metodi per la gestione dell'autenticazione utente.

php

 Show full code block 

```php
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Security\Authentication;
public function updateUser(Request $request, Authentication $auth): Response
{
    if (!$auth->isLogged()) {
        // ... gestisci utente non autenticato
    } 

    // Ottieni i dati dal form inviato via POST
    $username = $request->request->get('username');

    // ... logica di aggiornamento ...
}
```

## Generare Risposte

Ogni action deve restituire un oggetto `Response`. Il framework fornisce delle classi helper per creare facilmente i tipi di risposta più comuni.

### Classe `Render`: Viste e Dati

La classe `Render` si usa per generare risposte HTML (viste) o dati (es. JSON per API).

* `generateView(string $viewPath, array $vars)`: Carica un file di vista PHP, gli passa le variabili e restituisce una risposta HTML. Include automaticamente i file di localizzazione.
* `generateData(array $vars)`: Converte un array in una risposta JSON. Non carica i file di localizzazione, rendendolo ideale per le API.
* 

### Classe `Router`: Redirect

La classe `Router` si usa per reindirizzare l'utente a un'altra pagina.

```php
use SismaFramework\Core\HelperClasses\Router;

public function create(): Response
{
    // ... logica per creare una nuova risorsa ...

    // Reindirizza alla pagina di successo
    return Router::redirect('post/success');
}
```

### Classe `Templater`: Stringhe da Template

Simile a `Render`, ma invece di generare una risposta completa, restituisce una stringa processata da un template. È perfetta per creare il corpo di un'email o generare file di testo.

```php
use SismaFramework\Core\HelperClasses\Templater;

// ...
$emailBody = Templater::generateTemplate('emails/welcome', ['username' => 'Mario']);
// ... invia l'email con $emailBody ...
```

## Configurazione Avanzata (Opzionale)

Normalmente non è necessario modificare queste impostazioni. Se hai bisogno di personalizzare la struttura delle cartelle, puoi modificare le seguenti costanti nel file `Config/config.php`:

* `DEFAULT_CONTROLLER`, `DEFAULT_ACTION`: Definiscono il controller e l'action da eseguire se l'URL è vuoto.
* `CONTROLLERS`: Cambia il nome della cartella dei controller (default: `Controllers`).
* `CONTROLLERS_PATH`, `CONTROLLERS_NAMESPACE`: Permettono di ridefinire completamente il percorso e il namespace dei controller.

* * *

[Indice](index.md) | Precedente: [Struttura delle Cartelle](project-folder-structure.md) | Successivo: [Viste e Template](views.md)

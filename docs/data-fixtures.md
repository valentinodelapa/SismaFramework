# Data Fixtures

Le Data Fixtures sono classi utilizzate per caricare un set di dati "falsi" o di default nel database. Sono uno strumento essenziale durante lo sviluppo per testare l'applicazione con dati consistenti, popolare un'installazione di default o preparare un ambiente di demo.

## A Cosa Servono?

* **Popolare un database vuoto:** Carica dati essenziali come un utente amministratore, categorie di default, ecc.
* **Creare dati per i test:** Assicura che i test automatici o manuali vengano eseguiti su un set di dati noto e prevedibile.
* **Fornire dati per lo sviluppo:** Permette di lavorare sull'interfaccia utente senza dover inserire manualmente i dati ogni volta.

## Creare una Fixture

Una fixture è una classe PHP che risiede nella cartella `Fixtures` del tuo modulo. Deve estendere la classe base `SismaFramework\Core\BaseClasses\BaseFixture` e implementare il metodo `load()`.

Il `BaseFixture` astrae la logica di salvataggio e l'ordine di esecuzione attraverso due metodi principali che devi implementare:

* `setDependencies()`: Qui dichiari se la tua fixture dipende da altre. Il framework si assicurerà di eseguire prima le dipendenze.
* `setEntity()`: Qui crei l'istanza della tua entità, imposti le sue proprietà e la registri per il salvataggio.

### Esempio: Creare Utenti e Post

Vediamo come creare una fixture per un `Post` che dipende da una fixture per un `User`.

#### 1. Fixture dell'Utente (la dipendenza)

**`MyBlog/Application/Fixtures/UserFixture.php`**

```php
namespace MyBlog\Application\Fixtures;

use SismaFramework\Core\BaseClasses\BaseFixture;
use MyBlog\Application\Entities\User;

class UserFixture extends BaseFixture
{
    protected function setDependencies(): void
    {
        // Questa fixture non ha dipendenze
    }

    public function setEntity(): void
    {
        $user = new User();
        $user->setUsername('Mario Rossi');
        $user->setEmail('mario.rossi@example.com');

        // Aggiunge l'entità al gestore delle fixture.
        // Verrà salvata automaticamente.
        $this->addEntity($user);
    }
}
```

#### 2. Fixture del Post (che dipende dall'Utente)

**`MyBlog/Application/Fixtures/PostFixture.php`**

```php
namespace MyBlog\Application\Fixtures;

use SismaFramework\Core\BaseClasses\BaseFixture;
use MyBlog\Application\Entities\Post;

class PostFixture extends BaseFixture
{

    protected function setDependencies(): void
    {
        //Dichiara che questa fixture ha bisogno che UserFixture sia già stata eseguita
        $this->addDependency(UserFixture::class);
    }
  
    public function setEntity(): void
    {
        // Recupera l'entità User creata dalla sua fixture di dipendenza
        $author = $this->getEntityByFixtureName(UserFixture::class);
        $post = new Post();
        $post->setTitle('Il mio primo articolo');  
        $post->setContent('Contenuto dell\'articolo...');
        $post->setAuthor($author); // Associa l'autore recuperato
        $this->addEntity($post);
    }
} 
```

## Eseguire le Fixtures

Il modo più semplice per eseguire le fixtures durante lo sviluppo è tramite un URL dedicato.

1. Assicurati che `DEVELOPMENT_ENVIRONMENT` sia impostato su `true` nel tuo file `Config/config.php`.
2. Visita l'URL `/fixtures` nel tuo browser.

Il framework troverà ed eseguirà automaticamente tutte le classi `Fixture` presenti nei moduli registrati.

* * *

Indice | Precedente: [Barra di Debug](debug-bar.md) | Successivo: [Gestione Errori e Logging](error-handling-and-logging.md)

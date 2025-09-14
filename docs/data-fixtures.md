# Data Fixtures

Le Data Fixtures sono classi utilizzate per caricare un set di dati "falsi" o di default nel database. Sono uno strumento essenziale durante lo sviluppo per testare l'applicazione con dati consistenti, popolare un'installazione di default o preparare un ambiente di demo.

## A Cosa Servono?

*   **Popolare un database vuoto:** Carica dati essenziali come un utente amministratore, categorie di default, ecc.
*   **Creare dati per i test:** Assicura che i test automatici o manuali vengano eseguiti su un set di dati noto e prevedibile.
*   **Fornire dati per lo sviluppo:** Permette di lavorare sull'interfaccia utente senza dover inserire manualmente i dati ogni volta.

## Creare una Fixture

Una fixture è una classe PHP che risiede nella cartella `Fixtures` del tuo modulo. Deve estendere la classe base `SismaFramework\Core\BaseClasses\BaseFixture` e implementare il metodo `load()`.

Il `BaseFixture` fornisce accesso diretto all'oggetto `$this->dataMapper`, che puoi usare per salvare le entità.

### Esempio: Creare Utenti e Post

Vediamo come creare una fixture che popola il database con alcuni utenti e i loro post.

**`MyBlog/Application/Fixtures/BlogFixtures.php`**
```php
namespace MyBlog\Application\Fixtures;

use SismaFramework\Core\BaseClasses\BaseFixture;
use MyBlog\Application\Entities\User;
use MyBlog\Application\Entities\Post;

class BlogFixtures extends BaseFixture
{
    public function load(): void
    {
        // Crea un utente
        $user = new User();
        $user->setUsername('Mario Rossi');
        $user->setEmail('mario.rossi@example.com');
        // ... imposta altre proprietà

        $this->dataMapper->save($user);

        // Crea alcuni post per l'utente
        for ($i = 1; $i <= 5; $i++) {
            $post = new Post();
            $post->setTitle('Articolo di test #' . $i);
            $post->setContent('Questo è il contenuto dell\'articolo di test numero ' . $i);
            $post->setAuthor($user); // Associa il post all'utente creato

            $this->dataMapper->save($post);
        }
    }
}
```

## Eseguire le Fixtures

Il metodo standard per eseguire le fixtures è tramite un'interfaccia a riga di comando (CLI). Sebbene il comando specifico non sia ancora documentato, è importante sapere che ogni classe fixture può essere istanziata ed eseguita programmaticamente se necessario, poiché il metodo `load()` contiene tutta la logica necessaria.

* * *

Indice | Precedente: [Barra di Debug](debug-bar.md) | Successivo: [Gestione Errori e Logging](error-handling-and-logging.md)
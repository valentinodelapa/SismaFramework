# # Gestione dei Form

Il componente Form di SismaFramework è uno strumento potente che semplifica la gestione dei form HTML. Si occupa di tre compiti principali:

1. **Mappatura dei Dati:** Trasferisce i dati inviati da un form (`$_POST`) a un'entità.
2. **Validazione:** Controlla che i dati inviati rispettino le regole definite (es. un campo è obbligatorio, è un'email valida, ecc.).
3. **Gestione degli Errori e Ripopolamento:** In caso di dati non validi, facilita la visualizzazione dei messaggi di errore e ripopola automaticamente il form con i dati già inseriti dall'utente.

## Il Flusso di Lavoro Completo

Gestire un form in SismaFramework segue tre passi principali. Vediamoli con un esempio pratico: la creazione e modifica di un articolo di un blog (`Post`).

### Passo 1: Creare la Classe Form

Per prima cosa, crea una classe Form nella cartella `Forms` del tuo modulo. Questa classe definisce a quale entità è associato il form e quali sono le regole di validazione per ogni campo.

**`MyBlog/Application/Forms/PostForm.php`**

```php
namespace MyBlog\Application\Forms;

use SismaFramework\Core\BaseClasses\BaseForm;
use SismaFramework\Core\Enumerations\FilterType;
use MyBlog\Application\Entities\Post;

class PostForm extends BaseForm
{
    /**
     * Specifica la classe dell'entità gestita da questo form.
     */
    protected static function getEntityName(): string
    {
        return Post::class;
    }

    /**
     * Definisce le regole di validazione per ogni campo del form.
     */
    protected function setFilterFieldsMode(): void
    {
        $this->addFilterFieldMode('title', FilterType::string, ['minLength' => 3, 'maxLength' => 255]);
        $this->addFilterFieldMode('content', FilterType::string, ['minLength' => 10]);
        $this->addFilterFieldMode('publicationDate', FilterType::datetime, [], true); // Il campo può essere nullo
        $this->addFilterFieldMode('isPublished', FilterType::boolean);
    }

    /**
     * Usato per validazioni personalizzate più complesse.
     * In questo esempio non è necessario.
     */
    protected function customFilter(): void
    {
        // Esempio: se il titolo contiene una parola specifica, aggiungi un errore.
        // if (str_contains($this->entity->getTitle(), 'spam')) {
        //     $this->formFilterErrorManager->addErrorMessage('title', 'Il titolo non può contenere la parola "spam".');
        // }
    }

    /**
     * Usato per gestire form annidati (entità correlate).
     * In questo esempio non è necessario.
     */
    protected function setEntityFromForm(): void
    {
    }

    /**
     * Usato per iniettare dati nel form da fonti esterne (es. sessione).
     * In questo esempio non è necessario.
     */
    protected function injectRequest(): void
    {
    }
} 
```

### Passo 2: Gestire il Form nel Controller

Nel controller, devi creare un'action per gestire la richiesta del form. Il flusso è sempre lo stesso:

1. Crea un'istanza del form, passando opzionalmente un'entità esistente (per la modifica).
2. Passa la `Request` al metodo `handleRequest()`.
3. Controlla se il form è stato inviato (`isSubmitted()`) e se è valido (`isValid()`).
4. Se è valido, ottieni l'entità popolata e salvata con `resolveEntity()` e reindirizza l'utente.
5. Se non è valido (o non è stato inviato), renderizza la vista del form, passando i dati per il ripopolamento e gli errori.

**`MyBlog/Application/Controllers/PostController.php`**

```php
namespace MyBlog\Application\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use MyBlog\Application\Entities\Post;
use MyBlog\Application\Forms\PostForm;

class PostController extends BaseController
{
    // L'action per creare un nuovo post
    public function create(Request $request): Response
    {
        $form = new PostForm(); // Creiamo un form per una nuova entità
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Grazie a injectRequest(), non dobbiamo più gestire manualmente l'ID dell'autore.
            $nuovoPost = $form->resolveEntity();
            $this->dataMapper->save($nuovoPost);
            return Router::redirect('post/show/id/' . $nuovoPost->getId());
        }

        // Se il form non è valido o è la prima visita, mostriamo il form
        $this->vars['pageTitle'] = 'Modifica Articolo';
        // Passiamo l'entità per il ripopolamento e gli errori alla vista
        $this->vars['formEntity'] = $form->isSubmitted() ? $form->getEntityDataToStandardEntity() : $post;
        $this->vars['errors'] = $form->returnFilterErrors();

        return Render::generateView('post/form', $this->vars);
    }
}
```

### Passo 3: Visualizzare il Form nella Vista

Infine, crea il file della vista. I nomi dei campi (`name="..."`) nel form HTML devono corrispondere ai nomi delle proprietà dell'entità. La gestione degli errori è particolarmente elegante.

L'oggetto `$errors` (di tipo `FormFilterError`) restituito dal form utilizza i metodi magici di PHP. Questo significa che:
*   Per verificare se un campo ha un errore, puoi semplicemente controllare il valore di una proprietà con il suffisso `Error` (es. `$errors->titleError`). Se l'errore esiste, restituirà il messaggio (una stringa, che valuta a `true`); altrimenti, restituirà `false`.
*   Se hai definito un **messaggio di errore personalizzato** nella tua classe Form, puoi accedervi con il suffisso `CustomMessage` (es. `$errors->titleCustomMessage`). Questo conterrà la stringa del messaggio e ha la priorità su quello standard.
*   Non è necessario usare `isset()` o metodi come `hasError()`, rendendo il codice della vista molto più pulito e leggibile.
*   Il messaggio di errore standard deve essere definito nel file di lingua (es. `it_IT.php`) e richiamato nella vista.
**`MyBlog/Application/Views/post/form.php`**

```php
<?php require_once __DIR__ . '/../layout/header.php'; ?>
    
<h2><?= htmlspecialchars($pageTitle) ?></h2>
    
<form method="post">
    <div class="form-group">
        <label for="title">Titolo</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($formEntity->getTitle() ?? '') ?>">
        
        <?php // Controlla prima un messaggio personalizzato. Se non c'è, controlla se c'è un errore standard e mostra il messaggio dal file di lingua. ?>
        <?php if ($errors->titleCustomMessage) : ?>
            <div class="error"><?= $errors->titleCustomMessage ?></div>
        <?php elseif ($errors->titleError) : ?>
            <div class="error"><?= $defaulErrorMessage // Variabile dal file di lingua (es. 'it_IT.php') ?></div>
        <?php endif; ?>
    </div>
    
    <div class="form-group">
        <label for="content">Contenuto</label>
        <textarea id="content" name="content"><?= htmlspecialchars($formEntity->getContent() ?? '') ?></textarea>
        
        <?php if ($errors->contentCustomMessage) : ?>
            <div class="error"><?= $errors->contentCustomMessage ?></div>
        <?php elseif ($errors->contentError) : ?>
            <div class="error"><?= $defaulErrorMessage // Variabile dal file di lingua ?></div>
        <?php endif; ?>
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="isPublished" value="1" <?= $formEntity->isPublished() ? 'checked' : '' ?>>
            Pubblicato
        </label>
    </div>
    <button type="submit">Salva</button>
</form>
    
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

## Integrazione di Dati con `injectRequest()` e `addRequest()`

Il metodo `injectRequest()` è un hook potente che viene eseguito dopo che il form ha ricevuto l'oggetto `Request`, ma **prima** che inizi il processo di validazione e mappatura dei dati sull'entità.

All'interno di questo metodo, puoi usare il metodo helper `addRequest(string $propertyName, string|array $value)` per **aggiungere o sovrascrivere un singolo valore** nella richiesta del form.

Questo approccio è estremamente utile per:

*   **Popolare campi non presenti nel form HTML:** Il caso d'uso più comune è impostare l'ID dell'autore di un post con quello dell'utente attualmente loggato, senza doverlo inserire in un campo `<input type="hidden">`.
*   **Pre-elaborare o modificare dati:** Puoi modificare i dati grezzi prima che vengano validati (es. convertire un formato data, pulire una stringa).
*   **Impostare valori di default dinamici:** Fornire valori predefiniti che dipendono da logiche esterne al form.

### Esempio Pratico

Immaginiamo di voler associare automaticamente un post all'utente loggato, il cui ID è salvato in sessione.

```php
// In MyBlog/Application/Forms/PostForm.php

protected function injectRequest(): void
{
    // La sessione è accessibile tramite $this->session nel form.
    // Se l'utente è loggato, aggiungiamo il suo ID alla richiesta
    // sotto la chiave 'authorId'.
    if ($this->session->has('user_id')) {        
        $this->addRequest('authorId', $this->session->get('user_id'));
    }
}
```

## L'Oggetto `Request`

La classe `Request` è un wrapper orientato agli oggetti per le variabili superglobali di PHP. Viene iniettata automaticamente nelle action dei controller quando la si dichiara come argomento.

Le sue proprietà pubbliche mappano le superglobali:

* `$request->query`: Corrisponde a `$_GET`.
* `$request->request`: Corrisponde a `$_POST`.
* `$request->files`: Corrisponde a `$_FILES`.
* `$request->cookies`: Corrisponde a `$_COOKIE`.
* `$request->server`: Corrisponde a `$_SERVER`.

* * *

[Indice](index.md) | Precedente: [Viste e Template](views.md) | Successivo: [Internazionalizzazione (i18n)](internationalization.md)

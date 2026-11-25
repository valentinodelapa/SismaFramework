# Gestione dei Form

Il componente Form di SismaFramework è uno strumento potente che semplifica la gestione dei form HTML. Si occupa di tre compiti principali:

1. **Mappatura dei Dati:** Trasferisce i dati inviati da un form (`$_POST`, o il corpo JSON della richiesta) a un'[entità](orm.md#le-entità-in-dettaglio).
2. **Validazione:** Controlla che i dati inviati rispettino le regole definite usando i [FilterType](enumerations.md#filtertype).
3. **Gestione degli Errori:** Utilizza [`FormFilterError`](custom-types.md#formfiltererror) per gestire errori e ripopolamento automatico.

## Il Flusso di Lavoro Completo

Gestire un form in SismaFramework segue tre passi principali. Vediamoli con un esempio pratico: la creazione di un articolo di un blog (`Post`).

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
        $this->addFilterFieldMode('title', FilterType::isLimitString, [3, 255])
            ->addFilterFieldMode('content', FilterType::isMinLimitString, [10])
            ->addFilterFieldMode('publicationDate', FilterType::isDatetime, [], true) // Il campo può essere nullo
            ->addFilterFieldMode('isPublished', FilterType::isBoolean);
    }

    /**
     * Usato per validazioni personalizzate più complesse, dopo quelle dei filtri.
     * Deve ritornare true se la validazione ha successo, false altrimenti.
     */
    protected function customFilter(): bool
    {
        if (str_contains($this->entityData->title ?? '', 'spam')) {
            $this->formFilterError->titleError = true;
            $this->formFilterError->titleCustomMessage = 'Il titolo non può contenere la parola "spam".';
            return false;
        }
        return true;
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

`addFilterFieldMode()` riceve il nome della proprietà, il filtro, i parametri del filtro e, come ultimo argomento, se il campo può essere vuoto. I parametri vengono passati al metodo corrispondente di `Filter` dopo il valore da validare, nell'ordine in cui compaiono nell'array; un array con chiavi stringa li passa per nome (`['minLimit' => 3, 'maxLimit' => 255]`). L'elenco dei filtri e dei loro parametri è in [FilterType](enumerations.md#filtertype).

Il filtro riceve il valore già convertito nel tipo della proprietà: un intero per una proprietà `int`, un `SismaDateTime` per una proprietà `SismaDateTime`, un'entità per una chiave esterna. Un campo assente dalla richiesta o inviato vuoto vale `null` (o il valore di default della proprietà, se ne ha uno; `false` per una proprietà `bool` non nullable, come una checkbox non selezionata), e senza il quarto argomento a `true` non supera la validazione.

In `customFilter()` i dati del form validati sono disponibili in `$this->entityData`, e gli errori si segnalano su `$this->formFilterError`.

### Passo 2: Gestire il Form nel Controller

Nel controller, devi creare un'action per gestire la richiesta del form. Il flusso è sempre lo stesso:

1. Crea un'istanza del form, passando opzionalmente un'entità esistente (per la modifica).
2. Passa la `Request` al metodo `handleRequest()`.
3. Controlla se il form è stato inviato (`isSubmitted()`) e se è valido (`isValid()`).
4. Se è valido, ottieni l'entità popolata con `resolveEntity()`, salvala e reindirizza l'utente.
5. Se non è valido (o non è stato inviato), renderizza la vista del form, passando i dati per il ripopolamento e gli errori.

**`MyBlog/Application/Controllers/PostController.php`**

```php
namespace MyBlog\Application\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use MyBlog\Application\Forms\PostForm;

class PostController extends BaseController
{
    // L'action per creare un nuovo post
    public function create(Request $request): Response
    {
        $form = new PostForm(); // Creiamo un form per una nuova entità
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nuovoPost = $form->resolveEntity();
            $this->dataMapper->save($nuovoPost);
            return $this->router->redirect('post/show/post/' . $nuovoPost->id);
        }

        // Se il form non è valido o è la prima visita, mostriamo il form
        $this->vars['pageTitle'] = 'Nuovo Articolo';
        // Passiamo i dati inviati per il ripopolamento e gli errori alla vista
        $this->vars['formEntity'] = $form->getEntityDataToStandardEntity();
        $this->vars['errors'] = $form->getFilterErrors();

        return Render::generateView('post/form', $this->vars, $form->getResponseType());
    }
}
```

`isSubmitted()` verifica la presenza del campo `submitted` nella richiesta: il form HTML deve quindi inviarlo, ad esempio come nome del pulsante. `resolveEntity()` restituisce l'entità con i valori del form, senza salvarla. `getResponseType()` restituisce `400 Bad Request` dopo una validazione fallita e `200 OK` negli altri casi.

### Passo 3: Visualizzare il Form nella Vista

Infine, crea il file della vista. I nomi dei campi (`name="..."`) nel form HTML devono corrispondere ai nomi delle proprietà dell'entità. La gestione degli errori è particolarmente elegante.

L'oggetto `$errors` (di tipo `FormFilterError`) restituito dal form utilizza i metodi magici di PHP. Questo significa che:
*   Per verificare se un campo ha un errore, puoi semplicemente controllare il valore di una proprietà con il suffisso `Error` (es. `$errors->titleError`), che vale `true` se il campo non ha superato la validazione e `false` altrimenti.
*   Se hai impostato un **messaggio di errore personalizzato** nella tua classe Form, puoi accedervi con il suffisso `CustomMessage` (es. `$errors->titleCustomMessage`). Contiene la stringa del messaggio, oppure `false` se non è stato impostato.
*   Non è necessario usare `isset()` o metodi come `hasError()`, rendendo il codice della vista molto più pulito e leggibile.
*   Il messaggio di errore standard si definisce come label della vista nel file di localizzazione (vedi [Internazionalizzazione](internationalization.md)), che la rende disponibile come variabile.

Anche `$formEntity` (di tipo `StandardEntity`) restituisce `null` per le proprietà assenti, per cui alla prima visita i campi risultano vuoti.

**`MyBlog/Application/Views/post/form.php`**

```php
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<h2><?= htmlspecialchars($pageTitle) ?></h2>

<form method="post">
    <div class="form-group">
        <label for="title">Titolo</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($formEntity->title ?? '') ?>">

        <?php // Mostra il messaggio personalizzato, se presente, altrimenti quello standard del file di localizzazione. ?>
        <?php if ($errors->titleCustomMessage) : ?>
            <div class="error"><?= htmlspecialchars($errors->titleCustomMessage) ?></div>
        <?php elseif ($errors->titleError) : ?>
            <div class="error"><?= $defaultErrorMessage // Label dal file di localizzazione ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="content">Contenuto</label>
        <textarea id="content" name="content"><?= htmlspecialchars($formEntity->content ?? '') ?></textarea>

        <?php if ($errors->contentError) : ?>
            <div class="error"><?= $defaultErrorMessage ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="isPublished" value="1" <?= $formEntity->isPublished ? 'checked' : '' ?>>
            Pubblicato
        </label>
    </div>
    <button type="submit" name="submitted" value="1">Salva</button>
</form>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

## Integrazione di Dati con `injectRequest()` e `addRequest()`

Il metodo `injectRequest()` è un hook potente che viene eseguito dopo che il form ha ricevuto l'oggetto `Request`, ma **prima** che inizi il processo di validazione e mappatura dei dati sull'entità.

All'interno di questo metodo, puoi usare il metodo helper `addRequest(string $propertyName, string|int|float|bool|array|null $value, bool $override = true)` per **aggiungere o sovrascrivere un singolo valore** nella richiesta del form.

Il parametro `$override` (introdotto nella 11.8.0) controlla cosa succede se la proprietà è già presente nella richiesta:

*   `override: true` (default) → il valore passato sovrascrive sempre quello esistente.
*   `override: false` → il valore viene impostato solo se la proprietà **non** è già presente nella richiesta; un valore già inviato dal form (es. tramite un campo `<input>`) non viene toccato.

Questo approccio è estremamente utile per:

*   **Popolare campi non presenti nel form HTML:** Il caso d'uso più comune è impostare l'autore di un post con l'utente attualmente loggato, senza doverlo inserire in un campo `<input type="hidden">`.
*   **Pre-elaborare o modificare dati:** Puoi modificare i dati grezzi prima che vengano validati (es. convertire un formato data, pulire una stringa).
*   **Impostare valori di default dinamici senza sovrascrivere l'input dell'utente:** usando `override: false` puoi fornire un default solo quando il form non ha già fornito un valore.

### Esempio Pratico

Immaginiamo di voler associare automaticamente un post all'utente loggato, il cui ID è salvato in sessione. Come per i campi del form, la chiave è il nome della proprietà: per la chiave esterna `author` si passa l'ID, e il form carica l'entità corrispondente.

```php
// In MyBlog/Application/Forms/PostForm.php

use SismaFramework\Core\HelperClasses\Session;

protected function injectRequest(): void
{
    if (Session::hasItem('userId')) {
        $this->addRequest('author', Session::getItem('userId'));
    }
}
```

### Esempio con `$override: false`

Se vuoi impostare un valore di default solo quando il form non lo ha già fornito (ad esempio per non sovrascrivere un campo opzionale inviato dall'utente):

```php
protected function injectRequest(): void
{
    // Imposta 'status' a 'draft' solo se il form non ha già inviato un valore
    $this->addRequest('status', 'draft', override: false);
}
```

## L'Oggetto `Request`

La classe `Request` è un wrapper orientato agli oggetti per le variabili superglobali di PHP. Viene iniettata automaticamente nelle action dei controller quando la si dichiara come argomento.

Le sue proprietà pubbliche mappano le superglobali:

* `$request->query`: Corrisponde a `$_GET`.
* `$request->request`: Corrisponde a `$_POST`.
* `$request->files`: Corrisponde a `$_FILES`.
* `$request->cookie`: Corrisponde a `$_COOKIE`.
* `$request->server`: Corrisponde a `$_SERVER`.
* `$request->headers`: Contiene gli header HTTP della richiesta.
* `$request->data`: Contiene il corpo JSON decodificato delle richieste `POST`, `PUT`, `PATCH` e `DELETE` con `Content-Type: application/json`.
* `$request->input`: Contiene i dati del form usati da `BaseForm`: il corpo JSON, se presente, altrimenti `$_POST`.

---

[Indice](index.md) | Precedente: [Viste e Template](views.md) | Successivo: [Internazionalizzazione (i18n)](internationalization.md)

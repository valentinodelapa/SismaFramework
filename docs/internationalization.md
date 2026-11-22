# Internazionalizzazione (i18n)

SismaFramework è progettato per supportare applicazioni multilingua fin da subito. Il sistema di internazionalizzazione (spesso abbreviato in "i18n") ti permette di definire stringhe di testo in più lingue e di visualizzare quelle corrette in base alla configurazione.

## Come Funziona

Il meccanismo è semplice e automatico. Quando si utilizza `Render::generateView()` per renderizzare una vista, il framework esegue i seguenti passaggi:

1.  Determina la lingua da usare (vedi [Scelta della Lingua](#scelta-della-lingua)), che in assenza di altre indicazioni è quella della costante `LANGUAGE` nel file `Config/config.php` (es. `it_IT`).
2.  Cerca il file di localizzazione corrispondente (es. `it_IT.json`) nella cartella `Application/Locales/` del modulo.
3.  Estrae dal file le label relative alla vista da renderizzare e le rende disponibili come variabili all'interno della vista.

Lo stesso meccanismo è applicato da `Templater::generateTemplate()` ai template e dai trait delle enumerazioni alle label dei case (vedi [Localizzazione delle Enumerazioni](#localizzazione-delle-enumerazioni)).

## Configurazione

L'unica configurazione richiesta è impostare la lingua desiderata nel file `Config/config.php`.

```php
// in Config/config.php
const LANGUAGE = 'it_IT'; // o 'en_US', 'fr_FR', ecc.
```

## Creare un File di Lingua

1. Crea la cartella `Locales` nel tuo modulo, se non esiste.
2. Al suo interno, crea un file JSON con il nome della locale (es. `it_IT.json`).

Il file contiene una sezione `pages` per le viste e una sezione `templates` per i template. All'interno di ciascuna sezione, le chiavi rispecchiano la struttura delle cartelle `Views/` e `Templates/`, con un livello per ogni cartella e un ultimo livello per il file, a qualunque profondità.

A ogni livello può essere presente una chiave `common`, le cui label sono condivise da tutte le viste (o i template) contenute in quel livello e nei livelli sottostanti. Quando la stessa chiave è definita a più livelli, prevale quella del livello più specifico.

**`MyBlog/Application/Locales/it_IT.json`**

```json
{
    "pages": {
        "common": {
            "siteName": "Il mio Blog"
        },
        "post": {
            "common": {
                "formSaveButton": "Salva Modifiche"
            },
            "form": {
                "editPost": "Modifica Articolo"
            }
        }
    },
    "templates": {
        "common": {
            "signature": "Il team del Blog"
        },
        "emails": {
            "welcome": {
                "subject": "Benvenuto nel nostro Blog!"
            },
            "account": {
                "activation": {
                    "subject": "Attiva il tuo account"
                }
            }
        }
    }
}
```

## Utilizzo nelle Viste

Le label della vista vengono estratte automaticamente, quindi puoi accedervi come se fossero normali variabili PHP. Per la vista `post/form` sono disponibili `siteName`, `formSaveButton` ed `editPost`.

**`MyBlog/Application/Views/post/form.php`**

```php
<h2><?= $editPost ?></h2>

<form method="post">
    <!-- ... campi del form ... -->
    <button type="submit"><?= $formSaveButton ?></button>
</form>
```

Cambiando il valore della costante `LANGUAGE` in `config.php` da `it_IT` a `en_US`, la stessa vista mostrerà automaticamente il testo in inglese.

## Utilizzo nei Template

Per il template `emails/account/activation` (file `Templates/emails/account/activation.tpl`) sono disponibili i segnaposto `{{signature}}` e `{{subject}}`.

```php
$corpoEmail = Templater::generateTemplate('emails/account/activation', ['username' => 'Mario Rossi']);
```

Per leggere una singola label di un template senza generarlo, ad esempio l'oggetto di un'email da usare nell'intestazione del messaggio, si usa `Templater::getTemplateLabel()`. La label viene cercata con le stesse regole di `generateTemplate()`, comprese le label `common` dei livelli superiori. Se la label non esiste viene sollevata una `LocalizatorException`.

```php
$oggettoEmail = Templater::getTemplateLabel('emails/account/activation', 'subject');
```

> **Nota**: dalla versione 13.0.0 la chiave piatta con il percorso completo del template (es. `"emails/welcome": {...}` direttamente dentro `templates`), deprecata nella 12.4.0, non è più supportata e viene ignorata. Usa la forma gerarchica (`"emails": {"welcome": {...}}`); vedi [UPGRADING.md](../UPGRADING.md#da-12x-a-13x).

## Precedenza tra Label e Variabili

Sia nelle viste sia nei template, le variabili passate dal codice prevalgono sulle label con la stessa chiave: le label del file di localizzazione sono valori di default che lo sviluppatore può sempre sovrascrivere.

## Localizzazione delle Enumerazioni

Le label dei case delle enumerazioni si trovano nella sezione `enumerations` del file di localizzazione, sotto il nome breve dell'enumerazione con l'iniziale minuscola e poi sotto il nome del case. Si leggono con i trait [`LocalizableEnumeration` e `SelectableEnumeration`](traits.md).

Il valore di un case può essere una stringa oppure un oggetto con più attributi, anche annidati:

```json
{
    "enumerations": {
        "articleStatus": {
            "draft": "Bozza",
            "published": "Pubblicato"
        },
        "rank": {
            "knight": {
                "label": "Cavaliere",
                "description": "Primo grado dell'ordine",
                "male": {
                    "label": "Cavaliere",
                    "initial": "Cav."
                },
                "female": "Dama"
            }
        }
    }
}
```

### La label

Ogni elemento della localizzazione di un case ha una **label**, cioè la sua rappresentazione testuale, sotto la chiave riservata `label`. Una stringa è la forma abbreviata di un oggetto che contiene solo la label: `"Bozza"` equivale a `{"label": "Bozza"}`.

`getFriendlyLabel()` restituisce la label dell'elemento indicato dal percorso, o del case stesso se il percorso è vuoto:

```php
Rank::knight->getFriendlyLabel();                  // "Cavaliere"
Rank::knight->getFriendlyLabel('male');            // "Cavaliere"
Rank::knight->getFriendlyLabel('female');          // "Dama"
ArticleStatus::draft->getFriendlyLabel();          // "Bozza"
```

Un elemento composto senza la chiave `label` non ha una rappresentazione testuale propria. Ad esempio, un case che contiene solo `{"male": "...", "female": "..."}` non ha una label neutra rispetto al genere: `getFriendlyLabel()` senza percorso solleva una `LocalizatorException`, mentre `getFriendlyLabel('male')` restituisce la label maschile.

### Gli attributi

`getLocalizedAttribute()` restituisce il valore grezzo che si trova nel punto indicato dal percorso: una stringa, un oggetto (come array) oppure `null`, se il file di localizzazione contiene esplicitamente `null`.

```php
Rank::knight->getLocalizedAttribute('description');           // "Primo grado dell'ordine"
Rank::knight->getLocalizedAttribute(['male', 'initial']);     // "Cav."
```

Se una chiave del percorso non esiste, viene sollevata una `LocalizatorException`.

## Scelta della Lingua

La lingua usata per le viste, i template e le enumerazioni viene scelta in quest'ordine:

1. la lingua indicata esplicitamente per la singola chiamata, costruendo un `Localizator` con quella lingua;
2. la lingua impostata per la richiesta con `Localizator::setLanguage()`;
3. la costante `LANGUAGE` del file di configurazione.

### Lingua della Richiesta

In un'applicazione che sceglie la lingua per ogni richiesta, ad esempio in base alla preferenza dell'utente o all'URL, la lingua si imposta una volta sola, tipicamente nel costruttore del controller. Da quel momento viste, template ed enumerazioni la usano senza doverla passare a ogni chiamata.

```php
use SismaFramework\Core\HelperClasses\Localizator;

Localizator::setLanguage($utente->language);

$label = ArticleStatus::draft->getFriendlyLabel();
$scelte = ArticleStatus::getChoiceFromEnumerations();
```

`Localizator::unsetLanguage()` ripristina la lingua di configurazione.

### Lingua della Singola Chiamata

Quando una singola operazione deve usare una lingua diversa da quella della richiesta, ad esempio un'email nella lingua del destinatario, si passa un `Localizator` costruito con quella lingua:

```php
use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Core\HelperClasses\Localizator;

$localizator = new Localizator(Language::english);

$corpoEmail = Templater::generateTemplate('emails/account/activation', $vars, $localizator);
$oggettoEmail = Templater::getTemplateLabel('emails/account/activation', 'subject', $localizator);
$stato = ArticleStatus::draft->getFriendlyLabel([], $localizator);
```

Lo stesso `Localizator` può essere passato anche a `Render::generateView()` e `Render::generateData()`.

> **Deprecato dalla versione 12.17.0, sarà rimosso nella versione 13.0.0**: i metodi dei trait delle enumerazioni accettano ancora un `Language` al posto del `Localizator` (es. `getFriendlyLabel(Language::english)`, `getChoiceFromEnumerations(Language::english)`). Usa la lingua della richiesta oppure passa un `Localizator`: `getFriendlyLabel([], new Localizator(Language::english))`.

* * *

[Indice](index.md) | Precedente: [Gestione dei Form](forms.md) | Successivo: [Gestione degli Asset Statici](static-assets.md)

# Internazionalizzazione (i18n)

SismaFramework è progettato per supportare applicazioni multilingua fin da subito. Il sistema di internazionalizzazione (spesso abbreviato in "i18n") ti permette di definire stringhe di testo in più lingue e di visualizzare quelle corrette in base alla configurazione.

## Come Funziona

Il meccanismo è semplice e automatico. Quando si utilizza `Render::generateView()` per renderizzare una vista, il framework esegue i seguenti passaggi:

1.  Controlla la costante `LANGUAGE` nel file `Config/config.php` (es. `it_IT`).
2.  Cerca il file di localizzazione corrispondente (es. `it_IT.json`) nella cartella `Application/Locales/` del modulo.
3.  Estrae dal file le label relative alla vista da renderizzare e le rende disponibili come variabili all'interno della vista.

Lo stesso meccanismo è applicato da `Templater::generateTemplate()` ai template.

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

> **Deprecato dalla versione 12.4.0, sarà rimosso nella versione 13.0.0**: per i template in sottocartelle è ancora supportata la chiave piatta con il percorso completo (es. `"emails/welcome": {...}` direttamente dentro `templates`). Se presente, ha la precedenza sulla struttura gerarchica ma non riceve le label `common`. Convertila nella forma gerarchica (`"emails": {"welcome": {...}}`).

## Precedenza tra Label e Variabili

Sia nelle viste sia nei template, le variabili passate dal codice prevalgono sulle label con la stessa chiave: le label del file di localizzazione sono valori di default che lo sviluppatore può sempre sovrascrivere.

* * *

[Indice](index.md) | Precedente: [Gestione dei Form](forms.md) | Successivo: [Gestione degli Asset Statici](static-assets.md)

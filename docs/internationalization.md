# Internazionalizzazione (i18n)

SismaFramework è progettato per supportare applicazioni multilingua fin da subito. Il sistema di internazionalizzazione (spesso abbreviato in "i18n") ti permette di definire stringhe di testo in più lingue e di visualizzare quelle corrette in base alla configurazione.

## Come Funziona

Il meccanismo è semplice e automatico. Quando si utilizza `Render::generateView()` per renderizzare una vista, il framework esegue i seguenti passaggi:

1.  Controlla la costante `LANGUAGE` nel file `Config/config.php` (es. `it_IT`).
2.  Cerca un file di localizzazione corrispondente nella cartella `Application/Locales/` di ogni modulo registrato. Il framework supporta sia file `.php` che `.json`.
3.  Se trova un file, lo carica. Questo rende le variabili definite nel file di lingua disponibili direttamente all'interno della vista.

## Configurazione

L'unica configurazione richiesta è impostare la lingua desiderata nel file `Config/config.php`.

```php
// in Config/config.php
const LANGUAGE = 'it_IT'; // o 'en_US', 'fr_FR', ecc.
```

## Creare un File di Lingua

Puoi definire le tue traduzioni usando file PHP o JSON. Le chiavi sono gli identificatori delle stringhe e i valori sono le traduzioni.

1. Crea la cartella `Locales` nel tuo modulo, se non esiste.
2. Al suo interno, crea un file con il nome della locale (es. `it_IT.php` o `it_IT.json`).

**`MyBlog/Application/Locales/it_IT.php`**

```php
<?php

return [
    'welcomeTitle' => 'Benvenuto nel nostro Blog!',
    'editPost' => 'Modifica Articolo',
    'createNewPost' => 'Crea un nuovo articolo',
    'formSaveButton' => 'Salva Modifiche',
];
```

**`MyBlog/Application/Locales/en_US.json`**

```json
{
    "welcomeTitle": "Welcome to our Blog!",
    "editPost": "Edit Post",
    "createNewPost": "Create a new post",
    "formSaveButton": "Save Changes"
}
```

## Utilizzo nelle Viste

Poiché il file di lingua viene incluso automaticamente, puoi accedere alle traduzioni come se fossero normali variabili PHP.

**`MyBlog/Application/Views/post/form.php`**

```php
<h2><?= $edit_post ?></h2>

<form method="post">
    <!-- ... campi del form ... -->
    <button type="submit"><?= $formSaveButton ?></button>
</form>
```

Cambiando il valore della costante `LANGUAGE` in `config.php` da `it_IT` a `en_US`, la stessa vista mostrerà automaticamente il testo in inglese.

* * *

[Indice](index.md) | Precedente: [Gestione dei Form](forms.md) | Successivo: [Gestione degli Asset Statici](static-assets.md)

# Gestione degli Asset Statici

Per "Asset Statici" si intendono tutti i file che non necessitano di essere processati da PHP, come fogli di stile CSS, file JavaScript, immagini, font, ecc.

SismaFramework include un meccanismo intelligente per gestire e servire automaticamente gli asset statici direttamente dai tuoi moduli, senza bisogno di configurazioni complesse come i link simbolici.

## Come Funziona

Quando il browser richiede un file (es. `/css/style.css`), il `Dispatcher` del framework intercetta la richiesta e, invece di cercare un controller, esegue i seguenti passaggi:

1.  **Riconosce la richiesta** come una richiesta per un asset statico basandosi sull'estensione del file (le estensioni gestite da `Resource` e quelle dichiarate in `CUSTOM_RENDERABLE_RESOURCE_TYPES` e `CUSTOM_DOWNLOADABLE_RESOURCE_TYPES`).
2.  **Scansiona i Moduli:** Itinera attraverso i moduli registrati nel file `Config/config.php` (nell'ordine in cui sono elencati), e per ogni modulo controlla se il percorso richiesto esiste all'interno della sua cartella `Application/Assets/`.
3.  **Serve il File:** Cerca il file, nell'ordine, a partire dalla root del progetto, tra gli asset strutturali del framework e infine nella cartella `Assets` del modulo trovato, e lo serve con il MIME type corretto. I file PHP non vengono mai serviti, e le cartelle che contengono un file `.lock` (come `Logs/` e `Cache/`) sono escluse.

### Esempio Pratico

Supponiamo di avere un modulo `MyBlog` con i suoi asset.

```
MyBlog/
└── Application/
    └── Assets/
        └── css/
            └── style.css
```

Per includere questo foglio di stile in una vista, è sufficiente utilizzare un percorso relativo alla root del sito, che rispecchia la struttura all'interno della cartella `Assets`:

```html
<link rel="stylesheet" href="/css/style.css">
```

Il `Dispatcher` troverà automaticamente il file `MyBlog/Application/Assets/css/style.css` e lo invierà al browser.

> **Nota sull'ordine:** Poiché il framework serve il primo file che trova, se due moduli hanno un asset allo stesso percorso (es. `/css/style.css`), verrà servito quello del modulo che appare per primo nell'array `MODULE_FOLDERS` in `config.php`.

* * *

[Indice](index.md) | Precedente: [Internazionalizzazione (i18n)](internationalization.md) | Successivo: [Introduzione all'ORM (Data Mapper)](orm.md)
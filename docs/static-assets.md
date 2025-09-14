# Gestione degli Asset Statici

Per "Asset Statici" si intendono tutti i file che non necessitano di essere processati da PHP, come fogli di stile CSS, file JavaScript, immagini, font, ecc.

SismaFramework include un meccanismo intelligente per gestire e servire automaticamente gli asset statici direttamente dai tuoi moduli, senza bisogno di configurazioni complesse come i link simbolici.

## Come Funziona

Quando il browser richiede un file (es. `/assets/css/style.css`), il `Dispatcher` del framework intercetta la richiesta e, invece di cercare un controller, esegue i seguenti passaggi:

1.  **Riconosce la richiesta** come una richiesta per un asset statico basandosi sull'estensione del file.
2.  **Scansiona i Moduli:** Itinera attraverso i moduli registrati nel file `Config/config.php` (nell'ordine in cui sono elencati).
3.  **Trova il File:** Per ogni modulo, controlla se il percorso richiesto esiste all'interno della sua cartella `Application/Assets/`.
4.  **Serve il File:** Appena trova una corrispondenza, serve il file direttamente al browser con il MIME type corretto.

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
<link rel="stylesheet" href="/assets/css/style.css">
```

Il `Dispatcher` troverà automaticamente il file `MyBlog/Application/Assets/css/style.css` e lo invierà al browser.

> **Nota sull'ordine:** Poiché il framework serve il primo file che trova, se due moduli hanno un asset allo stesso percorso (es. `/assets/css/style.css`), verrà servito quello del modulo che appare per primo nell'array `MODULE_FOLDERS` in `config.php`.

* * *

Indice | Precedente: Internazionalizzazione (i18n) | Successivo: Database e ORM
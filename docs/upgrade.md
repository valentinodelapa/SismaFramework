# Upgrade dei Moduli

Quando il framework rilascia una nuova versione major, i moduli esistenti possono richiedere modifiche al codice per adeguarsi alle breaking changes introdotte. SismaFramework fornisce un comando di upgrade automatico che analizza i file di un modulo, applica le trasformazioni necessarie e genera un report dettagliato delle modifiche effettuate.

## Come Funziona

Il sistema di upgrade si basa su una pipeline di **strategy** e **transformer**:

1. **Rilevamento della versione**: la versione corrente del modulo viene letta automaticamente dal file `module.json` presente nella root del modulo.
2. **Selezione della strategia**: in base alla versione di partenza e a quella di destinazione, viene selezionata la strategia di upgrade appropriata (ad esempio, da 10.x a 11.0.0).
3. **Scansione dei file**: vengono individuati tutti i file PHP del modulo, suddivisi per categoria (controller, model, form, entity, file critici).
4. **Applicazione dei transformer**: ogni file viene processato da una serie di transformer, ciascuno responsabile di una specifica trasformazione del codice.
5. **Backup e rollback**: prima dell'applicazione delle modifiche viene creato un backup automatico in formato ZIP. In caso di errore, il sistema esegue il rollback ripristinando lo stato precedente.
6. **Aggiornamento della versione**: al termine dell'upgrade, il campo `framework_version` nel file `module.json` viene aggiornato alla versione di destinazione.

## Prerequisiti

Per il corretto funzionamento del comando di upgrade, è necessario che:

1. Il modulo da aggiornare contenga un file `module.json` nella propria root con un campo `framework_version` (o `version`) che indichi la versione corrente del framework a cui è allineato.
2. Il modulo rispetti la struttura standard delle cartelle del framework (cartella `Application/` con le sottocartelle `Controllers/`, `Models/`, `Forms/`, `Entities/`).
3. L'estensione PHP `zip` sia disponibile per la creazione automatica del backup.

### Esempio di module.json

```json
{
    "name": "Blog",
    "framework_version": "10.1.7"
}
```

## Sintassi del Comando

Il comando viene eseguito dalla riga di comando, dalla root del progetto:

```bash
php SismaFramework/Console/sisma upgrade <module> [options]
```

### Argomenti

- `<module>`: il nome del modulo da aggiornare (es. `Blog`, `Catalog`).

### Opzioni

| Opzione | Descrizione |
|---------|-------------|
| `--to=VERSION` | Versione di destinazione del framework (es. `11.0.0`). **Obbligatoria**. |
| `--from=VERSION` | Versione di partenza. Se omessa, viene rilevata automaticamente dal file `module.json`. |
| `--dry-run` | Esegue una simulazione senza modificare alcun file. Fortemente consigliato come primo passaggio. |
| `--skip-critical` | Esclude dall'elaborazione i file critici (`Public/index.php`, file nella cartella `Config/`). |
| `--skip-backup` | Salta la creazione del backup automatico (sconsigliato). |
| `--quiet` | Modalità con output minimale. |

## Flusso di Lavoro Consigliato

### 1. Anteprima con dry-run

Eseguire sempre prima una simulazione per verificare le modifiche che verranno applicate:

```bash
php SismaFramework/Console/sisma upgrade Blog --to=11.0.0 --dry-run
```

Il sistema analizzerà tutti i file del modulo e mostrerà un report dettagliato delle trasformazioni previste, senza toccare alcun file.

### 2. Applicazione dell'upgrade

Dopo aver verificato il report, eseguire il comando senza `--dry-run`:

```bash
php SismaFramework/Console/sisma upgrade Blog --to=11.0.0
```

Verrà creato un backup automatico del modulo (es. `Blog_backup_20250115143022.zip`) e le modifiche verranno applicate. Se il modulo si trova all'interno di un repository Git, verrà inoltre creato un commit di pre-backup.

### 3. Revisione manuale

Il report di upgrade può segnalare azioni manuali necessarie. Queste vanno eseguite manualmente dopo l'upgrade automatico.

### 4. Esecuzione dei test

Al termine dell'upgrade, è fondamentale eseguire la suite di test del modulo per verificare che tutto funzioni correttamente.

## Esempi di Utilizzo

```bash
# Anteprima dell'upgrade (sicuro, consigliato come primo passaggio)
php SismaFramework/Console/sisma upgrade Blog --to=11.0.0 --dry-run

# Applicazione dell'upgrade dopo aver verificato il dry-run
php SismaFramework/Console/sisma upgrade Blog --to=11.0.0

# Upgrade specificando la versione di partenza
php SismaFramework/Console/sisma upgrade Blog --from=10.1.7 --to=11.0.0

# Esclusione dei file critici (revisione manuale successiva)
php SismaFramework/Console/sisma upgrade Blog --to=11.0.0 --skip-critical

# Output minimale
php SismaFramework/Console/sisma upgrade Blog --to=11.0.0 --quiet
```

## Strategia di Upgrade 10.x -> 11.0.0

La strategia attualmente disponibile gestisce l'aggiornamento dalla versione major 10 alla versione major 11. Vengono applicati i seguenti transformer:

### Conversione da Metodi Statici a Metodi di Istanza

Le classi `ErrorHandler` e `Debugger` sono state convertite da classi con metodi statici a classi con metodi di istanza. Il transformer individua le chiamate statiche (es. `ErrorHandler::metodo()`) e le converte in chiamate di istanza (es. `$errorHandler->metodo()`).

Nel file `Public/index.php` il transformer inserisce automaticamente l'istanziamento degli oggetti dopo il require dell'autoload e aggiorna la creazione del `Dispatcher` per iniettare il `Debugger`.

**Prima:**
```php
ErrorHandler::handleNonThrowableError();
Debugger::init();
$dispatcher = new Dispatcher();
```

**Dopo:**
```php
$errorHandler = new ErrorHandler();
$debugger = new Debugger();

$errorHandler->registerNonThrowableErrorHandler();
$debugger->init();
$dispatcher = new Dispatcher(debugger: $debugger);
```

### Modifica del Return Type di customFilter

Il metodo `BaseForm::customFilter()` ha cambiato il tipo di ritorno da `void` a `bool`. Il transformer aggiorna automaticamente la firma del metodo nei file all'interno della cartella `Forms/` e aggiunge le istruzioni `return` appropriate:

- `return false;` dopo ogni assegnazione di errore (`$this->formFilterError->... = true;`)
- `return true;` alla fine del metodo

**Prima:**
```php
protected function customFilter(): void
{
    if ($this->entity->name === '') {
        $this->formFilterError->name = true;
    }
}
```

**Dopo:**
```php
protected function customFilter(): bool
{
    if ($this->entity->name === '') {
        $this->formFilterError->name = true;
        return false;
    }
    return true;
}
```

### Conversione del Costruttore di Response

Il metodo `Response::setResponseType()` e' stato rimosso in favore dell'iniezione tramite costruttore. Il transformer individua il pattern di creazione di un oggetto `Response` seguito dalla chiamata a `setResponseType()` e li unifica in un'unica istruzione.

**Prima:**
```php
$response = new Response();
$response->setResponseType(ResponseType::Json);
```

**Dopo:**
```php
$response = new Response(ResponseType::Json);
```

Se il pattern risulta troppo complesso per la trasformazione automatica, viene emesso un warning con la richiesta di revisione manuale.

### Rinomina dei Metodi

Il transformer di rinomina gestisce i metodi il cui nome e' cambiato tra le due versioni. Ad esempio:

| Metodo Precedente | Nuovo Metodo |
|---|---|
| `handleNonThrowableError()` | `registerNonThrowableErrorHandler()` |

### Breaking Changes della Versione 11.0.0

La strategia segnala le seguenti breaking changes che possono richiedere intervento manuale:

- `ErrorHandler` e `Debugger`: metodi statici convertiti in metodi di istanza
- `BaseForm::customFilter()`: tipo di ritorno cambiato da `void` a `bool`
- `Response::setResponseType()`: metodo rimosso in favore dell'iniezione tramite costruttore
- `ErrorHandler::handleNonThrowableError()`: rinominato in `registerNonThrowableErrorHandler()`
- `Public/index.php`: richiede aggiornamento per istanziare `ErrorHandler` e `Debugger`

## Il Report di Upgrade

Al termine dell'esecuzione, il comando genera un report che include:

- **Modulo**: nome del modulo aggiornato
- **Versione**: versione di partenza e di destinazione
- **Stato**: `SUCCESS`, `DRY-RUN` o errore
- **File modificati/saltati**: conteggio dei file elaborati
- **Dettaglio per file**: per ciascun file modificato vengono riportati il numero di modifiche, il livello di confidenza percentuale e le trasformazioni applicate
- **Warning**: eventuali avvertimenti su trasformazioni che richiedono revisione manuale
- **Azioni manuali**: elenco delle operazioni che devono essere eseguite manualmente dallo sviluppatore
- **Backup**: percorso del file di backup creato

### Livelli di Confidenza

Ogni transformer dichiara un livello di confidenza che indica l'affidabilita' della trasformazione automatica:

| Livello | Significato |
|---------|-------------|
| 80-100% | Trasformazione sicura, alta probabilita' di correttezza |
| 65-79% | Trasformazione generalmente corretta, consigliata una verifica |
| < 65% | Trasformazione incerta, revisione manuale necessaria |

## Categorizzazione dei File

Il sistema classifica i file del modulo nelle seguenti categorie:

| Categoria | Percorso | Elaborazione |
|-----------|----------|--------------|
| `form` | `Application/Forms/` | Sempre elaborato |
| `controller` | `Application/Controllers/` | Sempre elaborato |
| `model` | `Application/Models/` | Sempre elaborato |
| `entity` | `Application/Entities/` | Sempre elaborato |
| `critical` | `Public/index.php`, `Config/` | Elaborato salvo `--skip-critical` |
| `other` | Tutti gli altri file | Escluso dall'elaborazione |

## Backup e Rollback

Se non viene specificata l'opzione `--skip-backup`, il sistema crea automaticamente un archivio ZIP del modulo prima di applicare le modifiche. Il file viene salvato nella stessa directory del modulo con il formato `<NomeModulo>_backup_<timestamp>.zip`.

In caso di errore durante l'applicazione delle trasformazioni, il sistema esegue automaticamente il rollback ripristinando il modulo dallo stato del backup.

Se il modulo si trova all'interno di un repository Git, viene inoltre creato un commit con il messaggio `Pre-upgrade backup - <timestamp>` prima dell'applicazione delle modifiche.

* * *

[Indice](index.md) | Precedente: [Scaffolding](scaffolding.md) | Successivo: [Barra di Debug](debug-bar.md)

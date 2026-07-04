# Changelog

All notable changes to this project will be documented in this file.

## [11.9.2] - 2026-07-04 - Correzione Ordine `http_response_code()`/Flush e Cascata di Pagine d'Errore Duplicate

Patch che corregge due difetti resi visibili dalla chiusura esplicita del buffer introdotta in 11.9.1: impostare il codice di risposta HTTP dopo aver già scaricato l'output al client, e una shutdown function che non distingueva un errore fatale reale da un semplice warning residuo né verificava se una risposta fosse già stata inviata.

### 🐛 Bug Fixes

#### `Core/Services/RenderService` — `http_response_code()` impostato dopo l'invio dell'output

Da quando `BufferManager::flush()` chiude realmente tutti i livelli di buffer del framework fino al floor (11.9.1), invocarlo durante il rendering scarica per davvero il contenuto al client, gli header compresi. `generateView()`, `generateData()` e `generateJson()` costruivano però l'oggetto `Response` — il cui costruttore chiama `http_response_code()` per impostare il codice desiderato (es. `500` per una pagina d'errore) — solo *dopo* aver chiamato `BufferManager::flush()`. A quel punto gli header erano già stati inviati, e PHP generava il warning `http_response_code(): Cannot set response code - headers already sent (output started at .../BufferManager.php:55)`.

Spostata la costruzione di `Response` prima di `BufferManager::flush()` in tutti e tre i metodi: il codice di stato viene ora impostato mentre l'output è ancora bufferizzato, prima che gli header vengano effettivamente inviati.

**File modificati**:
- **`Core/Services/RenderService.php`**: `generateView()`, `generateData()`, `generateJson()`

#### `Core/HelperClasses/ErrorHandler::registerNonThrowableErrorHandler()` — cascata di pagine d'errore duplicate

Il warning descritto sopra, in quanto ultimo errore PHP della richiesta, veniva intercettato da `error_get_last()` nella shutdown function registrata da `registerNonThrowableErrorHandler()`. Questa si limitava a verificare `is_array($error)`, senza distinguere un errore realmente fatale da un semplice warning né controllare se una risposta fosse già stata inviata per la richiesta corrente: trattava quindi il warning come un crash non catturato e tentava di renderizzare un'ulteriore pagina d'errore completa, la quale — generando a sua volta lo stesso warning — poteva ripetere la cascata una terza volta, questa volta mostrata "nuda" da PHP perché `display_errors` era già stato riattivato da `showErrorInDevelopmentEnvironment()` (chiamato nel blocco `finally` di `Public/index.php`, eseguito prima della shutdown function).

Aggiunte due guardie alla shutdown function:
- se gli header sono già stati inviati (`headers_sent()`), l'errore viene solo loggato, senza tentare di renderizzare un'altra pagina che si limiterebbe ad accodarsi all'output già in volo;
- in produzione, la pagina generica d'errore (`callInternalServerErrorAction()`) viene ora invocata solo se l'errore appartiene ai livelli realmente fatali (`E_ERROR`, `E_PARSE`, `E_CORE_ERROR`, `E_COMPILE_ERROR`, `E_USER_ERROR`, `E_RECOVERABLE_ERROR`), tramite il nuovo metodo privato `isFatalError()`; un warning residuo viene loggato ma non genera più una pagina di errore al posto della risposta già prodotta.

In ambiente di sviluppo il comportamento resta volutamente invariato: qualsiasi errore residuo, warning incluso, continua a generare la pagina di dettaglio (`nonThrowableError()`), scelta progettuale per rendere visibile ogni anomalia durante lo sviluppo.

**File modificati**:
- **`Core/HelperClasses/ErrorHandler.php`**: `registerNonThrowableErrorHandler()`, aggiunto il metodo privato `isFatalError()`

### ♻️ Pulizia Codice

#### `Core/HelperClasses/Dispatcher/ControllerFactory::createController()` — rimossa condizione "fast path" ridondante

Il metodo selezionava un "fast path" (`new $controllerClassName($this->dataMapper, $this->debugger)`) per i costruttori senza parametri, con un solo parametro `DataMapper`, o con `DataMapper` seguito da `Debugger`. Per tutti e tre i casi, il percorso generico `resolveConstructorArguments()` produce però esattamente lo stesso risultato: PHP ignora silenziosamente gli argomenti posizionali passati in eccesso rispetto ai parametri dichiarati da un costruttore, quindi passare sempre `$dataMapper`/`$debugger` a un costruttore che ne dichiara meno (o nessuno) equivaleva già a quanto fa `resolveConstructorArguments()` valutando i tipi dichiarati uno per uno. La condizione non copriva inoltre alcun caso non già gestito dal percorso generico: era codice morto.

Rimossa la condizione: `createController()` passa ora sempre attraverso `resolveConstructorArguments()`.

**File modificati**:
- **`Core/HelperClasses/Dispatcher/ControllerFactory.php`**

### 📖 Documentazione

#### `docs-phpdoc/` — Rigenerazione completa

Rigenerata tramite `composer phpdoc`. Nessuna modifica di rilievo: le correzioni di questa release non toccano segnature pubbliche né aggiungono classi, l'unico file con differenze (`SismaFramework-Core-Services-RenderService.html`) riflette esclusivamente lo spostamento dei numeri di riga dovuto al riordino del codice in `RenderService.php`.

### ✅ Backward Compatibility

- **Nessun Breaking Change**: le firme pubbliche di `RenderService`, `ErrorHandler` e `ControllerFactory` restano invariate; tutte le correzioni intervengono solo sulla logica interna.

---

## [11.9.1] - 2026-07-04 - Correzione Dispatcher e Gestione Livelli di Buffer

Patch che corregge tre problemi: uno nella risoluzione dei parametri del costruttore in `ControllerFactory`, uno nella pulizia dei livelli di output buffering in `BufferManager`, e uno nella chiusura esplicita del buffer di rendering in `RenderService`. Quest'ultima correzione ha permesso di eliminare un workaround diventato necessario nella suite di test e ha reso visibili due difetti preesistenti che il workaround mascherava.

### 🐛 Bug Fixes

#### `Core/HelperClasses/Dispatcher/ControllerFactory::createController()` — Risoluzione errata dei parametri del costruttore

La condizione che decide se istanziare il controller iniettando direttamente `DataMapper` e `Debugger` verificava solo che il primo parametro del costruttore fosse di tipo `DataMapper`, senza controllare né il numero complessivo di parametri né il tipo del secondo. Un controller con un solo parametro `DataMapper` veniva quindi trattato come se accettasse anche un secondo parametro `Debugger` (passato comunque da `new $controllerClassName($this->dataMapper, $this->debugger)`), causando un `ArgumentCountError` a runtime; viceversa un costruttore con `DataMapper` seguito da un parametro di tipo diverso da `Debugger` veniva iniettato in modo scorretto.

La condizione ora distingue esplicitamente tre casi validi per l'iniezione diretta: nessun parametro, un solo parametro di tipo `DataMapper`, oppure esattamente due parametri di tipo `DataMapper` e `Debugger` in quest'ordine. In ogni altro caso i parametri vengono risolti tramite `resolveConstructorArguments()`, come già avveniva.

**File modificati**:
- **`Core/HelperClasses/Dispatcher/ControllerFactory.php`**: `createController()` verifica numero e tipo dei parametri prima di procedere all'iniezione diretta

#### `Core/HelperClasses/BufferManager` — Livelli di buffer non ripuliti in caso di errore

`clear()` e `flush()` agivano su un solo livello di buffering (`ob_clean()`/`ob_flush()`), assumendo implicitamente che ne fosse stato aperto al massimo uno con `start()`. Quando più livelli di buffer risultavano attivi contemporaneamente (ad esempio in seguito a un errore che aveva impedito la chiusura di un livello precedente), solo il livello più interno veniva ripulito, lasciando gli altri livelli — con il relativo contenuto residuo — attivi.

Aggiunta la proprietà statica `$baseLevel`, che memorizza il livello di `ob_get_level()` rilevato al primo utilizzo della classe (`ensureBaseLevel()`, invocato da `start()`, `clear()` e `flush()`). `clear()` e `flush()` ora iterano con `ob_end_clean()`/`ob_end_flush()` finché il livello corrente resta superiore a questo livello di base, ripulendo tutti i livelli di buffer aperti dal framework senza intaccare eventuale buffering preesistente all'esterno (es. quello del web server).

**File modificati**:
- **`Core/HelperClasses/BufferManager.php`**: aggiunta `$baseLevel` e `ensureBaseLevel()`; `clear()` e `flush()` ripuliscono tutti i livelli di buffer sopra il livello di base tramite `ob_end_clean()`/`ob_end_flush()`

#### `Core/Services/RenderService` — Buffer di rendering non chiuso esplicitamente

`assemblesComponents()` (usato da `generateView()` e `generateData()`) e `generateJson()` aprono un livello di buffer tramite `BufferManager::start()` ma non lo richiudevano mai esplicitamente: il contenuto renderizzato restava bufferizzato fino alla chiusura naturale dello script PHP, che scarica automaticamente i buffer residui a fine richiesta. Questo comportamento implicito funziona in un normale ciclo richiesta/risposta (un processo per richiesta), ma lascia il buffer indefinitamente "in sospeso" in qualunque contesto che riutilizzi lo stesso processo per più cicli logici — come la suite di test, dove PHPUnit esegue centinaia di test nello stesso processo.

Aggiunta una chiamata esplicita a `BufferManager::flush()` al termine di `generateView()`, `generateData()` e `generateJson()`, subito prima di restituire la `Response`: il buffer aperto da ciascuna chiamata viene ora sempre chiuso nello stesso punto logico in cui viene aperto, senza fare affidamento sullo scaricamento implicito di fine script. Valutata anche l'aggiunta della stessa chiamata in `Dispatcher::run()`: verificato che è superflua, perché `BufferManager::start()` viene invocato solo da `RenderService`, che ora si chiude sempre da solo.

**File modificati**:
- **`Core/Services/RenderService.php`**: `generateView()`, `generateData()` e `generateJson()` chiudono il proprio buffer con `BufferManager::flush()` prima di ritornare

### 🧪 Test

#### Allineamento della suite dei test alla nuova gestione del buffer

Con `BufferManager::flush()` ora invocato esplicitamente da `RenderService`, la suite di test ha smesso di richiedere il workaround manuale (`\ob_end_clean()` a inizio test) precedentemente necessario per compensare il buffer di rendering mai chiuso. Rimosse le chiamate manuali a `\ob_end_clean()` in `DispatcherTest`, `RenderTest` e `RenderServiceTest`, e unificate le chiamate multiple a `expectOutputRegex()` per singolo test (non supportate da PHPUnit oltre la prima) in un'unica regex con lookahead.

La correzione ha inoltre reso visibili due difetti preesistenti, prima mascherati dal workaround:
- le view di test (`TestsApplication/Views/**/*.php`) includevano i partial comuni (`baseHead.php`, `menu.php`, `footer.php`, `header.php`) con `require_once`: nel processo unico condiviso da PHPUnit, solo il primo render dell'intera suite li includeva realmente, lasciando `<head>` vuoto in tutti i render successivi. Cambiato in `require`.
- `DispatcherTest::testNotifyPath` verificava un titolo di pagina errato (`sample - index` invece di `sample - notify`, azione effettivamente instradata).

**File modificati**:
- **`Tests/Core/HelperClasses/DispatcherTest.php`**, **`Tests/Core/HelperClasses/RenderTest.php`**, **`Tests/Core/Services/RenderServiceTest.php`**: rimossi gli `\ob_end_clean()` manuali, unificate le `expectOutputRegex()` multiple, corretta l'asserzione di `testNotifyPath`
- **`TestsApplication/Views/controllerWithSlug/index.php`**, **`TestsApplication/Views/other/*.php`**, **`TestsApplication/Views/sample/*.php`**: `require_once` → `require` per l'inclusione dei partial comuni

### ✅ Backward Compatibility

- **Nessun Breaking Change**: le firme pubbliche di `ControllerFactory::createController()`, `BufferManager` e `RenderService` restano invariate; le correzioni intervengono solo sulla logica interna.

---

## [11.9.0] - 2026-06-27 - Configurazione Database e Crittografia tramite Variabili d'Ambiente

Questa minor release permette di configurare le credenziali del database e la passphrase di cifratura tramite variabili d'ambiente, evitando di doverle scrivere come valori letterali in `Config/configFramework.php` — file che, a differenza di `Config/config.php`, viene generato durante l'installazione e tipicamente committato nel progetto applicativo. L'installer rileva automaticamente quando queste variabili sono già presenti nell'ambiente e salta la richiesta interattiva/CLI corrispondente.

### ✨ Nuove Funzionalità

#### `Config/config.php` — Costanti database e cifratura risolte tramite `getenv()`

Le costanti `DATABASE_HOST`, `DATABASE_NAME`, `DATABASE_USERNAME`, `DATABASE_PASSWORD`, `DATABASE_PORT` ed `ENCRYPTION_PASSPHRASE` non sono più dichiarate con `const` ma con `define(__NAMESPACE__ . '\NOME_COSTANTE', getenv('NOME_COSTANTE') ?: "")`. Questo era necessario perché PHP non ammette chiamate a funzione (`getenv()`) all'interno di un'espressione costante dichiarata con `const`; `define()` accetta invece un'espressione valutata a runtime. Il namespace va qualificato esplicitamente (`__NAMESPACE__ . '\...'`) perché, a differenza di `const`, `define()` non eredita automaticamente il namespace del file.

Se la variabile d'ambiente non è impostata, il fallback resta la stringa vuota `""`, identico al valore di default precedente: il comportamento per chi non adotta variabili d'ambiente è invariato.

**File modificati**:
- **`Config/config.php`**: le sei costanti elencate sopra convertite da `const` a `define()` con fallback a `getenv()`

#### `Console/Commands/InstallationCommand::collectDatabaseConfiguration()` — Skip automatico con variabili d'ambiente già presenti

Aggiunto il metodo privato `hasDatabaseConfigFromEnvironment()`, che verifica se almeno una tra `DATABASE_HOST`, `DATABASE_NAME`, `DATABASE_USERNAME`, `DATABASE_PASSWORD`, `DATABASE_PORT` è già impostata nell'ambiente del processo. Il controllo viene eseguito dopo quello sulle opzioni CLI (`--db-host` e affini, che restano prioritarie se esplicitamente fornite) e prima del prompt interattivo: se l'ambiente fornisce già la configurazione, l'installer stampa un messaggio informativo e prosegue senza chiedere nulla, evitando di scrivere un fallback letterale — e quindi un potenziale segreto — in `Config/configFramework.php`.

Aggiornato anche il testo di `--help` del comando per documentare il nuovo comportamento.

**File modificati**:
- **`Console/Commands/InstallationCommand.php`**: aggiunto `hasDatabaseConfigFromEnvironment()`; `collectDatabaseConfiguration()` lo invoca prima del prompt interattivo; aggiornato il testo di `configure()`

#### `.env.example` — Documentazione delle variabili d'ambiente consultate dal framework

Aggiunto un file `.env.example` nella root del framework che elenca le sei variabili d'ambiente lette via `getenv()` in `Config/config.php`. Il file è puramente documentale: il framework non effettua alcun parsing di file `.env` (nessuna nuova dipendenza, nessun loader interno) — le variabili devono essere rese disponibili come variabili d'ambiente del processo PHP da chi gestisce il deployment (Docker `env_file`, direttive del web server, `systemd EnvironmentFile`, export manuale, ecc.).

**File creati**:
- **`.env.example`**

### 🔧 Modifiche Interne

#### `Console/Services/Installation/InstallationManager::updateConfigFile()` — Supporto al nuovo pattern `define()+getenv()`

La sostituzione dei valori raccolti da CLI/prompt interattivo in `Config/configFramework.php` riconosceva solo il pattern `const NOME = "valore";`. Aggiunto un secondo pattern che riconosce `define(__NAMESPACE__ . '\NOME', getenv('NOME') ?: "valore")` e aggiorna soltanto il valore di fallback dopo `?:`, lasciando intatta la chiamata a `getenv()` — così l'ambiente continua ad avere priorità anche su un valore scritto in fase di installazione.

**File modificati**:
- **`Console/Services/Installation/InstallationManager.php`**: `updateConfigFile()` applica entrambi i pattern (`const` e `define()+getenv()`) per ciascuna chiave di configurazione

### ✅ Backward Compatibility

- **Nessun Breaking Change**: il nome, il namespace e le modalità di utilizzo delle costanti (`Config\DATABASE_HOST`, ecc.) restano identici per chi le consulta; cambia solo il meccanismo interno di dichiarazione (`define()` invece di `const`).
- **Comportamento di default invariato**: senza variabili d'ambiente impostate, il fallback è la stessa stringa vuota `""` di prima — installazioni standalone esistenti non notano differenze.
- **Funzionalità opt-in**: il rilevamento automatico in fase di installazione si attiva solo se l'ambiente fornisce già la configurazione; il flusso CLI/interattivo esistente resta invariato in tutti gli altri casi.

---

## [11.8.1] - 2026-06-25 - Correzione Parsing Code Block Annidati nelle Blockquote (Sample)

Patch che corregge il parser Markdown del sito di autopromozione/documentazione (`Sample/Controllers/DocsController.php`): i fenced code block annidati in una blockquote venivano interpretati in modo scorretto, corrompendo il rendering di tutto il contenuto successivo del documento. Corretto anche un fence orfano nel file `docs/advanced-orm.md`.

### 🐛 Bug Fixes

#### `Sample/Controllers/DocsController::parseMarkdown()` — Code block annidati in blockquote

La regex che estrae i fenced code block (sequenza di tre backtick) cercava i marcatori ovunque nel testo, senza considerare un eventuale prefisso `> ` di blockquote. Quando un code block era annidato in una blockquote (es. una riga `> ` seguita dai tre backtick e dal nome del linguaggio), il marcatore di chiusura — anch'esso prefissato da `> ` — non veniva riconosciuto come tale: la regex continuava ad espandersi non-greedy fino ai successivi tre backtick "nudi" (non prefissati da `> `) nel documento, inghiottendo tutto il contenuto intermedio (inclusi altri code block, header e liste) in un unico blocco corrotto con lingua errata.

Aggiunto un nuovo step, eseguito prima dell'estrazione dei code block "normali" e dello step delle blockquote, che riconosce specificamente i fenced code block prefissati da `> ` su ogni riga (apertura, contenuto e chiusura), rimuove il prefisso dal contenuto e li converte in `<pre><code>` esattamente come gli altri code block. Le righe di blockquote senza code block annidato continuano a essere gestite dallo step esistente.

**File modificati**:
- **`Sample/Controllers/DocsController.php`**: aggiunto step di estrazione per i code block annidati in blockquote in `parseMarkdown()`

#### `docs/advanced-orm.md` — Fence di chiusura orfano

Un marcatore di chiusura (tre backtick) senza apertura corrispondente era stato lasciato per errore dopo una lista (sezione "Best Practices" del capitolo sul lazy loading). Da quel punto in poi, l'alternanza apertura/chiusura dei fence successivi nel documento risultava sfasata, producendo blocchi di codice corrotti che inghiottivano header e sezioni successive.

**File modificati**:
- **`docs/advanced-orm.md`**: rimosso il fence orfano

### ✅ Backward Compatibility

- **Nessun Breaking Change**: la modifica riguarda esclusivamente il rendering del sito demo/documentazione (`Sample`) e un file Markdown di documentazione; non tocca alcuna API pubblica del framework.

---

## [11.8.0] - 2026-06-23 - Miglioramento Flessibilità `addRequest()` in BaseForm e Standardizzazione Formattazione

Questa minor release estende la flessibilità del metodo `addRequest()` della classe `BaseForm` permettendo il controllo esplicito sulle sovrascritture di valori nella request. Inoltre, standardizza la formattazione del codice con miglior indentazione e trailing comma secondo le best practice PHP moderne.

### ✨ Nuove Funzionalità

#### `Core/BaseClasses/BaseForm::addRequest()` — Controllo esplicito sulle sovrascritture

Il metodo `addRequest()` è stato esteso con due miglioramenti:

**Ampliamento tipi di `$value`**:
- ❌ **Prima**: `string|array $value`
- ✅ **Dopo**: `string|int|float|bool|array|null $value`

Consente di iniettare nella request dati di diversi tipi primitivi, non solo stringhe e array.

**Aggiunta parametro `$override` con controllo sulle sovrascritture**:
- ❌ **Prima**: `protected function addRequest(string $propertyName, string|array $value): self` — sovrascrive sempre il valore
- ✅ **Dopo**: `protected function addRequest(string $propertyName, string|int|float|bool|array|null $value, bool $override = true): self`

Il parametro `$override = true` (default) mantiene il comportamento precedente: sovrascrive sempre. Passando `false`, il metodo scrive il valore **solo se** la proprietà non esiste ancora in `request->input`.

**Caso d'uso**:
```php
// Inietta un valore di default che non sovrascrive l'input dell'utente
$this->addRequest('email', 'default@example.com', override: false);
```

**File modificati**:
- **`Core/BaseClasses/BaseForm.php`**: Firma di `addRequest()` aggiornata; logica di controllo sulla sovrascrittura implementata

### 🎨 Miglioramenti Formattazione Codice

#### Standardizzazione Indentazione e Trailing Comma

Standardizzate le convenzioni di formattazione secondo PSR-12:

**Costruttore di `BaseForm`**:
- Apertura parentesi su nuova riga dopo `__construct(`
- Indentazione coerente dei parametri (4 spazi)
- Trailing comma dopo l'ultimo parametro
- Chiusura parentesi e apertura brace sulla stessa riga

**Metodi con parametri multipli**:
- Aggiunta trailing comma dopo l'ultimo parametro anche in `validate()` e `resolveEntity()`

**Vantaggi della trailing comma**:
- Migliora la diff nei version control (meno noise su modifiche EOL)
- Facilita l'aggiunta di nuovi parametri senza modificare la riga precedente
- Stile coerente con il resto del codebase moderno

**File modificati**:
- **`Core/BaseClasses/BaseForm.php`**: Formattazione costruttore e indentazione parametri standardizzate

### 🧪 Test

#### `Tests/Core/BaseClasses/BaseFormTest` — Copertura nuovo parametro `$override`

Aggiunti tre test per verificare il comportamento del nuovo parametro `$override` in `addRequest()`:

- `testAddRequestWithOverrideTrueShouldOverwriteExistingValue()`: Verifica che con `override = true` (default), un valore esistente viene sovrascritto dalla logica di `injectRequest()`
- `testAddRequestWithOverrideFalseShouldNotOverwriteExistingValue()`: Verifica che con `override = false`, un valore preesistente in `request->input` non viene sovrascritto
- `testAddRequestWithOverrideFalseShouldInjectMissingValue()`: Verifica che con `override = false`, un valore mancante viene comunque iniettato

**File creati**:
- **`TestsApplication/Forms/SimpleEntityWithAddRequestOverrideFalseForm.php`**: Form di test che utilizza `addRequest(..., override: false)`

**File modificati**:
- **`Tests/Core/BaseClasses/BaseFormTest.php`**: Aggiunti tre nuovi test

### ✅ Backward Compatibility

- **Nessun Breaking Change**: Il default `$override = true` mantiene il comportamento precedente per tutto il codice esistente
- **API Estensibile**: Chi ha esigenze specifiche di controllo sulle sovrascritture può ora passare `false` per ottenere il comportamento conservativo
- **Formattazione**: Le modifiche di formattazione non impattano il comportamento a runtime

---

## [11.7.0] - 2026-06-18 - Opzione `--module` e Discovery dei Moduli Non Configurati

Introduce l'opzione `--module=NomeModulo` per selezionare esplicitamente quale modulo deve gestire un comando quando più moduli registrano lo stesso nome. Aggiunge contestualmente la discovery automatica dei moduli fisicamente presenti su filesystem ma non ancora dichiarati in `MODULE_FOLDERS`, risolvendo il problema di bootstrap circolare per cui un modulo non poteva registrarsi tramite il proprio comando di installazione perché non ancora configurato.

### ✨ Nuove Funzionalità

#### `Console/HelperClasses/CommandDispatcher` — Opzione `--module` per selezione esplicita del modulo

Aggiunta l'opzione globale opzionale `--module=NomeModulo` al dispatcher dei comandi console. Quando specificata, il dispatcher filtra la lista dei comandi compatibili e ne esegue solo uno appartenente al modulo indicato; se nessun comando di quel modulo è compatibile, viene lanciata `RuntimeException` come per un comando sconosciuto. Senza l'opzione il comportamento rimane invariato (primo match vince, nell'ordine di priorità della discovery).

Per supportare il filtro, ogni comando scoperto viene ora associato al proprio modulo di appartenenza tramite un array parallelo `$commandModules[]`. La firma di `discoverFromDirectory()` include il parametro `string $module` e `addCommandStrategy()` accetta un secondo parametro opzionale `string $module = ''` per retrocompatibilità con i chiamanti esistenti.

**File modificati**:
- **`Console/HelperClasses/CommandDispatcher.php`**: aggiunto `$commandModules[]`; `run()` chiama `extractModuleOption()` prima del loop; `discoverFromDirectory()` riceve e salva il modulo; `addCommandStrategy()` accetta `module` opzionale

#### `Console/HelperClasses/CommandDispatcher` — Discovery automatica dei moduli non configurati

Il metodo privato `discoverUnconfiguredModules()` esegue un glob su `{rootPath}/*/Console/Commands/` e restituisce le cartelle modulo che hanno quella struttura ma non sono ancora presenti in `MODULE_FOLDERS`. Questi moduli vengono scansionati dopo quelli configurati e prima del framework, con priorità inferiore rispetto ai moduli dichiarati nella configurazione. La combinazione con `--module=` permette di eseguire il comando di installazione di un modulo anche prima che sia stato aggiunto a `MODULE_FOLDERS`.

**File modificati**:
- **`Console/HelperClasses/CommandDispatcher.php`**: aggiunto `discoverUnconfiguredModules()`; `discoverCommands()` lo invoca tra il loop su `moduleFolders` e la discovery del framework

### 🧪 Test

#### `Tests/Console/HelperClasses/CommandDispatcherTest` — Copertura opzione `--module` e moduli non configurati

- `testAddCommandStrategyWithModuleRunsNormally`: verifica che `addCommandStrategy()` con parametro modulo non alteri il comportamento di base
- `testModuleFilterRunsOnlyMatchingModuleCommand`: due comandi compatibili da moduli diversi — con `--module=ModuleA` solo il primo viene eseguito
- `testModuleFilterSkipsAllCommandsWhenModuleNotFound`: `--module=NonExistent` → `RuntimeException("Unknown command")`
- `testModuleFilterWithoutModuleOptionIgnoresModuleOwnership`: senza `--module` la logica "primo match vince" rimane invariata
- `testDiscoveryFindsCommandsInUnconfiguredModuleFolders`: crea su filesystem un modulo assente da `MODULE_FOLDERS` e verifica che il suo comando sia comunque scoperto ed eseguito
- `testModuleFilterSelectsUnconfiguredModuleOverConfiguredOne`: con due moduli che espongono lo stesso comando, `--module=UnconfiguredModule` esegue il comando del modulo non configurato ignorando quello configurato

**File modificati**:
- **`Tests/Console/HelperClasses/CommandDispatcherTest.php`**: aggiunti sei test

### ✅ Backward Compatibility

- **Nessun Breaking Change**: `addCommandStrategy()` aggiunge il parametro `module` con default `''` — tutti i chiamanti esistenti compilano senza modifiche. Senza `--module` il comportamento di `run()` è identico a prima. I comandi che non usano `--module` non ricevono l'opzione in modo diverso rispetto alle altre opzioni (finisce in `$options['module']` come qualsiasi altra opzione `--key=value`).

---

## [11.6.3] - 2026-06-17 - Correzione Input Password e Sostituzione Valori Numerici nel File di Configurazione

Patch che corregge due bug nel comando di installazione CLI: `askSecret()` emetteva un errore `stty` su ambienti senza TTY reale (IDE, pipe, container), e `updateConfigFile()` troncava i valori numerici come la porta del database a causa di un'ambiguità nelle backreference PCRE della stringa di sostituzione.

### 🐛 Bug Fixes

#### `Console/Traits/InteractiveInputTrait` — Errore `stty` su stdin non-TTY

Il metodo `askSecret()` chiamava `system('stty -echo')` condizionato solo al check `PHP_OS === 'WIN'`, che non copre i casi in cui stdin non è un terminale reale anche su Linux/macOS (IDE come VS Code o PhpStorm, esecuzione via pipe, container Docker, ambienti CI). In questi contesti `stty` stampava l'errore `stty: 'standard input': Inappropriate ioctl for device` subito dopo il prompt della password.

Il check è stato sostituito con `stream_isatty($handle)`, che verifica correttamente se il file descriptor è collegato a un TTY reale indipendentemente dal sistema operativo. Se stdin non è un TTY, la password viene letta senza tentare di disabilitare l'echo.

**File modificati**:
- **`Console/Traits/InteractiveInputTrait.php`**: `askSecret()` — rimosso il check `PHP_OS === 'WIN'`, sostituito con `stream_isatty($handle)`

#### `Console/Services/Installation/InstallationManager` — Troncamento valori numerici in `updateConfigFile()`

Il metodo `updateConfigFile()` costruiva la stringa di sostituzione per `preg_replace()` interpolando il valore direttamente in una stringa PHP: `"$1$2{$value}$2"`. Quando `$value` iniziava con una cifra (es. la porta `3306`), l'interpolazione produceva la stringa `$1$23306$2`, che PCRE interpretava come `$1` + `$23` (backreference al gruppo 23, inesistente → stringa vuota, consumando la prima cifra) + `306` (letterale) + `$2` (la virgoletta). Il risultato nel file di configurazione era `DATABASE_PORT = 306"` — valore troncato e virgoletta di chiusura mancante.

Il metodo è stato riscritto usando `preg_replace_callback()`: il valore di sostituzione viene concatenato direttamente nella closure PHP, senza mai passare per il parser delle backreference PCRE. Questo risolve anche il caso analogo di password contenenti `$` o `\`.

**File modificati**:
- **`Console/Services/Installation/InstallationManager.php`**: `updateConfigFile()` — `preg_replace()` sostituito con `preg_replace_callback()`

### 🧪 Test

#### `Tests/Console/Services/Installation/InstallationManagerTest` — Copertura regressione valori numerici

- `testInstallWithDatabaseConfig`: aggiunta asserzione su `DATABASE_PORT`; il template di config ora usa valori vuoti (`""`) per `DATABASE_PASSWORD` e `DATABASE_PORT`, replicando lo scenario reale che innescava il bug
- `testUpdateConfigFileWithNumericValueDoesNotMangle` (nuovo, con data provider): verifica che il valore `DATABASE_PORT` non venga troncato sia con il valore di default (`3306`) sia con un valore inserito dall'utente (`5432`); include una negative assertion che esclude la presenza del valore troncato

### ✅ Backward Compatibility

- **Nessun Breaking Change**: `askSecret()` mantiene la stessa firma e comportamento visibile; su TTY reale il comportamento (echo disabilitato) rimane invariato. `updateConfigFile()` è un metodo privato interno.

---

## [11.6.2] - 2026-06-09 - Correzione Ordine di Discovery dei Comandi Console

Patch che corregge un comportamento anomalo nel `CommandDispatcher`: i comandi dei moduli venivano scoperti dopo quelli del framework, impedendo ai moduli di estendere o sovrascrivere i comandi nativi. L'ordine è stato invertito — moduli prima (nell'ordine di `MODULE_FOLDERS`), framework come fallback — allineando il `CommandDispatcher` alla stessa logica di precedenza già adottata dal web `Dispatcher`.

### 🐛 Bug Fixes

#### `Console/HelperClasses/CommandDispatcher` — Ordine di discovery dei comandi

Il metodo `discoverCommands()` scansionava prima la directory dei comandi del framework (`SismaFramework/Console/Commands/`) e poi quella dei moduli, nell'ordine inverso rispetto al comportamento atteso. Poiché `run()` si ferma al primo comando compatibile, qualsiasi comando di un modulo con lo stesso nome di un comando del framework veniva silenziosamente ignorato, rendendo impossibile estendere o sovrascrivere i comandi nativi dall'esterno del framework.

L'ordine è stato corretto: i moduli vengono scansionati per primi, rispettando la sequenza dichiarata in `MODULE_FOLDERS`; il framework viene aggiunto per ultimo come fallback. Questo rispecchia esattamente la logica di precedenza del web `Dispatcher` e permette ai moduli di estendere i comandi del framework tramite ereditarietà, chiamando `parent::execute()` dopo aver aggiunto la propria logica.

**File modificati**:
- **`Console/HelperClasses/CommandDispatcher.php`**: in `discoverCommands()`, il `foreach ($this->config->moduleFolders ...)` spostato prima della chiamata a `discoverFromDirectory()` sul path di sistema

### ✅ Backward Compatibility

- **Nessun Breaking Change**: i progetti che non hanno comandi omonimi nei moduli non subiscono alcuna variazione di comportamento. I progetti che avevano un comando con lo stesso nome sia nel framework sia in un modulo vedranno ora eseguito quello del modulo anziché quello del framework — il che è il comportamento corretto e atteso.

---

## [11.6.1] - 2026-05-26 - Compatibilità phpDocumentor, Fix SismaLogger e Correzione Documentazione

Patch di manutenzione che allarga il vincolo su `psr/log` per consentire l'installazione di phpDocumentor come dipendenza di sviluppo, corregge un potenziale `TypeError` in `SismaLogger::interpolate()` con messaggi `\Stringable`, aggiorna la documentazione Markdown (esempi API errati, sezione OAuth mancante) e rigenera la documentazione phpDocumentor allineandola alle classi introdotte in 11.6.0.

### 🐛 Bug Fixes

#### `Core/HelperClasses/SismaLogger` — Gestione `\Stringable` in `interpolate()`

Il metodo privato `interpolate()` dichiarava `string $message` come tipo del parametro. Poiché `LoggerInterface` (psr/log 2.x/3.x) consente di passare oggetti `\Stringable` ai metodi di log, qualsiasi chiamata con un `\Stringable` avrebbe generato un `TypeError` prima di raggiungere il metodo. Il tipo è stato aggiornato a `\Stringable|string` e viene applicato un cast `(string)` all'inizio del metodo, garantendo la compatibilità con l'intera gamma di messaggi ammessi dall'interfaccia PSR-3.

**File modificati**:
- **`Core/HelperClasses/SismaLogger.php`**: Firma `interpolate()` aggiornata a `\Stringable|string $message`; aggiunto `$message = (string) $message` come prima istruzione

#### `Console/Services/Installation/InstallationManager` — Vincolo `psr/log` nei nuovi progetti

Il metodo che inietta la dipendenza `psr/log` nel `composer.json` dei nuovi progetti impostava il vincolo a `^3.0`. Aggiornato a `^2.0 || ^3.0` per allinearlo al vincolo del framework e consentire la coesistenza con phpDocumentor anche nei progetti installati.

**File modificati**:
- **`Console/Services/Installation/InstallationManager.php`**: Vincolo iniettato aggiornato da `^3.0` a `^2.0 || ^3.0`

### 🔧 Dipendenze e Tooling

#### `composer.json` — Allargamento vincolo `psr/log` e aggiunta phpDocumentor

Il vincolo `"psr/log": "^3.0"` impediva l'installazione di phpDocumentor come `require-dev`, poiché le sue dipendenze indirette richiedono `psr/log ^2.0`. Il vincolo è stato allargato a `^2.0 || ^3.0`: il codice del framework non usa alcuna API specifica di psr/log 3.x (i metodi `LoggerInterface` sono implementati senza type hint espliciti su `$message`, compatibili con tutte e tre le major), quindi l'allargamento non introduce alcun rischio regressivo.

Aggiunto inoltre `"config": {"platform": {"php": "8.4.99"}}` per permettere la risoluzione delle dipendenze su PHP 8.5 (dove `phpdocumentor/json-path` — dipendenza indiretta — non dichiara ancora supporto esplicito, pur funzionando correttamente). Aggiunto script `"phpdoc": "php vendor/bin/phpdoc --config phpdoc.xml"` per semplificare la rigenerazione della documentazione API.

**File modificati**:
- **`composer.json`**: `psr/log` aggiornato a `^2.0 || ^3.0`; aggiunto `phpdocumentor/phpdocumentor: ^3.10` in `require-dev`; aggiunte sezioni `config` e `scripts`

### 🧪 Test

#### `Tests/Console/Services/Installation/InstallationManagerTest` — Allineamento asserzioni

Le due asserzioni che verificavano il valore del vincolo `psr/log` iniettato da `InstallationManager` sono state aggiornate da `'^3.0'` a `'^2.0 || ^3.0'`.

**File modificati**:
- **`Tests/Console/Services/Installation/InstallationManagerTest.php`**: Due `assertEquals('^3.0', ...)` aggiornati

### 📖 Documentazione

#### `docs/security.md` — Correzione esempi API e aggiunta sezione OAuth

La sezione di esempio per l'autenticazione form-based conteneva riferimenti a metodi inesistenti nell'API pubblica (`isLogged()`, `login()`) e a un pattern logicamente scorretto (`checkAuthenticable() && checkPassword()`, dove `checkPassword()` è già chiamato internamente da `checkAuthenticable()`). Corretti anche gli accessi alle proprietà di `Request` (da notazione ad oggetto `->get()` a accesso array `['key']`, coerente con la definizione della classe) e il nome del metodo `getAuthenticable()` → `getAuthenticableInterface()`.

Aggiunta sezione completa **Autenticazione OAuth 2.0** che documenta `OAuthAuthentication`, `OAuthWrapperInterface`, il flusso Authorization Code in due fasi e un esempio di implementazione di un wrapper provider.

**File modificati**:
- **`docs/security.md`**: Corretti esempi form-based; aggiunta sezione OAuth

#### `docs/forms.md` — Correzione nome metodo `getFilterErrors()`

L'esempio del controller utilizzava `$form->returnFilterErrors()`, metodo inesistente. Corretto in `$form->getFilterErrors()` (metodo ereditato da `SubmittableTrait`).

**File modificati**:
- **`docs/forms.md`**: `returnFilterErrors()` → `getFilterErrors()`

#### `docs/controllers.md` — Correzione esempio autowiring `Authentication`

L'esempio di autowiring utilizzava `$auth->isLogged()` (metodo inesistente), il namespace errato `SismaFramework\Security\Authentication` e l'accesso alle proprietà di `Request` tramite `->get()`. Corretti namespace, metodo di verifica sessione e accesso array.

**File modificati**:
- **`docs/controllers.md`**: Namespace, controllo sessione e accesso `Request` corretti

#### `docs/api-reference.md` — Correzione firme `BaseForm` e aggiunta sezioni Security/HTTP

`getErrors(): FormFilterErrorCollection` era il nome errato del metodo (corretto in `getFilterErrors(): FormFilterError`); la firma di `handleRequest()` mancava del parametro `Request $request`. Aggiunte le sezioni **Security Classes** (`Authentication`, `OAuthAuthentication`, `OAuthWrapperInterface`, `BaseVoter`, `BasePermission`) e **HTTP Classes** (`Response`), che erano elencate nell'indice del documento ma mai implementate nel corpo.

**File modificati**:
- **`docs/api-reference.md`**: Firme `BaseForm` corrette; sezioni Security e HTTP aggiunte

#### `docs-phpdoc/` — Rigenerazione completa

Rigenerata da zero tramite `composer phpdoc` per includere le nuove classi introdotte in 11.6.0 (`OAuthAuthentication`, `OAuthWrapperInterface`, `BaseAuthentication`, `SubmittableTrait`) ed eliminare il file orfano `SismaFramework-Core-AbstractClasses-Submittable.html`, rimasto dalla generazione precedente dopo la rimozione del file PHP sorgente.

#### Correzione annotazioni `@deprecated` — versione di introduzione e rimozione

Quattro classi/metodi presentavano annotazioni `@deprecated` incomplete o errate: mancavano la versione in cui la deprecazione era stata introdotta, la versione di rimozione pianificata, oppure il testo era in inglese anziché italiano, creando incoerenza con il resto della codebase.

**`Orm/ExtendedClasses/DependentModel`** e **`Orm/ExtendedClasses/SelfReferencedModel`** — i tre metodi deprecati (`countEntityCollectionByEntity`, `getEntityCollectionByEntity`, `deleteEntityCollectionByEntity`) riportavano `dalla versione 11.0.0`, ma la deprecazione era stata introdotta in `v10.1.0` (commit `9c9f5ed4`, 2025-11-21). Corretto in `dalla versione 10.1.0`; aggiunta la versione di rimozione pianificata `12.0.0`.

**`Security/ExtendedClasses/LogException`** e **`Security/ExtendedClasses/NoLogException`** — le annotazioni erano in inglese e prive di numeri di versione. La deprecazione è stata introdotta in `v11.0.0` (commit `87843e03`, 2025-12-18). Aggiunta versione di introduzione `11.0.0`, versione di rimozione `12.0.0`; testo armonizzato in italiano coerentemente con gli altri messaggi di deprecazione del framework.

**File modificati**:
- **`Orm/ExtendedClasses/DependentModel.php`**: versione `@deprecated` corretta da `11.0.0` a `10.1.0`; aggiunto `sarà rimosso nella versione 12.0.0` (3 metodi)
- **`Orm/ExtendedClasses/SelfReferencedModel.php`**: stessa correzione (3 metodi)
- **`Security/ExtendedClasses/LogException.php`**: annotazione `@deprecated` riscritta con versioni e in italiano
- **`Security/ExtendedClasses/NoLogException.php`**: annotazione `@deprecated` riscritta con versioni e in italiano

### ✅ Backward Compatibility

- **Nessun Breaking Change**: tutte le modifiche sono correzioni di bug, aggiornamenti di documentazione o aggiunta di tooling di sviluppo. Le firme pubbliche di `SismaLogger` rimangono invariate; il comportamento di `interpolate()` è identico per input di tipo `string` (il 100% dei casi d'uso interni).

---

## [11.6.0] - 2026-05-04 - Rifattorizzazione Gerarchia di Autenticazione, Introduzione SubmittableTrait e Supporto OAuth

Rifattorizzazione interna del sistema di autenticazione: la classe astratta `Submittable` è stata convertita in un trait, e il comportamento comune a tutte le classi di autenticazione è stato estratto nella nuova classe astratta `BaseAuthentication`. Il refactoring ha abilitato l'implementazione di `OAuthAuthentication`, che supporta il flusso Authorization Code OAuth 2.0 senza `SubmittableTrait` poiché in OAuth non esiste un form da sottomettere né errori di validazione da riportare al template.

### ♻️ Refactoring

#### `Core/Traits/SubmittableTrait` — Conversione da classe astratta a trait

`Submittable` era una classe astratta `@internal` usata come base sia da `Authentication` che da `BaseForm`, pur non rappresentando un tipo condiviso tra le due gerarchie, bensì un comportamento ortogonale (rilevamento form submission). È stata convertita in un trait e spostata in `Core/Traits/`.

Il trait espone:
- `protected FormFilterError $formFilterError`
- `protected function initSubmittable(): void` — da chiamare nel costruttore della classe utilizzatrice
- `public function isSubmitted(): bool`
- `public function getFilterErrors(): FormFilterError`

**File modificati**:
- **`Core/Traits/SubmittableTrait.php`** *(nuovo)*: Implementazione del trait, marcato `@internal`
- **`Core/AbstractClasses/Submittable.php`** *(eliminato)*

#### `Security/BaseClasses/BaseAuthentication` — Nuova classe astratta base per l'autenticazione

Estratta da `Authentication` la logica comune a qualsiasi flusso di autenticazione (form-based, OAuth, ecc.). La nuova classe astratta `BaseAuthentication`, marcata `@internal`, centralizza:

- `protected Request $request`
- `protected Filter $filter`
- `protected Session $session`
- `protected ?AuthenticableInterface $authenticableInterface`
- `public function getAuthenticableInterface(): AuthenticableInterface`

`SubmittableTrait` non è incluso in `BaseAuthentication` perché non tutti i flussi di autenticazione hanno un form: `Authentication` (form-based) lo usa, `OAuthAuthentication` no.

**File modificati**:
- **`Security/BaseClasses/BaseAuthentication.php`** *(nuovo)*: Classe astratta base, marcata `@internal`

#### `Security/HttpClasses/Authentication` — Adeguamento alla nuova gerarchia

`Authentication` passa da `extends Submittable` a `extends BaseAuthentication` con `use SubmittableTrait`. Le property `$filter`, `$session`, `$authenticableInterface` e il metodo `getAuthenticableInterface()` sono stati spostati in `BaseAuthentication`. Il costruttore chiama `parent::__construct()` e `$this->initSubmittable()`.

**File modificati**:
- **`Security/HttpClasses/Authentication.php`**: Aggiornamento gerarchia e rimozione membri ora in `BaseAuthentication`

#### `Core/BaseClasses/BaseForm` — Adeguamento al SubmittableTrait

`BaseForm` passa da `extends Submittable` a `use SubmittableTrait`, dichiarando `protected Request $request` direttamente nella classe. Il costruttore sostituisce `parent::__construct()` con `$this->initSubmittable()`.

**File modificati**:
- **`Core/BaseClasses/BaseForm.php`**: Sostituzione ereditarietà con trait; dichiarazione esplicita di `$request`

### ✨ Nuove Funzionalità

#### `Security/HttpClasses/OAuthAuthentication` — Autenticazione OAuth 2.0 Authorization Code Flow

Nuova classe `OAuthAuthentication extends BaseAuthentication` che implementa il flusso Authorization Code OAuth 2.0. Non usa `SubmittableTrait` perché in OAuth non esiste un form da sottomettere: gli errori arrivano come parametri URL dal provider e vengono gestiti tramite valori di ritorno ed eccezioni, non tramite `FormFilterError`.

Il flusso si articola in due fasi:

**Fase 1 — Redirect al provider**:
- `getAuthorizationUrl(): string` genera uno `state` casuale con `random_bytes`, lo persiste in sessione e delega la costruzione dell'URL a `OAuthWrapperInterface::getAuthorizationUrl()`.

**Fase 2 — Callback dal provider**:
- `checkCallback(): bool` verifica la presenza di errori del provider (`$request->query['error']`), convalida lo `state` in modo timing-safe tramite `hash_equals()`, scambia il `code` per un identificatore utente tramite `OAuthWrapperInterface::getAuthenticableIdentifier()` e recupera l'entità autenticabile tramite `AuthenticableModelInterface`.

La protezione CSRF del callback segue lo stesso pattern difensivo di `Authentication::checkCsrfToken()`: verifica sequenziale con early return.

**File modificati**:
- **`Security/HttpClasses/OAuthAuthentication.php`** *(nuovo)*

#### `Security/Interfaces/Wrappers/OAuthWrapperInterface` — Contratto per i provider OAuth

Nuova interfaccia che astrae la comunicazione con il provider OAuth. Ogni provider (Google, GitHub, ecc.) implementa:
- `getAuthorizationUrl(string $state): string` — costruisce l'URL di autorizzazione con il parametro `state`
- `getAuthenticableIdentifier(string $code): string` — scambia il codice di autorizzazione per un identificatore utente (es. email); eventuali errori di rete o token invalidi propagano come eccezioni al chiamante

**File modificati**:
- **`Security/Interfaces/Wrappers/OAuthWrapperInterface.php`** *(nuovo)*

### 🧪 Test

#### `Tests/Security/HttpClasses/OAuthAuthenticationTest` — Copertura completa del flusso OAuth

Sette test che coprono tutti i percorsi di `checkCallback()` e `getAuthorizationUrl()`:

- `testGetAuthorizationUrl` — verifica che lo `state` venga scritto in sessione e che l'URL venga restituito dal wrapper
- `testCheckCallbackWithProviderError` — early return `false` in presenza di `error` nella query string
- `testCheckCallbackWithMissingSessionState` — `false` se lo `state` non è presente in sessione
- `testCheckCallbackWithMissingRequestState` — `false` se lo `state` manca nella query string
- `testCheckCallbackWithMismatchedState` — `false` se gli `state` non corrispondono
- `testCheckCallbackWithMissingCode` — `false` se il `code` manca dalla query string
- `testCheckCallbackWithUserNotFound` — `false` se il modello non trova l'utente
- `testCheckCallbackSuccess` — `true` con verifica di `getAuthenticableInterface()`

**File modificati**:
- **`Tests/Security/HttpClasses/OAuthAuthenticationTest.php`** *(nuovo)*

### ✅ Backward Compatibility

- **Nessun Breaking Change sull'API pubblica**: Le firme pubbliche di `Authentication` e `BaseForm` sono invariate. `Submittable` era marcata `@internal` by design e non esposta come API consumabile dall'esterno del framework.
- **`OAuthAuthentication` e `OAuthWrapperInterface`** sono addizioni pure: nessuna classe esistente è modificata dalla loro introduzione.

---

## [11.5.2] - 2026-04-04 - Rifattorizzazione Template Controller nello Scaffolding

Piccola rifattorizzazione del template del controller generato dal comando di scaffolding, per semplificare eventuali personalizzazioni post-generazione.

### ♻️ Refactoring

#### `Console/Services/Scaffolding/Templates/Controller.tpl` — Estrazione variabile entità prima del salvataggio

Nelle azioni `create` e `update`, la chiamata a `resolveEntity()` era concatenata direttamente come argomento di `$this->dataMapper->save()` su un'unica riga. L'entità risolta viene ora assegnata a una variabile dedicata prima di essere passata al DataMapper.

- ❌ **11.5.1**: `$this->dataMapper->save(${{entityShortNameLower}}Form->resolveEntity());`
- ✅ **11.5.2**:
  ```php
  ${{entityShortNameLower}} = ${{entityShortNameLower}}Form->resolveEntity();
  $this->dataMapper->save(${{entityShortNameLower}});
  ```

Questo rende il codice generato più leggibile e facilita eventuali personalizzazioni (es. manipolare l'entità tra `resolveEntity()` e `save()`), senza alcuna modifica al comportamento a runtime.

Rimossi inoltre i trailing whitespace sulle righe vuote tra i metodi della classe.

**File modificati**:
- **`Console/Services/Scaffolding/Templates/Controller.tpl`**: Estrazione variabile entità nelle azioni `create` e `update`; pulizia trailing whitespace

### ✅ Backward Compatibility

- **Nessun Breaking Change**: La modifica impatta esclusivamente il codice generato dallo scaffolding per nuovi controller. I controller già generati non sono influenzati.

---

## [11.5.1] - 2026-04-01 - Correzione Template Controller nello Scaffolding

Questa patch corregge due bug nel template del controller generato dal comando di scaffolding.

### 🐛 Bug Fixes

#### `Console/Services/Scaffolding/Templates/Controller.tpl` — Namespace modello errato e metodo form scorretto

**Bug 1 — Namespace `use` del modello con segmento `Models` duplicato**

Il namespace nell'istruzione `use` includeva un segmento `\Models\` ridondante: poiché `{{modelNamespace}}` contiene già il segmento `Models`, il risultato era una duplicazione (es. `…\Models\Models\{{entityShortName}}Model`), producendo un'istruzione non valida nel controller generato.

- ❌ **11.5.0**: `use {{modelNamespace}}\Models\{{entityShortName}}Model;`
- ✅ **11.5.1**: `use {{modelNamespace}}\{{entityShortName}}Model;`

**Bug 2 — Uso di `getEntity()` al posto di `resolveEntity()` nelle azioni `create` ed `edit`**

Il salvataggio dell'entità nelle azioni `create` ed `edit` chiamava `getEntity()`, che non risolve correttamente le relazioni del form. Il metodo corretto è `resolveEntity()`.

- ❌ **11.5.0**: `$this->dataMapper->save(${{entityShortNameLower}}Form->getEntity());`
- ✅ **11.5.1**: `$this->dataMapper->save(${{entityShortNameLower}}Form->resolveEntity());`

**File modificati**:
- **`Console/Services/Scaffolding/Templates/Controller.tpl`**: Corretto namespace `use` del modello; sostituito `getEntity()` con `resolveEntity()` nelle azioni `create` ed `edit`

### ✅ Backward Compatibility

- **Nessun Breaking Change**: La modifica impatta esclusivamente il codice generato dallo scaffolding per nuovi controller. I controller già generati non sono influenzati.

---

## [11.5.0] - 2026-03-15 - Supporto Cross-Platform per il Comando `sisma`

Questa minor aggiunge il supporto nativo al comando `sisma` su Windows e semplifica l'avvio su Linux/macOS tramite shebang.

### ✨ Nuove Funzionalità

#### `Console/sisma` — Aggiunto shebang `#!/usr/bin/env php`

Lo script `sisma` può ora essere invocato direttamente da terminale su Linux e macOS (es. `sisma fixtures`) senza anteporre `php`, grazie alla riga shebang. PHP ignora la riga `#!` quando il file viene eseguito tramite `php sisma`, garantendo piena retrocompatibilità.

**File modificati**:
- **`Console/sisma`**: Aggiunta riga `#!/usr/bin/env php` come prima riga del file

#### `Console/sisma.bat` — Nuovo wrapper per Windows

Aggiunto file `sisma.bat` nella stessa directory di `sisma`, che consente di invocare il comando come `sisma fixtures` anche su Windows nativo (senza Docker). Windows riconosce automaticamente l'estensione `.bat` quando il nome del comando è nel `PATH`.

**File aggiunti**:
- **`Console/sisma.bat`**: Wrapper `@php "%~dp0sisma" %*`

### ✅ Backward Compatibility

- **Nessun Breaking Change**: `php sisma <comando>` continua a funzionare invariato su qualsiasi piattaforma.

---

## [11.4.1] - 2026-03-11 - Consolidamento Bootstrap e Estrazione `enableErrorDisplay()` in `ErrorHandler`

Questa patch consolida la gestione del bootstrap nei due entry point del framework. Il `require_once` dell'autoload di Composer viene spostato direttamente nello skeleton di `index.php`, semplificando la procedura d'installazione. La logica di abilitazione degli errori viene estratta in un metodo statico di `ErrorHandler`, eliminando la dipendenza da `LoggerInterface` nel contesto di bootstrap della console.

### ♻️ Refactoring

#### `ErrorHandler` — Estrazione di `enableErrorDisplay()` come metodo statico

La versione 11.4.0 aveva introdotto `showErrorInDevelopmentEnvironment()` come metodo d'istanza, usato anche nello script `sisma`. Questo richiedeva l'istanziazione di `ErrorHandler` e quindi la dipendenza da `Psr\Log\LoggerInterface` (via vendor autoload) già in fase di bootstrap della console, prima ancora di qualsiasi comando. Il blocco `ini_set` è stato estratto nel nuovo metodo statico `enableErrorDisplay()`, senza dipendenze esterne, riutilizzabile sia da `sisma` che internamente da `showErrorInDevelopmentEnvironment()`.

**Modifica**:

- ❌ **11.4.0**: `$errorHandler = new ErrorHandler(); $errorHandler->showErrorInDevelopmentEnvironment();` in `sisma` (richiede vendor autoload)
- ✅ **11.4.1**: `ErrorHandler::enableErrorDisplay();` in `sisma` (metodo statico, nessuna istanza, nessuna dipendenza vendor); `showErrorInDevelopmentEnvironment()` delega internamente a `self::enableErrorDisplay()`

**File modificati**:
- **`Core/HelperClasses/ErrorHandler.php`**: Aggiunto metodo statico `enableErrorDisplay()`; `showErrorInDevelopmentEnvironment()` ora chiama `self::enableErrorDisplay()` al posto del blocco `ini_set` inline
- **`Console/sisma`**: Sostituito blocco `ini_set` con `ErrorHandler::enableErrorDisplay()`; rimosso `require_once vendor/autoload.php`

#### `Public/index.php` — `vendor/autoload.php` incluso nello skeleton

Il `require_once` dell'autoload di Composer veniva iniettato dinamicamente da `InstallationManager::copyPublicFolder()` tramite manipolazione di stringa sul file copiato. Poiché il percorso relativo `dirname(__DIR__) . '/vendor/autoload.php'` è invariante sia nello skeleton (`SismaFramework/vendor/`) sia nel progetto installato (`projectRoot/vendor/`), la riga è ora inclusa direttamente nel file sorgente. L'ordine di caricamento è: autoload SismaFramework prima, autoload vendor dopo.

**File modificati**:
- **`Public/index.php`**: Aggiunto `require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';` dopo il require dell'autoload interno
- **`Console/Services/Installation/InstallationManager.php`**: Rimossa la logica di iniezione dinamica della riga `vendor/autoload.php` in `copyPublicFolder()`

### ✅ Backward Compatibility

- **Nessun Breaking Change**: Il comportamento runtime di entrambi gli entry point rimane identico. `showErrorInDevelopmentEnvironment()` mantiene la stessa firma e semantica.

---

## [11.4.0] - 2026-03-09 - Auto-discovery dei Comandi Console tramite Factory Pattern

Questa minor introduce l'auto-discovery automatico dei comandi console tramite il pattern factory nel `CommandDispatcher`, allineando l'architettura della console a quella del `Dispatcher` HTTP. I comandi non devono più essere registrati manualmente nello script `sisma`: vengono scoperti automaticamente sia nel framework che in tutti i moduli configurati.

### ✨ Nuove Funzionalità

#### `CommandDispatcher` — Auto-discovery dei comandi via factory

Il `CommandDispatcher` registrava i comandi esclusivamente tramite chiamate esplicite ad `addCommandStrategy()` nello script di avvio. Questo approccio richiedeva la modifica manuale del file `sisma` ad ogni aggiunta di un nuovo comando, sia nel framework che nei moduli applicativi.

Ora il costruttore invoca internamente `discoverCommands()`, che scansiona via reflection le directory `Console/Commands/` del framework e di tutti i moduli presenti in `Config::$moduleFolders`, istanziando automaticamente ogni classe concreta che estende `BaseCommand` tramite la nuova classe `CommandFactory`.

**Modifica**:

- ❌ **11.3.x**: I comandi venivano registrati manualmente con `$commandDispatcher->addCommandStrategy(new FooCommand())`
- ✅ **11.4.0**: I comandi vengono scoperti e istanziati automaticamente alla costruzione del `CommandDispatcher`

**File modificati**:
- **`Console/HelperClasses/CommandDispatcher.php`**: Aggiunta dipendenza opzionale `Config`, aggiunti i metodi privati `discoverCommands()` e `discoverFromDirectory()`; il costruttore accetta ora un secondo parametro opzionale `?Config $config = null`
- **`Console/HelperClasses/Dispatcher/CommandFactory.php`**: Nuova classe factory che istanzia comandi tramite reflection, con risoluzione automatica delle dipendenze non primitive del costruttore

#### `Console/sisma` — Rimozione registrazione manuale dei comandi

Lo script di avvio della console registrava esplicitamente tutti i comandi del framework (`FixturesCommand`, `InstallationCommand`, `ScaffoldCommand`, `UpgradeCommand`) tramite `addCommandStrategy()`. Con l'auto-discovery queste chiamate sono ridondanti e sono state rimosse.

Sostituito inoltre il blocco `error_reporting` / `ini_set` con l'utilizzo di `ErrorHandler::showErrorInDevelopmentEnvironment()`, in linea con il resto del framework.

**File modificati**:
- **`Console/sisma`**: Rimossi i `use` e le chiamate `addCommandStrategy()` per i quattro comandi nativi; rimosso blocco `error_reporting`/`ini_set` sostituito da `$errorHandler->showErrorInDevelopmentEnvironment()`

### ✅ Backward Compatibility

- **Nessun Breaking Change**: Il metodo `addCommandStrategy()` è ancora disponibile per la registrazione manuale di comandi aggiuntivi. Il parametro `$config` del costruttore è opzionale e retrocompatibile.

---

## [11.3.6] - 2026-03-08 - Transazione Atomica nell'Esecuzione delle Fixtures e Fix Rollback

Questa patch introduce l'esecuzione atomica delle fixtures tramite una transazione globale nel `FixturesManager`, e corregge il comportamento del `TransactionManager::rollback()` che non verificava lo stato attivo della transazione prima di eseguire il rollback sul database.

### 🐛 Bug Fixes

#### `TransactionManager::rollback()` — Guardia su transazione attiva

Il metodo `rollback()` eseguiva `$this->adapter->rollbackTransaction()` incondizionatamente, senza verificare se una transazione fosse effettivamente aperta. Questo poteva causare un errore del driver database in caso di chiamata su connessione senza transazione attiva.

**Modifica**:

- ❌ **11.3.5**: `$this->adapter->rollbackTransaction();` (incondizionato)
- ✅ **11.3.6**: esecuzione solo se `self::$isActiveTransaction === true`, con reset del flag dopo il rollback

**File modificati**:
- **`Orm/HelperClasses/DataMapper/TransactionManager.php`**: Aggiunta guardia `if (self::$isActiveTransaction)` e reset di `$isActiveTransaction = false` in `rollback()`

### ✨ Miglioramenti

#### `FixturesManager::run()` — Esecuzione atomica tramite transazione globale

L'esecuzione delle fixtures avveniva senza una transazione globale: ogni `save()` apriva e chiudeva la propria transazione autonomamente. In caso di errore a metà esecuzione, i record già inseriti dai fixture precedenti rimanevano nel database.

Ora `run()` apre una transazione prima di eseguire i fixture e la committa solo al termine di tutti. Se un `save()` fallisce internamente, esegue il rollback dell'intera transazione e rilancia l'eccezione, che propaga naturalmente al `sisma` script.

**File modificati**:
- **`Console/Services/Fixtures/FixturesManager.php`**: Aggiunte chiamate `startTransaction()` prima di `executeFixturesArray()` e `commitTransaction()` dopo

#### `Console/sisma` — Path con `DIRECTORY_SEPARATOR`

Il file di avvio della console usava `/` hardcoded per costruire i path di configurazione e autoload, causando potenziali problemi su sistemi Windows.

**File modificati**:
- **`Console/sisma`**: Sostituiti i separatori `/` hardcoded con `DIRECTORY_SEPARATOR` nei path di `configFramework.php`, `config.php` e `autoload.php`

### 🧪 Test

#### `ScaffoldingManagerTest::testDoubleExecution` — Path con `DIRECTORY_SEPARATOR`

Il messaggio di eccezione atteso nel test usava `\` hardcoded per il path, causando il fallimento del test su sistemi Linux/macOS dove il separatore è `/`.

**File modificati**:
- **`Tests/Console/Services/Scaffolding/ScaffoldingManagerTest.php`**: Sostituiti i separatori `\` hardcoded con `DIRECTORY_SEPARATOR` nei messaggi di eccezione attesi

### ✅ Backward Compatibility

- **Nessun Breaking Change**: Le firme dei metodi pubblici restano invariate.

---

## [11.3.5] - 2026-03-04 - Ripristino Compatibilità PHP in ModuleManager

Questa patch ripristina il codice precedente nel metodo `setApplicationModuleByClassName()` della classe `ModuleManager`, rimuovendo l'uso di `array_first()` introdotto involontariamente nella versione 11.3.4. La funzione `array_first()` è disponibile solo a partire da PHP 8.5, incompatibile con il requisito minimo del framework (PHP 8.3).

### 🐛 Bug Fixes

#### Ripristino accesso diretto all'array in `ModuleManager::setApplicationModuleByClassName()`

Il commit della versione 11.3.4 aveva sostituito `$classNameParts[0]` con `array_first($classNameParts)`, funzione introdotta in PHP 8.5 e non disponibile in PHP 8.3 e 8.4.

**Ripristino**:

- ❌ **11.3.4**: `self::setApplicationModule(array_first($classNameParts));`
- ✅ **11.3.5**: `$module = $classNameParts[0];` / `self::setApplicationModule($module);`

**File modificati**:
- **`Core/HelperClasses/ModuleManager.php`**: Ripristinato accesso tramite indice array

### ✅ Backward Compatibility

- **Nessun Breaking Change**: La firma del metodo e il comportamento restano invariati.

---

## [11.3.4] - 2026-03-03 - Impostazione Modulo nella Classe ErrorHandler

Questa patch release corregge un bug per cui la classe `ErrorHandler` non impostava il modulo applicativo prima di invocare i controller di errore, causando un fallimento nella risoluzione delle view di errore.

### 🐛 Bug Fixes

#### Impostazione del modulo nei metodi di gestione errori

I metodi pubblici di `ErrorHandler` chiamavano i controller di errore senza prima impostare il modulo tramite `ModuleManager`, a differenza di quanto avviene nel `Dispatcher`. Il sistema di rendering non riusciva quindi a individuare il percorso corretto delle view.

**Modifiche applicate**:

- **`registerNonThrowableErrorHandler()`**: Aggiunta chiamata `ModuleManager::setApplicationModuleByClassName(get_class($controller))` all'inizio della shutdown function, prima di invocare il controller di errore non-throwable.
- **`handleBaseException()`**: Aggiunta chiamata `ModuleManager::setApplicationModuleByClassName()` in entrambi i branch (`developmentEnvironment` e produzione), rispettivamente con `$structuralController` e `$defaultController` come sorgente del modulo.
- **`handleThrowableError()`**: Aggiunta chiamata `ModuleManager::setApplicationModuleByClassName(get_class($structuralController))` dopo `BufferManager::clear()`, prima del log e dell'invocazione del controller.

**File modificati**:
- **`Core/HelperClasses/ErrorHandler.php`**: Aggiunte 4 chiamate a `ModuleManager::setApplicationModuleByClassName()` nei metodi di gestione errori

### 🧪 Test

#### Correzione `BackupManagerTest` con estensione ZIP non disponibile

Il `tearDown()` accedeva alla proprietà tipizzata `$testDir` anche quando `setUp()` aveva chiamato `markTestSkipped()` prima di inizializzarla (assenza dell'estensione ZIP), causando un errore `Typed property must not be accessed before initialization`.

**Fix**: l'assegnazione di `$testDir` è stata spostata prima del controllo sull'estensione, garantendo che la proprietà sia sempre inizializzata prima che `tearDown()` venga eseguito.

**File modificati**:
- **`Tests/Console/Services/Upgrade/Utils/BackupManagerTest.php`**: Spostata l'inizializzazione di `$testDir` prima di `markTestSkipped()`

### ✅ Backward Compatibility

- **Nessun Breaking Change**: Le firme dei metodi pubblici restano invariate; la modifica aggiunge solo la corretta inizializzazione del modulo prima delle chiamate esistenti.

---

## [11.3.3] - 2026-02-22 - Ripristino Proprietà project nella Classe Config

Questa patch release corregge un errore introdotto nella versione 11.3.2, dove la proprietà `$project` era stata erroneamente rimossa dalla classe `Config` nonostante venisse ancora utilizzata dal `FrameworkController`.

### 🐛 Bug Fixes

#### Ripristino di `Config::$project`

La proprietà `$project` era stata inclusa per errore nell'elenco delle proprietà "orfane" rimosse nella versione 11.3.2. In realtà viene letta a runtime in `FrameworkController::throwableError()` e `FrameworkController::nonThrowableError()` per popolare la variabile di template `$vars['project']` nella pagina di errore visibile.

**File modificati**:
- **`Core/HelperClasses/Config.php`**: Ripristinata la proprietà `protected readonly string $project`

### ✅ Backward Compatibility

- **Nessun Breaking Change**: Ripristino di una proprietà rimossa per errore; nessuna modifica all'interfaccia pubblica.

---

## [11.3.2] - 2026-02-21 - Spostamento Fixtures nella Console

Questa patch release rifattorizza il sistema di esecuzione delle fixtures, spostandolo dal contesto HTTP (Dispatcher) al contesto CLI (Console). Il comportamento delle fixtures resta invariato: cambiano solo il punto di invocazione e la collocazione del codice. Include inoltre la documentazione del sistema di Upgrade introdotto nella versione 11.3.0.

### 🔧 Refactoring

#### Migrazione delle Fixtures dal Dispatcher alla Console

Le fixtures erano una funzionalità nata quando il framework non disponeva di una console CLI. Venivano eseguite tramite un endpoint HTTP (`/fixtures`), integrato nel Dispatcher e nel RouteResolver. Con l'introduzione della console, questa collocazione risultava architetturalmente inadeguata.

**Nuovo comando CLI**:
```bash
php SismaFramework/Console/sisma fixtures
```

**File creati**:
- **`Console/Commands/FixturesCommand.php`**: Nuovo comando che estende `BaseCommand`, registrato nel file `sisma`
- **`Console/Services/Fixtures/FixturesManager.php`**: Logica di gestione fixtures spostata dal Dispatcher alla Console

**File modificati**:
- **`Console/sisma`**: Registrato `FixturesCommand` tra le strategie del `CommandDispatcher`
- **`Core/HelperClasses/Dispatcher.php`**: Rimosso il branch `elseif` per le fixtures nel metodo `handle()`
- **`Core/HelperClasses/Dispatcher/RouteResolver.php`**:
  - Rimossa la dipendenza da `FixturesManager` nel costruttore
  - Rimossi i metodi `isFixturesRequest()` e `runFixtures()`
  - Rimosso il check `isFixtures()` in `parsePathWithMultipleCleanParts()`
- **`Core/HelperClasses/Config.php`**: Rimossa la proprietà `$fixtures` (nome della route HTTP, non più necessario)

**File eliminati**:
- **`Core/HelperClasses/Dispatcher/FixturesManager.php`**: Sostituito dalla versione in `Console/Services/Fixtures/`

**Modifiche al FixturesManager**:
- Il metodo `run()` ora restituisce `void` invece di `Response` (non essendo più in contesto HTTP)
- Rimosso il metodo `isFixtures()` (non più necessario senza routing HTTP)
- Il namespace cambia da `SismaFramework\Core\HelperClasses\Dispatcher` a `SismaFramework\Console\Services\Fixtures`

#### Pulizia proprietà orfane nella classe Config

Rimosse 10 proprietà dalla classe `Config` che non venivano mai lette a runtime tramite `$config->proprietà`. Queste proprietà esistevano come mapping delle corrispondenti costanti in `config.php`, ma nessun codice PHP le accedeva — le costanti servono esclusivamente come building block per la composizione di altre costanti e restano invariate.

**Proprietà rimosse**: `$adapters`, `$assets`, `$cache`, `$core`, `$defaultController`, `$logs`, `$project`, `$resources`, `$thisDirectory`, `$directoryUp`

### ✅ Test

- **`Tests/Console/Services/Fixtures/FixturesManagerTest.php`**: Test spostato dal contesto Core al contesto Console
- **`Tests/Core/HelperClasses/DispatcherTest.php`**: Rimosso `testRunFixture` e tutti i riferimenti a `FixturesManager`
- **`Tests/Core/HelperClasses/DebuggerTest.php`**: Rimosso l'attributo `#[RunTestsInSeparateProcesses]` per incompatibilità con PHPUnit 12
- **`Tests/Core/HelperClasses/ConfigTest.php`**: Aggiornati i test di reflection per riflettere la rimozione delle proprietà orfane

### 📖 Documentazione

- **`docs/upgrade.md`**: Aggiunta guida completa al sistema di Upgrade automatico introdotto nella versione 11.3.0
- **`docs/index.md`**: Aggiunto riferimento alla nuova pagina di documentazione
- **`docs/data-fixtures.md`**: Aggiornata la sezione "Eseguire le Fixtures" da URL browser a comando CLI
- **`docs/helper-classes.md`**: Rimossa la sezione FixturesManager e il relativo riferimento nella tabella panoramica
- **`docs/getting-started.md`**: Aggiornate le istruzioni di esecuzione fixtures da URL a comando CLI
- **`docs/testing.md`**: Aggiornato il namespace di FixturesManager e rimosso l'uso del metodo `isFixtures()` nell'esempio
- **`docs/configuration-reference.md`**: Rimosso il riferimento alle fixtures dalla descrizione di `DEVELOPMENT_ENVIRONMENT`

### ✅ Backward Compatibility

- **Nessun Breaking Change**: Le classi fixture degli utenti (`BaseFixture`, `setEntity()`, `setDependencies()`) restano invariate
- **Nessuna modifica ai file fixture**: La posizione, il namespace e il contratto delle fixture applicative non cambiano
- **Solo il punto di invocazione cambia**: Da `GET /fixtures` (browser) a `php sisma fixtures` (terminale)

---

## [11.3.1] - 2026-02-11 - Correzione Percorsi Cross-Platform nell'Autoloader

Questa patch release corregge un bug nell'Autoloader che impediva il caricamento delle classi mappate tramite `AUTOLOAD_NAMESPACE_MAPPER` e `AUTOLOAD_CLASS_MAPPER` su sistemi Linux/macOS.

### 🐛 Bug Fixes

#### Correzione Conversione Separatori di Directory nei Mapper dell'Autoloader

Corretti i metodi `mapNamespace()` e `mapClass()` in `Autoloader.php` per convertire correttamente i backslash nei percorsi provenienti dalle costanti di configurazione:

*   **Core/HelperClasses/Autoloader.php (`mapNamespace()`)**:
    - ❌ **Prima**: `$this->config->rootPath . $value . str_replace('\\', DIRECTORY_SEPARATOR, $actualClassName) . '.php'`
    - ✅ **Dopo**: `$this->config->rootPath . str_replace('\\', DIRECTORY_SEPARATOR, $value . $actualClassName) . '.php'`
    - La conversione `str_replace('\\', DIRECTORY_SEPARATOR, ...)` veniva applicata solo a `$actualClassName`, ma non a `$value` (il percorso dalla configurazione)

*   **Core/HelperClasses/Autoloader.php (`mapClass()`)**:
    - ❌ **Prima**: `$this->config->rootPath . $this->config->autoloadClassMapper[$this->className] . '.php'`
    - ✅ **Dopo**: `$this->config->rootPath . str_replace('\\', DIRECTORY_SEPARATOR, $this->config->autoloadClassMapper[$this->className]) . '.php'`
    - Il percorso dalla configurazione non veniva convertito affatto

**Scenario del bug**:
1. La configurazione `AUTOLOAD_NAMESPACE_MAPPER` contiene percorsi con backslash (es. `"plugins\PHPMailer\src"`)
2. Su Windows, i backslash funzionano come separatori di directory, mascherando il problema
3. Su Linux/macOS (es. dentro un container Docker), `DIRECTORY_SEPARATOR` è `/`
4. Il percorso risultante conteneva backslash letterali: `/var/www/html/plugins\PHPMailer\src/PHPMailer.php`
5. `file_exists()` falliva perché il percorso non era valido su Linux

**Dopo la correzione**:
- I backslash nei valori di `AUTOLOAD_NAMESPACE_MAPPER` e `AUTOLOAD_CLASS_MAPPER` vengono convertiti in `DIRECTORY_SEPARATOR`
- Le classi vengono caricate correttamente su tutti i sistemi operativi
- Il percorso risultante è corretto: `/var/www/html/plugins/PHPMailer/src/PHPMailer.php`

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: La correzione estende il supporto cross-platform senza modificare il comportamento su Windows
*   **Configurazioni Esistenti**: Funzionano correttamente senza modifiche

### 📊 Impatto

*   **Cross-Platform**: L'autoloader funziona correttamente su Windows, Linux e macOS
*   **Docker**: Risolto il problema del caricamento di classi di terze parti (es. PHPMailer) in ambienti containerizzati

---

## [11.3.0] - 2026-02-08 - Sistema di Upgrade Automatico e Miglioramenti ORM Fulltext

Questa release introduce un sistema completo di upgrade automatico che consente di migrare moduli tra versioni major del framework applicando automaticamente le trasformazioni necessarie per i breaking changes. Inoltre, viene aggiunto il parametro `TextSearchMode` ai metodi di ricerca fulltext dell'ORM, consentendo un controllo esplicito sulla modalità di ricerca testuale.

### ✨ Nuove Funzionalità

* **Sistema di Upgrade Automatico**: Nuovo comando CLI `upgrade` per automatizzare la migrazione dei moduli tra versioni major

  - **UpgradeCommand** (`Console/Commands/UpgradeCommand.php`): Entry point CLI con supporto completo per opzioni
    - `--to=VERSION`: Versione target (obbligatorio)
    - `--from=VERSION`: Versione sorgente (auto-rilevata da module.json se omesso)
    - `--dry-run`: Preview delle modifiche senza applicarle (raccomandato)
    - `--skip-critical`: Salta file critici (Public/index.php, Config)
    - `--skip-backup`: Salta backup automatico (non raccomandato)
    - `--quiet`: Output minimale

  - **UpgradeManager** (`Console/Services/Upgrade/UpgradeManager.php`): Orchestrator principale
    - Fluent Interface per configurazione
    - Gestione completa del ciclo di upgrade: validazione → backup → trasformazione → report
    - Rollback automatico su errore
    - Selezione automatica della strategia di upgrade in base alle versioni

  - **Sistema a Plugin con Strategy Pattern**:
    - `UpgradeStrategyInterface`: Interfaccia per strategie di upgrade
    - `Upgrade10to11Strategy`: Implementazione per migrazione 10.x → 11.0.0
    - Estensibile per future versioni (11.x → 12.0, etc.)

  - **Transformers Chain of Responsibility**:
    - `TransformerInterface`: Interfaccia base per trasformatori di codice
    - `StaticToInstanceTransformer`: Converte chiamate statiche a istanze (ErrorHandler, Debugger)
    - `ReturnTypeTransformer`: Aggiorna signature customFilter() da void a bool + return statements
    - `ResponseConstructorTransformer`: Converte setResponseType() a constructor injection
    - `MethodRenameTransformer`: Rinomina metodi deprecati

  - **Utilities**:
    - `VersionDetector`: Rilevamento e aggiornamento versione framework in module.json
    - `FileScanner`: Scansione intelligente dei file del modulo con categorizzazione
    - `BackupManager`: Creazione backup ZIP + git commit (se disponibile)
    - `ReportGenerator`: Report dettagliati con confidence score e warning

  - **DTO (Data Transfer Objects)**:
    - `TransformationResult`: Risultato di una trasformazione con confidence, warning, modifiche
    - `UpgradeReport`: Report completo di upgrade con statistiche e azioni manuali richieste

* **Sistema di Versionamento Moduli**: Introdotto file `module.json` per tracciare la versione framework di ogni modulo

  ```json
  {
    "name": "ModuleName",
    "version": "1.0.0",
    "framework_version": "11.0.0",
    "description": "Module description",
    "authors": ["Author Name"],
    "requires": {
      "sismaframework": ">=11.0.0"
    }
  }
  ```

* **Approccio Regex-Based Zero-Dependency**: Sistema di trasformazione basato su pattern Regex avanzati senza dipendenze esterne

  | Trasformazione | Confidence | Note |
  |----------------|------------|------|
  | Static→Instance | 70-75% | Alta per index.php, warning per altri file |
  | ReturnType void→bool | 80-85% | Rilevamento automatico indentazione |
  | Response constructor | 65-70% | Warning per pattern complessi |
  | Method renaming | 90% | Alta affidabilità |

### 🛡️ Sicurezza e Affidabilità

* **Backup Automatico**: Creazione automatica di backup ZIP prima di ogni upgrade
* **Git Integration**: Commit automatico pre-upgrade se il progetto usa Git
* **Dry-run Obbligatorio**: Preview sicura prima di applicare modifiche
* **Rollback Automatico**: Ripristino da backup in caso di errore
* **Confidence Scoring**: Ogni trasformazione ha un punteggio di affidabilità
* **Warning System**: Segnalazione esplicita di pattern non riconosciuti

### 📊 Report e Trasparenza

* **Report Dettagliato**: 
  - File modificati con conteggio modifiche
  - Confidence score per file
  - Warning dettagliati
  - Lista azioni manuali richieste
  
* **Report Minimo** (--quiet):
  - Status (✓/◯/✗)
  - File modificati e warning count

### 🔧 Exceptions

* `UpgradeException`: Eccezione generica per errori di upgrade
* `VersionMismatchException`: Versione non valida o strategia non trovata
* `BackupFailedException`: Errore durante backup o rollback

### 📋 Esempi di Utilizzo

```bash
# Preview upgrade (raccomandato come primo step)
php Console/sisma upgrade Blog --to=11.0.0 --dry-run

# Applicazione upgrade dopo review
php Console/sisma upgrade Blog --to=11.0.0

# Upgrade da versione specifica
php Console/sisma upgrade Blog --from=10.1.7 --to=11.0.0

# Salta file critici per review manuale
php Console/sisma upgrade Blog --to=11.0.0 --skip-critical

# Output minimale
php Console/sisma upgrade Blog --to=11.0.0 --quiet
```

### 🔄 Estensibilità

Il sistema è progettato per essere facilmente estensibile:

1. **Nuova Major Version**: Creare `Upgrade11to12Strategy.php` implementando `UpgradeStrategyInterface`
2. **Nuove Trasformazioni**: Creare transformer implementando `TransformerInterface`
3. **Custom Strategies**: Sistema a plugin completamente estensibile

### 🔧 Miglioramenti ORM

#### Aggiunta parametro `TextSearchMode` alla ricerca fulltext

Aggiunto il parametro `TextSearchMode` ai metodi di ricerca fulltext per consentire un controllo esplicito sulla modalità di ricerca testuale:

*   **Orm/HelperClasses/Query.php** (`setFulltextIndexColumn()`):
    - Aggiunto parametro `TextSearchMode $textSearchMode = TextSearchMode::inNaturaLanguageMode`
    - Il parametro consente di specificare la modalità di ricerca fulltext direttamente dalla query

*   **Orm/BaseClasses/BaseAdapter.php**:
    - `opFulltextIndex()`: aggiunto parametro obbligatorio `TextSearchMode $textSearchMode`, rimossi valori di default dai parametri `$value` e `$columnAlias`
    - `fulltextConditionSintax()`: rimosso valore di default dal parametro `TextSearchMode $textSearchMode`, rendendolo obbligatorio

*   **Orm/Adapters/AdapterMysql.php**:
    - Aggiornate le implementazioni di `opFulltextIndex()` e `fulltextConditionSintax()` per propagare il parametro `TextSearchMode`
    - Aggiunta annotazione `@internal` alla classe

**Motivazione**:
- Consente di specificare la modalità di ricerca fulltext (es. Natural Language Mode, Boolean Mode) a livello di query
- Rende esplicita la dipendenza dalla modalità di ricerca, migliorando la leggibilità del codice
- L'adapter non assume più un valore di default per `TextSearchMode`, delegando la scelta al livello superiore (`Query`)

### ⚠️ Limitazioni

* **Confidence 65-85% vs 90%+ con AST**: Approccio Regex ha accuracy inferiore rispetto a parsing AST
* **Pattern Complessi**: Può non riconoscere casi edge (multilinea, commenti, stringhe)
* **Review Manuale**: Sempre necessaria dopo upgrade automatico

### 🛠️ Mitigazioni

* **Dry-run Preview**: Visualizza modifiche prima di applicarle
* **Backup Automatico**: Rollback sempre disponibile
* **Confidence Transparency**: Report chiaro su cosa è stato modificato
* **Warning Espliciti**: Segnala pattern non riconosciuti
* **Manual Actions List**: Lista chiara di cosa richiede intervento manuale

---

## [11.2.0] - 2026-01-30 - Aggiornamento Requisiti PHP e PHPUnit

Questa minor release aggiorna i requisiti minimi del framework a PHP 8.3 e PHPUnit 12, allineandosi con le versioni attivamente supportate e sfruttando le feature moderne del linguaggio già presenti nel codebase. Inoltre, il processo di installazione ora crea automaticamente la struttura del modulo applicativo.

### ✨ Nuove Funzionalità

#### Creazione Automatica Struttura Modulo durante Installazione

Il comando `install` ora crea automaticamente la struttura completa del modulo applicativo:

*   **Console/Services/Installation/InstallationManager.php**:
    - Aggiunta chiamata a `initializeModule($projectName)` nel metodo `install()`
    - La struttura del modulo viene creata automaticamente durante l'installazione

*   **Console/Commands/InstallationCommand.php**:
    - Aggiornato output per mostrare la nuova directory creata: `{$projectName}/Application/`

**Struttura creata automaticamente**:
```
MyProject/
├── Application/
│   ├── Controllers/
│   ├── Entities/
│   ├── Enumerations/
│   ├── Forms/
│   ├── Models/
│   └── Views/
├── Config/
│   └── configFramework.php
├── Public/
│   └── index.php
├── Cache/
├── Logs/
├── filesystemMedia/
├── .htaccess
└── composer.json
```

**Vantaggi**:
- Non è più necessario eseguire un secondo comando per creare il modulo applicativo
- Il progetto è immediatamente pronto per lo sviluppo dopo l'installazione
- Struttura MVC completa creata in un singolo passaggio

### 🔧 Aggiornamenti Requisiti

#### PHP 8.3 come Requisito Minimo

*   **composer.json**:
    - ❌ **Prima**: `"php": ">=8.1.0"`
    - ✅ **Dopo**: `"php": ">=8.3.0"`

**Motivazione**:
- PHP 8.1 ha raggiunto End of Life il 31/12/2025
- PHP 8.2 è in fase di solo supporto sicurezza (fino al 31/12/2026)
- Il codebase utilizza già l'attributo `#[\Override]` (introdotto in PHP 8.3) in 97 file
- Allineamento con le versioni attivamente supportate dalla community PHP

#### PHPUnit 12 come Requisito per i Test

*   **composer.json**:
    - ❌ **Prima**: `"phpunit/phpunit": "^10.0"`
    - ✅ **Dopo**: `"phpunit/phpunit": "^12.0"`

**Motivazione**:
- PHPUnit 10 non è più supportato (supporto terminato a febbraio 2025)
- PHPUnit 12 richiede PHP 8.3+, allineato con il nuovo requisito del framework
- Supporto garantito fino a febbraio 2027

### 🔍 Miglioramenti Qualità Codice

#### Completamento Attributi `#[\Override]`

Aggiunti gli attributi `#[\Override]` mancanti ai metodi che sovrascrivono metodi di classi parent o implementano metodi di interfacce:

*   **Core/CustomTypes/FormFilterErrorCollection.php**: `offsetGet()`
*   **Orm/CustomTypes/SismaDate.php**: `equals()`
*   **Orm/CustomTypes/SismaDateTime.php**: `equals()`
*   **Orm/CustomTypes/SismaTime.php**: `equals()`
*   **Orm/ExtendedClasses/SelfReferencedModel.php**: `__call()`
*   **Orm/BaseClasses/BaseResultSet.php**: `current()`, `next()`, `key()`, `rewind()`, `valid()` (interfaccia `\Iterator`)
*   **Core/HelperClasses/SismaLogger.php**: tutti i metodi PSR-3 (`emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`, `log()`)
*   **Core/HelperClasses/SismaLogReader.php**: `getLog()`, `getLogRowByRow()`, `getLogRowNumber()`, `clearLog()` (interfaccia `LogReaderInterface`)
*   **TestsApplication/Controllers/SampleController.php**: `error()`, `notify()` (interfaccia `DefaultControllerInterface`)

**Vantaggi**:
- PHP 8.3 segnala errori a runtime se un metodo marcato `#[\Override]` non sovrascrive effettivamente un metodo parent
- Migliore documentazione dell'intenzione del codice
- Prevenzione di bug dovuti a refactoring (es. rinomina di metodi in classi parent/interfacce)

### 🧪 Miglioramenti Test Suite

#### Conformità PHPUnit 12

Aggiornati i test per conformarsi alle best practice di PHPUnit 12:

*   **Sostituzione `createMock()` con `createStub()`**: Nei test dove non vengono configurate expectations sui mock objects, è stato utilizzato `createStub()` invece di `createMock()` per evitare PHPUnit notices:
    - `BaseControllerTest.php`
    - `ErrorHandlerTest.php`
    - `RenderServiceTest.php`
    - `RouterServiceTest.php`

#### Riorganizzazione Configurazione PHPUnit

*   **Rinominato e spostato file di configurazione**:
    - ❌ **Prima**: `Tests/configuration.xml`
    - ✅ **Dopo**: `phpunit.xml` (nella root del framework)

*   **Aggiornamenti in `phpunit.xml`**:
    - Schema aggiornato a PHPUnit 12.5
    - Percorso bootstrap corretto: `Tests/bootstrap.php`
    - Tutti i percorsi relativi aggiornati per riflettere la nuova posizione

**Vantaggi**:
- PHPUnit trova automaticamente `phpunit.xml` nella root senza necessità di specificare `-c`
- Convenzione standard seguita dalla maggior parte dei progetti PHP
- Esecuzione test semplificata: `./vendor/bin/phpunit` (senza parametri aggiuntivi)

### 📖 Utilizzo

```bash
# Esecuzione test (PHPUnit trova automaticamente phpunit.xml)
./vendor/bin/phpunit

# Esecuzione test senza code coverage
./vendor/bin/phpunit --no-coverage

# Esecuzione test con coverage (richiede Xdebug)
XDEBUG_MODE=coverage ./vendor/bin/phpunit
```

### ✅ Backward Compatibility

*   **Breaking Change Requisiti Runtime**: Progetti che utilizzano PHP 8.1 o 8.2 devono aggiornare a PHP 8.3+
*   **Nessun Breaking Change API**: Tutte le API pubbliche del framework rimangono invariate
*   **Test Suite**: I test esistenti continuano a funzionare senza modifiche (a meno che non utilizzassero feature deprecate di PHPUnit 10)

### 📚 Aggiornamento Documentazione e Materiali Promozionali

Tutti i riferimenti alla versione PHP sono stati aggiornati da 8.1 a 8.3 nei seguenti file:

*   **README.md**: Badge versione PHP, descrizione e requisiti
*   **docs/*.md**: Documentazione tecnica (installation, overview, best-practices, api-reference, enumerations, traits)
*   **.github/workflows/release.yml**: Versione PHP per CI/CD
*   **Sample/Assets/linkedin-post-text.md**: Testi promozionali per LinkedIn
*   **Sample/SITE_INFO.md**: Documentazione del sito di esempio
*   **Sample/database_setup.sql**: Commenti nei dati di esempio
*   **Sample/Assets/images/*.svg**: Immagini Open Graph e social cards
*   **Sample/Locales/*.json**: File di localizzazione (en_US, it_IT)
*   **Sample/Views/home/index.php**: Homepage del sito di esempio
*   **Sample/Views/commonParts/siteLayout.php**: Meta tag SEO e structured data
*   **Tests/Core/ValidateNewTestsTest.php**: Test di compatibilità versione PHP

### 📊 File Modificati

| File | Tipo | Descrizione |
|------|------|-------------|
| `composer.json` | Modificato | Requisiti PHP ≥8.3, PHPUnit ^12.0 |
| `.gitignore` | Modificato | Aggiunto `composer.lock` |
| `Console/Services/Installation/InstallationManager.php` | Modificato | Aggiunta chiamata a `initializeModule()` |
| `Console/Commands/InstallationCommand.php` | Modificato | Aggiornato output installazione |
| `Core/CustomTypes/FormFilterErrorCollection.php` | Modificato | Aggiunto `#[\Override]` |
| `Core/HelperClasses/SismaLogger.php` | Modificato | Aggiunto `#[\Override]` ai metodi PSR-3 |
| `Core/HelperClasses/SismaLogReader.php` | Modificato | Aggiunto `#[\Override]` |
| `Orm/CustomTypes/SismaDate.php` | Modificato | Aggiunto `#[\Override]` |
| `Orm/CustomTypes/SismaDateTime.php` | Modificato | Aggiunto `#[\Override]` |
| `Orm/CustomTypes/SismaTime.php` | Modificato | Aggiunto `#[\Override]` |
| `Orm/BaseClasses/BaseResultSet.php` | Modificato | Aggiunto `#[\Override]` ai metodi Iterator |
| `Orm/ExtendedClasses/SelfReferencedModel.php` | Modificato | Aggiunto `#[\Override]` |
| `phpunit.xml` | Nuovo (rinominato) | Configurazione PHPUnit spostata nella root |
| `Tests/configuration.xml` | Rimosso | Sostituito da `phpunit.xml` |
| `Tests/Console/Services/Installation/InstallationManagerTest.php` | Modificato | Aggiunto test per verifica creazione modulo |
| `Tests/Core/BaseClasses/BaseControllerTest.php` | Modificato | `createMock` → `createStub` |
| `Tests/Core/HelperClasses/ErrorHandlerTest.php` | Modificato | `createMock` → `createStub` |
| `Tests/Core/Services/RenderServiceTest.php` | Modificato | `createMock` → `createStub` |
| `Tests/Core/Services/RouterServiceTest.php` | Modificato | `createMock` → `createStub` |
| `TestsApplication/Controllers/SampleController.php` | Modificato | Aggiunto `#[\Override]` |

---

## [11.1.0] - 2026-01-21 - Input Interattivo per Configurazione Database

Questa minor release aggiunge la possibilità di configurare i parametri del database in modo interattivo durante l'installazione del framework, migliorando l'esperienza utente senza compromettere la retrocompatibilità.

### ✨ Nuove Funzionalità

#### Input Interattivo da Console

Aggiunto nuovo trait `InteractiveInputTrait` per gestire l'input utente dalla console:

*   **Console/Traits/InteractiveInputTrait.php** (nuovo file):
    - `ask(string $question, ?string $default = null): string` - Richiede input testuale con valore predefinito opzionale
    - `askConfirmation(string $question, bool $default = true): bool` - Richiede conferma Y/N
    - `askSecret(string $question): string` - Richiede input senza echo (password)
    - Supporto cross-platform (Windows/Linux/macOS)

#### Configurazione Database Interattiva

Migliorato `InstallationCommand` con richiesta interattiva dei parametri database:

*   **Console/Commands/InstallationCommand.php**:
    - Aggiunta opzione `--skip-db` per saltare completamente la configurazione database
    - Se non vengono passati parametri da command line, viene avviata la configurazione interattiva
    - L'utente può scegliere se configurare il database (default: No)
    - Parametri richiesti interattivamente: Host, Port, Name, Username, Password
    - I parametri da command line (`--db-host`, `--db-name`, ecc.) hanno priorità sull'input interattivo

### 📖 Utilizzo

```bash
# Installazione con richiesta interattiva database
php SismaFramework/Console/sisma install MyProject

# Installazione senza configurazione database
php SismaFramework/Console/sisma install MyProject --skip-db

# Installazione con parametri da command line (comportamento precedente)
php SismaFramework/Console/sisma install MyProject --db-host=localhost --db-name=mydb --db-user=root
```

**Esempio di output interattivo**:
```
Installing SismaFramework project: MyProject

Database Configuration (optional)
Press Enter to skip each field or use defaults.

Do you want to configure database settings? [y/N]: y
Database Host [127.0.0.1]: localhost
Database Port [3306]: 3306
Database Name []: myproject_db
Database Username []: root
Database Password: ********

Creating project structure...
```

### ✅ Backward Compatibility

*   **100% Retrocompatibile**: Tutti i parametri da command line funzionano esattamente come prima
*   **Comportamento Predefinito**: Se vengono passati parametri `--db-*`, l'input interattivo viene saltato
*   **Nessuna Breaking Change**: Il trait è opzionale e non modifica le API esistenti

### 🔧 Dettagli Tecnici

*   Il trait `InteractiveInputTrait` può essere riutilizzato da altri comandi o moduli
*   Su Windows, `askSecret()` non nasconde l'input (limitazione del sistema)
*   Su Linux/macOS, `askSecret()` utilizza `stty -echo` per nascondere l'input

### 📊 File Modificati

| File | Tipo | Descrizione |
|------|------|-------------|
| `Console/Traits/InteractiveInputTrait.php` | Nuovo | Trait per input interattivo |
| `Console/Commands/InstallationCommand.php` | Modificato | Aggiunta configurazione interattiva DB |

---

## [11.0.5] - 2026-01-21 - Correzione Sostituzione Costanti File Configurazione

Questa patch release corregge un bug nel processo di installazione che impediva la corretta sostituzione delle costanti nel file di configurazione quando queste utilizzavano apici doppi invece di apici singoli.

### 🐛 Bug Fixes

#### Correzione Regex Sostituzione Costanti

Corrette le espressioni regolari in `InstallationManager.php` per supportare sia apici singoli (`'`) che apici doppi (`"`) nella sostituzione delle costanti:

*   **InstallationManager.php (copyConfigFolder)**:
    - ❌ **Prima**: Le regex cercavano solo apici singoli: `/(const\s+PROJECT\s*=\s*')[^']*(')/`
    - ✅ **Dopo**: Le regex supportano entrambi i tipi di apici: `/(const\s+PROJECT\s*=\s*)(['\"])[^'\"]*\\2/`
    - Costanti interessate: `PROJECT`, `APPLICATION`

*   **InstallationManager.php (updateConfigFile)**:
    - ❌ **Prima**: Pattern con apici singoli: `/(const\s+{$key}\s*=\s*')[^']*(')/`
    - ✅ **Dopo**: Pattern con entrambi gli apici: `/(const\s+{$key}\s*=\s*)(['\"])[^'\"]*\\2/`
    - Costanti interessate: `DATABASE_HOST`, `DATABASE_NAME`, `DATABASE_USERNAME`, `DATABASE_PASSWORD`, `DATABASE_PORT` e tutte le altre costanti passate nel parametro `$config`

**Scenario del bug**:
1. Il file `Config/config.php` del framework utilizza apici doppi per le stringhe (es. `const PROJECT = "SismaFramework"`)
2. Le regex in `InstallationManager` cercavano solo apici singoli
3. Durante l'installazione (`php SismaFramework/Console/sisma install MyProject`), le costanti non venivano sostituite
4. Il file `configFramework.php` risultante manteneva i valori originali invece di quelli specificati dall'utente

**Dopo la correzione**:
- Le costanti vengono sostituite correttamente indipendentemente dal tipo di apice usato
- Il processo di installazione funziona sia con file di configurazione che usano apici singoli che doppi
- Maggiore robustezza e compatibilità del processo di installazione

### 🧪 Testing

#### Aggiornamento Test InstallationManager

Aggiornati i test per essere agnostici rispetto al tipo di apice utilizzato:

*   **InstallationManagerTest.php**:
    - **testInstallCopiesConfigFile()**: Modificato per usare `assertMatchesRegularExpression()` invece di `assertStringContainsString()`
    - **testInstallWithDatabaseConfig()**: Modificato per verificare la presenza delle costanti con entrambi i tipi di apice
    - **testInstallWithForceOverwritesExistingConfig()**: Modificato per usare regex nella verifica

**Esempio di verifica aggiornata**:
```php
// ❌ Prima (verificava solo apici singoli):
$this->assertStringContainsString("const PROJECT = 'MyTestProject'", $content);

// ✅ Dopo (verifica entrambi i tipi di apici):
$this->assertMatchesRegularExpression("/const PROJECT = ['\"]MyTestProject['\"]/", $content);
```

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: La correzione estende il supporto senza rimuovere funzionalità esistenti
*   **Installazioni Esistenti**: Progetti installati con versioni precedenti che presentano costanti non sostituite devono essere aggiornati manualmente

### 📊 Impatto

*   **Correttezza**: Le costanti vengono ora sostituite correttamente durante l'installazione
*   **Robustezza**: Il processo di installazione è più resiliente rispetto alle variazioni nel formato del file di configurazione
*   **Compatibilità**: Supporto per entrambe le convenzioni di quotazione delle stringhe in PHP

---

## [11.0.4] - 2026-01-17 - Correzione Percorso File di Log

Questa patch release corregge un bug nel file di configurazione del framework dove la costante `LOG_PATH` puntava a un percorso errato.

### 🐛 Bug Fixes

#### Correzione Costante LOG_PATH

Corretto il percorso del file di log nella configurazione predefinita del framework:

*   **Config/config.php**:
    - ❌ **Prima**: `const LOG_PATH = DIRECTORY_SEPARATOR . 'log.txt';`
    - ✅ **Dopo**: `const LOG_PATH = LOG_DIRECTORY_PATH . "log.txt";`

**Scenario del bug**:
1. La costante `LOG_PATH` era definita come `DIRECTORY_SEPARATOR . 'log.txt'`
2. Questo puntava erroneamente alla root del filesystem (`/log.txt` su Linux, `\log.txt` su Windows)
3. Nella configurazione standard, questa costante non viene modificata durante l'installazione
4. Il file di log non veniva scritto nella posizione corretta (`Sample/Logs/log.txt`)

**Dopo la correzione**:
- `LOG_PATH` utilizza correttamente `LOG_DIRECTORY_PATH` come base del percorso
- Il file di log viene creato nella directory corretta: `{ROOT}/Sample/Logs/log.txt`
- Il sistema di logging funziona correttamente senza necessità di configurazione manuale

### ✅ Backward Compatibility

*   **Installazioni Esistenti**: Progetti che hanno già modificato manualmente `LOG_PATH` nel proprio file di configurazione non sono interessati
*   **Nuove Installazioni**: Funzionano correttamente senza modifiche

### 📊 Impatto

*   **Correttezza**: Il file di log viene ora scritto nella posizione corretta
*   **Funzionalità**: Il sistema di logging funziona out-of-the-box senza configurazione aggiuntiva

---

## [11.0.3] - 2026-01-08 - Correzione Installazione File .htaccess

Questa patch release corregge un bug nel processo di installazione che non copiava il file .htaccess necessario per il reindirizzamento verso la directory Public.

### 🐛 Bug Fixes

#### Copia File .htaccess Durante Installazione

Aggiunta la copia del file `.htaccess` durante il processo di installazione automatica:

*   **InstallationManager.php**:
    - Aggiunto nuovo metodo `copyHtaccessFile()` che copia il file `.htaccess` dalla directory del framework alla root del progetto
    - Il metodo rispetta il flag `--force`: se il file esiste e non viene specificato `--force`, il file non viene sovrascritto
    - Integrato nella sequenza di installazione in `install()` dopo `copyPublicFolder()`

*   **InstallationCommand.php**:
    - Aggiornato l'output del comando per mostrare `.htaccess` tra i file creati

**Scenario del bug**:
1. Utente esegue: `php SismaFramework/Console/sisma install MyProject`
2. Il file `.htaccess` non veniva copiato nella root del progetto
3. Il web server non riusciva a reindirizzare correttamente le richieste verso `Public/index.php`
4. L'applicazione non funzionava correttamente senza configurazione manuale del virtual host

**Dopo la correzione**:
- Il file `.htaccess` viene copiato automaticamente nella root del progetto
- Il file contiene le regole di reindirizzamento verso `Public/` già configurate
- L'applicazione funziona immediatamente senza configurazione aggiuntiva del web server

**Contenuto del file .htaccess**:
```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{QUERY_STRING} fbclid= [NC]
RewriteRule ^(.*)$ /$1? [R=301,L]
RewriteCond %{THE_REQUEST} \ /(.+/)?index\.php\/(.*)$ [NC]
RewriteRule ^(.+/)?index\.php\/(.*)$ /$1$2 [R=301,L]
RewriteCond %{THE_REQUEST} \ /(.+/)?index\.php(.*)$ [NC]
RewriteRule ^(.+/)?index\.php(.*)$ /$1$2 [R=301,L]
RewriteCond %{REQUEST_URI} !^/Public/
RewriteRule ^(.*)$ Public/ [L]
</IfModule>
```

### ✅ Backward Compatibility

*   **Installazioni Esistenti**: Progetti installati con versioni precedenti (11.0.0 - 11.0.2) devono copiare manualmente il file `.htaccess` dal framework o configurare il virtual host per puntare alla directory Public
*   **Nuove Installazioni**: Il file `.htaccess` viene copiato automaticamente

### 📊 Impatto

*   **Facilità d'uso**: Eliminata la necessità di configurazione manuale del web server
*   **Completezza**: Il processo di installazione è ora completo e funzionante out-of-the-box
*   **Sicurezza**: Le regole di reindirizzamento proteggono correttamente i file della root

---

## [11.0.1] - 2026-01-06 - Correzioni Processo di Installazione

Questa patch release corregge tre bug critici nel processo di installazione automatica del framework che causavano errori nella generazione del file composer.json e nel riferimento al file di configurazione.

### 🐛 Bug Fixes

#### Correzione Formato Nome Composer.json

Corretto il formato del nome del progetto nel file composer.json generato durante l'installazione:

*   **InstallationManager.php (createOrUpdateComposerJson)**:
    - ❌ **Prima**: `'name' => strtolower(str_replace(' ', '-', $projectName))` generava solo `"nome-progetto"`
    - ✅ **Dopo**: `'name' => "vendor/{$normalizedName}"` genera correttamente `"vendor/nome-progetto"`
    - Il formato generato è ora conforme allo standard Composer che richiede il formato `vendor/package-name`

**Scenario del bug**:
1. Utente esegue: `php SismaFramework/Console/sisma install MyProject`
2. Il file `composer.json` veniva creato con `"name": "myproject"` invece di `"name": "vendor/myproject"`
3. Questo causava errori durante `composer install` o `composer update` perché il formato non era valido

**Dopo la correzione**:
- Il file `composer.json` ha il formato nome corretto: `"vendor/myproject"`
- Il comando `composer install` funziona senza errori

#### Correzione Percorso configFramework.php in index.php

Corretto il percorso del file di configurazione nel file `Public/index.php` generato durante l'installazione:

*   **InstallationManager.php (copyPublicFolder)**:
    - ❌ **Prima**: La sostituzione cambiava il percorso Config in `'SismaFramework' . DIRECTORY_SEPARATOR . 'Config'`
    - ✅ **Dopo**: Il percorso Config rimane in `'Config'` (root del progetto), solo Autoload viene modificato
    - Il file configFramework.php si trova correttamente in `root/Config/configFramework.php` e non in `root/SismaFramework/Config/configFramework.php`

**Scenario del bug**:
1. Durante l'installazione, il file `Config/configFramework.php` viene creato nella root del progetto
2. Il file `Public/index.php` veniva modificato per cercare il config in `SismaFramework/Config/configFramework.php`
3. L'applicazione non trovava il file di configurazione causando errori fatali

**Dopo la correzione**:
- Il file `Public/index.php` include correttamente `dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'configFramework.php'`
- Il percorso Autoload viene correttamente modificato in `SismaFramework/Autoload`
- L'applicazione trova il file di configurazione e si avvia correttamente

#### Correzione ROOT_PATH in configFramework.php

Corretto il numero di livelli di risalita della costante ROOT_PATH nel file di configurazione installato:

*   **InstallationManager.php (copyConfigFolder)**:
    - ❌ **Prima**: La costante ROOT_PATH manteneva la risalita di due livelli del file originale del framework
    - ✅ **Dopo**: ROOT_PATH viene modificata per risalire di un solo livello
    - Aggiunta sostituzione: `__DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_UP . DIRECTORY_SEPARATOR . DIRECTORY_UP` → `__DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_UP`

**Scenario del bug**:
1. Il file originale `SismaFramework/Config/config.php` si trova in `root/SismaFramework/Config/config.php`
2. La ROOT_PATH sale di due livelli: `SismaFramework/Config` → `SismaFramework` → `root` (corretto per il framework)
3. Il file installato `Config/configFramework.php` si trova in `root/Config/configFramework.php`
4. Con due livelli di risalita: `Config` → `root` → `parent` (errato)
5. Con un livello di risalita: `Config` → `root` (corretto)

**Dopo la correzione**:
- La costante ROOT_PATH punta correttamente alla root del progetto
- Tutti i percorsi derivati (cache, log, etc.) funzionano correttamente

### 🧪 Testing

#### Aggiornamento Test Suite

Aggiornati i test per riflettere le correzioni apportate:

*   **InstallationManagerTest.php**:
    - **testInstallCopiesPublicFolder()**: 
        - ❌ **Prima**: Verificava presenza di `'SismaFramework' . DIRECTORY_SEPARATOR . 'Config'`
        - ✅ **Dopo**: Verifica presenza di `'Config' . DIRECTORY_SEPARATOR . 'configFramework.php'`
    
    - **testInstallCreatesComposerJson()**:
        - ❌ **Prima**: Verificava nome come `'mytestproject'`
        - ✅ **Dopo**: Verifica nome come `'vendor/mytestproject'`

*   ✅ **Tutti i test passano correttamente**

### ✅ Backward Compatibility

*   **Installazioni Esistenti**: Progetti installati con versioni precedenti (11.0.0) devono:
    1. Aggiornare manualmente `composer.json` per aggiungere il prefisso `vendor/`
    2. Verificare che `Public/index.php` punti a `Config/configFramework.php` e non a `SismaFramework/Config/configFramework.php`
    3. Verificare che ROOT_PATH in `Config/configFramework.php` salga di un solo livello

*   **Nuove Installazioni**: Funzionano correttamente senza necessità di modifiche manuali

### 📊 Impatto

*   **Correttezza**: Eliminati tre bug critici nel processo di installazione
*   **Conformità**: File composer.json conforme allo standard Composer
*   **Stabilità**: Applicazioni installate funzionano immediatamente senza errori
*   **Manutenibilità**: Ridotta necessità di interventi manuali post-installazione

---

## [11.0.0] - 2026-01-02 - Rifattorizzazione Architetturale e Semplificazione API

Questa major release introduce miglioramenti architetturali significativi: rifattorizzazione completa di BaseForm con principi SOLID, semplificazione API Response attraverso rimozione del metodo pubblico setResponseType(), e implementazione completa dello standard PSR-3 per il logging con supporto per logger di terze parti.

La release introduce breaking changes: il metodo astratto customFilter() di BaseForm ora ritorna bool invece di void, il metodo pubblico setResponseType() di Response è stato rimosso in favore dell'immutabilità tramite constructor injection, e le classi ErrorHandler e Debugger sono state trasformate da statiche a di istanza per migliorare testabilità e dependency injection.

### 🏗️ Architettura

* **Rifattorizzazione Completa BaseForm con Principi SOLID**: La classe `BaseForm` è stata completamente rifattorizzata applicando il Single Responsibility Principle, con estrazione delle responsabilità in classi dedicate:

  - **`FilterManager`** (`Core/BaseClasses/BaseForm/FilterManager.php`): Gestisce la configurazione e l'applicazione dei filtri di validazione
    - Metodo `addFilterFieldMode()`: Registra la configurazione del filtro per una proprietà
    - Metodo `hasFilter()`: Verifica se una proprietà ha un filtro configurato
    - Metodo `getFilterConfig()`: Recupera la configurazione completa di un filtro
    - Metodo `applyFilter()`: Applica il filtro a un valore specifico
    - Metodo `isNullable()`: Verifica se una proprietà accetta valori null
    - Metodo `getAllFilteredPropertyNames()`: Ritorna l'elenco di tutte le proprietà con filtri configurati

  - **`FormValidator`** (`Core/BaseClasses/BaseForm/FormValidator.php`): Responsabile della validazione completa del form
    - Gestisce la validazione di proprietà standard, entità referenziate e collezioni
    - Applica i filtri configurati e popola gli errori di validazione
    - Gestisce la parsing di proprietà complesse (foreign keys, self-referenced entities, collections)
    - Ritorna un array con `entityData` (StandardEntity validato) e `filterResult` (bool)
    - Supporta dependency injection di `DataMapper`, `FilterManager` e `Config`

  - **`EntityResolver`** (`Core/BaseClasses/BaseForm/EntityResolver.php`): Gestisce la risoluzione e il popolamento delle entità a partire dai dati validati
    - Metodo `resolveEntity()`: Popola l'entità con i dati validati dal form
    - Gestisce la risoluzione di entità nidificate tramite form
    - Gestisce la risoluzione di SismaCollection con entità multiple
    - Distingue tra proprietà semplici, entità referenziate e collezioni

  **Vantaggi della rifattorizzazione**:
  - Ogni classe ha una singola, chiara responsabilità (SRP)
  - Codice più testabile con dipendenze iniettabili
  - Migliore leggibilità e manutenibilità
  - Facilita l'estensione con validatori o filtri custom
  - Riduce la complessità della classe `BaseForm` da oltre 400 linee a circa 200

* **Dependency Injection in BaseForm**: Il costruttore di `BaseForm` ora accetta le nuove classi helper come parametri opzionali:
  ```php
  public function __construct(
      ?BaseEntity $baseEntity = null,
      DataMapper $dataMapper = new DataMapper(),
      FilterManager $filterManager = new FilterManager(),
      ?FormValidator $formValidator = null,
      EntityResolver $entityResolver = new EntityResolver()
  )
  ```
  Questo permette di iniettare implementazioni custom per testing o estensioni.

* **Implementazione Standard PSR-3 per Logging**: Il sistema di logging del framework è stato completamente rifattorizzato per aderire allo standard PSR-3 (PHP Standards Recommendation 3), permettendo l'integrazione di logger di terze parti:

  - **`SismaLogger`** (`Core/HelperClasses/SismaLogger.php`): Nuova classe che implementa `Psr\Log\LoggerInterface`
    - Implementa tutti i metodi PSR-3: `emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`
    - Metodo `log()` con supporto completo per interpolazione dei placeholder (`{key}`) secondo PSR-3
    - Supporto per context array con chiavi riservate: `code`, `file`, `line`, `trace`
    - Gestione automatica di trace per debug approfondito
    - Dependency injection di `Locker` e `Config` per massima testabilità
  
  - **`SismaLogReader`** (`Core/HelperClasses/SismaLogReader.php`): Nuova classe per la lettura strutturata dei log
    - Implementa `LogReaderInterface` per permettere implementazioni custom
    - Metodo `getLogRowByRow()` per lettura riga per riga dei file di log
    - Integrato con `Debugger` per visualizzazione nella debug bar
  
  - **`LogReaderInterface`** (`Core/Interfaces/Logging/LogReaderInterface.php`): Nuova interfaccia per astrazione lettori di log
  
  - **`ShouldBeLoggedException`** (`Security/Interfaces/Exceptions/ShouldBeLoggedException.php`): Nuova marker interface
    - Permette alle eccezioni di dichiarare esplicitamente se devono essere loggate
    - `ErrorHandler` verifica automaticamente se un'eccezione implementa questa interfaccia
    - Separazione delle responsabilità: le eccezioni decidono autonomamente il loro comportamento di logging

  **Vantaggi dell'implementazione PSR-3**:
  - **Interoperabilità**: Possibilità di sostituire `SismaLogger` con qualsiasi logger PSR-3 compatibile (Monolog, Log4php, etc.)
  - **Standard de facto**: Conformità allo standard più diffuso nell'ecosistema PHP
  - **Dependency Injection**: Logger iniettabile via costruttore in `ErrorHandler`, `BaseAdapter`, e altre classi
  - **Context-aware**: Supporto per metadati contestuali tramite array `$context`
  - **Testing facilitato**: Possibilità di iniettare logger mock nei test
  - **Compatibilità framework**: Integrazione semplificata con framework di terze parti

  **Esempio di utilizzo con logger custom**:
  ```php
  use Monolog\Logger;
  use Monolog\Handler\StreamHandler;
  
  // Logger di terze parti (Monolog)
  $monolog = new Logger('app');
  $monolog->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));
  
  // Injection in ErrorHandler
  $errorHandler = new ErrorHandler(logger: $monolog);
  $errorHandler->registerNonThrowableErrorHandler();
  ```

* **Trasformazione ErrorHandler e Debugger da Statiche a di Istanza**: Le classi `ErrorHandler` e `Debugger` sono state completamente rifattorizzate da classi con metodi statici a classi di istanza con dependency injection:

  - **`ErrorHandler`** (`Core/HelperClasses/ErrorHandler.php`):
    - ❌ **Prima (10.x)**: Tutti i metodi erano statici: `ErrorHandler::disableErrorDisplay()`, `ErrorHandler::handleBaseException()`
    - ✅ **Dopo (11.0.0)**: Classe di istanza con costruttore che accetta `LoggerInterface` e `Config`
    - Metodi rinominati per chiarezza: `handleNonThrowableError()` → `registerNonThrowableErrorHandler()`
    - Nuovo metodo `handleBaseException()` che verifica `ShouldBeLoggedException` prima di loggare
    - Nuovo metodo `handleThrowableError()` per gestione generica di `Throwable`
  
  - **`Debugger`** (`Core/HelperClasses/Debugger.php`):
    - ❌ **Prima (10.x)**: Metodi statici con stato globale condiviso
    - ✅ **Dopo (11.0.0)**: Classe di istanza con costruttore che accetta `LogReaderInterface`
    - Dependency injection di `SismaLogReader` (o implementazioni custom)
    - Stato isolato per istanza, permettendo multiple istanze di debugger
  
  - **Aggiornamento `Public/index.php`**: Modificato il bootstrap dell'applicazione per utilizzare le nuove classi di istanza:
    ```php
    // Prima (10.x):
    ErrorHandler::disableErrorDisplay();
    ErrorHandler::handleNonThrowableError();
    Debugger::startExecutionTimeCalculation();
    
    // Dopo (11.0.0):
    $errorHandler = new ErrorHandler();
    $errorHandler->disableErrorDisplay();
    $errorHandler->registerNonThrowableErrorHandler();
    $debugger = new Debugger();
    $debugger->startExecutionTimeCalculation();
    $dispatcher = new Dispatcher(debugger: $debugger);
    ```
  
  **Motivazione della trasformazione**:
  - **Testabilità**: Possibilità di iniettare mock di logger e config nei test
  - **Dependency Injection**: Pattern moderno che favorisce loose coupling
  - **Stato Isolato**: Eliminazione dello stato globale condiviso
  - **SOLID Principles**: Conformità al Dependency Inversion Principle
  - **Flessibilità**: Possibilità di avere multiple istanze con configurazioni diverse

* **Dependency Injection in BaseAdapter**: La classe `BaseAdapter` e le sue sottoclassi (`AdapterMysql`) ora accettano `LoggerInterface` via costruttore per logging delle query SQL e degli errori di connessione database.

* **Implementazione Pattern Facade per Render e Router**: Le classi `Render` e `Router` sono state rifattorizzate implementando il pattern Facade combinato con Singleton, separando la logica di business in Service classes dedicate:

  - **`RenderService`** (`Core/Services/RenderService.php`): Nuova classe singleton che contiene tutta la logica di rendering delle view
    - Pattern Singleton con metodi `getInstance()`, `setInstance()`, `resetInstance()`
    - Metodi per rendering: `generateView()`, `generateData()`, `generateJson()`
    - Gestione completa del ciclo di vita del rendering (assembly componenti, device detection, debug bar)
    - Supporto per view strutturali e modulari
    - Dependency injection di `Localizator`, `Debugger`, `Config` per massima testabilità
  
  - **`RouterService`** (`Core/Services/RouterService.php`): Nuova classe singleton che gestisce tutte le operazioni di routing
    - Pattern Singleton con metodi `getInstance()`, `setInstance()`, `resetInstance()`
    - Gestione URL: `redirect()`, `concatenateMetaUrl()`, `setMetaUrl()`, `getMetaUrl()`
    - Gestione route: `setActualCleanUrl()`, `getControllerUrl()`, `getActionUrl()`, `getActualCleanUrl()`
    - Metodi di utilità: `getRootUrl()`, `getActualUrl()`, `resetMetaUrl()`, `reloadWithParsedQueryString()`
    - Stato incapsulato in istanza singleton invece di proprietà statiche
  
  - **`Render` come Facade** (`Core/HelperClasses/Render.php`): La classe `Render` è ora una facade pura che delega a `RenderService`
    - Implementa `__callStatic()` per chiamate statiche: `Render::generateView()` → `RenderService::getInstance()->generateView()`
    - Implementa `__call()` per chiamate di istanza: `$render->generateView()` → `RenderService::getInstance()->generateView()`
    - Retrocompatibilità totale: tutte le chiamate esistenti continuano a funzionare
    - Zero logica di business: solo delegazione al service sottostante
  
  - **`Router` come Facade** (`Core/HelperClasses/Router.php`): La classe `Router` è ora una facade pura che delega a `RouterService`
    - Implementa `__callStatic()` per chiamate statiche: `Router::redirect()` → `RouterService::getInstance()->redirect()`
    - Implementa `__call()` per chiamate di istanza: `$router->redirect()` → `RouterService::getInstance()->redirect()`
    - Retrocompatibilità totale: tutte le chiamate esistenti continuano a funzionare
    - Zero logica di business: solo delegazione al service sottostante

  **Vantaggi del Pattern Facade + Singleton**:
  - **Flessibilità di utilizzo**: Possibilità di usare sia sintassi statica (`Render::generateView()`) che di istanza (`$this->render->generateView()`)
  - **Dependency Injection nei Controller**: `BaseController` ora ha proprietà `$this->render` e `$this->router` utilizzabili come istanze
  - **Testabilità**: Metodi `setInstance()` permettono di iniettare mock nei test
  - **Isolamento dello stato**: Lo stato è incapsulato nel singleton invece di proprietà statiche sparse
  - **Retrocompatibilità al 100%**: Nessun breaking change, tutto il codice esistente continua a funzionare
  - **Separazione delle responsabilità**: Facade (interfaccia pubblica) separato da Service (logica di business)
  - **Facilità di testing**: Metodo `resetInstance()` permette di resettare lo stato nei test

  **Utilizzo nei Controller**:
  ```php
  class ProductController extends BaseController
  {
      public function index(): Response
      {
          // Sintassi di istanza (nuovo, preferito):
          return $this->render->generateView('product/index', $this->vars);
          
          // Sintassi statica (legacy, ancora supportato):
          return Render::generateView('product/index', $this->vars);
      }
      
      public function create(): Response
      {
          // Sintassi di istanza per router:
          return $this->router->redirect('product/list');
          
          // Sintassi statica (legacy):
          return Router::redirect('product/list');
      }
  }
  ```

* **Proprietà Render e Router in BaseController**: La classe `BaseController` ora inizializza le proprietà `$this->render` e `$this->router` nel costruttore:
  ```php
  protected RenderService $router;
  protected RenderService $render;
  
  public function __construct(DataMapper $dataMapper = new DataMapper(), Debugger $debugger = new Debugger())
  {
      $this->dataMapper = $dataMapper;
      $this->debugger = $debugger;
      $this->router = RouterService::getInstance();
      $this->render = RenderService::getInstance();
      // ...
  }
  ```
  Questo permette di utilizzare `$this->render` e `$this->router` come istanze in tutti i controller che estendono `BaseController`.

### 💥 Breaking Changes

* **Rimozione Metodo Response::setResponseType()**: Il metodo pubblico setResponseType() è stato rimosso dalla classe Response

  **Prima (10.x)**:
  ```php
  $response = new Response();
  $response->setResponseType(ResponseType::httpNotFound);
  ```

  **Dopo (11.0.0)**:
  ```php
  $response = new Response(ResponseType::httpNotFound);
  ```

  **Motivazione**:
  - Promuove immutabilità: un oggetto Response dovrebbe nascere con un tipo e mantenerlo
  - Proprietà `$responseType` ora `readonly` (PHP 8.1+) per garantire immutabilità a livello di linguaggio
  - Semplifica API: constructor injection è più pulito e type-safe
  - Elimina metodi non utilizzati: nessun codice nel framework usava setResponseType() dopo creazione oggetto
  - Migliora testabilità: stato dell'oggetto più prevedibile

  **Impatto**: Il metodo setResponseType() non è più disponibile. Utilizzare il costruttore per impostare il response type.

  **Azione richiesta**:
  - Sostituire chiamate a setResponseType() passando il ResponseType al costruttore
  - Se necessario modificare il response type, creare una nuova istanza di Response

* **Modifica Firma Metodo `customFilter()`**: Il metodo astratto `customFilter()` ora ritorna `bool` invece di `void`

  **Prima (10.x)**:
  ```php
  abstract protected function customFilter(): void;
  ```

  **Dopo (11.0.0)**:
  ```php
  abstract protected function customFilter(): bool;
  ```

  **Motivazione**: Il nuovo tipo di ritorno `bool` permette al metodo `customFilter()` di contribuire al risultato finale di validazione del form. Ritornando `true` se la validazione custom ha successo o `false` in caso di errori, si ottiene un'API più coerente e un flusso di validazione più chiaro.

  **Impatto**: Tutte le classi che estendono `BaseForm` devono essere aggiornate per ritornare un valore booleano dal metodo `customFilter()`.

  **Azione richiesta**:
  - Aggiungere `return true;` alla fine del metodo `customFilter()` se non ci sono errori di validazione custom
  - Ritornare `false` quando la validazione custom fallisce
  - Esempio:
    ```php
    // Prima (10.x):
    protected function customFilter(): void
    {
        if ($this->entity->startDate > $this->entity->endDate) {
            $this->formFilterError->startDateError = true;
        }
    }

    // Dopo (11.0.0):
    protected function customFilter(): bool
    {
        if ($this->entity->startDate > $this->entity->endDate) {
            $this->formFilterError->startDateError = true;
            return false;
        }
        return true;
    }
    ```

* **Trasformazione da Metodi Statici a Istanze**: Le classi `ErrorHandler` e `Debugger` non sono più utilizzabili con chiamate statiche. È necessario creare istanze di queste classi.

  **Prima (10.x)**:
  ```php
  ErrorHandler::disableErrorDisplay();
  ErrorHandler::handleNonThrowableError();
  Debugger::startExecutionTimeCalculation();
  ```

  **Dopo (11.0.0)**:
  ```php
  $errorHandler = new ErrorHandler();
  $errorHandler->disableErrorDisplay();
  $errorHandler->registerNonThrowableErrorHandler();
  $debugger = new Debugger();
  $debugger->startExecutionTimeCalculation();
  ```

  **Motivazione**: 
  - Eliminazione dello stato globale
  - Miglioramento della testabilità attraverso dependency injection
  - Conformità ai principi SOLID
  - Possibilità di iniettare logger custom conformi a PSR-3

  **Impatto**: Il file `Public/index.php` deve essere aggiornato per creare istanze delle classi invece di usare metodi statici. Tutte le chiamate statiche a `ErrorHandler` e `Debugger` devono essere convertite a chiamate di istanza.

  **Azione richiesta**:
  - Aggiornare il file `Public/index.php` per creare istanze di `ErrorHandler` e `Debugger`
  - Se si desidera utilizzare un logger custom (es. Monolog), iniettarlo nel costruttore di `ErrorHandler`
  - Verificare che non esistano altre chiamate statiche a queste classi nel codebase

* **Correzione Typo nel Metodo `Encryptor::createInitializationVector()`**: Il metodo `createInizializationVector()` è stato rinominato in `createInitializationVector()` per correggere l'errore di spelling.

  **Prima (10.x)**:
  ```php
  $iv = Encryptor::createInizializationVector();
  ```

  **Dopo (11.0.0)**:
  ```php
  $iv = Encryptor::createInitializationVector();
  ```

  **Motivazione**: Correzione di un typo nel nome del metodo per migliorare la coerenza del codebase e facilitare l'uso dell'API.

  **Impatto**: Questo è un potenziale breaking change se il metodo veniva chiamato direttamente nel codice utente. Il metodo è principalmente utilizzato internamente dal framework (in `DataMapper` per proprietà crittografate), ma potrebbe essere stato usato in codice custom per crittografia manuale.

  **Azione richiesta**:
  - Cercare tutte le occorrenze di `createInizializationVector` nel proprio codebase
  - Sostituire con `createInitializationVector` (con la "t" invece della "z")
  - Verificare il corretto funzionamento delle operazioni di crittografia

### ✨ Miglioramenti

* **Messaggi di Eccezione Descrittivi in BaseForm**: Tutte le eccezioni lanciate dalla classe `BaseForm` ora includono messaggi descrittivi che spiegano chiaramente il problema:
  - `FormException`: "Entity name returned by getEntityName() must be a subclass of BaseEntity"
  - `InvalidArgumentException`: "BaseEntity parameter must be an instance of {EntityClassName} or null"

  Questo facilita il debugging e rende più chiaro agli sviluppatori il motivo degli errori di configurazione.

### 🧪 Testing

* **Test Aggiornati per BaseForm**: Aggiornati tutti i test esistenti per riflettere la nuova firma del metodo `customFilter()`:
  - `BaseFormTest.php`: Aggiornato per testare il valore di ritorno booleano
  - Creato nuovo test `FormWithCustomFilterFalse.php` per verificare il comportamento quando `customFilter()` ritorna `false`
  - Tutti i form di test nell'applicazione di test aggiornati con la nuova firma

* **Copertura Completa Nuove Classi**: Le tre nuove classi helper (`EntityResolver`, `FilterManager`, `FormValidator`) sono completamente testate attraverso i test esistenti di `BaseForm`, garantendo che la rifattorizzazione non abbia introdotto regressioni.

* **Test Aggiornati per Response**: Rimossi tutti i test che utilizzavano setResponseType(), sostituiti con test per constructor injection:
  - ResponseTest.php: Ridotto da 13 a 6 test, focalizzati sul costruttore
  - Aggiunto testConstructorWithVariousResponseTypes() che testa tutti i ResponseType principali inclusi 206 e 416

* **Test Completi per Sistema di Logging PSR-3**: Aggiunti test completi per verificare la corretta implementazione dello standard PSR-3 e la gestione delle eccezioni loggabili:
  
  - **`LoggerTest.php`**: Test completi per la classe `SismaLogger`
    - Verifica implementazione di tutti i metodi PSR-3: `emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`
    - Test interpolazione placeholder secondo standard PSR-3 (`{key}`)
    - Verifica supporto context array con chiavi riservate (`code`, `file`, `line`, `trace`)
    - Test gestione trace per debug approfondito
    - Verifica creazione automatica directory e file di log
    - Test truncate automatico dei log secondo configurazione
  
  - **`ErrorHandlerTest.php`**: Test completi per la nuova classe `ErrorHandler` di istanza
    - Test gestione eccezioni che implementano `ShouldBeLoggedException`
    - Verifica che eccezioni loggabili vengano effettivamente scritte nel log
    - Test che eccezioni non loggabili (es. `NoLogException`) non vengano scritte nel log
    - Verifica dependency injection di `LoggerInterface` custom
    - Test registrazione handler per errori non throwable
    - Verifica gestione corretta di `BaseException` e `Throwable` generici
  
  - **`TestLoggableException.php`**: Fixture di test per eccezione che implementa `ShouldBeLoggedException`
  
  - **`TestNonLoggableException.php`**: Fixture di test per eccezione che non implementa l'interfaccia

* **Test Aggiornati per Debugger**: Aggiornati tutti i test per utilizzare la nuova classe di istanza invece dei metodi statici:
  - `DebuggerTest.php`: Modificato per creare istanze di `Debugger` con dependency injection di `LogReaderInterface`
  - Verifica isolamento dello stato tra multiple istanze di debugger

* **Test Completi per Pattern Facade Render e Router**: Aggiunti test completi per verificare il corretto funzionamento del pattern Facade e dei Service sottostanti:
  
  - **`RenderServiceTest.php`** (259 linee): Test completi per la classe `RenderService`
    - Test pattern Singleton: verifica che `getInstance()` ritorni sempre la stessa istanza
    - Test `setInstance()` e `resetInstance()` per dependency injection nei test
    - Test rendering view con diverse configurazioni (view standard, view strutturali)
    - Test `generateView()`, `generateData()`, `generateJson()` con vari parametri
    - Verifica generazione debug bar in development environment
    - Test device detection (mobile/desktop)
    - Test integrazione con `Localizator`, `Debugger`, `Config`
    - Test gestione path view modulari e strutturali
  
  - **`RouterServiceTest.php`** (195 linee): Test completi per la classe `RouterService`
    - Test pattern Singleton con `getInstance()`, `setInstance()`, `resetInstance()`
    - Test `redirect()` con vari scenari di URL
    - Test `concatenateMetaUrl()`, `setMetaUrl()`, `getMetaUrl()`
    - Test `setActualCleanUrl()`, `getControllerUrl()`, `getActionUrl()`, `getActualCleanUrl()`
    - Test `getRootUrl()`, `getActualUrl()` con diverse configurazioni di server
    - Test `resetMetaUrl()` per reset dello stato
    - Test `reloadWithParsedQueryString()` per parsing query string in URL
    - Verifica isolamento dello stato tra reset delle istanze
  
  - **`BaseControllerTest.php`**: Esteso con nuovi test per le proprietà `$render` e `$router`
    - Test che `$this->render` sia istanza di `RenderService`
    - Test che `$this->router` sia istanza di `RouterService`
    - Verifica inizializzazione corretta nel costruttore di `BaseController`
    - Test che le variabili di routing (`controllerUrl`, `actionUrl`, `metaUrl`, etc.) siano popolate correttamente

**Copertura totale**: +603 linee di test per garantire affidabilità del nuovo pattern architetturale.

### 📝 Documentazione

* **Classi Marcate @internal**: Le tre nuove classi helper sono marcate con l'annotazione `@internal` per indicare che fanno parte dell'implementazione interna di `BaseForm` e non dovrebbero essere utilizzate direttamente dagli sviluppatori.

### 🔄 Compatibilità

**Questa è una major release (11.0.0)** che introduce breaking changes. L'aggiornamento richiede modifiche al codice esistente:

- ⚠️ **Richiesta modifica**: Tutte le classi che estendono `BaseForm` devono aggiornare il metodo `customFilter()` per ritornare `bool`
- ⚠️ **Richiesta modifica**: Il file `Public/index.php` deve essere aggiornato per creare istanze di `ErrorHandler` e `Debugger` invece di usare metodi statici
- ✅ **Retrocompatibilità API BaseForm**: Tutti gli altri metodi pubblici e protetti di `BaseForm` mantengono la stessa interfaccia
- ✅ **Retrocompatibilità API Response**: Constructor injection mantiene compatibilità con chiamate esistenti a `new Response()`
- ✅ **Retrocompatibilità totale Render/Router**: Il pattern Facade garantisce che tutte le chiamate esistenti (statiche o di istanza) continuino a funzionare senza modifiche
- ✅ **Nessun impatto su DataMapper/ORM**: Le modifiche sono isolate ai sistemi di form, logging, rendering e routing
- 💡 **Nuova sintassi preferita**: Nei controller, preferire `$this->render->generateView()` e `$this->router->redirect()` invece della sintassi statica legacy

### 📋 Checklist di Migrazione da 10.x a 11.0.0

- [ ] **Form con customFilter()**
  - [ ] Aggiungere tipo di ritorno `: bool` alla firma del metodo `customFilter()`
  - [ ] Ritornare `true` quando la validazione custom ha successo
  - [ ] Ritornare `false` quando la validazione custom fallisce
  - [ ] Verificare che la logica di validazione custom sia corretta

- [ ] **Utilizzo di Response**
  - [ ] Cercare tutte le occorrenze di ->setResponseType(
  - [ ] Sostituire con constructor injection: new Response(ResponseType::...)
  - [ ] Se necessario modificare response type, creare nuova istanza invece di chiamare metodo

- [ ] **ErrorHandler e Debugger - Trasformazione a Istanze**
  - [ ] Aggiornare `Public/index.php` per creare istanze invece di chiamare metodi statici
  - [ ] Sostituire `ErrorHandler::disableErrorDisplay()` con `$errorHandler = new ErrorHandler(); $errorHandler->disableErrorDisplay();`
  - [ ] Sostituire `ErrorHandler::handleNonThrowableError()` con `$errorHandler->registerNonThrowableErrorHandler();`
  - [ ] Sostituire `Debugger::startExecutionTimeCalculation()` con `$debugger = new Debugger(); $debugger->startExecutionTimeCalculation();`
  - [ ] Iniettare `$debugger` nel costruttore di `Dispatcher`: `new Dispatcher(debugger: $debugger)`
  - [ ] Sostituire tutte le chiamate statiche a `ErrorHandler::handleBaseException()` con `$errorHandler->handleBaseException()`
  - [ ] Sostituire `ErrorHandler::handleThrowableError()` con `$errorHandler->handleThrowableError()`
  - [ ] (Opzionale) Se si desidera usare un logger custom PSR-3, iniettarlo nel costruttore di ErrorHandler

- [ ] **Testing**
  - [ ] Eseguire tutti i test unitari
  - [ ] Verificare che i form funzionino correttamente in tutti i flussi
  - [ ] Testare sia casi di validazione con successo che con fallimento
  - [ ] Verificare che tutti i response codes siano impostati correttamente
  - [ ] Verificare che il logging funzioni correttamente con il nuovo sistema PSR-3
  - [ ] Se si usa un logger custom, testare l'integrazione

---


## [10.1.7] - 2025-12-21 - Correzione Bug buildPropertiesConditions e Test Suite

Questa patch release corregge un bug critico introdotto nella versione 10.1.0 nel metodo `buildPropertiesConditions` di `DependentModel` e `SelfReferencedModel`, che impediva il corretto override del metodo di `BaseModel`. Inoltre corregge errori sistematici nella test suite che utilizzavano nomi di proprietà in formato snake_case invece di camelCase.

### 🐛 Bug Fixes

#### Correzione Typo Nome Metodo buildPropertiesConditions

Corretto un errore di battitura nel nome del metodo introdotto nella versione 10.1.0 che impediva l'override corretto del metodo di `BaseModel`:

*   **DependentModel.php** e **SelfReferencedModel.php**:
    - ❌ **Prima (10.1.0-10.1.6)**: `protected function buildPropertyConditions(...)` (singolare - typo)
    - ✅ **Dopo (10.1.7)**: `protected function buildPropertiesConditions(...)` (plurale - corretto)
    - Il metodo ora fa correttamente override del metodo definito in `BaseModel`

**Scenario del bug**:
1. Nella versione 10.1.0 è stata introdotta la feature "Estensione Query Dinamiche ORM a Tutte le Proprietà"
2. Il metodo in `BaseModel` si chiamava correttamente `buildPropertiesConditions` (plurale)
3. Il metodo in `DependentModel` e `SelfReferencedModel` era stato erroneamente chiamato `buildPropertyConditions` (singolare)
4. A causa del nome diverso, **non avveniva l'override** del metodo
5. Questo causava due problemi critici:
   - Il quarto parametro di `appendCondition()` non veniva passato correttamente per distinguere proprietà entity da builtin
   - I bind types venivano hardcodati a `DataType::typeEntity` invece di essere determinati dinamicamente

**Conseguenze del bug**:
- Per le proprietà `ReferencedEntity`, il quarto parametro (`$isForeignKey`) non veniva impostato a `true`
- Questo impediva l'aggiunta automatica del suffisso `_id` ai nomi delle colonne foreign key
- Per le proprietà builtin (string, int, bool, etc.), il bind type era erroneamente `typeEntity` invece del tipo corretto
- Query SQL potenzialmente malformate e errori di binding dei parametri

**Impatto della correzione**:
- Il metodo ora fa correttamente override, utilizzando l'implementazione specializzata per `DependentModel`/`SelfReferencedModel`
- Il quarto parametro di `appendCondition()` viene passato correttamente: `$propertyValue instanceof ReferencedEntity`
- I bind types vengono determinati dinamicamente tramite `DataType::fromReflection()` invece di essere hardcodati
- Le query SQL vengono costruite correttamente con i suffissi `_id` per le foreign key

*   **Aggiunto import mancante**:
    - `use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;` in `DependentModel.php`
    - Necessario per il check `$propertyValue instanceof ReferencedEntity`

#### Correzione Test Suite: Convenzione Naming Proprietà

Corretti errori sistematici nella test suite che utilizzavano nomi di proprietà in formato snake_case (convenzione database) invece di camelCase (convenzione PHP):

*   **DependentModelTest.php** (12 occorrenze corrette):
    - ❌ **Prima**: `'referenced_entity_with_initialization'`, `'string_with_inizialization'`, `'nullable_string_with_inizialization'`
    - ✅ **Dopo**: `'referencedEntityWithInitialization'`, `'stringWithInizialization'`, `'nullableStringWithInizialization'`

*   **SelfReferencedModelTest.php** (6 occorrenze corrette):
    - ❌ **Prima**: `'parent_self_referenced_sample'`, `'base_sample'`
    - ✅ **Dopo**: `'parentSelfReferencedSample'`, `'baseSample'`

**Motivo del problema**:
- I metodi `getEntityCollectionByEntity()`, `countEntityCollectionByEntity()`, `deleteEntityCollectionByEntity()` accettano array con chiavi = **nomi proprietà PHP** (camelCase)
- I test utilizzavano erroneamente nomi di colonne database (snake_case)
- Questo causava `ReflectionException` perché `new \ReflectionProperty($entityName, 'referenced_entity_with_initialization')` cercava una proprietà inesistente
- La proprietà corretta è `$referencedEntityWithInitialization` (camelCase)

**Esempio di correzione**:
```php
// ❌ PRIMA (errato - nome colonna database):
$posts = $postModel->getEntityCollectionByEntity(['author_id' => $user]);

// ✅ DOPO (corretto - nome proprietà PHP):
$posts = $postModel->getEntityCollectionByEntity(['author' => $user]);
```

**Convenzione del framework**:
- Magic methods: `getByAuthor($user)` → genera internamente `['author' => $user]` (camelCase)
- Metodi espliciti: devono ricevere `['author' => $user]` (camelCase), non `['author_id' => $user]`
- La conversione snake_case → camelCase avviene automaticamente solo nella costruzione delle query SQL

### 🧪 Testing

#### Nuovi Test per Prevenire Regressioni

Aggiunti 2 nuovi test in `DependentModelTest.php` che avrebbero catturato il bug della versione 10.1.0:

*   **`testBuildPropertiesConditionsPassesCorrectFourthParameterToAppendCondition()`**:
    - Verifica che il quarto parametro di `appendCondition()` sia `true` per proprietà `ReferencedEntity`
    - Verifica che il quarto parametro sia `false` per proprietà builtin (string, bool, int)
    - **Questo test avrebbe fallito** con il bug 10.1.0-10.1.6 perché il metodo non veniva sovrascritto

*   **`testBuildPropertiesConditionsGeneratesCorrectBindTypesForMixedProperties()`**:
    - Verifica che i bind types siano corretti per proprietà miste (entity + builtin)
    - Usa spy pattern per catturare i valori effettivi di `$bindTypes` passati a `DataMapper::getCount()`
    - Verifica: `DataType::typeEntity` per `ReferencedEntity`, `DataType::typeBoolean` per `bool`, `DataType::typeString` per `string`
    - **Questo test avrebbe fallito** con il bug 10.1.0-10.1.6 che hardcodava `DataType::typeEntity` per tutte le proprietà

**Copertura test totale**:
- 2 nuovi test aggiunti
- 18 test esistenti corretti (convenzione naming)
- Tutti i test ora passano correttamente

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: La correzione ripristina il comportamento previsto dalla versione 10.1.0
*   **API Pubblica Invariata**: Tutti i metodi pubblici mantengono la stessa firma
*   **Convenzione Esistente**: I progetti che utilizzavano correttamente nomi di proprietà in camelCase non sono affetti

### 📊 Impatto

*   **Correttezza**: Query SQL ora costruite correttamente con suffissi `_id` per foreign key
*   **Type Safety**: Bind types corretti per tutte le tipologie di proprietà
*   **Stabilità**: Eliminati errori di binding e query malformate
*   **Test Coverage**: Aggiunti test specifici per prevenire regressioni future
*   **Qualità**: Test suite conforme alle convenzioni del framework

### 🎓 Note per gli Sviluppatori

Quando si utilizzano i metodi `getEntityCollectionByEntity()`, `countEntityCollectionByEntity()`, `deleteEntityCollectionByEntity()`, ricordare che le chiavi dell'array devono essere **nomi di proprietà PHP in camelCase**, non nomi di colonne database in snake_case:

```php
// ✅ CORRETTO:
$posts = $postModel->getEntityCollectionByEntity([
    'author' => $user,           // nome proprietà PHP
    'category' => $category,     // nome proprietà PHP
    'isPublished' => true        // nome proprietà PHP
]);

// ❌ ERRATO:
$posts = $postModel->getEntityCollectionByEntity([
    'author_id' => $user,        // nome colonna database - causerà ReflectionException
    'category_id' => $category,  // nome colonna database - causerà ReflectionException
    'is_published' => true       // nome colonna database - causerà ReflectionException
]);
```

La conversione da camelCase (proprietà PHP) a snake_case (colonne database) avviene automaticamente all'interno del framework tramite `NotationManager`.


## [10.1.6] - 2025-12-20 - Hotfix Costante LOG_DIRECTORY_PATH

Questa patch release corregge un bug critico introdotto nella versione 10.1.5 relativo alla definizione ricorsiva della costante `LOG_DIRECTORY_PATH` nel file di configurazione.

### 🐛 Bug Fixes

#### Correzione Definizione Ricorsiva LOG_DIRECTORY_PATH

Corretto un bug introdotto nella versione 10.1.5 che causava una definizione ricorsiva della costante `LOG_DIRECTORY_PATH`:

*   **Config/config.php**:
    - ❌ **Prima (10.1.5)**: `const LOG_DIRECTORY_PATH = SYSTEM_PATH . APPLICATION_PATH . LOGS . LOG_DIRECTORY_PATH;`
    - ✅ **Dopo (10.1.6)**: `const LOG_DIRECTORY_PATH = SYSTEM_PATH . APPLICATION_PATH . LOGS . DIRECTORY_SEPARATOR;`
    - La costante ora usa correttamente `DIRECTORY_SEPARATOR` invece di fare riferimento a se stessa

*   **Console/Services/Installation/InstallationManager.php**:
    - Aggiornato il pattern di sostituzione nel metodo `copyConfigFolder()` per riflettere la definizione corretta:
    ```php
    // Pattern di sostituzione corretto (linee 115-116):
    "const LOG_DIRECTORY_PATH = SYSTEM_PATH . APPLICATION_PATH . LOGS . DIRECTORY_SEPARATOR;",
    "const LOG_DIRECTORY_PATH = ROOT_PATH . LOGS . DIRECTORY_SEPARATOR;",
    ```

*   **Tests/Console/Services/Installation/InstallationManagerTest.php**:
    - Aggiornato il test `testInstallCopiesConfigFile()` per verificare la costante corretta:
    ```php
    $this->assertStringContainsString(
        "const LOG_DIRECTORY_PATH = ROOT_PATH . LOGS . DIRECTORY_SEPARATOR;", 
        $content
    );
    ```

**Scenario del bug**:
1. Nella versione 10.1.5, la costante `LOG_DIRECTORY_PATH` era definita usando se stessa: `... . LOG_DIRECTORY_PATH`
2. Questo causava una definizione ricorsiva non valida che avrebbe potuto generare errori a runtime
3. Il bug era presente sia nel file di configurazione del framework che nel processo di installazione

**Impatto della correzione**:
- Il percorso dei log viene ora costruito correttamente utilizzando `DIRECTORY_SEPARATOR`
- Sia il file `Config/config.php` del framework che il processo di installazione automatica utilizzano la definizione corretta
- I test verificano che la sostituzione durante l'installazione funzioni correttamente

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: La correzione risolve un bug senza modificare l'API pubblica
*   **Installazioni Esistenti**: Progetti installati con versione 10.1.5 devono aggiornare manualmente il file `Config/configFramework.php` sostituendo la riga errata

### 📊 Impatto

*   **Correttezza**: Eliminata definizione ricorsiva della costante `LOG_DIRECTORY_PATH`
*   **Stabilità**: Prevenuti potenziali errori a runtime causati dalla definizione errata
*   **Qualità**: Test aggiornati per garantire che il processo di installazione generi la costante corretta


## [10.1.5] - 2025-12-20 - Correzione Configurazione Framework Post-Installazione

Questa patch release corregge un problema nel processo di installazione automatica che non modificava correttamente alcune costanti del file di configurazione framework nella root del progetto.

### 🐛 Bug Fixes

#### Aggiornamento Automatico Costanti in configFramework.php

Corretto il processo di installazione per aggiornare automaticamente le costanti del file `configFramework.php` creato nella root del progetto durante l'installazione:

*   **InstallationManager.php (copyConfigFolder)**:
    - Il metodo `copyConfigFolder()` ora modifica correttamente le seguenti costanti quando crea il file di configurazione nella root del progetto:
    
    **1. APPLICATION**:
    - ❌ **Prima**: Rimaneva `'Sample'` (valore originale del framework)
    - ✅ **Dopo**: Viene impostato a `'Application'`
    
    **2. REFERENCE_CACHE_DIRECTORY**:
    - ❌ **Prima**: `SYSTEM_PATH . APPLICATION_PATH . CACHE . DIRECTORY_SEPARATOR`
    - ✅ **Dopo**: `ROOT_PATH . CACHE . DIRECTORY_SEPARATOR`
    
    **3. LOG_DIRECTORY_PATH**:
    - ❌ **Prima**: `SYSTEM_PATH . APPLICATION_PATH . LOGS . LOG_DIRECTORY_PATH`
    - ✅ **Dopo**: `ROOT_PATH . LOGS . LOG_DIRECTORY_PATH`
    
    **4. MODULE_FOLDERS**:
    - ❌ **Prima**: Array contenente `'SismaFramework'`
    - ✅ **Dopo**: Array vuoto `[]`

**Scenario del problema**:
1. Utente esegue: `php SismaFramework/Console/sisma install MyProject`
2. Il file `Config/configFramework.php` veniva creato nella root del progetto
3. La costante `PROJECT` veniva aggiornata correttamente, ma `APPLICATION`, `REFERENCE_CACHE_DIRECTORY`, `LOG_DIRECTORY_PATH` e `MODULE_FOLDERS` mantenevano i valori del framework originale
4. Questo causava percorsi errati per cache e log, e riferimenti all'applicazione 'Sample' invece di 'Application'

**Dopo la correzione**:
- Il file `configFramework.php` ha i valori corretti per un nuovo progetto
- I percorsi di cache e log puntano alla root del progetto invece che al framework
- L'applicazione è correttamente identificata come 'Application'
- L'array MODULE_FOLDERS è vuoto, pronto per essere popolato dall'utente

### 🧪 Testing

#### Aggiornamento Test InstallationManager

Aggiornati i test per verificare le nuove modifiche al processo di installazione:

*   **InstallationManagerTest.php**:
    - **testInstallCopiesConfigFile()**: Esteso per verificare che tutte le costanti vengano modificate correttamente:
      - Verifica `const APPLICATION = 'Application'`
      - Verifica `const REFERENCE_CACHE_DIRECTORY = ROOT_PATH . CACHE . DIRECTORY_SEPARATOR;`
      - Verifica `const LOG_DIRECTORY_PATH = ROOT_PATH . LOGS . LOG_DIRECTORY_PATH;`
      - Verifica `const MODULE_FOLDERS = [];`
    
    - **createFrameworkStructure()**: Aggiornato per creare un file `config.php` di test più completo con tutte le costanti necessarie:
      - Aggiunge costanti `APPLICATION = 'Sample'`
      - Aggiunge costanti `CACHE`, `LOGS`, `SYSTEM_PATH`, `APPLICATION_PATH`, `ROOT_PATH`
      - Aggiunge `REFERENCE_CACHE_DIRECTORY` e `LOG_DIRECTORY_PATH` con valori originali del framework
      - Aggiunge `MODULE_FOLDERS` con `'SismaFramework'` nel array
      - Questo permette ai test di verificare che la trasformazione avvenga correttamente

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: Tutte le modifiche riguardano solo il processo di installazione
*   **File Framework Invariato**: Il file `SismaFramework/Config/config.php` originale rimane inalterato
*   **Installazioni Esistenti**: Progetti già installati non sono influenzati, solo nuove installazioni beneficiano della correzione

### 📊 Impatto

*   **Correttezza**: I nuovi progetti hanno la configurazione corretta fin dall'inizio
*   **Manutenibilità**: Riduce la necessità di modifiche manuali post-installazione
*   **Qualità**: I test garantiscono che tutte le costanti vengano aggiornate correttamente


## [10.1.4] - 2025-12-14 - Correzioni Installazione e Aggiornamento Test Suite PHPUnit

Questa patch release corregge un bug nel processo di installazione automatica e aggiorna la test suite per conformità alle best practice di PHPUnit 11+ eliminando deprecation notices relative all'uso di mock al posto di stub.

### 🐛 Bug Fixes

#### Correzione Riferimento File Configurazione in Installazione

Corretto il processo di installazione automatica per rinominare correttamente il riferimento al file di configurazione in `Public/index.php`:

*   **InstallationManager.php**:
    - ❌ **Prima**: Il file `Public/index.php` copiato manteneva il riferimento hardcoded a `'Config' . DIRECTORY_SEPARATOR . 'config.php'`
    - ✅ **Dopo**: Aggiunto pattern di sostituzione per rinominare il riferimento a `'Config' . DIRECTORY_SEPARATOR . 'configFramework.php'`
    - Pattern aggiunto all'array di replacements (linea 117):
    ```php
    $patterns = [
        "dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config'",
        "dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Autoload'",
        "'Config' . DIRECTORY_SEPARATOR . 'config.php'",  // ← NUOVO
    ];
    $replacements = [
        "dirname(__DIR__) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'Config'",
        "dirname(__DIR__) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'Autoload'",
        "'Config' . DIRECTORY_SEPARATOR . 'configFramework.php'",  // ← NUOVO
    ];
    ```

**Scenario del bug**:
1. Utente esegue: `php Console/sisma install MyProject`
2. Il file `Public/index.php` veniva copiato e aggiornato per i path di Autoload e Config
3. Tuttavia, il riferimento al file di configurazione rimaneva `config.php` invece di `configFramework.php`
4. L'applicazione non riusciva a trovare il file di configurazione causando errori fatali

**Dopo la correzione**:
- Il file `Public/index.php` include correttamente `Config/configFramework.php`
- L'installazione automatica produce un progetto immediatamente funzionante

### 🧪 Testing

#### Aggiornamento Test Suite per PHPUnit 11+

Aggiornati tutti i file di test per utilizzare `createStub()` al posto di `createMock()` quando non vengono configurate aspettative (expectations), eliminando deprecation notices introdotte in PHPUnit 11:

*   **Motivazione del Cambiamento**:
    - PHPUnit 11+ depreca l'uso di `expects()` su oggetti creati con `createStub()`
    - PHPUnit 12 (futuro) non permetterà più questa configurazione
    - Best practice: `createStub()` per test stub (solo valori di ritorno), `createMock()` per mock object (verifica interazioni)

*   **File Aggiornati** (49 test totali):
    - **Console Tests** (3 file):
        * `InstallationCommandTest.php`: `$mockInstallationManager` → `$installationManagerStub`
        * `ScaffoldCommandTest.php`: `$mockScaffoldingManager` → `$scaffoldingManagerStub`
        * `ScaffoldingManagerTest.php`: `$configMock` → `$configStub` (2 occorrenze)
        * `InstallationManagerTest.php`: Rimossi `setAccessible(true)` deprecati (PHP 8.1+)
    
    - **Core Tests** (13 file):
        * `BaseFixtureTest.php`: `$configMock` → `$configStub`, `$dataMapperMock` → stub
        * `BaseFormTest.php`: `$configMock` → `$configStub`, `$dataMapperMock` → stub, `$requestMock` → stub
        * `FilterTypeTest.php`, `AutoloaderTest.php`, `ConfigTest.php`, `DebuggerTest.php`, `DispatcherTest.php`
        * `EncryptorTest.php`, `FilterTest.php`, `FixturesManagerTest.php`, `LoggerTest.php`
        * `ModuleManagerTest.php`, `NotationManagerTest.php`
    
    - **ORM Tests** (10 file):
        * `AdapterMysqlTest.php`, `BaseEntityTest.php`, `BaseModelTest.php`, `SismaCollectionTest.php`
        * `DependentModelTest.php`, `ReferencedEntityTest.php`, `SelfReferencedEntityTest.php`, `SelfReferencedModelTest.php`
        * `CacheTest.php`, `DataMapperTest.php`, `ErrorHandlerTest.php`, `ProcessedEntitiesCollectionTest.php`, `QueryTest.php`
        * `JoinEagerLoadingTest.php`, `ResultSetMysqlTest.php`
    
    - **Security Tests** (3 file):
        * `BasePermissionTest.php`, `BaseVoterTest.php`, `AuthenticationTest.php`

*   **Pattern di Aggiornamento Applicato**:
    ```php
    // ❌ Prima (PHPUnit 11 deprecation warning):
    $configMock = $this->createMock(Config::class);
    $configMock->expects($this->any())  // ← expects() su stub non necessario
            ->method('__get')
            ->willReturnMap([...]);
    
    // ✅ Dopo (conforme PHPUnit 11+):
    $configStub = $this->createStub(Config::class);
    $configStub->method('__get')  // ← solo configurazione valori di ritorno
            ->willReturnMap([...]);
    ```

*   **Deprecation PHP 8.1+**:
    - Rimossi tutti i `setAccessible(true)` in `InstallationManagerTest.php` (non più necessari per proprietà private da PHP 8.1)

### 🔧 Refactoring

#### Pulizia Codice InstallationManager

Refactorizzata formattazione del codice in `InstallationManager.php` per migliorare leggibilità:

*   Rimossi commenti ridondanti che duplicavano informazioni evidenti dal codice
*   Normalizzata formattazione spaziatura e indentazione
*   Rimossi spazi vuoti superflui tra metodi
*   Codice più conciso mantenendo identica funzionalità

**Impatto**: Nessun cambiamento funzionale, solo miglioramento della manutenibilità.

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: Tutte le modifiche sono retrocompatibili
*   **Test Suite**: Tutti i test continuano a passare con identica copertura
*   **Installazione**: Il processo di installazione ora funziona correttamente end-to-end

### 📊 Metriche

*   **Test Aggiornati**: 49 file di test modificati
*   **Deprecation Warnings**: Eliminati tutti i warning PHPUnit 11+
*   **Conformità**: Test suite conforme alle best practice PHPUnit 11/12
*   **Compatibilità PHP**: Rimossi pattern deprecati da PHP 8.1+


## [10.1.3] - 2025-12-10 - Correzione Parsing Argomenti CLI

Questa patch release corregge un bug critico nel sistema di parsing degli argomenti posizionali dei comandi CLI che impediva il corretto funzionamento del comando `install`.

### 🐛 Bug Fixes

#### Parsing Dinamico Argomenti Posizionali CLI

Corretto bug nel `CommandDispatcher` che utilizzava nomi hardcodati per gli argomenti posizionali, causando incompatibilità tra comandi diversi:

*   **CommandDispatcher.php**:
    - ❌ **Prima**: Gli argomenti posizionali erano assegnati con nomi fissi (`entity`, `module`)
    - ✅ **Dopo**: Utilizzo di indici numerici (`0`, `1`, `2`, ...) per massima flessibilità
    - Ogni comando può ora definire autonomamente i propri nomi di argomenti
    - Eliminata dipendenza dal tipo di comando nel dispatcher

*   **InstallationCommand.php**:
    - Aggiornato per leggere `getArgument('0')` invece di `getArgument('projectName')`
    - Il comando ora riceve correttamente il nome del progetto dal primo argomento posizionale

*   **ScaffoldCommand.php**:
    - Aggiornato per leggere `getArgument('0')` e `getArgument('1')` invece di `entity` e `module`
    - Mantiene piena compatibilità con la sintassi esistente

**Scenario del bug**:
1. Utente esegue: `php Console/sisma install MyProject`
2. `CommandDispatcher` assegnava l'argomento come `['entity' => 'MyProject']`
3. `InstallationCommand` cercava `getArgument('projectName')` → `null`
4. Il comando falliva con errore "Project name is required"

**Dopo la correzione**:
1. `CommandDispatcher` assegna: `['0' => 'MyProject']`
2. `InstallationCommand` legge `getArgument('0')` → `'MyProject'`
3. Il comando funziona correttamente

### 🧪 Testing

*   **InstallationCommandTest.php**: Aggiornati tutti i test per utilizzare indici numerici negli argomenti
    - `testSuccessfulInstallation()`: `['0' => 'MyProject']` invece di `['projectName' => 'MyProject']`
    - `testInstallationWithDatabaseOptions()`: Stessa modifica
    - `testInstallationFailure()`: Stessa modifica

*   **ScaffoldCommandTest.php**: Aggiornati tutti i test per utilizzare indici numerici
    - `testExecuteWithMissingModule()`: `['0' => 'User']` invece di `['entity' => 'User']`
    - `testSuccessfulExecution()`: `['0' => 'MockEntity', '1' => 'TestModule']`

*   ✅ **Tutti i 13 test passano correttamente**

### ✅ Backward Compatibility

*   **Nessun Breaking Change per gli utenti**: La sintassi CLI rimane identica
    - `php Console/sisma install MyProject` continua a funzionare
    - `php Console/sisma scaffold User Blog` continua a funzionare
*   **Refactoring interno**: Il cambio riguarda solo l'implementazione interna del dispatcher

### 📊 Impatto

*   **Correttezza**: Il comando `install` ora funziona come previsto
*   **Flessibilità**: Il sistema di comandi può ora supportare comandi con argomenti posizionali arbitrari
*   **Estensibilità**: Nuovi comandi possono definire i propri schemi di argomenti senza vincoli


## [10.1.2] - 2025-12-10 - Normalizzazione Gestione Slash nei Path

Questa patch release migliora la robustezza della gestione dei path nel router attraverso la normalizzazione automatica degli slash, eliminando potenziali bug da doppi slash o slash mancanti.

### 🔧 Refactoring

#### Correzioni PHPStan per Qualità del Codice

Risolti warning di analisi statica segnalati da PHPStan per migliorare la qualità e la correttezza del codice:

*   **Console/Exceptions/** (3 file): Rimosso `return` non necessario dai costruttori delle eccezioni
    - `ApplicationPathException.php:41`, `EntityPathException.php:47`, `ModulePathException.php:41`
    - I costruttori non devono avere statement `return`
    - Prima: `return parent::__construct($message, $code, $previous);`
    - Dopo: `parent::__construct($message, $code, $previous);`

*   **ScaffoldingManager.php:168**: Rimosso parametro inutilizzato da chiamata a metodo
    - Prima: `$this->checkDependencies($this->entityReflection)`
    - Dopo: `$this->checkDependencies()`

*   **QueryExecutor.php**: Rimosso metodo morto `findWithJoins()` (codice non utilizzato da refactoring precedente)

*   **SampleController.php:18**: Corretto namespace import per classe `Authentication`
    - Prima: `use SismaFramework\Security\Authentication;`
    - Dopo: `use SismaFramework\Security\HttpClasses\Authentication;`
    - Rimosso import inutilizzato `SampleReferencedEntity`

#### Normalizzazione Automatica Slash in Router

Migliorata la gestione dei path nel Router per rendere più robusta e consistente la concatenazione degli URL:

*   **Router.php**:
    - **`concatenateMetaUrl()`**: Il metodo ora gestisce automaticamente l'aggiunta del `/` iniziale e rimuove eventuali trailing slash tramite `rtrim()`
      - ❌ **Prima**: La responsabilità di aggiungere `/` era del chiamante (`concatenateMetaUrl('/path')`)
      - ✅ **Dopo**: Il metodo normalizza automaticamente il path (`concatenateMetaUrl('path')` → `/path`)
    - **`redirect()`**: Aggiunto `rtrim($relativeUrl, '/')` per normalizzare l'URL di destinazione prima del redirect
    - **Vantaggi**:
      - Idempotenza: `rtrim()` rende l'operazione sempre sicura
      - Prevenzione doppi slash: eliminati potenziali path malformati come `/meta//url`
      - API più intuitiva: non serve più passare `/` manualmente

*   **RouteResolver.php**:
    - **`slicePathElement()`**: Aggiornata la chiamata a `concatenateMetaUrl()` per passare il path senza `/` iniziale
      - Prima: `Router::concatenateMetaUrl('/' . $this->pathController)`
      - Dopo: `Router::concatenateMetaUrl($this->pathController)`
    - Il comportamento funzionale rimane identico grazie alla normalizzazione automatica

### 🧪 Testing

*   **RouterTest.php**: Aggiornati i test per riflettere la nuova interfaccia del metodo `concatenateMetaUrl()`
    - `testGetActualUrl()`: Ora utilizza chiamate separate (`concatenateMetaUrl('meta')` + `concatenateMetaUrl('url')`) invece di una singola chiamata con path completo
    - `testSetMetaUrlOverwritesPreviousValue()`: Stessa modifica per testare la sovrascrittura
    - I test verificano che il comportamento esterno rimanga identico nonostante il refactoring interno

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: Il comportamento funzionale dell'API pubblica rimane completamente invariato
*   **Compatibilità Chiamate Esistenti**: Grazie a `rtrim()`, sia `concatenateMetaUrl('/path')` che `concatenateMetaUrl('path')` producono lo stesso risultato
*   **Fix Implicito**: Risolve edge case con slash duplicati o mancanti che potrebbero causare URL malformati

### 📊 Impatto

*   **Robustezza**: Gestione slash più affidabile e meno soggetta a errori
*   **Manutenibilità**: Logica di normalizzazione centralizzata in un unico punto
*   **Pulizia API**: Interfaccia più semplice e intuitiva per i chiamanti


## [10.1.1] - 2025-12-06 - Supporto HTTP Range Requests e Miglioramenti API Response

Questa patch release corregge un bug critico di conformità agli standard HTTP che impediva la riproduzione di video in Safari. Implementato il supporto completo per HTTP Range Requests (RFC 7233) con gestione di 206 Partial Content e 416 Range Not Satisfiable. Migliorata l'API della classe Response con constructor injection.

### 🐛 Bug Fixes

#### Supporto HTTP Range Requests per Streaming Media

Corretto bug critico nel serving di file statici che causava la mancata riproduzione di video in Safari:

*   **ResourceMaker.php**:
    - ❌ **Prima**: Il server ignorava l'header `Range` e restituiva sempre 200 OK con l'intero file
    - ✅ **Dopo**: Gestione completa delle range requests secondo RFC 7233
    - `viewResource()` e `downloadResource()`: Rilevamento header `Range` e delega a `servePartialContent()`
    - `servePartialContent()`: Gestisce risposta 206 Partial Content
    - `parseRangeHeader()`: Validazione formato con regex e controlli
    - `getResourceDataRange()`: Lettura efficiente chunk-based (8KB)

*   **RangeNotSatisfiableException.php** (nuova classe):
    - Eccezione dedicata per gestire range invalidi
    - Risposta 416 Range Not Satisfiable conforme a RFC 7233
    - Header `Content-Range: bytes */filesize` settato automaticamente
    - Validazione: formato header, start ≤ end, range entro limiti file

**Scenario del bug**:
1. Safari richiede un video con header `Range: bytes=0-1023`
2. Il server ignorava l'header e restituiva 200 OK con l'intero file
3. Safari rifiutava di riprodurre il video
4. Impossibilità di fare seek/skip nei file multimediali

**Casi d'uso risolti**:
- Video/audio streaming con seek (Safari, Chrome, Firefox, Edge)
- Download resumable con download manager
- Caricamento progressivo PDF di grandi dimensioni

### 🎨 Refactoring

#### Response Constructor Injection

Migliorata l'API della classe `Response`:

*   **Response.php**:
    - Aggiunto parametro opzionale `?ResponseType $responseType = null`
    - Pattern conciso: `new Response(ResponseType::httpPartialContent)`
    - Backward compatible al 100%

*   **Applicato in**:
    - `ResourceMaker::servePartialContent()`: -2 linee
    - `Render::getResponse()`: Metodo rimosso (-7 linee)
    - `Render`: Return diretto in `generateView()`, `generateData()`, `generateJson()`

### 🧪 Testing

*   **ResourceMakerTest.php**: 6 nuovi test
*   **ResponseTest.php**: 3 nuovi test
*   **large-sample.css**: File test 384 bytes

### 🔧 Dettagli Tecnici

*   **Standard**: RFC 7233, RFC 7231
*   **Response Codes**: 206, 416
*   **Headers**: `Range`, `Content-Range`, `Accept-Ranges`, `Content-Length`

## [10.1.0] - 2025-12-02 - Strumenti CLI per Scaffolding, Installazione e Rifatorizzazione Dispatcher

Benvenuti alla release 10.1.0, una delle più ricche di novità nella storia del framework! Utility CLI rivoluzionano il flusso di sviluppo quotidiano, con scaffolding automatico e installazione guidata che accelerano drasticamente la creazione di nuovi progetti. Ottimizzato profondamente il Dispatcher attraverso una rifatorizzazione completa seguendo i principi SOLID, separando le responsabilità in sette classi specializzate che rendono il codice più manutenibile e testabile.

Nascono nuove funzionalità per l'ORM: le funzioni di aggregazione SQL (AVG, MAX, MIN, SUM) permettono ora query analitiche avanzate con supporto per DISTINCT, alias, subquery e aggregazioni multiple, mentre l'estensione del sistema di query dinamiche con metaprogrammazione a tutte le proprietà (non più solo entità referenziate) riduce drasticamente la necessità di scrivere metodi repository ripetitivi generando automaticamente query type-safe, e il supporto completo per JOIN SQL con eager loading gerarchico multi-entità risolve definitivamente il problema N+1 delle query supportando relazioni nested a più livelli con dot notation e sintassi array.

Comandi CLI di scaffolding generano automaticamente l'intero stack CRUD (Controller, Model, Form, Views) a partire da un'Entity esistente, mentre il sistema di installazione configura progetti completi in pochi secondi. Oltre 400 linee di nuovi test garantiscono una copertura completa di tutte le nuove funzionalità, assicurando robustezza e affidabilità.

Molto è stato fatto anche sul fronte architetturale: la rifatorizzazione del Dispatcher riduce la complessità ciclomatica da oltre 400 linee a meno di 200, creando sette nuovi file di helper classes che gestiscono routing, factory dei controller, parsing degli argomenti e gestione delle risorse statiche. Pattern consolidati come Command/Manager vengono applicati sistematicamente ai comandi CLI, con dependency injection e gestione centralizzata delle eccezioni.

Livello enterprise raggiunto con le funzioni di aggregazione ORM: AVG, MAX, MIN e SUM supportano ora DISTINCT, alias personalizzati, subquery e aggregazioni multiple sulla stessa query, portando l'ORM a competere con i framework più evoluti. Estesa significativamente anche la documentazione, con due nuove guide complete per scaffolding e installazione automatica.

Architettura completamente rinnovata: il Dispatcher è stato suddiviso in sette classi specializzate (RouteResolver, ControllerFactory, ActionArgumentsParser, ResourceHandler, RouteInfo, FixturesManager, ResourceMaker) che seguono i principi SOLID e facilitano manutenibilità ed estensibilità future.

Notevole il numero di miglioramenti introdotti in questa release, che rappresenta un punto di svolta nella maturità del framework. Dedichiamo questa versione a tutti gli sviluppatori che quotidianamente utilizzano SismaFramework per creare applicazioni robuste e scalabili.

Nuove possibilità si aprono con questi strumenti professionali: template personalizzabili per lo scaffolding, configurazione database da CLI, protezione contro sovrascritture accidentali e auto-detection intelligente del tipo di Model più appropriato.

Ottima base per futuri sviluppi: questa release pone le fondamenta per ulteriori miglioramenti al sistema di scaffolding e all'ORM, con possibilità di estensione illimitate che verranno esplorate nelle prossime versioni.

Finalmente, dopo mesi di lavoro intenso, possiamo dire che il framework ha raggiunto un livello di maturità che lo rende adatto anche a progetti complessi e mission-critical.

Ricordiamo che questa release è completamente retrocompatibile e l'aggiornamento è fortemente consigliato a tutti gli utenti della versione 10.0.x per beneficiare di questi importanti miglioramenti.

Articolata in tre aree principali (CLI Tools, Architettura, ORM), questa release rappresenta un passo significativo nell'evoluzione del framework, portando strumenti professionali di livello enterprise alla portata di tutti gli sviluppatori PHP.

### ✨ Nuove Funzionalità

* **Sistema di Scaffolding Automatico**: Introdotto il comando CLI `scaffold` che genera automaticamente Controller, Model, Form e Views a partire da un'Entity esistente.
  - **Generazione CRUD Completa**: Il comando crea controller con implementazione base del pattern CRUD (metodi `index`, `create`, `update`, `delete`)
  - **Auto-Detection del Tipo Model**: Il sistema analizza automaticamente l'Entity e determina il tipo di Model più appropriato (`BaseModel`, `DependentModel`, o `SelfReferencedModel`)
  - **Form con Filtri Pre-configurati**: Genera Form con filtri standard per tutte le proprietà dell'Entity
  - **Template Personalizzabili**: Supporto per template custom tramite l'opzione `--template=PATH`
  - **Gestione Collisioni**: Protezione contro la sovrascrittura accidentale di file esistenti con l'opzione `--force`

  **Esempio di utilizzo**:
  ```bash
  php SismaFramework/Console/sisma scaffold Product Catalog
  php SismaFramework/Console/sisma scaffold User Blog --type=DependentModel --force
  ```

* **Sistema di Installazione Automatica Progetti**: Introdotto il comando CLI `install` per configurare rapidamente nuovi progetti.
  - **Setup Struttura Automatico**: Crea automaticamente le cartelle essenziali (`Config/`, `Public/`, `Cache/`, `Logs/`, `filesystemMedia/`)
  - **Configurazione Framework**: Copia e configura `configFramework.php` con il nome del progetto
  - **Setup Database da CLI**: Permette di configurare i parametri database direttamente da riga di comando tramite opzioni dedicate
  - **Path Autoloader Aggiornati**: Aggiorna automaticamente i percorsi in `Public/index.php` per puntare alla sottocartella `SismaFramework/`
  - **Permessi Corretti**: Imposta automaticamente i permessi corretti (777) per le cartelle Cache, Logs e filesystemMedia

  **Esempio di utilizzo**:
  ```bash
  php SismaFramework/Console/sisma install MyProject
  php SismaFramework/Console/sisma install BlogPersonale --db-host=localhost --db-name=blog_db --db-user=root --db-pass=secret
  ```

* **Estensione Query Dinamiche ORM a Tutte le Proprietà**: Esteso il sistema esistente di metaprogrammazione per query dinamiche, precedentemente limitato alle sole entità referenziate, ora funzionante con **tutte le proprietà** delle entità.
  - **Ambito Ampliato**: Precedentemente solo `getByReferencedEntity()`, ora supporta qualsiasi proprietà: `getByName()`, `countByStatus()`, `deleteByEmail()`
  - **Tipi Supportati**: Tipi builtin (`int`, `string`, `float`, `bool`), oggetti custom (`SismaDate`, `SismaDateTime`, `SismaTime`), enum PHP 8.1+, proprietà nullable
  - **Type Safety con Reflection**: Validazione automatica del tipo di ogni argomento con `ReflectionType::allowsNull()` per gestione corretta dei valori null
  - **Pattern Multiple Properties**: Supporto per condizioni AND su più proprietà: `getByNameAndCategory()`, `countByStatusAndType()`
  - **Backward Compatibility**: I metodi legacy come `getEntityCollectionByEntity()` rimangono funzionanti ma vengono marcati `@deprecated` (rimozione prevista in v11.0.0)
  - **Refactoring Interno**: Unificata la logica con `buildPropertyConditions()` che supporta sia entità che proprietà builtin

  **Esempi di utilizzo**:
  ```php
  // PRIMA (solo entità referenziate):
  $model->getEntityCollectionByEntity(['referenced_entity' => $entity]);

  // ADESSO (qualsiasi proprietà):
  $users = $userModel->getByStatus(UserStatus::ACTIVE);
  $count = $productModel->countByPrice(99.99);
  $model->deleteByEmail('test@example.com');

  // Proprietà multiple con AND logico:
  $products = $productModel->getByNameAndCategory('iPhone', $electronics);

  // Con searchKey e paginazione:
  $articles = $articleModel->getByAuthor($author, 'keyword', ['date' => 'DESC'], 0, 20);

  // Query gerarchiche (SelfReferencedModel):
  $subCategories = $categoryModel->getByParentAndActive($parent, true);

  // Valori null su proprietà nullable:
  $orphans = $entityModel->getByNullableParent(null); // WHERE nullable_parent IS NULL
  ```

### 🏗️ Architettura

* **Rifatorizzazione Completa del Dispatcher**: Il `Dispatcher` è stato completamente rifatorizzato seguendo i principi SOLID, con separazione delle responsabilità in classi dedicate:
  - **`RouteResolver`**: Gestisce il parsing e la risoluzione delle route dall'URL, determinando modulo, controller e action
  - **`ControllerFactory`**: Responsabile della creazione e istanziazione dei controller con dependency injection automatica
  - **`ActionArgumentsParser`**: Analizza e prepara gli argomenti per i metodi action, gestendo type-hinting e auto-wiring delle entità
  - **`ResourceHandler`**: Gestisce il serving dei file statici (CSS, JS, immagini) separando questa logica dal flusso principale
  - **`RouteInfo`**: Value object immutabile che contiene tutte le informazioni sulla route corrente
  - **`FixturesManager`**: Estratta la logica di gestione delle fixtures in una classe dedicata
  - **`ResourceMaker`**: Gestisce la creazione e lo streaming ottimizzato delle risorse statiche

  **Vantaggi della rifatorizzazione**:
  - Codice più testabile con responsabilità chiaramente separate
  - Migliore manutenibilità e leggibilità
  - Facilita l'estensione futura con nuove funzionalità di routing
  - Riduce la complessità ciclomatica del Dispatcher principale da oltre 400 linee a meno di 200

* **Pattern Command/Manager**: Entrambi i comandi CLI seguono il pattern consolidato di separazione tra Command (interfaccia CLI) e Manager (logica di business):
  - `ScaffoldCommand` + `ScaffoldingManager`
  - `InstallationCommand` + `InstallationManager`
* **Dependency Injection**: I Command accettano i Manager via costruttore, facilitando il testing con mock
* **Gestione Eccezioni Centralizzata**: Le eccezioni vengono propagate e gestite centralmente dal dispatcher CLI nel file `sisma`
* **Deprecazione Metodi Legacy ORM**: Metodi per query con entità marcati `@deprecated dalla versione 11.0.0` in favore del sistema di query dinamiche:
  - `DependentModel`: `countEntityCollectionByEntity()`, `getEntityCollectionByEntity()`, `deleteEntityCollectionByEntity()`
  - `SelfReferencedModel`: `countEntityCollectionByParentAndEntity()`, `getEntityCollectionByParentAndEntity()`, `deleteEntityCollectionByParentAndEntity()`
  - **Backward Compatibility Garantita**: I metodi rimangono pienamente funzionanti fino alla rimozione prevista nella v11.0.0

### 🧪 Testing

* **Copertura Test Completa**: Aggiunti test completi per tutti i nuovi componenti:
  - **ScaffoldCommandTest**: 4 test con mock del ScaffoldingManager
  - **ScaffoldingManagerTest**: 10 test che verificano generazione per BaseEntity, SelfReferencedEntity, DependentEntity, custom types, custom templates, e gestione errori
  - **InstallationCommandTest**: 8 test con mock dell'InstallationManager, inclusi test per opzioni database e gestione eccezioni
  - **InstallationManagerTest**: 8 test con filesystem temporaneo per verificare creazione struttura, copia file, aggiornamento config, e gestione flag `--force`
  - **BaseModelTest**: +5 test per query dinamiche (searchKey, paginazione, null su nullable, eccezione su non-nullable)
  - **DependentModelTest**: +3 test per query dinamiche con entità e searchKey/paginazione
  - **SelfReferencedModelTest**: +5 test per query gerarchiche dinamiche con searchKey, null e eccezioni
* **Output Buffer Corretto**: Tutti i test catturano correttamente l'output dei comandi senza "sporcare" la console di PHPUnit
* **Entità di Test Estese**: Aggiunte proprietà nullable (`NotDependentEntity::$nullableString`, `SelfReferencedSample::$nullableText`) per testare correttamente la gestione dei valori null

### 📝 Documentazione

* **Nuova Documentazione Scaffolding** (`docs/scaffolding.md`):
  - Spiegazione dettagliata del funzionamento del meccanismo
  - Descrizione completa di tutti gli argomenti e opzioni
  - Esempi pratici per ogni caso d'uso
  - Prerequisiti e struttura cartelle richiesta

* **Documentazione Installazione Aggiornata** (`docs/installation.md`):
  - Suddivisa in due metodi: **Automatico (CLI)** e **Manuale**
  - Il metodo CLI è ora consigliato come approccio principale
  - Esempi completi con tutte le opzioni disponibili
  - Guida passo-passo per entrambi i metodi
  - Istruzioni chiare sui "Prossimi Passi" post-installazione

### 🚀 ORM

* **Funzioni di Aggregazione per Colonne**: Aggiunto supporto completo per le funzioni di aggregazione SQL nelle query dell'ORM:
  - **Nuovi Metodi nella Classe `Query`**: Introdotti i metodi `setAVG()`, `setMax()`, `setMin()`, e `setSum()` per applicare funzioni di aggregazione alle colonne
  - **Supporto per DISTINCT**: Tutti i metodi di aggregazione supportano il parametro `$distinct` per applicare l'aggregazione solo su valori distinti
  - **Modalità Append**: Il parametro `$append` permette di aggiungere funzioni di aggregazione a colonne già selezionate, consentendo query con multiple aggregazioni
  - **Alias per Colonne**: Supporto per alias personalizzati tramite il parametro `$columnAlias`
  - **Subquery**: Ogni funzione di aggregazione può accettare sia una stringa (nome colonna) che un'istanza `Query` (subquery)
  - **Nuove Funzioni Aggregate**: Estesa l'enumerazione `AggregationFunction` con i casi `max` e `min` (in aggiunta a `avg`, `count`, `sum`)
  - **Metodo Adapter**: Aggiunto il metodo `opAggregationFunction()` in `BaseAdapter` per gestire la generazione SQL delle funzioni aggregate

  **Esempio di utilizzo**:
  ```php
  // Media dei prezzi
  $query->setAVG('price', 'average_price');

  // Somma con DISTINCT
  $query->setSum('amount', 'total', distinct: true);

  // Multiple aggregazioni
  $query->setMin('price', 'min_price')
        ->setMax('price', 'max_price', append: true)
        ->setAVG('price', 'avg_price', append: true);
  ```

* **Supporto JOIN SQL con Eager Loading Gerarchico Multi-Entità**: Implementato supporto completo per operazioni JOIN SQL con caricamento eager e idratazione gerarchica automatica, risolvendo il problema N+1 delle query.
  - **Nuovo Enum `JoinType`**: Introdotta enumerazione per gestire in modo type-safe i diversi tipi di JOIN (INNER, LEFT, RIGHT, CROSS)
  - **Estensione Query Builder**: Aggiunta proprietà `$joins` e metodi `appendJoin()`, `appendJoinOnForeignKey()`, `hasJoins()`, `getJoins()`, `getColumns()` nella classe `Query`
  - **Metodi Eager Loading in BaseModel**:
    * `getEntityCollectionWithRelations()`: Carica collection con relazioni via JOIN e batch loading
    * `getEntityByIdWithRelations()`: Carica singola entità con le sue relazioni
    * `flattenRelations()`: Normalizza sintassi dot notation e array nested per relazioni multi-livello
    * `appendNestedRelationJoin()`: Costruisce ricorsivamente JOIN per relazioni nested
  - **Supporto Relazioni Nested Multi-Livello**: Permette eager loading di relazioni a più livelli con due sintassi:
    * Dot notation: `['author.country.continent']`
    * Array nested: `['author' => ['country' => ['continent']]]`
    * Sintassi mista supportata
  - **Idratazione Gerarchica Automatica in BaseResultSet**:
    * Aggiunta proprietà `$joinMetadata` per tracciare metadati delle tabelle joined
    * `convertToHierarchicalEntity()`: Separa dati entità principali da nested entities
    * `hydrateNestedEntities()`: Idratazione ricorsiva di relazioni multi-livello
    * `getEntityClassForAlias()`: Risoluzione entity class da alias JOIN
  - **Supporto ReferencedEntity Collections**: Eager loading di relazioni one-to-many inverse tramite batch loading ottimizzato (singola query IN per tutte le entities)
  - **Supporto SelfReferencedEntity**: Gestione nativa di relazioni ricorsive (tree structures) tramite self-join
  - **Integrazione con Cache**: Piena compatibilità con Identity Map pattern esistente per evitare duplicazione di entità in memoria
  - **Estensione BaseAdapter**:
    * `buildJoinedColumns()`: Genera automaticamente colonne con alias (separatore `__`)
    * `buildJoinMetadata()`: Costruisce metadati JOIN includendo `relatedEntityClass`
    * Modifica di `buildJoinOnForeignKey()` per includere `relatedEntityClass` nei metadati
  - **Zero Breaking Changes**: Implementazione completamente trasparente che rileva automaticamente presenza di JOIN nei metadati

  **Esempi di utilizzo**:
  ```php
  // Many-to-one: eager loading con JOIN
  $articles = $articleModel->getEntityCollectionWithRelations(['author', 'category']);
  foreach ($articles as $article) {
      echo $article->author->name; // Già caricato, nessuna query N+1
  }

  // One-to-many: eager loading con batch loading
  $authors = $authorModel->getEntityCollectionWithRelations(['articleCollection']);

  // Relazioni nested multi-livello (dot notation)
  $articles = $articleModel->getEntityCollectionWithRelations(['author.country.continent']);

  // Relazioni nested (sintassi array)
  $articles = $articleModel->getEntityCollectionWithRelations([
      'author' => ['country', 'publisher' => ['city']]
  ]);

  // SelfReferencedEntity (tree structures)
  $categories = $categoryModel->getEntityCollectionWithRelations([
      'parentCategory',     // Padre
      'sonCollection'       // Figli
  ]);

  // Con parametri aggiuntivi
  $products = $productModel->getEntityCollectionWithRelations(
      relations: ['category', 'brand'],
      searchKey: 'laptop',
      order: ['price' => 'ASC'],
      limit: 20,
      joinType: JoinType::inner
  );
  ```

### 🧪 Testing

* **Copertura Test Completa per Funzioni di Aggregazione**: Aggiunti test completi per le nuove funzionalità:
  - **AggregationFunctionTest**: 159 linee di test che verificano tutti i casi dell'enumerazione e la corretta generazione SQL per MySQL
  - **QueryTest**: 149 linee di test per i nuovi metodi `setAVG()`, `setMax()`, `setMin()`, `setSum()` con varie combinazioni di parametri (distinct, append, alias, subquery)
  - **AdapterMysqlTest**: 57 linee di test per verificare il metodo `opAggregationFunction()` con tutte le funzioni aggregate disponibili
* **Copertura Test per JOIN ed Eager Loading**: Aggiunti test completi per verificare supporto JOIN e relazioni nested:
  - **JoinEagerLoadingTest**: 19 test totali che coprono tutti gli aspetti delle funzionalità JOIN
  - Test normalizzazione sintassi relazioni: `testFlattenRelationsDotNotation()`, `testFlattenRelationsNestedArray()`, `testFlattenRelationsMixedSyntax()`
  - Test query custom con JOIN: `testCustomQueryWithJoinAndConditionOnJoinedTable()`, `testCustomQueryWithMultipleJoins()`, `testCustomQueryWithManualJoinAndCustomCondition()`
  - Test tipi di JOIN: `testCustomQuerySupportsCrossJoin()`, `testJoinTypeEnumHasAllCases()`
  - Test metodi helper: `testQueryAppendColumnForJoinedTables()`, `testBaseAdapterHasBuildJoinedColumnsMethod()`
  - Test qualificazione colonne: `testAllColumnsReturnsQualifiedNameWithTable()`, `testAllColumnsReturnsAsteriskWithoutTable()`
  - Test presenza metodi in classi base: `testBaseModelHasNestedRelationMethods()`, `testBaseResultSetHasNestedHydrationMethods()`

### 🔧 Miglioramenti Interni

* **BaseAdapter: Qualificazione Automatica delle Colonne con Nome Tabella**: Modificato `allColumns()` per accettare un parametro opzionale `$table` e restituire `table.*` quando fornito, invece di `*`. Questo centralizza la logica di qualificazione delle colonne nell'adapter (dove appartiene concettualmente, essendo formattazione SQL) invece che nella Query. Previene conflitti di nomi colonna sia con JOIN che senza, rendendo le query più robuste. La modifica è backward compatible grazie al parametro opzionale.
* **Router: Aggiunto metodo setMetaUrl()**: Introdotto il metodo `Router::setMetaUrl()` per permettere la sovrascrittura completa del metaUrl, completando l'API esistente che già forniva `getMetaUrl()`, `concatenateMetaUrl()` e `resetMetaUrl()`. Il nuovo metodo offre maggiore flessibilità nella gestione del routing e migliora la testabilità del componente.
* **RouterTest**: Aggiunti due nuovi test per il metodo `setMetaUrl()`: `testSetMetaUrl()` verifica l'impostazione corretta del valore, `testSetMetaUrlOverwritesPreviousValue()` verifica la sovrascrittura completa anche di valori precedentemente concatenati
* **Convenzione Naming Config**: Il file di configurazione del framework viene ora copiato come `configFramework.php` invece di `config.php`, permettendo ad ogni modulo di avere il proprio `config.php` senza conflitti
* **Correzioni Documentazione**: Corretti vari typo nella documentazione esistente dello scaffolding (es. "pattend" → "pattern", "tramikte" → "tramite", "prosuppone" → "presuppone")
* **Pulizia Formattazione**: Rimosso spazio superfluo nella generazione delle query SELECT in `BaseAdapter`
* **Ottimizzazione Type Check in BaseModel**: Correzione gestione enum in `isVariableOfType()` rimuovendo `enum_exists()` dalla condizione OR per evitare TypeError (BaseModel.php:236)
* **Ottimizzazione Nullable Check**: Invertite condizioni in `buildPropertiesArray()` per verificare prima `allowsNull()` (O(1)) poi `isVariableOfType()` (più costoso) migliorando le performance (BaseModel.php:217)
* **Refactoring DependentModel**: Rinominato `buildReferencedEntitiesConditions()` in `buildPropertyConditions()` per unificare logica tra entità referenziate e proprietà builtin

## [10.0.7] - 2025-11-17 - Correzione Bug SismaCollection

Questa patch release corregge un bug critico nella gestione delle entità persistenti all'interno delle SismaCollection.

### 🐛 Bug Fixes

#### Correzione Inserimento Entità Persistenti in SismaCollection

Corretto un bug nel metodo `addOrUpdateIntoEntityCollection()` della classe `ReferencedEntity` che causava errori durante l'inserimento in una SismaCollection di entità già persistenti dopo entità non ancora salvate:

*   **ReferencedEntity.php**:
    - ❌ **Prima**: Il confronto `$includedEntity->id === $entity->id` falliva quando `$includedEntity->id` era `null` (entità non ancora persistita)
    - ✅ **Dopo**: Aggiunto controllo `isset($includedEntity->id)` prima del confronto per evitare confronti con valori `null`
    - Codice corretto (linea 209):
    ```php
    // Prima (bug):
    if (isset($entity->id) && ($includedEntity->id === $entity->id)) {
        $includedEntity = $entity;
        $found = true;
    }
    
    // Dopo (corretto):
    if (isset($entity->id) && isset($includedEntity->id) && ($includedEntity->id === $entity->id)) {
        $includedEntity = $entity;
        $found = true;
    }
    ```

**Scenario del bug**:
1. Una SismaCollection contiene un'entità non ancora salvata (con `id = null`)
2. Si tenta di aggiungere un'entità già persistente (con `id` valorizzato)
3. Il confronto `null === 123` falliva, ma il controllo `isset()` mancava per `$includedEntity->id`
4. Questo poteva causare comportamenti imprevisti nell'aggiornamento della collection

**Impatto**: Risolve problemi di inconsistenza nelle SismaCollection quando si mescolano entità persistite e non persistite.

### 🧪 Testing

*   **ReferencedEntityTest.php**: Aggiunto test specifico per verificare il corretto inserimento di entità persistenti dopo entità non persistite

## [10.0.6] - 2025-11-07 - Refactoring Filter e Documentazione Migrazione

Questa patch release migliora la qualità del codice della classe Filter attraverso l'eliminazione di duplicazioni e il riordino dei metodi secondo i principi del Clean Code. Include inoltre la documentazione per la migrazione dalla versione 9.x alla 10.x.

### 🔧 Refactoring

#### Eliminazione Duplicazione Codice in Filter.php

Refactorizzata la classe `Filter` per eliminare codice duplicato nei metodi di validazione con limiti di lunghezza:

*   **Prima (10.0.5)**:
    - ❌ Codice duplicato in 12 metodi pubblici per validazione lunghezze (min, max, range)
    - ❌ Pattern ripetitivo con variabile `$result` e assegnazioni condizionali multiple
    - ❌ Esempio del pattern duplicato:
    ```php
    public function isMinLimitString($value, int $minLimit): bool
    {
        $result = true;
        $result = ($this->isString($value)) ? $result : false;
        $result = (strlen($value) >= $minLimit) ? $result : false;
        return $result;
    }
    ```

*   **Dopo (10.0.6)**:
    - ✅ Introdotti 3 metodi helper privati riutilizzabili
    - ✅ Pattern funzionale con callable e operatori booleani
    - ✅ Codice più conciso e dichiarativo:
    ```php
    public function isMinLimitString($value, int $minLimit): bool
    {
        return $this->isMinLengthForValidator($value, $minLimit, fn($v) => $this->isString($v));
    }

    private function isMinLengthForValidator(mixed $value, int $minLimit, callable $validator): bool
    {
        return $validator($value) && strlen($value) >= $minLimit;
    }
    ```

*   **Metodi Helper Introdotti**:
    - `isMinLengthForValidator()`: Valida lunghezza minima con validatore custom
    - `isMaxLengthForValidator()`: Valida lunghezza massima con validatore custom
    - `isLengthRangeForValidator()`: Valida range di lunghezza con validatore custom

*   **Metodi Refactorizzati** (12 totali):
    - String: `isMinLimitString()`, `isMaxLimitString()`, `isLimitString()`
    - AlphabeticString: `isMinLimitAlphabeticString()`, `isMaxLimitAlphabeticString()`, `isLimitAlphabeticString()`
    - AlphanumericString: `isMinLimitAlphanumericString()`, `isMaxLimitAlphanumericString()`, `isLimitAlphanumericString()`
    - StrictAlphanumericString: `isMinLimitStrictAlphanumericString()`, `isMaxLimitStrictAlphanumericString()`, `isLimitStrictAlphanumericString()`

*   **Riordino Metodi (Clean Code Stepdown Rule)**:
    - Metodi organizzati logicamente per categoria funzionale
    - Pattern coerente: validatore base → min → max → range
    - Metodi helper privati alla fine della classe

### 📚 Documentazione

#### Aggiunta Guida Migrazione 9.x → 10.x

Introdotto il file `UPGRADING.md` con documentazione completa per la migrazione:

*   **Breaking Changes Documentati**:
    - `CallableController::checkCompatibility()` ora metodo statico
    - Rimozione interfaccia `CrudInterface`
    - `Language::getFriendlyLabel()` richiede file di localizzazione

*   **Checklist di Migrazione**: Guida passo-passo per aggiornamento sicuro
*   **Esempi di Codice**: Prima/dopo per ogni breaking change
*   **Miglioramenti Non-Breaking**: Lazy loading database, refactoring DataMapper

### 📊 Metriche

*   **Filter.php**: -20 righe (-26% di duplicazione eliminata)
*   **Metodi pubblici invariati**: API backward compatible al 100%
*   **Metodi helper**: 3 nuovi metodi privati riutilizzabili
*   **Complessità ciclomatica**: Ridotta grazie a pattern funzionale

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: API pubblica completamente invariata
*   **Refactoring Interno**: Solo implementazione modificata, signature identiche
*   **Test Compatibili**: Tutti i test esistenti continuano a funzionare

## [10.0.5] - 2025-11-01 - Refactoring Architetturale DataMapper

Questa patch release rifattorizza il DataMapper monolitico introducendo una separazione delle responsabilità in classi dedicate, seguendo i principi SOLID e Clean Code.

### 🏗️ Architettura

#### Refactoring DataMapper: Da Monolite a Separazione delle Responsabilità

Suddiviso il DataMapper monolitico (420 righe) in componenti specializzati per migliorare manutenibilità e testabilità:

*   **Struttura Prima del Refactoring (10.0.4)**:
    - ❌ **DataMapper.php monolitico**: 420 righe contenenti tutta la logica (persistenza, transazioni, query di lettura, cache)
    - ❌ **Responsabilità miste**: Gestione transazioni, query di lettura, persistenza, cache, tutto in un unico file
    - ❌ **Metodo `getType()` privato**: Duplicazione logica per determinare tipi di binding nelle query
    - ❌ **Gestione transazioni inline**: Logica sparsa tra vari metodi (`startTransaction()`, `commitTransaction()`, flag statico `$isActiveTransaction`)
    - ❌ **Query di lettura inline**: Metodi `find()`, `findFirst()`, `getCount()` direttamente nel DataMapper con logica cache integrata

*   **Struttura Dopo il Refactoring (10.0.5)**:
    - ✅ **DataMapper.php**: 331 righe, responsabile solo di coordinamento persistenza e operazioni CRUD
    - ✅ **TransactionManager** (89 righe, classe `@internal`): Gestione isolata delle transazioni database
      - Metodi: `start()`, `commit()`, `rollback()`
      - Flag di stato transazione centralizzato
      - Testabile indipendentemente
    - ✅ **QueryExecutor** (151 righe, classe `@internal`): Esecuzione query di lettura con integrazione cache
      - Metodi: `find()`, `findFirst()`, `getCount()`, `setVariable()`
      - Logica cache isolata e riutilizzabile
      - Parametro esplicito `bool $ormCacheEnabled` passato ai metodi invece di dereferenziare proprietà
    - ✅ **DataType::fromReflection()**: Metodo statico pubblico per determinare tipi di binding automaticamente
      - Elimina duplicazione del metodo privato `getType()`
      - Riutilizzabile in altri contesti del framework

*   **PHP 8.1 Constructor Property Promotion**:
    - Adottato Constructor Property Promotion con `new` in initializers:
    ```php
    public function __construct(
        ?BaseAdapter $adapter = null,
        ?ProcessedEntitiesCollection $processedEntityCollection = null,
        ?Config $config = null,
        private TransactionManager $transactionManager = new TransactionManager(),
        private QueryExecutor $queryExecutor = new QueryExecutor()
    )
    ```
    - Ridotto boilerplate eliminando dichiarazioni di proprietà ridondanti
    - Dependency injection con valori di default per backward compatibility

*   **Delegazione Metodi Pubblici**:
    - `find()`, `findFirst()`, `getCount()`, `setVariable()` → delegati a `QueryExecutor`
    - `save()` → utilizza `TransactionManager::start()`, `commit()`, `rollback()`
    - Metodi di persistenza (`insert()`, `update()`, `delete()`, `parseValues()`) rimangono privati in DataMapper

*   **Stepdown Rule (Clean Code)**:
    - Metodi riorganizzati in ordine di chiamata top-down
    - Flusso naturale e leggibile: `save()` → `insert()`/`update()` → `parseValues()` → helper privati

### 🔧 Miglioramenti Interni

*   **Ridotta Complessità**: DataMapper passa da 420 a 331 righe (-21%)
*   **Single Responsibility Principle**: Ogni classe ha una responsabilità ben definita
*   **Testabilità**: TransactionManager e QueryExecutor testabili indipendentemente
*   **Eliminata Duplicazione**: `DataType::fromReflection()` sostituisce metodo privato `getType()`
*   **Stack Trace Più Chiari**: Nomi di classe/metodi espliciti invece di logica inline
*   **Dependency Injection**: Componenti iniettabili per facilitare testing e estensibilità

### ✅ Backward Compatibility

*   **API Pubblica Invariata**: Tutti i metodi pubblici mantengono firma identica
*   **Costruttore Backward Compatible**: Nuovi parametri opzionali alla fine con valori di default
*   **Nessun Breaking Change**: Codice esistente continua a funzionare senza modifiche
*   **Classi `@internal`**: TransactionManager e QueryExecutor sono marcate come interne, non parte dell'API pubblica stabile

### 📊 Metriche

*   **Prima (10.0.4)**: 1 file, 420 righe (DataMapper.php monolitico)
*   **Dopo (10.0.5)**: 3 file, 571 righe totali
    - DataMapper.php: 331 righe (-89 righe, -21%)
    - TransactionManager: 89 righe (nuovo)
    - QueryExecutor: 151 righe (nuovo)
*   **Responsabilità Separate**: 3 classi con ruoli distinti
*   **Complessità Ridotta**: Ogni classe più semplice da comprendere e manutenere

## [10.0.4] - 2025-10-22 - Miglioramenti Qualità Codice e Correzione Dispatcher

Questa patch release corregge un bug importante nella gestione del routing.

### 🐛 Bug Fixes

#### Correzione Impostazione URL nel Router

Corretto il momento in cui viene impostato l'URL attuale nel Router all'interno del Dispatcher:

*   **Dispatcher.php**:
    - ❌ **Prima**: `Router::setActualCleanUrl()` veniva chiamato prima del controllo dell'esistenza dell'action, impostando l'URL anche per azioni inesistenti
    - ✅ **Dopo**: `Router::setActualCleanUrl()` viene chiamato solo dopo aver verificato che l'action esista ed è valida (dentro il blocco `if`)
    - Corretto il secondo parametro da `$this->parsedAction` a `$this->pathAction` per maggiore coerenza con la nomenclatura

**Impatto**: Previene l'impostazione di URL per azioni non valide, migliorando la precisione del routing e la gestione degli errori 404.

## [10.0.3] - 2025-10-08 - Hotfix Test Suite

Questa hotfix release corregge i test rotti nella versione 10.0.2.

### 🐛 Bug Fixes

#### Ripristino Mock BaseAdapter nei Test con DataMapper Reale

Ripristinati i mock di `BaseAdapter` nei test che istanziano `DataMapper` con costruttore reale:

*   **Test Core**:
    - `DispatcherTest.php`, `ParserTest.php`, `NotationManagerTest.php`, `FixturesManagerTest.php`, `FilterTest.php`
    - `BaseFormTest.php`, `BaseFixtureTest.php`

**Causa del problema**: Questi test creano istanze di `DataMapper` con costruttore (non completamente mockato), che a sua volta istanzia `Query`, il cui costruttore chiama `BaseAdapter::getDefault()`. Senza il mock, `getDefault()` ritorna `null` causando errori `Call to a member function getAdapterClass() on null`.

**Soluzione**: Ripristinato `BaseAdapter::setDefault($baseAdapterMock)` in questi test specifici.

### ✅ Test Suite Finale

**Mock rimossi con successo (14 test)**:
- Test ORM: `ProcessedEntitiesCollectionTest.php`, `CacheTest.php`, `ResultSetMysqlTest.php`, `SelfReferencedEntityTest.php`, `ReferencedEntityTest.php`, `SelfReferencedModelTest.php`, `DependentModelTest.php`, `BaseEntityTest.php`, `BaseModelTest.php`, `SismaCollectionTest.php`
- Test Security: `AuthenticationTest.php`, `BaseVoterTest.php`, `BasePermissionTest.php`
- Test Core: `RenderTest.php`

**Mock mantenuti (7 test + 3 specifici ORM)**:
- Test Core con DataMapper reale: `DispatcherTest.php`, `ParserTest.php`, `NotationManagerTest.php`, `FixturesManagerTest.php`, `FilterTest.php`, `BaseFormTest.php`, `BaseFixtureTest.php`
- Test ORM specifici: `DataMapperTest.php`, `QueryTest.php`, `AdapterMysqlTest.php`

## [10.0.2] - 2025-10-08 - Ottimizzazione Connessione Database [RITIRATA]

**⚠️ NOTA**: Questa versione è stata ritirata a causa di test rotti. Utilizzare la versione 10.0.3 invece.

Questa patch release ottimizza significativamente le performance eliminando connessioni al database non necessarie attraverso l'implementazione del lazy loading in BaseAdapter.

### 🚀 Performance

#### Lazy Loading della Connessione Database

Implementato lazy loading della connessione al database in `BaseAdapter` per evitare connessioni inutili:

*   **BaseAdapter.php**:
    - ❌ **Prima**: La connessione veniva aperta nel costruttore, sempre e per qualsiasi richiesta
    - ✅ **Dopo**: La connessione viene aperta solo al primo utilizzo effettivo (primo `select()`, `execute()`, `beginTransaction()`, etc.)
    - Aggiunta proprietà `$isConnected` (bool) e `$connectionOptions` (array)
    - Aggiunto metodo `ensureConnected()` per apertura on-demand
    - Metodi wrappati con lazy loading: `select()`, `execute()`, `beginTransaction()`, `commitTransaction()`, `rollbackTransaction()`, `lastInsertId()`
    - Pattern di delegazione esteso con nuovi metodi: `beginTransactionToDelegateAdapter()`, `commitTransactionToDelegateAdapter()`, `rollbackTransactionToDelegateAdapter()`, `lastInsertIdToDelegateAdapter()`

*   **AdapterMysql.php**:
    - Aggiornate signature dei metodi per il pattern di delegazione
    - Rinominati: `beginTransaction()` → `beginTransactionToDelegateAdapter()`, `commitTransaction()` → `commitTransactionToDelegateAdapter()`, `rollbackTransaction()` → `rollbackTransactionToDelegateAdapter()`, `lastInsertId()` → `lastInsertIdToDelegateAdapter()`

**Impatto sulle performance**:
- **0 connessioni DB** per file statici (CSS, JS, immagini, fonts)
- **0 connessioni DB** per crawl components (robots.txt, sitemap.xml)
- **0 connessioni DB** per richieste 404 immediate
- **1 connessione DB** solo quando effettivamente necessaria per query/transazioni
- Riduzione significativa del carico sul database server
- Miglioramento dei tempi di risposta per richieste non-database

### 🧪 Testing

#### Semplificazione Test Suite

Rimossi 21 mock di `BaseAdapter` non più necessari grazie al lazy loading:

*   **Test Core**:
    - `DispatcherTest.php`, `ParserTest.php`, `NotationManagerTest.php`, `FixturesManagerTest.php`, `FilterTest.php`, `RenderTest.php`
    - `BaseFormTest.php`, `BaseFixtureTest.php`

*   **Test ORM**:
    - `ProcessedEntitiesCollectionTest.php`, `CacheTest.php`, `ResultSetMysqlTest.php`
    - `SelfReferencedEntityTest.php`, `ReferencedEntityTest.php`, `SelfReferencedModelTest.php`, `DependentModelTest.php`
    - `BaseEntityTest.php`, `BaseModelTest.php`, `SismaCollectionTest.php`

*   **Test Security**:
    - `AuthenticationTest.php`, `BaseVoterTest.php`, `BasePermissionTest.php`

**Impatto**: Test più puliti e leggibili, eliminando boilerplate di setup per il mock del database.

### ✅ Backward Compatibility

*   **Nessun Breaking Change**: L'API pubblica rimane identica
*   **Comportamento Trasparente**: Il lazy loading è completamente trasparente per il codice esistente
*   **Compatibilità Test**: I test esistenti continuano a funzionare senza modifiche

## [10.0.1] - 2025-09-25 - Correzione Bug Router

Questa patch release corregge un bug nella generazione degli URL con il Router.

### 🐛 Bug Fixes

#### Correzione Generazione URL con Parametri Query String

Corretto il metodo `Router::makeCleanUrl()` per gestire correttamente i parametri query string:

*   **Router.php**:
    - ❌ **Prima**: I parametri query string venivano sempre aggiunti come `?param=value` anche quando l'URL aveva già una query string
    - ✅ **Dopo**: Utilizzato `http_build_query()` per costruire correttamente la query string e concatenarla con `?` o `&` in base alla presenza di query string esistente nell'URL

**Esempio**:
```php
// Prima (bug):
Router::makeCleanUrl('/search', ['q' => 'test', 'page' => 2])
// Output errato: /search?q=test?page=2

// Dopo (corretto):
Router::makeCleanUrl('/search', ['q' => 'test', 'page' => 2])
// Output corretto: /search?q=test&page=2
```

**Impatto**: Risolve problemi di URL malformati quando si passano parametri query string al Router.

## [10.0.0] - 2025-09-15 - Release Maggiore con Breaking Changes

Questa major release introduce breaking changes significativi per migliorare la qualità del codice e l'architettura del framework.

### 💥 Breaking Changes

#### 1. CallableController::checkCompatibility() è ora statico

**Motivazione**: Il metodo `checkCompatibility()` non dovrebbe dipendere dallo stato dell'istanza del controller.

*   **Prima (9.x)**:
```php
class MyController extends BaseController implements CallableController
{
    public function checkCompatibility(array $arguments): bool
    {
        return count($arguments) === 2;
    }
}
```

*   **Dopo (10.x)**:
```php
class MyController extends BaseController implements CallableController
{
    public static function checkCompatibility(array $arguments): bool
    {
        return count($arguments) === 2;
    }
}
```

**Azione richiesta**: Aggiungere la keyword `static` alla firma del metodo `checkCompatibility()` in tutti i controller che implementano `CallableController`.

---

#### 2. Rimozione dell'interfaccia CrudInterface

**Motivazione**: L'interfaccia `CrudInterface` non forniva valore aggiunto rispetto a `BaseController` e creava confusione.

*   **Prima (9.x)**:
```php
class PostController extends BaseController implements CrudInterface
{
    // Implementazione
}
```

*   **Dopo (10.x)**:
```php
class PostController extends BaseController
{
    // Implementazione (nessuna modifica ai metodi)
}
```

**Azione richiesta**: Rimuovere `implements CrudInterface` dalla dichiarazione delle classi controller. Nessuna modifica ai metodi è necessaria.

---

#### 3. Language::getFriendlyLabel() richiede file di localizzazione

**Motivazione**: Eliminare valori hardcoded e centralizzare le traduzioni in file di configurazione.

*   **Prima (9.x)**:
```php
// Funzionava anche senza file di localizzazione
$label = Language::getFriendlyLabel('it');
// Output: "Italiano" (hardcoded)
```

*   **Dopo (10.x)**:
```php
// Richiede il file config/locales/it.json con:
// {
//   "language": {
//     "friendly_label": "Italiano"
//   }
// }
$label = Language::getFriendlyLabel('it');
// Output: "Italiano" (da file di configurazione)
```

**Azione richiesta**:
1. Creare la directory `config/locales/` se non esiste
2. Per ogni lingua supportata, creare un file JSON (es. `it.json`, `en.json`)
3. Aggiungere la struttura richiesta con il nome della lingua

**Esempio di file di localizzazione**:

`config/locales/it.json`:
```json
{
  "language": {
    "friendly_label": "Italiano"
  }
}
```

`config/locales/en.json`:
```json
{
  "language": {
    "friendly_label": "English"
  }
}
```

---

### 🚀 Miglioramenti

*   **Qualità del Codice**: Eliminato codice legacy e migliorata la consistenza dell'architettura
*   **Manutenibilità**: Localizzazione centralizzata e interfacce più pulite
*   **Type Safety**: Maggiore utilizzo della tipizzazione forte di PHP 8.1+

### 📚 Migrazione

Per una guida completa alla migrazione dalla versione 9.x alla 10.x, consultare il file [UPGRADING.md](UPGRADING.md).

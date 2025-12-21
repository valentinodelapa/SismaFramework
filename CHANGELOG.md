# Changelog

All notable changes to this project will be documented in this file.

## [11.0.0] - 2025-11-18 - Rifattorizzazione Architetturale e Semplificazione API

Questa major release introduce miglioramenti architetturali significativi: rifattorizzazione completa di BaseForm con principi SOLID e semplificazione API Response attraverso rimozione del metodo pubblico setResponseType().

La release introduce breaking changes: il metodo astratto customFilter() di BaseForm ora ritorna bool invece di void, e il metodo pubblico setResponseType() di Response è stato rimosso in favore dell'immutabilità tramite constructor injection.
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

### 💥 Breaking Changes

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

### 📝 Documentazione

* **Classi Marcate @internal**: Le tre nuove classi helper sono marcate con l'annotazione `@internal` per indicare che fanno parte dell'implementazione interna di `BaseForm` e non dovrebbero essere utilizzate direttamente dagli sviluppatori.

### 🔄 Compatibilità

**Questa è una major release (11.0.0)** che introduce breaking changes. L'aggiornamento richiede modifiche al codice esistente:

- ⚠️ **Richiesta modifica**: Tutte le classi che estendono `BaseForm` devono aggiornare il metodo `customFilter()` per ritornare `bool`
- ✅ **Retrocompatibilità API**: Tutti gli altri metodi pubblici e protetti di `BaseForm` mantengono la stessa interfaccia
- ✅ **Nessun impatto su DataMapper/ORM**: Le modifiche sono isolate al sistema di form

### 📋 Checklist di Migrazione da 10.x a 11.0.0

- [ ] **Form con customFilter()**
  - [ ] Aggiungere tipo di ritorno `: bool` alla firma del metodo `customFilter()`
  - [ ] Ritornare `true` quando la validazione custom ha successo
  - [ ] Ritornare `false` quando la validazione custom fallisce
  - [ ] Verificare che la logica di validazione custom sia corretta

- [ ] **Testing**
  - [ ] Eseguire tutti i test unitari
  - [ ] Verificare che i form funzionino correttamente in tutti i flussi
  - [ ] Testare sia casi di validazione con successo che con fallimento


- [ ] **Utilizzo di Response**
  - [ ] Cercare tutte le occorrenze di ->setResponseType(
  - [ ] Sostituire con constructor injection: new Response(ResponseType::...)
  - [ ] Se necessario modificare response type, creare nuova istanza invece di chiamare metodo

- [ ] **Testing**
  - [ ] Eseguire tutti i test unitari
  - [ ] Verificare che i form funzionino correttamente in tutti i flussi
  - [ ] Testare sia casi di validazione con successo che con fallimento
  - [ ] Verificare che tutti i response codes siano impostati correttamente

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

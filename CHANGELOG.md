# Changelog

All notable changes to this project will be documented in this file.

## [11.0.0] - 2025-11-18 - Rifattorizzazione Architetturale BaseForm con Principi SOLID

Questa major release rifattorizza completamente la classe `BaseForm` applicando il Single Responsibility Principle, estraendo le responsabilità in tre classi specializzate che migliorano manutenibilità e testabilità del sistema di gestione form.

La rifattorizzazione introduce **breaking changes**: il metodo astratto `customFilter()` ora ritorna `bool` invece di `void`, permettendo una gestione più coerente della validazione custom e contribuendo al risultato finale di validità del form.

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

---

## [10.1.0] - 2025-12-02 - Strumenti CLI per Scaffolding, Installazione e Rifatorizzazione Dispatcher

Benvenuti alla release 10.1.0, una delle più ricche di novità nella storia del framework! Utility CLI rivoluzionano il flusso di sviluppo quotidiano, con scaffolding automatico e installazione guidata che accelerano drasticamente la creazione di nuovi progetti. Ottimizzato profondamente il Dispatcher attraverso una rifatorizzazione completa seguendo i principi SOLID, separando le responsabilità in sette classi specializzate che rendono il codice più manutenibile e testabile.

Nascono nuove funzionalità per l'ORM: le funzioni di aggregazione SQL (AVG, MAX, MIN, SUM) permettono ora query analitiche avanzate con supporto per DISTINCT, alias, subquery e aggregazioni multiple.

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

### 🧪 Testing

* **Copertura Test Completa**: Aggiunti test completi per tutti i nuovi componenti:
  - **ScaffoldCommandTest**: 4 test con mock del ScaffoldingManager
  - **ScaffoldingManagerTest**: 10 test che verificano generazione per BaseEntity, SelfReferencedEntity, DependentEntity, custom types, custom templates, e gestione errori
  - **InstallationCommandTest**: 8 test con mock dell'InstallationManager, inclusi test per opzioni database e gestione eccezioni
  - **InstallationManagerTest**: 8 test con filesystem temporaneo per verificare creazione struttura, copia file, aggiornamento config, e gestione flag `--force`
* **Output Buffer Corretto**: Tutti i test catturano correttamente l'output dei comandi senza "sporcare" la console di PHPUnit

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

### 🧪 Testing

* **Copertura Test Completa per Funzioni di Aggregazione**: Aggiunti test completi per le nuove funzionalità:
  - **AggregationFunctionTest**: 159 linee di test che verificano tutti i casi dell'enumerazione e la corretta generazione SQL per MySQL
  - **QueryTest**: 149 linee di test per i nuovi metodi `setAVG()`, `setMax()`, `setMin()`, `setSum()` con varie combinazioni di parametri (distinct, append, alias, subquery)
  - **AdapterMysqlTest**: 57 linee di test per verificare il metodo `opAggregationFunction()` con tutte le funzioni aggregate disponibili

### 🔧 Miglioramenti Interni

* **Convenzione Naming Config**: Il file di configurazione del framework viene ora copiato come `configFramework.php` invece di `config.php`, permettendo ad ogni modulo di avere il proprio `config.php` senza conflitti
* **Correzioni Documentazione**: Corretti vari typo nella documentazione esistente dello scaffolding (es. "pattend" → "pattern", "tramikte" → "tramite", "prosuppone" → "presuppone")
* **Pulizia Formattazione**: Rimosso spazio superfluo nella generazione delle query SELECT in `BaseAdapter`

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

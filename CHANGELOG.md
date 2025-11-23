# Changelog

All notable changes to this project will be documented in this file.


## [10.1.0] - 2025-12-02 - Strumenti CLI per Scaffolding, Installazione e Rifatorizzazione Dispatcher

Benvenuti alla release 10.1.0, una delle più ricche di novità nella storia del framework! Utility CLI rivoluzionano il flusso di sviluppo quotidiano, con scaffolding automatico e installazione guidata che accelerano drasticamente la creazione di nuovi progetti. Ottimizzato profondamente il Dispatcher attraverso una rifatorizzazione completa seguendo i principi SOLID, separando le responsabilità in sette classi specializzate che rendono il codice più manutenibile e testabile.

Nascono nuove funzionalità per l'ORM: le funzioni di aggregazione SQL (AVG, MAX, MIN, SUM) permettono ora query analitiche avanzate con supporto per DISTINCT, alias, subquery e aggregazioni multiple, mentre l'estensione del sistema di query dinamiche con metaprogrammazione a tutte le proprietà (non più solo entità referenziate) riduce drasticamente la necessità di scrivere metodi repository ripetitivi generando automaticamente query type-safe.

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

### 🧪 Testing

* **Copertura Test Completa per Funzioni di Aggregazione**: Aggiunti test completi per le nuove funzionalità:
  - **AggregationFunctionTest**: 159 linee di test che verificano tutti i casi dell'enumerazione e la corretta generazione SQL per MySQL
  - **QueryTest**: 149 linee di test per i nuovi metodi `setAVG()`, `setMax()`, `setMin()`, `setSum()` con varie combinazioni di parametri (distinct, append, alias, subquery)
  - **AdapterMysqlTest**: 57 linee di test per verificare il metodo `opAggregationFunction()` con tutte le funzioni aggregate disponibili

### 🔧 Miglioramenti Interni

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

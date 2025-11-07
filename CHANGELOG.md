# Changelog

All notable changes to this project will be documented in this file.


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

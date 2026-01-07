# Guida all'Aggiornamento di SismaFramework

Questa guida fornisce istruzioni dettagliate per aggiornare SismaFramework tra versioni major.

## Indice

- [Da 10.x a 11.x](#da-10x-a-11x)
- [Da 9.x a 10.x](#da-9x-a-10x)

---

## Da 10.x a 11.x

La versione 11.0.0 introduce modifiche architetturali significative e breaking changes. Questa sezione fornisce una guida completa per la migrazione.

### Breaking Changes

#### 1. BaseForm::customFilter() ora ritorna bool

**Impatto**: Medio  
**Componenti interessati**: Tutti i form che estendono `BaseForm`

Il metodo astratto `customFilter()` è stato modificato per ritornare `bool` invece di `void`, permettendo di contribuire al risultato finale della validazione.

**Prima (10.x)**:
```php
class ProductForm extends BaseForm
{
    protected function customFilter(): void
    {
        if ($this->entity->startDate > $this->entity->endDate) {
            $this->formFilterError->startDateError = true;
        }
    }
}
```

**Dopo (11.x)**:
```php
class ProductForm extends BaseForm
{
    protected function customFilter(): bool
    {
        if ($this->entity->startDate > $this->entity->endDate) {
            $this->formFilterError->startDateError = true;
            return false;
        }
        return true;
    }
}
```

**Azione richiesta**:
- Aggiungere tipo di ritorno `: bool` alla firma del metodo
- Ritornare `true` quando la validazione custom ha successo
- Ritornare `false` quando la validazione custom fallisce

---

#### 2. Rimozione di Response::setResponseType()

**Impatto**: Basso  
**Componenti interessati**: Codice che modifica il response type dopo la creazione dell'oggetto

Il metodo pubblico `setResponseType()` è stato rimosso in favore dell'immutabilità tramite constructor injection.

**Prima (10.x)**:
```php
$response = new Response();
$response->setResponseType(ResponseType::httpNotFound);
```

**Dopo (11.x)**:
```php
$response = new Response(ResponseType::httpNotFound);
```

**Azione richiesta**:
- Sostituire tutte le chiamate a `setResponseType()` passando il `ResponseType` al costruttore
- Se necessario modificare il response type, creare una nuova istanza di `Response`

---

#### 3. ErrorHandler e Debugger: da metodi statici a istanze

**Impatto**: Alto  
**Componenti interessati**: File `Public/index.php` e codice che utilizza `ErrorHandler` o `Debugger`

Le classi `ErrorHandler` e `Debugger` sono state trasformate da classi con metodi statici a classi di istanza con dependency injection.

**Prima (10.x)**:
```php
// Public/index.php
ErrorHandler::disableErrorDisplay();
ErrorHandler::handleNonThrowableError();
Debugger::startExecutionTimeCalculation();
```

**Dopo (11.x)**:
```php
// Public/index.php
$errorHandler = new ErrorHandler();
$errorHandler->disableErrorDisplay();
$errorHandler->registerNonThrowableErrorHandler();
$debugger = new Debugger();
$debugger->startExecutionTimeCalculation();
$dispatcher = new Dispatcher(debugger: $debugger);
```

**Azione richiesta**:
- Aggiornare il file `Public/index.php` per creare istanze invece di usare metodi statici
- Sostituire `handleNonThrowableError()` con `registerNonThrowableErrorHandler()`
- Iniettare `$debugger` nel costruttore di `Dispatcher`
- (Opzionale) Iniettare un logger PSR-3 custom nel costruttore di `ErrorHandler` se necessario

---

#### 4. Correzione typo: Encryptor::createInitializationVector()

**Impatto**: Basso  
**Componenti interessati**: Codice che usa manualmente la crittografia

Il metodo `createInizializationVector()` è stato rinominato in `createInitializationVector()` per correggere un errore di spelling.

**Prima (10.x)**:
```php
$iv = Encryptor::createInizializationVector();
```

**Dopo (11.x)**:
```php
$iv = Encryptor::createInitializationVector();
```

**Azione richiesta**:
- Cercare tutte le occorrenze di `createInizializationVector` nel codebase
- Sostituire con `createInitializationVector` (con la "t" invece della "z")

---

### Miglioramenti non Breaking

#### Implementazione PSR-3 per il Logging

Il framework ora implementa completamente lo standard PSR-3 per il logging, permettendo l'integrazione con logger di terze parti come Monolog.

**Nuove classi**:
- `SismaLogger`: Implementa `Psr\Log\LoggerInterface`
- `SismaLogReader`: Lettura strutturata dei log
- `ShouldBeLoggedException`: Marker interface per eccezioni che devono essere loggate

**Esempio di utilizzo con logger custom**:
```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$monolog = new Logger('app');
$monolog->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));

$errorHandler = new ErrorHandler(logger: $monolog);
$errorHandler->registerNonThrowableErrorHandler();
```

**Nessuna azione richiesta** - il logger di default funziona automaticamente.

---

#### Rifattorizzazione BaseForm con Principi SOLID

La classe `BaseForm` è stata rifattorizzata estraendo responsabilità in classi dedicate:
- `FilterManager`: Gestione filtri di validazione
- `FormValidator`: Validazione completa del form
- `EntityResolver`: Risoluzione e popolamento delle entità

**Nessuna azione richiesta** - l'API pubblica rimane invariata.

---

#### Pattern Facade per Render e Router

Le classi `Render` e `Router` ora implementano il pattern Facade, delegando la logica a `RenderService` e `RouterService`.

**Nuovo utilizzo preferito nei controller**:
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
}
```

**Nessuna azione richiesta** - la sintassi statica continua a funzionare, ma la sintassi di istanza è preferita.

---

### Checklist di Migrazione

Utilizza questa checklist per assicurarti di aver completato tutti i passaggi necessari:

- [ ] **Form con customFilter()**
  - [ ] Aggiunto tipo di ritorno `: bool` alla firma del metodo
  - [ ] Ritornato `true` per validazioni con successo
  - [ ] Ritornato `false` per validazioni fallite
  - [ ] Testata la logica di validazione custom
  
- [ ] **Utilizzo di Response**
  - [ ] Cercate tutte le occorrenze di `->setResponseType(`
  - [ ] Sostituite con constructor injection
  
- [ ] **ErrorHandler e Debugger**
  - [ ] Aggiornato `Public/index.php` per creare istanze
  - [ ] Sostituito `handleNonThrowableError()` con `registerNonThrowableErrorHandler()`
  - [ ] Iniettato `$debugger` in `Dispatcher`
  - [ ] Verificate altre chiamate statiche a `ErrorHandler` o `Debugger`
  
- [ ] **Encryptor (se applicabile)**
  - [ ] Cercate occorrenze di `createInizializationVector`
  - [ ] Sostituite con `createInitializationVector`
  
- [ ] **Testing**
  - [ ] Eseguiti tutti i test unitari
  - [ ] Testati tutti i form in ambiente di sviluppo
  - [ ] Verificato funzionamento del logging
  - [ ] Verificato funzionamento in ambiente di staging

---

## Da 9.x a 10.x

La versione 10.0.0 introduce alcune modifiche che rompono la retrocompatibilità. Questa sezione fornisce una guida completa per la migrazione.

### Breaking Changes

#### 1. CallableController::checkCompatibility() è ora statico

**Impatto**: Medio  
**Componenti interessati**: Controller che implementano `CallableController`

Il metodo `checkCompatibility()` nell'interfaccia `CallableController` è stato modificato da metodo di istanza a metodo statico.

**Prima (9.x)**:
```php
class MyController extends BaseController implements CallableController
{
    public function checkCompatibility(array $arguments): bool
    {
        return count($arguments) === 2;
    }
}
```

**Dopo (10.x)**:
```php
class MyController extends BaseController implements CallableController
{
    public static function checkCompatibility(array $arguments): bool
    {
        return count($arguments) === 2;
    }
}
```

**Azione richiesta**:
- Aggiungere la keyword `static` alla firma del metodo `checkCompatibility()`
- Se il metodo accede a proprietà di istanza (`$this`), refactorizzare per utilizzare solo i parametri passati

---

#### 2. Rimozione dell'interfaccia CrudInterface

**Impatto**: Basso  
**Componenti interessati**: Controller che implementano `CrudInterface`

L'interfaccia `CrudInterface` è stata rimossa in quanto non forniva valore aggiunto rispetto a `BaseController`.

**Prima (9.x)**:
```php
class PostController extends BaseController implements CrudInterface
{
    // Implementazione
}
```

**Dopo (10.x)**:
```php
class PostController extends BaseController
{
    // Implementazione (nessuna modifica ai metodi)
}
```

**Azione richiesta**:
- Rimuovere `implements CrudInterface` dalla dichiarazione della classe
- Nessuna modifica ai metodi è necessaria

---

#### 3. Language::getFriendlyLabel() richiede file di localizzazione

**Impatto**: Alto  
**Componenti interessati**: Tutti i progetti che utilizzano `Language::getFriendlyLabel()`

Il metodo `getFriendlyLabel()` non utilizza più valori hardcoded ma richiede la presenza di file di localizzazione nella directory `config/locales/`.

**Prima (9.x)**:
```php
// Funzionava anche senza file di localizzazione
$label = Language::getFriendlyLabel('it');
```

**Dopo (10.x)**:
```php
// Richiede il file config/locales/it.json con:
// {
//   "language": {
//     "friendly_label": "Italiano"
//   }
// }
$label = Language::getFriendlyLabel('it');
```

**Azione richiesta**:
1. Creare la directory `config/locales/` se non esiste
2. Per ogni lingua supportata, creare un file JSON (es. `it.json`, `en.json`)
3. Aggiungere la struttura richiesta:
```json
{
  "language": {
    "friendly_label": "Nome Lingua"
  }
}
```

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

### Miglioramenti non Breaking

#### Lazy Loading della connessione database

La connessione al database viene ora stabilita solo quando effettivamente necessaria, migliorando le performance per operazioni che non richiedono accesso al database.

**Nessuna azione richiesta** - funziona automaticamente.

---

#### Refactoring del DataMapper

Il sistema ORM è stato refactorizzato per migliorare la manutenibilità e la testabilità:
- Introdotto `TransactionManager` per la gestione delle transazioni
- Introdotto `QueryExecutor` per l'esecuzione delle query
- Migliorata la separazione delle responsabilità

**Nessuna azione richiesta** - l'API pubblica rimane invariata.

---

### Checklist di Migrazione

Utilizza questa checklist per assicurarti di aver completato tutti i passaggi necessari:

- [ ] **Controller con CallableController**
  - [ ] Aggiunto `static` a tutti i metodi `checkCompatibility()`
  - [ ] Verificato che `checkCompatibility()` non utilizzi `$this`
  
- [ ] **Controller con CrudInterface**
  - [ ] Rimosso `implements CrudInterface` dalle dichiarazioni delle classi
  
- [ ] **Localizzazione**
  - [ ] Creata directory `config/locales/`
  - [ ] Creati file JSON per ogni lingua supportata
  - [ ] Aggiunta struttura `language.friendly_label` in ogni file
  - [ ] Testato `Language::getFriendlyLabel()` per tutte le lingue
  
- [ ] **Testing**
  - [ ] Eseguiti tutti i test unitari
  - [ ] Eseguiti test di integrazione
  - [ ] Verificato funzionamento in ambiente di staging

---

### Supporto e Risorse

- **Changelog completo**: Vedi [CHANGELOG.md](CHANGELOG.md)
- **Policy di supporto**: Vedi [SECURITY.md](SECURITY.md)
- **Issue tracker**: [GitHub Issues](https://github.com/tuouser/sismaframework/issues)

Per domande o problemi durante la migrazione, apri una issue sul repository GitHub.

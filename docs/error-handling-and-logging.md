# Gestione Errori e Logging

SismaFramework fornisce un sistema robusto per la gestione degli errori e il logging, essenziale sia in fase di sviluppo che in produzione.

## Gestione degli Errori

Il framework è progettato per catturare tutti gli errori e le eccezioni non gestite, prevenendo il "crash" dell'applicazione e la visualizzazione di messaggi di errore sensibili agli utenti finali.

Il file `Public/index.php` contiene il blocco `try...catch` principale che si affida alla classe `ErrorHandler` per:
*   **In ambiente di produzione (`DEVELOPMENT_ENVIRONMENT = false`):** Registrare l'errore nel file di log e mostrare all'utente una pagina di errore generica (es. Errore 500), senza rivelare dettagli tecnici.
*   **In ambiente di sviluppo (`DEVELOPMENT_ENVIRONMENT = true`):** Mostrare una pagina di errore dettagliata con la traccia completa dello stack (stack trace) per facilitare il debug.

## Logging

Il logging è uno strumento indispensabile per monitorare il comportamento dell'applicazione e diagnosticare problemi.

### Configurazione

Il comportamento del logger può essere personalizzato tramite diverse costanti nel file `Config/config.php`:

*   `LOG_DIRECTORY_PATH` e `LOG_PATH`: Definiscono dove viene salvato il file di log.
*   `LOG_VERBOSE_ACTIVE`: Se impostato su `true`, verranno registrati anche i messaggi di livello "informativo", non solo gli errori.
*   `LOG_DEVELOPMENT_MAX_ROW` e `LOG_PRODUCTION_MAX_ROW`: Controllano la rotazione del log, ovvero il numero massimo di righe prima che il file venga archiviato e ne venga creato uno nuovo.

### Scrivere nel Log

Il sistema di logging di SismaFramework è progettato per funzionare principalmente in modo **automatico** in risposta a errori ed eccezioni.

#### Logging Automatico (tramite Eccezioni)

Il caso d'uso principale del `Logger` è la registrazione automatica degli errori. Quando un'eccezione non viene gestita, viene catturata dal blocco `try...catch` in `Public/index.php`. Questo invoca la classe `ErrorHandler`, che a sua volta estrae tutte le informazioni rilevanti dall'eccezione (messaggio, codice, file, linea e stack trace) e le passa ai metodi `Logger::saveLog()` e `Logger::saveTrace()`.

Questo significa che non devi fare nulla per registrare gli errori: il framework lo fa per te.

#### Logging Manuale (Avanzato)

Sebbene non sia il suo scopo primario, è possibile invocare manualmente il `Logger` per registrare eventi specifici che non sono necessariamente errori. Per fare ciò, devi usare il metodo statico `Logger::saveLog()`.

Questo metodo richiede di fornire esplicitamente tutte le informazioni necessarie.

**`Logger::saveLog(string $message, int|string $code, string $file, string $line)`**

##### Esempio di Utilizzo in un Controller

```php
namespace MyModule\Application\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HelperClasses\Logger;
use SismaFramework\Core\HttpClasses\Response;

class AdminController extends BaseController
{
    public function performSensitiveAction(): Response
    {
        // ... logica dell'azione ...
        
        // Registriamo un evento importante per scopi di audit
        Logger::saveLog('L\'utente admin ha eseguito un\'azione sensibile.', 'AUDIT', __FILE__, __LINE__);
        
        // ... resto della logica ...
    }
}
```

I messaggi registrati appariranno nel file di log (Logs/log.txt) e, se sei in ambiente di sviluppo, saranno visibili anche nella sezione "Log" della Barra di Debug

* * *

[Indice](index.md) | Precedente: [Data Fixtures](data-fixtures.md) | Successivo: [Best Practices](best-practices.md)
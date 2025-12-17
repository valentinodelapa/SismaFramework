# Gestione Errori e Logging

SismaFramework fornisce un sistema robusto per la gestione degli errori e il logging, essenziale sia in fase di sviluppo che in produzione.

## Gestione degli Errori

Il framework è progettato per catturare tutti gli errori e le eccezioni non gestite, prevenendo il "crash" dell'applicazione e la visualizzazione di messaggi di errore sensibili agli utenti finali.

Il file `Public/index.php` contiene il blocco `try...catch` principale che si affida alla classe `ErrorHandler` per:
*   **In ambiente di produzione (`DEVELOPMENT_ENVIRONMENT = false`):** Registrare l'errore nel file di log e mostrare all'utente una pagina di errore generica (es. Errore 500), senza rivelare dettagli tecnici.
*   **In ambiente di sviluppo (`DEVELOPMENT_ENVIRONMENT = true`):** Mostrare una pagina di errore dettagliata con la traccia completa dello stack (stack trace) per facilitare il debug.

## Logging

Il logging è uno strumento indispensabile per monitorare il comportamento dell'applicazione e diagnosticare problemi.

### PSR-3 Logger Interface

SismaFramework implementa lo standard **PSR-3 (Logger Interface)**, garantendo compatibilità con l'ecosistema PHP moderno e permettendoti di sostituire il logger predefinito con qualsiasi implementazione PSR-3 compatibile (come Monolog).

Il framework fornisce due componenti principali:

*   **`SismaLogger`**: Implementa `Psr\Log\LoggerInterface` per la scrittura dei log
*   **`SismaLogReader`**: Fornisce metodi per leggere e gestire i file di log

### Configurazione

Il comportamento del logger può essere personalizzato tramite diverse costanti nel file `Config/config.php`:

*   `LOG_DIRECTORY_PATH` e `LOG_PATH`: Definiscono dove viene salvato il file di log.
*   `LOG_VERBOSE_ACTIVE`: Se impostato su `true`, verranno registrati anche i messaggi di livello "informativo", non solo gli errori.
*   `LOG_DEVELOPMENT_MAX_ROW` e `LOG_PRODUCTION_MAX_ROW`: Controllano la rotazione del log, ovvero il numero massimo di righe prima che il file venga archiviato e ne venga creato uno nuovo.

### Scrivere nel Log

Il sistema di logging di SismaFramework è progettato per funzionare principalmente in modo **automatico** in risposta a errori ed eccezioni.

#### Logging Automatico (tramite Eccezioni)

Il caso d'uso principale del logger è la registrazione automatica degli errori. Quando un'eccezione non viene gestita, viene catturata dal blocco `try...catch` in `Public/index.php`. Questo invoca la classe `ErrorHandler`, che utilizza il logger PSR-3 per registrare tutte le informazioni rilevanti dall'eccezione (messaggio, codice, file, linea e stack trace).

Questo significa che non devi fare nulla per registrare gli errori: il framework lo fa per te.

#### Logging Manuale (Avanzato)

Puoi utilizzare il logger PSR-3 per registrare eventi specifici che non sono necessariamente errori. Il logger supporta tutti i livelli standard PSR-3:

**Livelli di Log Disponibili:**

*   `emergency()` - Sistema inutilizzabile
*   `alert()` - Azione richiesta immediatamente
*   `critical()` - Condizioni critiche
*   `error()` - Errori di runtime
*   `warning()` - Avvertimenti
*   `notice()` - Eventi normali ma significativi
*   `info()` - Messaggi informativi
*   `debug()` - Informazioni dettagliate di debug

##### Esempio di Utilizzo in un Controller

```php
namespace MyModule\Application\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HelperClasses\SismaLogger;
use SismaFramework\Core\HttpClasses\Response;
use Psr\Log\LogLevel;

class AdminController extends BaseController
{
    private SismaLogger $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = new SismaLogger();
    }

    public function performSensitiveAction(): Response
    {
        // ... logica dell'azione ...

        // Registriamo un evento importante per scopi di audit
        $this->logger->info('L\'utente admin ha eseguito un\'azione sensibile.', [
            'code' => 'AUDIT',
            'file' => __FILE__,
            'line' => __LINE__
        ]);

        // Oppure per eventi critici
        $this->logger->warning('Tentativo di accesso non autorizzato', [
            'code' => 'SECURITY',
            'file' => __FILE__,
            'line' => __LINE__
        ]);

        // ... resto della logica ...
    }
}
```

I messaggi registrati appariranno nel file di log (Logs/log.txt) e, se sei in ambiente di sviluppo, saranno visibili anche nella sezione "Log" della Barra di Debug.

### Utilizzo di Logger PSR-3 Personalizzati

Grazie alla compatibilità PSR-3, puoi facilmente sostituire `SismaLogger` con qualsiasi altra implementazione, come Monolog:

```php
// Esempio con Monolog
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));

$logger->warning('Questo è un messaggio di warning');
$logger->error('Questo è un messaggio di errore', ['code' => 500]);
```

Questo ti permette di sfruttare feature avanzate come l'invio di log a servizi esterni (Slack, email, Sentry, ecc.) senza modificare il core del framework

* * *

[Indice](index.md) | Precedente: [Data Fixtures](data-fixtures.md) | Successivo: [Best Practices](best-practices.md)
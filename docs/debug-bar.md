# Barra di Debug

SismaFramework include una barra di debug integrata, uno strumento indispensabile durante lo sviluppo. Appare in fondo alla pagina e fornisce informazioni in tempo reale sulla richiesta corrente, aiutandoti a identificare rapidamente problemi di performance, errori e a ispezionare i dati.

## Attivazione

Per visualizzare la barra di debug, è sufficiente abilitare l'ambiente di sviluppo nel file `Config/config.php`:

```php
// in Config/config.php
const DEVELOPMENT_ENVIRONMENT = true;

```

Sezioni della Barra
-------------------

La barra è divisa in diverse sezioni che forniscono informazioni specifiche.

| Sezione              | Descrizione                                                                                                                                                            |
| -------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Database**         | Mostra un elenco dettagliato di tutte le query SQL eseguite durante la richiesta, inclusi i tempi di esecuzione. Fondamentale per individuare query lente o duplicate. |
| **Log**              | Visualizza le ultime voci del file di log (`Logs/log.txt`), permettendoti di controllare errori e messaggi di debug senza dover aprire il file manualmente.            |
| **Form**             | Se la richiesta corrente è l'invio di un form, questa sezione mostra l'esito della validazione per ogni campo, evidenziando eventuali errori.                          |
| **Variables**        | Elenca tutte le variabili che il controller ha passato alla vista. Utile per verificare che i dati corretti vengano inviati al template.                               |
| **Peso Pagina**      | Indica il consumo di memoria (in MB) richiesto per generare la pagina. Aiuta a monitorare l'efficienza dell'applicazione.                                              |
| **Tempo Esecuzione** | Misura il tempo totale (in secondi) impiegato dal server per processare la richiesta e generare la risposta.                                                           |

* * *

[Indice](index.md) | Precedente: [Sicurezza](security.md) | Successivo: [Data Fixtures](data-fixtures.md)

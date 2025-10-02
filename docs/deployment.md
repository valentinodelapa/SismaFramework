# Deployment in Produzione

Mettere un'applicazione in produzione richiede alcuni passaggi aggiuntivi rispetto all'ambiente di sviluppo per garantire sicurezza, performance e stabilità. Questa guida elenca i passaggi chiave per il deployment di un'applicazione SismaFramework.

## Checklist per il Deployment

### 1. Imposta l'Ambiente di Produzione

Questa è l'impostazione più importante. Nel tuo file `Config/config.php`, assicurati che `DEVELOPMENT_ENVIRONMENT` sia impostato su `false`.

```php
// in Config/config.php
const DEVELOPMENT_ENVIRONMENT = false;
```

Questa modifica ha diversi effetti critici:
*   **Disattiva la Barra di Debug:** La barra di debug non verrà più mostrata.
*   **Nasconde gli Errori Dettagliati:** In caso di errore, all'utente verrà mostrata una pagina generica (es. Errore 500) e i dettagli tecnici verranno scritti solo nel file di log, senza esporre informazioni sensibili.
*   **Applica le Regole di Logging di Produzione:** Verranno usate le impostazioni di rotazione del log definite in `LOG_PRODUCTION_MAX_ROW`.

### 2. Controlla la Configurazione del Web Server

Assicurati che la configurazione del tuo web server (es. il file `.htaccess` per Apache) sia corretta e reindirizzi tutte le richieste al front controller `Public/index.php`. La configurazione dovrebbe impedire l'accesso diretto a qualsiasi file al di fuori della cartella `Public`.

### 3. Forza l'HTTPS

Per la sicurezza, è fondamentale che la tua applicazione giri su HTTPS in produzione. Puoi forzare il reindirizzamento da HTTP a HTTPS impostando la seguente costante in `config.php`:

```php
// in Config/config.php
const HTTPS_IS_FORCED = true;
```

### 4. Verifica i Permessi delle Cartelle

Il tuo web server (es. l'utente `www-data` su sistemi Debian/Ubuntu) deve avere i permessi di scrittura su alcune cartelle specifiche per funzionare correttamente:

*   **`Logs/`**: Per poter scrivere i file di log.
*   **`Cache/`**: Se l'ORM è configurato per usare la cache delle relazioni (`ORM_CACHE = true`), il framework deve poter scrivere il file `referenceCache.json`.

Assicurati che i permessi di queste cartelle siano impostati correttamente. Ad esempio:

```bash
# Assumendo che il tuo web server giri come www-data
chown -R www-data:www-data SismaFramework/Application/Logs
chown -R www-data:www-data SismaFramework/Application/Cache
chmod -R 775 SismaFramework/Application/Logs
chmod -R 775 SismaFramework/Application/Cache
```

### 5. Abilita la Cache dell'ORM

Per massimizzare le performance in produzione, assicurati che la cache delle relazioni dell'ORM sia attiva.

```php
// in Config/config.php
const ORM_CACHE = true;
```

* * *

[Indice](index.md) | Precedente: [Best Practices](best-practices.md) | Successivo: [Troubleshooting](troubleshooting.md)
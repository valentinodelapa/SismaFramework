# Installazione e Configurazione

Questa guida ti accompagnerà passo dopo passo nell'installazione e nella configurazione di un nuovo progetto con SismaFramework.

## Prerequisiti

Prima di iniziare, assicurati che il tuo ambiente di sviluppo soddisfi i seguenti requisiti:

* **PHP 8.3** o superiore.
* **Composer** per la gestione delle dipendenze.
* Un web server come **Apache** o **Nginx**.
  * Per Apache, è necessario che il modulo `mod_rewrite` sia abilitato.
* Un database **MySQL** o **MariaDB**.
* **Git** installato sulla tua macchina.

## Procedura di Installazione

L'approccio consigliato per includere SismaFramework nel tuo progetto è tramite **Git submodule**. Questo ti permette di mantenere il codice del framework separato dal tuo codice applicativo, facilitando gli aggiornamenti futuri.

SismaFramework offre **due metodi di installazione**:

1. **Installazione automatica tramite CLI** (consigliato)
2. **Installazione manuale**

---

## Metodo 1: Installazione Automatica (CLI)

Il metodo più rapido e sicuro per configurare un nuovo progetto è utilizzare il comando di installazione fornito dal framework.

### Passo 1: Crea il progetto e aggiungi il framework

```bash
mkdir il-mio-progetto
cd il-mio-progetto
git init
git submodule add https://github.com/valentinodelapa/SismaFramework.git
```

### Passo 2: Installa le dipendenze

```bash
cd SismaFramework
composer install
cd ..
```

Questo comando installerà le dipendenze necessarie, inclusa la libreria PSR-3 per il logging standard.

### Passo 3: Esegui il comando di installazione

> **Nota sul comando `sisma`**
> A partire dalla versione 11.5.0, lo script include lo shebang `#!/usr/bin/env php` ed è affiancato da un wrapper `sisma.bat` per Windows. Questo consente di invocarlo direttamente come:
> ```bash
> sisma install NomeDelProgetto
> ```
> Se il comando non è nel `PATH` o i permessi di esecuzione non sono stati impostati, usa la forma esplicita:
> ```bash
> php SismaFramework/Console/sisma install NomeDelProgetto
> ```

```bash
sisma install NomeDelProgetto
```

Questo comando creerà automaticamente:
- La cartella `Config/` con il file `configFramework.php` pre-configurato con il nome del progetto
- La cartella `Public/` con `index.php` configurato (path dell'autoloader aggiornati automaticamente) e il relativo `.htaccess`
- Il file `.htaccess` nella root del progetto, che indirizza le richieste alla cartella `Public/`
- Le cartelle `Cache/`, `Logs/` e `filesystemMedia/` con permessi **0777** (lettura, scrittura, esecuzione per tutti)

#### Configurazione Interattiva del Database

A partire dalla versione 11.1.0, il comando di installazione supporta la **configurazione interattiva** dei parametri del database. Se non vengono passati parametri da riga di comando, il sistema chiederà interattivamente se si desidera configurare il database:

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

**Note sulla configurazione interattiva:**
- La password viene inserita in modo nascosto (su Linux/macOS)
- È possibile premere Invio per accettare i valori predefiniti
- Per saltare completamente la configurazione database, rispondere "N" alla domanda iniziale

#### Opzioni da Riga di Comando

Se preferisci evitare la configurazione interattiva, puoi passare i parametri direttamente da riga di comando:

```bash
sisma install NomeDelProgetto \
  --db-host=localhost \
  --db-name=mio_database \
  --db-user=root \
  --db-pass=password \
  --db-port=3306
```

**Opzioni:**
- `--force` - Forza la sovrascrittura di file esistenti
- `--skip-db` - Salta completamente la configurazione database (nessuna richiesta interattiva)
- `--db-host=HOST` - Host del database (default: 127.0.0.1)
- `--db-name=NAME` - Nome del database
- `--db-user=USER` - Username del database
- `--db-pass=PASS` - Password del database
- `--db-port=PORT` - Porta del database (default: 3306)

**Esempi:**

```bash
# Installazione con configurazione interattiva (default)
sisma install BlogPersonale

# Installazione senza configurazione database
sisma install BlogPersonale --skip-db

# Installazione con parametri da riga di comando
sisma install BlogPersonale \
  --db-host=localhost \
  --db-name=blog_db \
  --db-user=root \
  --db-pass=mypassword
```

#### Comportamento con File Esistenti

Se esegui il comando `install` in una directory dove `Config/configFramework.php` esiste già, il comando **fallirà** per proteggere da sovrascritture accidentali. Per forzare la sovrascrittura, usa l'opzione `--force`:

```bash
sisma install MyProject --force
```

⚠️ **Attenzione:** L'opzione `--force` sovrascriverà tutti i file di configurazione esistenti. Usala solo se sei sicuro.

#### Note sui Permessi delle Cartelle

Le cartelle `Cache/`, `Logs/` e `filesystemMedia/` vengono create con **permessi 0777** (rwxrwxrwx) per garantire che il web server possa scrivere log e file temporanei.

Se il tuo ambiente richiede permessi più restrittivi per motivi di sicurezza, puoi modificarli manualmente dopo l'installazione:

```bash
# Per ambienti multi-utente più sicuri
chmod 755 Cache Logs filesystemMedia
```

### Passo 4: Configura il Web Server

Con Apache non serve altro: il comando di installazione copia il `.htaccess` della root, che indirizza tutte le richieste a `Public/`, e quello di `Public/`, che le passa a `index.php`. Il routing legge il percorso dall'URL della richiesta (`REQUEST_URI`). Con `mod_rewrite` abilitato, la tua applicazione è ora pronta! ✅

Con Nginx, imposta `Public/` come document root e inoltra a `index.php` le richieste che non corrispondono a un file:

```nginx
root /percorso/del/progetto/Public;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## Metodo 2: Installazione Manuale

Se preferisci configurare manualmente il progetto, segui questi passaggi.

### Passo 1: Crea il tuo progetto e aggiungi il framework

1. Crea la cartella principale per il tuo nuovo progetto e inizializza un repository Git:

   ```bash
   mkdir il-mio-progetto
   cd il-mio-progetto
   git init
   ```

2. Aggiungi SismaFramework come submodule:

   ```bash
   git submodule add https://github.com/valentinodelapa/SismaFramework.git
   ```

   Questo creerà una cartella `SismaFramework` all'interno del tuo progetto.

3. Installa le dipendenze del framework:

   ```bash
   cd SismaFramework
   composer install
   cd ..
   ```

### Passo 2: Prepara la struttura del progetto

Per garantire che gli aggiornamenti del framework non sovrascrivano le tue configurazioni, devi copiare le cartelle `Config` e `Public` dalla libreria alla root del tuo progetto.

```bash
cp -R SismaFramework/Config/ .
cp -R SismaFramework/Public/ .
cp SismaFramework/.htaccess .
```

Rinomina il file di configurazione del framework:

```bash
mv Config/config.php Config/configFramework.php
```

Crea le cartelle necessarie con i permessi appropriati:

```bash
mkdir Cache Logs filesystemMedia
chmod 777 Cache Logs filesystemMedia  # 0777 = rwxrwxrwx (tutti possono leggere, scrivere, eseguire)
```

**Nota:** I permessi 0777 sono necessari affinché il web server possa scrivere log e file temporanei. Per ambienti più sicuri, considera l'uso di permessi più restrittivi (es. 0755) se il web server ha lo stesso proprietario delle cartelle.

La struttura della tua cartella di progetto dovrebbe ora essere simile a questa:

```
il-mio-progetto/
├── Cache/
├── .htaccess
├── Config/
│   └── configFramework.php
├── Logs/
├── Public/
│   ├── .htaccess
│   └── index.php
├── filesystemMedia/
└── SismaFramework/
    └── ... (codice del framework)
```

### Passo 3: Configura i file principali

Ora devi modificare i file che hai appena copiato per farli puntare correttamente alla libreria nella sottocartella `SismaFramework`.

1. **Modifica `Public/index.php`**

   Apri il file `Public/index.php`: la configurazione deve essere quella del progetto (`Config/configFramework.php`), mentre l'autoloader è quello della sottocartella `SismaFramework`.

   _Cerca queste righe:_

   ```php
   require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';
   require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Autoload' . DIRECTORY_SEPARATOR . 'autoload.php';
   ```

   _E modificale in:_

   ```php
   require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'configFramework.php';
   require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'SismaFramework' . DIRECTORY_SEPARATOR . 'Autoload' . DIRECTORY_SEPARATOR . 'autoload.php';
   ```

2. **Modifica `Config/configFramework.php`**

   Apri il file `Config/configFramework.php` e imposta le costanti necessarie per il tuo ambiente.

   * **Impostazioni del Progetto:** il file copiato contiene i valori del modulo del framework, da adattare al progetto come fa il comando di installazione:

     ```php
     const PROJECT = 'NomeDelTuoProgetto';
     const APPLICATION = 'Application';   // nel modulo vale 'Sample'
     const MODULE_FOLDERS = [];           // nel modulo vale ['SismaFramework']: aggiungi qui i tuoi moduli

     // Il file si trova in Config/ del progetto, un livello sotto la root: rimuovi un DIRECTORY_UP
     const ROOT_PATH = __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_UP . DIRECTORY_SEPARATOR;

     // Cache e log nelle cartelle Cache/ e Logs/ della root del progetto
     const REFERENCE_CACHE_DIRECTORY = ROOT_PATH . CACHE . DIRECTORY_SEPARATOR;
     const LOG_DIRECTORY_PATH = ROOT_PATH . LOGS . DIRECTORY_SEPARATOR;
     ```

   * **Impostazioni del Database:** le credenziali sono lette dalle variabili d'ambiente `DATABASE_*`; il secondo valore di ogni riga è quello usato quando la variabile non è impostata. Imposta le variabili d'ambiente (consigliato in produzione) oppure modifica questi valori, senza aggiungere altre dichiarazioni delle stesse costanti. `DEFAULT_ADAPTER_TYPE` vale `'mysql'`, l'adapter per MySQL e MariaDB.

     ```php
     define(__NAMESPACE__ . '\DATABASE_HOST', getenv('DATABASE_HOST') ?: "127.0.0.1");
     define(__NAMESPACE__ . '\DATABASE_NAME', getenv('DATABASE_NAME') ?: "nome_database");
     define(__NAMESPACE__ . '\DATABASE_PASSWORD', getenv('DATABASE_PASSWORD') ?: "password_db");
     define(__NAMESPACE__ . '\DATABASE_PORT', getenv('DATABASE_PORT') ?: "3306");
     define(__NAMESPACE__ . '\DATABASE_USERNAME', getenv('DATABASE_USERNAME') ?: "utente_db");
     ```

   * **Chiave di Cifratura:** Genera una chiave sicura e impostala nella variabile d'ambiente `ENCRYPTION_PASSPHRASE`, o come valore di ripiego della riga corrispondente. È fondamentale per la crittografia dei dati.

     ```php
     define(__NAMESPACE__ . '\ENCRYPTION_PASSPHRASE', getenv('ENCRYPTION_PASSPHRASE') ?: "una-frase-segreta-molto-lunga-e-casuale");
     ```

### Passo 4: Configura il Web Server

Il framework utilizza un unico punto di accesso (`Public/index.php`). Tutte le richieste devono essere reindirizzate a questo file.

Con Apache bastano i due `.htaccess` copiati al passo 2: quello della root indirizza le richieste a `Public/`, quello di `Public/` le passa a `index.php`. Con Nginx, imposta `Public/` come document root e inoltra a `index.php` le richieste che non corrispondono a un file, come indicato nel [metodo automatico](#passo-4-configura-il-web-server).

A questo punto, la tua applicazione dovrebbe essere configurata e funzionante. Punta il tuo browser all'URL del progetto per verificare.

---

## Prossimi Passi

Dopo aver completato l'installazione:

1. Verifica che l'applicazione funzioni accedendo all'URL del progetto nel browser
2. Familiarizza con l'[architettura a moduli](module-architecture.md) del framework
3. Consulta la [documentazione completa](index.md) per iniziare a sviluppare

***

[Indice](index.md) | Precedente: [Introduzione al Framework](overview.md) | Successivo: [Architettura a Moduli](module-architecture.md)

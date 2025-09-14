# Installazione e Configurazione

Questa guida ti accompagnerà passo dopo passo nell'installazione e nella configurazione di un nuovo progetto con SismaFramework.

## Prerequisiti

Prima di iniziare, assicurati che il tuo ambiente di sviluppo soddisfi i seguenti requisiti:

* **PHP 8.1** o superiore.
* Un web server come **Apache** o **Nginx**.
  * Per Apache, è necessario che il modulo `mod_rewrite` sia abilitato.
* Un database **MySQL** o **MariaDB**.
* **Git** installato sulla tua macchina.

## Procedura di Installazione

L'approccio consigliato per includere SismaFramework nel tuo progetto è tramite **Git submodule**. Questo ti permette di mantenere il codice del framework separato dal tuo codice applicativo, facilitando gli aggiornamenti futuri.

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

### Passo 2: Prepara la struttura del progetto

Per garantire che gli aggiornamenti del framework non sovrascrivano le tue configurazioni, devi copiare le cartelle `Config` e `Public` dalla libreria alla root del tuo progetto.

```bash
cp -R SismaFramework/Config/ .
cp -R SismaFramework/Public/ .
```

La struttura della tua cartella di progetto dovrebbe ora essere simile a questa:

```
il-mio-progetto/
├── Config/
│   └── config.php
├── Public/
│   └── index.php
└── SismaFramework/
    └── ... (codice del framework)
```

### ### Passo 3: Configura i file principali

Ora devi modificare i file che hai appena copiato per farli puntare correttamente alla libreria nella sottocartella `SismaFramework`.

1. **Modifica `Public/index.php`**
   Apri il file `Public/index.php` e modifica il percorso di inclusione dell'autoloader.
   _Codice originale:_
   
   ```php
   require_once(__DIR__ . '/../Autoload/autoload.php');
   ```
   
   _Codice modificato:_
   
   ```php
   require_once(__DIR__ . '/../SismaFramework/Autoload/autoload.php');
   ```

2. **Modifica `Config/config.php`**
   Apri il file `Config/config.php` e imposta le costanti necessarie per il tuo ambiente.
   
   * **Impostazioni del Progetto:**
     
     ```php
    // Non è necessario modificare PROJECT e ROOT_PATH se si segue la struttura standard.
    // ROOT_PATH viene calcolato automaticamente.
     ```
   
   * **Impostazioni del Database:**
     
     ```php
     const DATABASE_ADAPTER_TYPE = 'mysql'; // o 'mariadb'
     const DATABASE_HOST = '127.0.0.1';
     const DATABASE_NAME = 'nome_database';
     const DATABASE_USERNAME = 'utente_db';
     const DATABASE_PASSWORD = 'password_db';
     const DATABASE_PORT = '3306';
     ```
   
   * **Chiave di Cifratura:** Genera una chiave sicura e inseriscila qui. È fondamentale per la crittografia dei dati.
     
     ```php
     const ENCRYPTION_PASSPHRASE = 'una-frase-segreta-molto-lunga-e-casuale';
     ```

### Passo 4: Configura il Web Server

Il framework utilizza un unico punto di accesso (`Public/index.php`). Tutte le richieste devono essere reindirizzate a questo file.

**Esempio per Apache (`.htaccess`)**

Crea un file `.htaccess` nella root del tuo progetto (`il-mio-progetto/.htaccess`) con il seguente contenuto:

```apacheconf
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ Public/index.php?url=$1 [QSA,L]
</IfModule>
```

A questo punto, la tua applicazione dovrebbe essere configurata e funzionante. Punta il tuo browser all'URL del progetto per verificare.

***

[Indice](index.md) | Precedente: [Introduzione al Framework](overview.md) | Successivo: [Architettura a Moduli](module-architecture.md)

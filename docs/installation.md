# Installazione

La presente libreria può essere considerata *Stand alone* dal momento che non necessita di alcuna libreira di terze parti per poter funzionare, fatto salvo ovviamente per le librerie utilizzate come riferimento e/o spunto in fase di sviluppo (si vedano in dal senso i files [README.md](../README.md) e [NOTICE.md](../NOTICE.md))

Alla luce di ciò, l'utilizzo della tecnica del **submodule** è da preferire alla tecnica del **vendoring** per l'inizializzazione di un nuovo progetto basato su di essa. Il vantaggio consiste nel fatto che, mentre la tecnica del vendoring copia l'intera repository all'interno del progetto, aggiungendo la libreria come sottomodulo della repository principale essa verrà aggiunta come dipendenza, rendendo più manuteibile l'intero progetto.

## Inizializzazione del progetto

Come noto, in genere il pattern MVC presenta un unico punto di accesso dell'applicazione. In questo progetto il punto di accesso è rappresentato dal file `index.php` presente nella cartella `Public`. Un file `.htaccess` configurato a dovere ed inserito nella root del progetto si dovrà occupare di rindirizare le richieste al file indicato.

Per mantenere l'integrita della libreria e quindi mantenere la configurazione personalizzata qualora siano disponibili aggiornamenti e si intenda eseguirli (azione ovviamente consigliata), è possibile creare una copia della cartella `Public` nella root del progetto e configurare il file `.htaccess` in modo che punti ad esso.

Qualora di intenda procedere verso tale scenario è necessario apportare alcune modifiche alla copia effettuata del file `index.php`, ovvero sostituire la seguenteriga di codice:

```php
// codice originale
require_once(__DIR__ . '/../Autoload/autoload.php');
```

con la seguente:

```php
// codice modificato
require_once(__DIR__ . '/../SismaFramework/Autoload/autoload.php');
```

Altro file necessario per l'inizializzazione del progetto è quello di configurazione `config.php` presente nella cartella `Config`. Sempre per mantenere l'integrita della libreria è consigliabile creare una copia della cartella `Config` nella root del progetto in modo tale da non dover modificare altre righe nel file `index.php`. Quest'ultimo infatti incorpora il file di configurazione la cui cartella, per impostazione predefinita, si trova allo stesso livello della cartella `Public`: qualora quindi si decida di copiare tale cartella ma non quella `Config` si dovrebbe editare, all'interno del file `index.php` anche la riga di codice che incorpora il file di configurazione.

```php
// codice originale
require_once(__DIR__ . '/../Config/config.php');

// codice modificato
require_once(__DIR__ . '/../SismaFramework/Config/config.php');
```

Nel caso il progetto necessiti anch'esso di un file di configurazione, è possibile aggiungerlo con lo stesso *Namespace* all'interno della cartella copiata nella root del progetto ed aggiungerne il riferimento nel file `index.php`.

### File di configurazione

Il passo successivo è quello di editare il file di configurazione in modo tale che il framework possa gestire l'intera cartella di progetto.

Vedremo di seguito solo le costanti di configurazioni sulle quali è inizialmente necessario  intervenire e faremo poi riferimento alle altre in relazione ai compontenti con i quali interagisce, nel momento in cui verranno illustrati.

È innanzitutto opportuno modificare la costante che riguarda il nome del progetto:

```php
...
const PROJECT = 'NomeDelProgetto';
...
```

Nello scenario descritto in precedenza è inoltre necessario ricalibrare, in base alla root del progetto, la posizione dei vari componenti modificando le seguenti costanti:

```php
...
const ROOT_PATH = 'percorso della cartella radice';
...
const ORM_PATH = 'percorso della cartella che contiene il modulo ORM';
..
const REFERENCE_CACHE_DIRECTORY = 'percorso della cartella che conterrà la cache delle referenze';
...
const LOG_DIRECTORY_PATH = 'percorso della cartella che conterrà il file di log';
...
```

Infine è necessario settare i parametri di connessione al database e la chiave di cifratura degli eventuali campi crittografati:

```php
...
const ENCRYPTION_PASSPHRASE = '';
...
/* Database Constant */
const DATABASE_ADAPTER_TYPE = '';
const DATABASE_HOST = '';
const DATABASE_NAME = '';
const DATABASE_PASSWORD = '';
const DATABASE_PORT = '';
const DATABASE_USERNAME = '';
```

---

[Indice](index.md) | Precedente: [Introduzione](overview.md) | Successivo: [Struttura progetto](project-folder-structure.md)

# Struttura delle Cartelle

SismaFramework è progettato per essere modulare. Un'applicazione può essere composta da uno o più **Moduli**, ognuno dei quali incapsula una parte specifica della logica di business (es. un modulo per il blog, uno per l'e-commerce, uno per l'amministrazione).

Questa pagina descrive la struttura di cartelle raccomandata per un singolo modulo, che garantisce il corretto funzionamento dell'autoloader e una chiara organizzazione del codice.

## Struttura di un Modulo

La struttura predefinita per un modulo (es. `MyBlog`) è la seguente:

```bash
MyBlog/
└── App/
    ├── Assets/
    ├── Controllers/
    ├── Entities/
    ├── Enumerations/
    ├── Forms/
    ├── Locales/
    ├── Models/
    ├── Permissions/
    ├── Templates/
    ├── Views/
    └── Voters/
```

Non tutte le cartelle sono obbligatorie. Puoi creare solo quelle che ti servono.

### Descrizione delle Cartelle

| Cartella         | Scopo                                                                                                          |
| ---------------- | -------------------------------------------------------------------------------------------------------------- |
| **Assets**       | Contiene file statici come CSS, JavaScript e immagini.                                                         |
| **Controllers**  | Contiene le classi Controller, che gestiscono le richieste HTTP e coordinano la risposta.                      |
| **Entities**     | Contiene le classi Entity, che rappresentano gli oggetti del dominio e sono mappate alle tabelle del database. |
| **Enumerations** | Contiene le `Enum` PHP, utili per definire set di valori costanti (es. stati, tipi).                           |
| **Forms**        | Contiene le classi Form, che gestiscono la validazione e l'elaborazione dei dati inviati tramite form HTML.    |
| **Locales**      | Contiene i file di traduzione per l'internazionalizzazione (i18n).                                             |
| **Models**       | Contiene le classi Model, che si occupano di recuperare e persistere le `Entities` nel database.               |
| **Permissions**  | Contiene le classi per la gestione delle autorizzazioni, che determinano se un utente può eseguire un'azione.  |
| **Templates**    | Contiene i template (solitamente HTML) usati per generare output come il corpo di un'email.                    |
| **Views**        | Contiene i file di vista (PHP/HTML) che si occupano di presentare i dati all'utente.                           |
| **Voters**       | Contiene le classi `Voter`, logica granulare per le decisioni di sicurezza.                                    |

Configurazione
--------------

Per far sì che il framework riconosca il tuo nuovo modulo, devi registrarlo nel file `Config/config.php`.

1. **Aggiungi il modulo all'array `MODULE_FOLDERS`**:
   
   ```php
   const MODULE_FOLDERS = [
       'SismaFramework', // Modulo di sistema (non rimuovere)
       'MyBlog',         // Il tuo nuovo modulo
   ];
   ```

2. **Imposta la cartella dell'applicazione (opzionale)**: Se la cartella principale del tuo modulo che contiene `Controllers`, `Entities`, etc. non si chiama `App`, puoi specificare il nome corretto modificando la costante `APPLICATION`.
   
   ```php
   // Nel file config.php
   const APPLICATION = 'App'; // Valore di default
   ```

Autoloading e Namespace
-----------------------

L'autoloader di SismaFramework si basa sullo standard PSR-4. Questo significa che il **namespace** di una classe deve corrispondere esattamente al suo **percorso** nel filesystem, a partire dalla root del progetto.

**Esempio:**

Una classe Controller per i post del blog...

* **File:** `MyBlog/App/Controllers/PostController.php`
* **Namespace:** `MyBlog\App\Controllers`
* **Classe:** `PostController`

---

[Indice](index.md) | Precedente: [Installazione](installation.md) | Successivo: [Controllori](controllers.md)

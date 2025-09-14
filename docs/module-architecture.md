# Architettura a Moduli

Una delle caratteristiche più potenti di SismaFramework è la sua **architettura modulare**. Invece di costruire un'unica applicazione monolitica, il framework ti incoraggia a suddividere la tua applicazione in **Moduli** indipendenti e riutilizzabili. Questo approccio mantiene il codice organizzato, facile da navigare e da manutenere, specialmente in progetti di grandi dimensioni.

## Cos'è un Modulo?

Un modulo è un'unità autocontenuta che raggruppa una specifica funzionalità dell'applicazione. Ogni modulo ha la sua struttura di cartelle, con i propri Controller, Modelli, Viste, Form, ecc.

Ad esempio, in un'applicazione complessa potresti avere moduli separati per:
*   **User**: Gestione degli utenti, profili, autenticazione.
*   **Blog**: Gestione di articoli, categorie, commenti.
*   **Shop**: Gestione di prodotti, ordini, pagamenti.

## Struttura delle Cartelle di un Modulo

La struttura predefinita per un modulo (es. `MyBlog`) è la seguente. Non tutte le cartelle sono obbligatorie; puoi creare solo quelle che ti servono.

```bash
MyBlog/
└── Application/
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

| Cartella | Scopo |
|:---|:---|
| **Assets** | Contiene file statici come CSS, JavaScript e immagini. |
| **Controllers** | Contiene le classi Controller, che gestiscono le richieste HTTP. |
| **Entities** | Contiene le classi Entity, che rappresentano gli oggetti del dominio. |
| **Enumerations** | Contiene le `Enum` PHP per definire set di valori costanti. |
| **Forms** | Contiene le classi Form per la validazione dei dati. |
| **Locales** | Contiene i file di traduzione per l'internazionalizzazione (i18n). |
| **Models** | Contiene le classi Model, che si occupano della persistenza dei dati. |
| **Permissions** | Contiene le classi per la gestione delle autorizzazioni. |
| **Templates** | Contiene i template usati per generare output come il corpo di un'email. |
| **Views** | Contiene i file di vista (PHP/HTML) per la presentazione. |
| **Voters** | Contiene le classi `Voter`, logica granulare per le decisioni di sicurezza. |

## Registrazione e Autoloading

Per far sì che il framework riconosca il tuo nuovo modulo, devi registrarlo nel file `Config/config.php`.

```php
// in Config/config.php
const MODULE_FOLDERS = [
    'SismaFramework', // Modulo di sistema (non rimuovere)
    'MyBlog',         // Il tuo nuovo modulo
];
```

L'autoloader del framework userà questo array per trovare e caricare automaticamente le classi da tutti i moduli registrati.

## Interazione tra Moduli

I moduli non sono isolati; possono e devono interagire tra loro.

### Routing

Il Dispatcher non fa distinzione tra moduli quando risolve un URL. Quando riceve una richiesta per `/post/show`, cercherà un `PostController` in tutti i moduli registrati, seguendo l'ordine definito in `MODULE_FOLDERS`. Il primo che trova, viene utilizzato. Questo permette a un modulo di "sovrascrivere" le rotte di un altro semplicemente posizionandolo prima nell'array di configurazione.

### Relazioni tra Entità

L'ORM gestisce senza problemi le relazioni tra entità di moduli diversi. Quando definisci una relazione in un'entità, è sufficiente usare il **namespace completo** della classe dell'entità correlata.

**Esempio:** Un'entità `Post` nel modulo `Blog` che ha una relazione con un'entità `User` nel modulo `User`.

**`Blog/Application/Entities/Post.php`**
```php
namespace Blog\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use User\Application\Entities\User; // Importa l'entità dall'altro modulo tramite il suo namespace

class Post extends BaseEntity
{
    // ... altre proprietà
    protected User $author; // La relazione funziona perfettamente

    // ...
}
```

* * *

[Indice](index.md) | Precedente: [Installazione e Configurazione](installation.md) | Successivo: [Controllori](controllers.md)
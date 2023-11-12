# Controllori

I controllori si occupano di elaborare i dati e le informazioni e fornirle all'interfaccia grafia dell'applicazione.

Facendo riferimento alla stuttura standard illustrata nel capitolo [Struttura cartelle di progetto](project-folder-structure.md), la cartella `Controllers` è quella deputata a contenere le classi di questo tipo. Per modificare tale comportamento prodefinito bisogna agire sulle constanti di configurazione come segue:

* nel caso si intenda variare esclusivamente il nome della cartella contenitore è sufficiente modificare la seguente costante
  
  ```php
  ...
  const CONTROLLERS = 'segnapostoControllers';
  ...
  ```

* se, al contrario, si intende modificare l'intera struttura dell'albero delle cartelle si dovra intervenire anche sulle seguenti costanti
  
  ```php
  ...
  const CONTROLLERS_PATH = 'pathControllers';
  const CONTROLLERS_NAMESPACE = 'namespaceControllers';
  ...
  ```

La nuova classe controllore dovrà estendere la classe `BaseController`, che contiene alcune funzionalità ed informazioni predefinite che si riveleranno utili durante lo sviluppo della classe.

```php
namespace SismaFramework\Sample\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;

class SampleController extends BaseController
{
	
}
```

I metodi della classi Controllore sono chiamati *action*: essi si occupano di implementare la logica della singola interfaccia dell'applicazione. Possono accettare alcuni parametri che devono essere necessariamente tipizzati per permettere alla classe `Dispatcher`, tramite la classe `Parser`, di processare gli argomenti. I tipi accettati dalle action di un controllore sono i seguenti:

* tipi nativi (int, float, string, array), che vengono parsati forzandone il tipo in modo esplicito;

* oggetti `DateTime`(nello specifico di tipo `SismaDatetime`), che accettano il formato `Y-d-m H:i:s`;

* enumerazioni di tipo `\BackedEnum`, che vengono parsate mediante il valore dichiarato per il singolo case;

* entità, che vengono parsate in base al tipo ed all'id passato come parametro (si veda il capitolo [ORM](orm.md)).

Il meccanismo di passaggio dei parametri avviene mediante la sintassi `/nome-argomento/valore/` inserira nella barra degli indirizzi dopo il nome del *controller* e quello dell'*action*:

```
https://nome-dominio.net/nome-controller/nome-action/nome-parametro/valore/
```



È inoltre presente un meccanismo di *autowiring* che inietta automaticamente determinati tipi di parametri qualora siano presenti nella dichiarazione del metodo: i tipi oggetto dell'*autowiring* sono i seguenti:

* `Request`, che raccoglie in una struttura le variabili super-globali `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES` e `$_SERVER`;

* `Authentication`, che si occupa di rendere disponibili a livello generale i metodi per implementare un sistema di autenticazione.

Come anticipato, nella presente libreria la classe `Dispathcer` di occupa di intercettare ed istanziare il *controllore *corretto e la relativa *action* elaborando, tramite la tecnica dell'*url rewrite*, la richiesta del browser.

Alcune costanti presenti nel file di configurazione si occupano di indicare alla classe `Dispatcher` quale *controllore* istanziare e quale *action* richiamare nel caso in cui non siano presenti le informazioni necessarie all'interno dell'url.

```php
...
const DEFAULT_PATH = '';
const DEFAULT_ACTION = '';
const DEFAULT_CONTROLLER = '';
...
```

## Classe Render

La classe `Render` è quella che si occupa, una volta eseguita tutta la logica di una singola `action` di richiamare la relativa View che si occuperà dell'esposizione dei dati all'utente od al servizio che li ha richiesti. 

Espone due metodi statici la cui sintatti viene di seguito illustrata:

* `generateView()`;

* `generateData()`.

Entrambi i metodi richiedono due parametri:

* `string $view`: indica il percorso del file *.php* (senza l'estensione) che contiene la vista a partire dalla cartella radice delle viste definita dalle seguenti costanti presente nel file di configurazione;
  
  ```php
  ...
  const VIEWS = '';
  ...
  const VIEWS_PATH = '';
  ...
  ```

* `array $vars`: è l'array che contiene tutte le informazioni che vengono confivise dal controllore alla vista.

La differenza tra i due metodi è che il primo è rivolto ad un utente umano e carica il file di localizzazione mentre il secondo, utile allo sviluppo di API's ne evita il caricamento.

Restituisce un oggetto di tipo `Response` che rappresenta di codice di stato HTTP generato dalla richiesta.

## Classe Templater

La classe `Templater` ha un funzionamento simile alla classe precedente con la differenza che invece di stampare un risultato a schermo restituisce lo stesso sotto forma di stringa.

Ha un unico metodo statico pubblico `generateTemplate()` che accetta due argomenti

* `string $template`: ha la stessa funzione dell'argomento `$views` descritto in precedenza con l'unica differenza delle costanti che indicatìno la cartella radice dei templates;
  
  ```php
  ...
  const TEMPLATES = '';
  ...
  /* Templater Constant */
  const TEMPLATES_PATH = '';
  const STRUCTURAL_TEMPLATES_PATH = '';
  ...
  ```

* `array $args`: anche qui si tratta di un parametro con le stesse funzioni del medesimo descritto in precedenza nell'ambito della classe `Render`.

## Classe  Router

La classe `Router` invece di occupa del reindirizzamento verso un altro coltroller/action nei casi in cui l'action corrente non abbia dati ta esporre all'utente in questione. Come la precedente anche questa classe restituisce un oggetto `Response`.

* * *

[Indice](index.md) | Precedente: [Struttura cartelle di progetto](project-folder-structure.md) | Successivo: [Viste](views.md)



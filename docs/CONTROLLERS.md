# Controllori

I controllori si occupano di elaborare i dati e le informazioni e fornirle all'interfaccia grafia dell'applicazione.

Facendo riferimento alla stuttura standard illustrata nel capitolo [Struttura cartelle di progetto](PROJECT_FOLDER_STRUCTURE.md), la cartella `Controllers` è quella deputata a contenere le classi di questo tipo. Per modificare tale comportamento prodefinito bisogna agire sulle constanti di configurazione come segue:

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

I metodi della classi Controllore sono chiamati *action*

Nella presente libreria la classe `Dispathcer` di occupa di intercettare ed istanziare il *controllore *corretto e la relativa *action* elaborando, tramite la tecnica dell'*url rewrite*, la richiesta del browser.

Alcune costanti presenti nel file di configurazione si occupano di indicare alla classe `Dispatcher` quale *controllore* istanziare e quale *action* richiamare nel caso in cui non siano presenti le informazioni necessarie all'interno dell'url.

```php
...
const DEFAULT_PATH = '';
const DEFAULT_ACTION = '';
const DEFAULT_CONTROLLER = '';
...
```



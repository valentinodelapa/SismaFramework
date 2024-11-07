# SismaFramework

![PHPUnit tests status](https://img.shields.io/badge/tests-passing-green)
![Code coverage status](https://img.shields.io/badge/coverage-%3E75%25-yellow)
![PHP Version Support](https://img.shields.io/badge/php-%3E%3D8.1-blue)
[![license](https://img.shields.io/badge/license-MIT-yellowgreen)](https://github.com/valentinodelapa/SismaFramework/blob/master/LICENSE)
[![Documentation](https://img.shields.io/badge/documentation-phpDocumentor-blue)](https://github.com/valentinodelapa/SismaFramework/blob/master/docs-phpdoc/index.html)

## Descrizione

Benvenuto nel progetto! Questa libreria consiste in un framework basato su pattern MVC che integra un ORM per l'interazione con il database.
Utilizza in modo massiccio la tipizzazione, che risulta necessaria al funzionamento della libreria stessa.
Utilizza anche le enumerazioni, introdotte nel linguaggio php dalla versione 8.1.
Supporta nativamente i link parlanti con notazione kebab case.

## Installazione

Il progetto non necessita nativamente di dipendenze: il mio consiglio è quindi di includerlo con *submodule* invece di utilizzare la tecnica di *vendoring* (e quindi tramite composer).
In tal modo nel proprio progetto viene inserito un riferimento alla libreria che viene inclusa nel progetto principale come una dipendenza.
Al contrario, con il vendoring, il codice di terze parti viene copiato direttamente nel progetto principale. Questo può rendere il progetto più difficile da mantenere e aggiornare in futuro.

Una volta inclusa la libreria nella root del proprio progetto le cartelle `Config` e `Public` presenti al suo interno devono essere copiate anch'esse nella stessa posizione ed il file al loro interno editati.
All'interno del file `index.php`, presente nella cartella `Public` copiata nella root del progetto, è necessario modificare la riga di codice indicata:

```php
// codice originale
require_once(dirname(__DIR__) . '/Autoload/autoload.php');
```

con la seguente:

```php
// codice modificato
require_once(dirname(__DIR__) . '/SismaFramework/Autoload/autoload.php');
```

Anche nel file di configurazione `config.php`, presente nella cartella `Config` copiata nella root del progetto, è necessario fare alcune modifiche.
Di seguito le costanti da modificare con la relativa spiegazione:

```php
// costanti da modificare per il corretto funzionamento della libreria
const APPLICATION = "CartellaDellApplicazione";
const DEFAULT_PATH = "PathDelControllerDefault";
const DEFAULT_ACTION = 'ActionDefaultValidaPerTuttiIController';
const DEFAULT_CONTROLLER = "ControllerDefault";
const PROJECT = "NomeProgetto";
const ROOT_PATH = "PathDellaCartellaRootDelProgetto";
const MODULE_FOLDERS = [
    "ModuloProgettoUno",
    "ModuloProgettoDue",
    "ModuloProgettoTre",
];
const ORM_PATH = "PathDelModuloOrm";
const ENCRYPTION_PASSPHRASE = '';

// costanti da modificare per spostare i log delle seguenti funzionalità nella root
const REFERENCE_CACHE_DIRECTORY = "CartellaNellaRootDellaCacheDelleReferenze";
const LOG_DIRECTORY_PATH = "CartellaNellaRootDeiLogs";;

// parametri di configurazione della connessione al database
const DATABASE_ADAPTER_TYPE = '';
const DATABASE_HOST = '';
const DATABASE_NAME = '';
const DATABASE_PASSWORD = '';
const DATABASE_PORT = '';
const DATABASE_USERNAME = '';
```

Nel caso in cui si intendano aggiungere altri file di configurazione è nescessario aggiungerli all'interno del file `index.php` presente nella cartella Public nella root del progetto.

## Utilizzo

Di seguito alcune informazioni base su come utilizzare la libreria.
Per maggiori informazioni si faccia riferimento alla [documentazione ](docs/index.md)ed agli esempi presenti nella cartella `Sample` del progetto.

### Framework

Nella creazione di un progetto che utilizza la presente libreria consiglio di mantenere separati i moduli che lo comporranno.

I vari moduli devono essere aggiunti alla costante `MODULE_FOLDERS` indicato nella sezione precedente, in modo tale che i controllori possano essere individuati dal sistema.

Di default la struttura del singolo modulo deve rispettare il seguente schema:

* NomeModulo
  * CartellaApplicazione
    * Assets 
    * Controllers 
    * Entities 
    * Enumerations
    * Forms
    * Locales
    * Models
    * Permissions
    * Templates
    * Views

Non tutte le cartelle indicate devono necessariamente essere presenti e ve ne posso essere delle altre: in ogni caso la struttura può essere modificata agendo sul file di configurazione.

#### Controllori

I controllori devono estendere la classe `BaseController`.

```php
class SampleController extends BaseController
{
    // actions
}
```

I parametri delle action devono essere passati come argomenti dei relativi metodi e necessitano la tipizzazione in fase di dicharazione.

```php
public function error(string $message): Response
{
    $this->vars['message'] = urldecode($message);
    return Render::generateView('sample/error', $this->vars);
}
```

Il sistema è configurato per parsare automaticamente i tipi non nativi.

#### Form

I forms si occupano di intecettare i dati forniti tramite un form html e ne gestiscono la validazione ed il ripopolamento automatico. Estendono la classe `BaseForm`.

```php
class SampleForm extends BaseForm
{
    // codice della classe
}
```

#### Localizzazioni

I file di linguaggio si trovano di default in questa cartella ed utilizzano la notazione *lingua_PAESE*. La libreria accetta file di tipo php o json (opzione configurabile nel file di configurazione).

#### Permissions

Le classi Permissions servono a gestire gli accessi secondo parametri che variano in base alla configurazione delle stesse ed estendono la classe `BasePermission`

```php
class SamplePermission extends BasePermission
{
    // codice della classe
}
```

#### Template e Viste

Le viste sono utili generare le interfacce del portale e si presentano sotto forma di file php contenenti la struttura html della pagina
I template possono essere utilizzati, tra le altre cose, per la creazione di template per email. Sono file in formato html e possono contenere segnaposto che vengono sostituiti dalle corrispondenti variabili in fase di generazione.
I segnaposto sono racchiusi in una coppia di doppie parentesi graffe.

```html
<div>{{segnapostoVariabile}}</div>
```

### ORM

La componente orm è basata su pattern *Data Mapper a mappatura implicita*, i cui oggetti di dominio sono denominati entità.

#### Entità

Accettano proprietà di tipo nativo, oggetti di tipo `\DateTime` (o, per meglio dire, di tipo `SismaDatetime`, classe che estende `\DateTime`), Enumerazioni (`\BackedEnum`, utili a gestire vocabolari chiusi) oppure altre Entità.
Possono estendere le classi astratte `BaseEntity`, `ReferencedEntity` o `SelfReferencedEntity`, in base alle caratteristiche della tabella di riferimento.

#### Modelli

Per ogni entità presente nel progetto è necessario implementare anche il relativo modello.
L'implementazione base della classe include query standard, che variano in base alla classe genitore utilizzata.
È possibile gestire richieste ad-hoc tramite query implementate con l'apposito dialetto messo a disposizione dalla classe `Query`.
Anche in questo caso sono presenti tre diverse classi astratte dalle quali estendere il singolo modello, da scegliere in base alla tipologia di entità alla quale il modello fa riferimento: `BaseModel`, `ReferencedModel` e `SelfReferencedModel`.

## Contributi e Ringraziamenti

Questo progetto è stato sviluppato prendendo spunto da alcune librerie open souce e guide presenti sul web. Vengono citate di seguito:

* La libreria [**MVC_todo**](https://github.com/ngrt/MVC_todo).
  Questa libreria, consistente anch'essa in un semplice framework basato su patter MVC, è stata molto utile per un'iniziale panoramica su come organizzare l'albero delle classi che compongono la libreria.
  Successivamente lo stesso è stato più volte rimaneggiato ma il contributo fornito dalla libreria citata è innegabile
* La guida [**Creare un e-commerce con PHP**](https://www.html.it/guide/creare-un-e-commerce-con-php/) e, nello specifico le lezioni riguardanti il pattern MVC.
  Probabilmente lo sviluppo embrionale della classe Dispatcher della presente libreria deve molto alla relativa lezione di questa guida.
  Anche il meccanismo di autoloading delle classi per mezzo del namespace è ispirato alla presente guida.
* La libreria [**Symfony**](https://github.com/symfony/symfony).
  Questa libreria, che non ha certo bisogno di presentazioni, è stata uno spunto prezioso per quanto riguarda alcune tecniche utilizzate (seppur con notevoli differenze) anche durante lo sviluppo del presente progetto.
* La libreria [**SimpleORM**](https://github.com/davideairaghi/php/tree/master/library/Airaghi/DB/SimpleORM).
  Discorso a parte va fatto per questa libreria, che è stata utilizzata come punto di partenza per sviluppare l'ORM presente all'interno del progetto.
  Dopo varie rifattorizzazioni, aggiunta di funzionalità specifiche e rimozione di altre fuori contesto vi è ormai poco della libreria orginaria nel modulo attuale ma di certo essa è stata fondamentale per il suo sviluppo.
* **[Template di errore](https://codepen.io/gaiaian/details/QVVxaR)**
  
  Nella presente libreria è stato incluso un template di errore predefinito, riconducibile alla libreria di terze parti linkata nell'intestazione del corrente punto. Tale libreria è stata inclusa per intero, con piccole modifiche utili esclusivamente ad embeddare tutto il contenuto nella stessa pagina web. Questo per evitare che, nel caso vi siano errori, gli stessi impedissero il reperimento di eventuali risorse esterne.

Ringrazio tutti gli autori e gli sviluppatori delle fonti citate e faccio riferimento al file [`NOTICE.md`](NOTICE.md) della presente repository per i dettagli riguardanti le informazioni aggiuntive, le licenze e le notifiche di copyright originali.

Ringrazio il mio amico [Francesco Iezzi](https://github.com/Francesco997) per aver esaminato la libreria dal punto di vista della sicurezza ed aver fornito preziosi consigli in tale ambito.

Ringrazio inoltre i miei amici e colleghi per i supporto, il confronto ed i feedback forniti.

## Licenza

[MIT](https://github.com/valentinodelapa/SismaFramework/blob/master/LICENSE) Copyright (c) 2020-present Valentino de Lapa

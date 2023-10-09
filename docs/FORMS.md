# Forms

Le classi forms  si occupano della validazione dei dati di input inseriti nei forms presenti nel progetto. Ogni classe form deve fare riferimento ad un'entità, della quale può gestire tutte o solo alcune proprietà: in alcuni scenari infatti diversi form possono interfacciarsi con la medesima entità e gestirne aspetti differenti. I campi dei form *html* che si interfaceranno con la funzionalità in questione dovranno avere gli stessi nomi delle proprietà delle entità alle quali fanno riferimento.

È possibile gestire con un unico form *html* diverse entità, a patto che le stesse siano collegate con un qualche tipo di relazione.I form infatti possono essere concatenati smistando proprietà di entità referenziate o collezioni agli appositi form: sarà poi il form di partenza ad assemblare il tutto in un albero di entità che la componente *ORM* è in grado di salvare in un unica operazione, mantenendo l'integrità referenziale.

Altra funzionalità gestita dalle classi di tipo forms è il ripololamento: qualora qualcosa non dovesse andare a buon fine nella fase di *submit* del fomr *html* , lo stesso verrà ripresentato con le informazioni già compilate e le segnalazioni di errore negli eventuali campi non corretti.

La singola classe Form estende la classe `BaseForm` che gli fornisce tutte le funzionalità della quale necessita e che verranno esposte in seguito.

```php
namespace SismaFramework\Sample\Forms;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class BaseSampleForm extends BaseForm
{
    ...
}


```

Eredita dalla medesima classe base alcuni metodi astratti che deve implementare:

* `getEntityName()`: questo metodo serve ad embeddare l'entità (o meglio il suo nome) a cui la classe form fa riferimento all'interno suo interno. Deve restituire una stringa, che rappresenta appunto il nome dell'entità.
  
  ```php
  protected static function getEntityName(): string
  {
      return BaseSample::class;
  }
  ```

* `setFilterFieldsMode()`: questo metodo si occupa di settare i filtri da applicare ai singoli campi dell'entità gestiti dal form.
  
  ```php
  protected function setFilterFieldsMode(): void
  {
      ...
  }
  ```
  
  A tale scopo utilizza un altro metodo della classe `BaseForm`, ovvero `addFilterFieldMode()` che ha la seguente sintassi e può essere richiamato in modo ridondate in quanto restituisce lo stesso oggetti di tipo `BaseForm`.
  
  ```php
  
  protected function addFilterFieldMode(string $propertyName, FilterType $filterType, array $parameters = [], bool $allowNull = false): self
  {
      ...
  }
  ```
  
  Gli argomenti che quest'ultimo accetta sono i seguenti:
  
  * `$propertyName`: argomento di tipo `string` rappresenta il nome della proprietà dell'entità per la quale la classe deve gestire la validazione. 
  
  * `$filterType`: argomento di tipo `FilterType` (`BackedEnum` che utilizzato per interfacciare la classe di tipo `BaseForm` con la classe `Filter`) rappresenta il tipo di filtro da applicare per validare la proprietà.
  
  * `$parameters`: argomento opzionale di tipo `array` rappresenta parametri opzionali utili in alcuni casi alla validazione della proprietà
  
  * `$allowNull`: argomento opzionale di tipo `bool` imposta la possibilità della proprietà di accettare valori nulli.

* `customFilter()`: questo metodo è utile ad implementare utlteriori filtri di validazione che esulano dagli standard implementati nella classe `Filter`.
  
  ```php
  protected function customFilter(): void
  {
      ....
  }
  ```

* `setEntityFromForm()`: questo metodo si occupa di implementare la funzionalità descritta in precedenza di concatenamento dei form che gestiscono entità collegate.
  
  ```php
  protected function setEntityFromForm(): void
  {
      ....
  }
  ```
  
  Viene utilizzata per questo scopo un altro metodo di servizio della classe `BaseForm`, `addEntityFromForm()`, la cui sintassi è la seguente:
  
  ```php
  protected function addEntityFromForm(string $propertyName, string $formPropertyClass, int $baseCollectionFormFromNumber = 0): self
  {
      ...
  }
  ```
  
  Gli argomenti del metodo di servizio sono i seguenti:
  
  * `$propertyName`: argomento di tipo `string` specifica il campo (più precisamente l'array presente nella variabile superglobale `$_POST`) che verrà passato al form collegato e quindi processato da quest'ultimo.
  
  * `$formPropertyClass`: arcomento di tipo `string` rappresenta il nome della classe form, che dovrà prendere in carico quella determinata serie di infromazioni.
  
  * `$baseCollectionFormFromNumer`: argomento di tipo `int` rappresenta il numero di eventuali collezioni di gestite dal meccanismo sono presenti di default nel form `html` prima che l'utente interagisca con esso.

* `injectRequest()`: questo metodo serve ad includere in un form informazioni provenenti da altre fonti (ad esempio la sessione).
  
  ```php
  protected function injectRequest(): void
  {
      ...
  }
  ```

Ogni classe di tipo `BaseForm` espone una serie di metodi utili a far interagire la stessa con un controllore che riceve le informazioni inviategli da un  form *html*. Inoltre il costruttore accetta come argomento opzionale un'entità del tipo gestito dal form: tale meccaniscmo è utile per le operazioni di modifica di entità materializzate.

I metodi esposti dalla classe `BaseForm` sono i seguenti:

* `handleRequest(Request $request)`: questo metodo, accettando un parametro di tipo Request (di cui si discuterà nell'apposito paragrafo), incorpora le informazioni inviate da un form *html* nell'oggetto di tipo `BaseForm` che lo sta richiamando.

* `isSubmitted()`: questo metodo restituisce un valore di tipo `bool` che rappresenta l'invio o meno dei dati da parte del form.

* `isValid()`: questo metodo restuisce un valore di tipo `bool` che rappresenta l'esito della validazione di tutti i campi da parte dell'oggetto.

* `resolveEntity()`: questo metodo, da richiamare nel caso in cui i due prcedenti restituiscano entrambi `true`, restituisce l'entità risultante dalla processazione dei dai ricevuti tramite form *html*.

* `getEntityDataToStandardEntity()`: questo metodo, da richiamare nel caso in cui almeno uno dei due metodi `isSubmitted()` o `isValid()` restituisca `false`, restituisce un oggetto di tipo `StandardEntity` (un'entità non specializzata e non tipizzata) che, data la sua essenza, può accettare anche proprietà errate. Questa funzionalità è utile al ripopolamento automatico del form *html* in caso di errore.

* `returnFilterErrors()`: questo metodo, anch'esso da richiamare nel caso in cui almeno uno dei due metodi `isSubmitted()` o `isValid()` restituisca `false`, restituisce un array che contiene l'esito della validazione di ogni singola proprietà processata dall'oggetto di tipo `BaseForm`.

Di seguito l'impementazione completa dell'interazione tra un controllore ed un oggetto di tipo `BaseForm`.

```php
public function sampleAction(Request $request, BaseSample $baseSample): Response
{
    $baseSampleForm = new BaseSampleForm($baseSample);
    $baseSampleForm->handleRequest($request);
    if($baseSampleForm->isSubmitted() && $baseSampleFrom->isValid()){
        $baseSampleModified = $baseSampleForm->resolveEntity();
        ...
        return Router::redirect(...);
    }
    ...
    $this->vars['baseSampleForForm'] = $baseSampleForm->isSubmitted() ? $baseSampleForm->getEntityDataToStandardEntity() : $baseSample;
    $this->vars['errors'] = $baseSampleForm->returnFilterErrors();
    ...
    return Render::generateView(..., $this->vars);
}
```

## Request

La classe `Request` consiste nella rappresentazione sotto forma di struttura di dati del complesso delle variabili super-globali di `php` `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES` e `$_SERVER`. Esse vengono fornite dalla classe tramite le sue proprietà pubbliche e, nello specifico:

* `$this->query`: fornisce la variabile super-globale `$_GET`

* `$this->request`: fornisce la variabile super-globale `$_POST`

* `$this->cookie`: fornisce la variabile super-globale `$_COOKIE`

* `$this->files`: fornisce la variabile super-globale `$_FILES`

* `$this->server`: fornisce la variabile super-globale `$_SERVER`

## Authentications

La classe `Authentication `serve per implementare i meccanismi di login in area riservata tramite username e password; supporta inoltre inplementazione dell'autenticazione a due fattori. Essa viene implementata direttamente dall'action che si occupa dell'autenticazione e richiama automaicamente al suo interno la classe `Request` dalla quale ottiene le informazioni necessarie per procedere.

Espone i seguenti metodi pubblici:

* `setUserModel()`: questo metodo inietta all'interno dellìoggetto il modello che si occuperà di reperire le informazioni dell'account per il quale il processo di autenticazione è implementato. Accetta come argomento un oggetto di tipo `BaseModel` che implementa l'interfaccia `UserModelInterface`.

* `setPasswordModel()`: questo metodo inietta all'interno dell'oggetto il modello che si occuperà di reperire la password dell'account per il quale il processo di autenticazione è implementato. Accetta come argomento un oggetto di tipo `BaseModel` che implementa l'interfaccia `PasswordModelInterface`.

* `setMultiFactorModel()`: questo metodo inietta all'interno dell'oggetto il modello che si occuperà di reperire, qualora sia implementato e settato l'accesso a più fattori, il token OPT collegato all'account per il quale il processo di autenticazione è implementato. Accetta come argomento un oggetto di tipo `BaseModel` che implementa l'interfaccia `MultiFactorModelInterface`.

* `setMultiFactorRecoveryModel()`: questo metodo inietta all'interno dell'oggetto il modello che si occuperà di reperire, qualora sia implementato e settato l'accesso a più fattori, i token di backup collegati all'account per il quale il processo di autenticazione è implementato, utili in caso di smarrimento e/o impossibilità di utilizzo del secondo fattore. Accetta come argomento un oggetto di tipo `BaseModel` che implementa l'interfaccia `MultiFactorRecoveryModelInterface`.

* `setMultiFactorWrapper()`: questo metodo inetta il wrapper che si occuperà, nel caso di implementazione di un servizio di accesso multi-fattore esterno, di interfacciarsi con il suddetto servizio e fornire le sue funzionalità al sistema. L'oggetto che il metodo accetta come argomento deve implementare l'interfaccia `MultiFactorWrapperInterface`.

* `checkUser()`: questo metodo controlla che ci sia una corrispondenza tra l'username ricevuto dal form *html* e un qualche untente registrato a sistema. Restituisce un valore di tipo `bool` che rappresenta l'esito della ricerca.

* `checkPassword()`: questo metodo, dato un oggetto di tipo `BaseEntity` che implementi l'interfaccia `UserInterface`, controlla la corrispondenza tra l'ultima password presente a sistema per l'utente il questione e quella inviata tramite form *html*. Restituisce un valore `bool` che rappresenta l'esito del confronto.

* `checkMultiFactor()`: questo metodo, dato un oggetto di tipo `BaseEntity` che implementi l'interfaccia `UserInterface`, in presenza dell'implementazione multi-fattore e dell'attivazione della stessa, controlla la che il token inserito framite form *html* corrisponda a quello fornito da sistema. Restituisce un valore `bool` che rappresenta l'esito del confronto.

* `checkMultiFactorRecovery()`: questo metodo, dato un oggetto di tipo `BaseEntity` che implementa l'interfaccia `MultiFactorInterface`, in presenza dell'implementazione multi-fattore e dell'attivazione della stessa, controlla la che il token inserito framite form *html* corrisponda ad uno dei codici di backup generati in fase si attivazione del secondo fattore di autenticazione, da utilizzare in caso di smarrimento e/o impossibilità di utilizzo dello stesso. Restituisce un valore `bool` che rappresenta l'esito del confronto.

* * *

[Indice](index.md) | Precedente: [Viste](views.md) | Successivo: [ORM](orm.md)

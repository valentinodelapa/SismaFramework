# Entità

Le Entità sono strutture che rappresentano le tabelle del database: ogni istanza di un’entità rappresenta un singolo record contenuto nella relativa tabella. Le proprietà della struttura corrispondono con le colonne delle tabelle del database (escludendo la notazione, che deve essere *camel case* per le priprietà dell’entità e *snake case* per le colonne della tabella) e devono essere dichiarate *protected*.

La tipizzazione delle proprietà è necessaria per il corretto funzionamento della loro mappatura ed i tipi accettati sono o seguenti:

* Tipi built-in: sono accettate proprietà di tipo string, int, float e boolean;

* Tipi data: il framework utilizza un tipo proprietario per gestire che estende la classe DateTime predefinita di PHP;

* Tipi enumerazione: i vocabolari chiusi possono essere gestiti tramite proprietà di tipo Backed Enumeration;

* Tipi entità: le chiavi esterne devono essere tipizzate con l’entità alla quale la chiave esterna fa riferimento.

Le proprietà che fanno riferimento alle colonne nelle quali è possibile inserire valore null devono a loro volta essere tipizzate con il tipo di riferimento o null.

```php
protected ?string $nullableString = null;
```

Nella classe genitore sono presenti due metodi astratti che devono essere implementati nelle singole entità:

* uno di essi permette di inizializzare i valori di default delle proprietà che necessitano tale opzione e che PHP non permette di inizializzare in fase di dichiarazione (nel caso concreto proprietà di tipo entità, ovvero chiavi esterne, e date).
  
  ```php
  protected function setPropertyDefaultValue(): void
  {
      ...
  }
  ```

* il secondo permette di indicare le proprietà che necessitano di una memorizzazione persistente crittografata. 
  
  ```php
  protected function setEncryptedProperties(): void
  {
      ...
  }
  ```

Le entità sono dotate di un meccanismo che intercetta le variazioni dei valori delle proprietà in modo tale che la classe *DataMapper* non effettui operazioni di update inutili su entità i cui dati non presentano alcuna differenza con i dati presenti nella relativa tabella.

È inoltre presente un meccanismo che permette di intercettare le modifiche delle entità referenziate in modo tale che il salvataggio di un’entità contente una chiave esterna effettui il salvataggio anche delle modifiche effettuate all’entità alla quale la chiave esterna fa riferimento.

La particolarità del sistema di generazione automatica delle referenze è che la richiesta delle informazioni delle entità referenziate non viene effettuata contemporaneamente a quella dell’entità che contiene la chiave esterna: in un promo momento viene salvata in una sorta di cache solo l’id della chiave presente nella tabella originale. La richiesta concreta delle informazioni aggiuntive viene effettuata solo nel momento in cui tali informazioni vengono richiamate all’interno del codice.

Questo comportamento è la motivazione per cui le proprietà dell’entità (che rappresentano, si è detto, le colonne della tabella di riferimento) devono essere dichiarate come protette e non pubbliche.

In meccanismo illustrato serve ad alleggerire il sistema limitando le richieste al database allo stretto necessario ed evitare loop in caso di entità auto-referenziate annidate (un esempio di tale scenario potrebbe essere l’implementazione di una blockchain).

Vedremo comunque le entità auto-referenziate nell’apposito paragrafo.
Di seguito un esempio di un’entità base completa.

```php
namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\ProprietaryTypes\SismaDateTime;
use SismaFramework\Sample\Enumerations\SampleType;

class BaseSample extends BaseEntity
{

    protected int $id;
    protected ReferencedSample $referencedEntityWithoutInitialization;
    protected ReferencedSample $referencedEntityWithInitialization;
    protected ?ReferencedSample $nullableReferencedEntityWithInitialization = null;
    protected OtherReferencedSample $otherReferencedSample;
    protected SismaDateTime $datetimeWithoutInitialization;
    protected SismaDateTime $datetimeWithInitialization;
    protected ?SismaDateTime $datetimeNullableWithInitialization = null;
    protected SampleType $enumWithoutInitialization;
    protected SampleType $enumWithInitialization = SampleType::one;
    protected ?SampleType $enumNullableWithInitialization = null;
    protected string $stringWithoutInizialization;
    protected string $stringWithInizialization = 'base sample';
    protected ?string $nullableStringWithInizialization = null;
    protected ?string $nullableSecureString = null;
    protected bool $boolean;

    protected function setPropertyDefaultValue(): void
    {
        $this->referencedEntityWithInitialization = new ReferencedSample();
        $this->datetimeWithInitialization = SismaDateTime::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');
    }

    protected function setEncryptedProperties(): void
    {

    }

}
```

## Entità referenziate

Per entità referenziata si intende una particolare entità utilizzata come tipo per una proprietà presente in un’altra entità. 

```php
namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;

class ReferencedSample extends ReferencedEntity
{
    protected int $id;
    protected string $text;
    protected ?int $nullableInteger = null;

    protected function setPropertyDefaultValue(): void
    {

    }

    protected function setEncryptedProperties(): void
    {

    }

}
```

È presente nell’ambito di tale classe una funzionalità particolare che permette, richiamandola come se fosse una proprietà dichiarata all’interno dell’entità, di ottenere una collezione di entità che la referenziano mediante una particolare proprietà.

Nell’utilizzo di tale funzionalità si possono presentare due diverse casistiche:

* Nel caso in cui vi sia un’unica proprietà nell’entità originaria che referenzia la l’entità in oggetto (si veda la proprietà `$otherReferencedSample` nell’esempio di `BaseEntity `riportato sopra di tipo `OtherReferencedSample`) è sufficiente richiamare il nome dell’entità contenente la chiave esterna seguito dalla parola chiave `Collection`.
  
  ```php
  $otherReferencedSample = new OtherReferencedSample();
  $otherReferencedSample->baseSampleCollection;
  ```

* Nel caso in cui vi siano più proprietà nell’entità originaria che referenziano l’entità originale (sempre relativamente all’esempio riportato sopra, si faccia riferimento alle proprietà `$referencedEntityWithoutInitialization`, `$referencedEntityWithInitialization` e `$nullableReferencedEntityWithInitialization`, tutte di tipo `ReferencedSample`) è necessario aggiungere alla sintassi indicata per la prima casistica il nome della proprietà della classe originaria alla quale il collegamento fa riferimento.
  
  ```php
  $referencedSample = new ReferencedSample();
  $referencedSample->baseSampleCollectionReferencedEntityWithoutInitialization;
  $referencedSample->baseSampleCollectionReferencedEntityWithInitialization;
  $referencedSample->baseSampleCollectionNullableReferencedEntityWithoutInitialization;
  ```

Le collezioni sono gestite tramite un’estensione proprietaria della classe nativa ArrayObject denominata `SismaCollection`.
In modo simile vengono gestiti alcuni metodi predefiniti che permetto di settare una collezione, aggiungere un’entità ad una collezione esistente e contare il numero di elementi di una collezione.

I tre metodi vengono attivati anteponendo al nome della collezione (che segue la sintassi esposta in precedenza) le seguenti parole chiave:

* `set`: accetta esclusivamente oggetti di tipo `SismaCollection `ed attribuisce il valore passato come parametro alla collezione alla quale il nome del metodo fa riferimento. Nel caso la collezione contenga già elementi, essi vengono sostituiti con quelli passati come parametro al metodo. 
  
  ```php
  $otherReferencedSample = new OtherReferencedSample();
  $otherReferencedSample->setBaseSampleCollection($baseSampleCollection);
  ```

* `add`: accetta esclusivamente oggetti il cui tipo deve corrispondere all’entità che possiede la chiave esterna (la proprietà di referenza) alla quale si sta facendo riferimento. Aggiunge alla collezione esistente l’oggetto passato come parametro. 
  
  ```php
  $otherReferencedSample = new OtherReferencedSample();
  $otherReferencedSample->addBaseSampleCollection($baseSample);
  ```

* count: non accetta parametri e conteggia il numero di entità che referenziano l’entità che sta chiamando il metodo tramite il collegamento al quale il nome del metodo si riferisce. 
  
  ```php
  $otherReferencedSample = new OtherReferencedSample();
  $otherReferencedSample->countBaseSampleCollection();
  ```

L’auto-determinazione delle relazioni inverse delle entità viene gestita in automatico e sarà illustrata nell’apposito paragrafo.

## Entità auto-referenziate

Caso particolare di entità referenziata è l’entità auto-referenziata, ovvero che presenta una o più proprietà tipizzate come oggetti del suo stesso tipo. 

```php
namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;

class SelfReferencedSample extends SelfReferencedEntity
{

    protected int $id;
    protected ?SelfReferencedSample $parentSelfReferencedSample = null;
    protected string $text;

    protected function setPropertyDefaultValue(): void
    {

    }

    protected function setEncryptedProperties(): void
    {

    }

}
```

Tramite la parola chiave parent anteposta al nome del tipo dell’entità è possibile, per quella determinata proprietà, usufruire di un meccanismo tramite il quale le relative collezioni referenziate possono essere ottenute mediante la semplice sintassi sonCollection.

```php
$selfReferencedSample = new SelfReferencedSample();
$selfReferencedSample->sonCollection;
```

Per ovvie problematiche di eventuali violazioni di integrità referenziale le proprietà auto-referenziate devono necessariamente essere dichiarate nullabili ed inizializzate e null in fase di dichiarazione.

Allo stesso modo è possibile sfruttare i metodi automatici sopra esposti tramite le apposite parole chiave. 

```php
$selfReferencedSample = new SelfReferencedSample();
$selfReferencedSample->setSonCollection(SelfReferencedSampleSampleCollection);
$selfReferencedSample->addSonCollection(SelfReferencedSampleSample);
$selfReferencedSample->countSonCollection();
```

Qualora la classe abbia più di una proprietà auto-referenziata, tale meccanismo può essere sfruttato esclusivamente da una di esse. Per le altre bisogna sfruttare i meccanismi esposti per le entità referenziate.

---

[Indice](index.md) | Precedente: [ORM](orm.md) | Successivo: [Modelli](orm-models.md)

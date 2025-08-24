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

Il cuore di questo ORM implementa un pattern che potremmo definire **Lazy Loading con Gestione a Doppio Stato** (*Lazy Loading with Dual-State Management*). Questa è una caratteristica voluta che offre notevoli vantaggi in termini di performance e flessibilità.

Una proprietà che rappresenta una relazione (una chiave esterna) può esistere in due stati distinti all'interno di un'entità:

1.  **Stato Non Risolto (ID Placeholder):** Dopo aver assegnato un ID a una proprietà (es. `$entity->user = 5;`), l'ORM non carica subito l'oggetto. Memorizza invece l'ID come un "promemoria" interno per il caricamento futuro. Questa operazione è estremamente rapida perché non richiede accessi al database.
2.  **Stato Risolto (Oggetto):** La proprietà contiene l'istanza completa dell'entità correlata. La transizione dallo stato "Non Risolto" a "Risolto" avviene automaticamente (tramite **Lazy Loading**) solo la prima volta che si tenta di accedere a una qualsiasi proprietà dell'oggetto correlato (es. `echo $entity->user->name;`).

È proprio per gestire questa logica interna che le proprietà delle entità devono essere dichiarate `protected` e l'accesso avviene tramite i metodi magici `__get` e `__set`.

Questo meccanismo serve ad alleggerire il sistema limitando le richieste al database allo stretto necessario. È importante notare che questo stato interno (risolto/non risolto) influisce sull'output di metodi di servizio come `toArray()`. Se una relazione non è ancora stata "risolta" tramite accesso, `toArray()` restituirà il suo ID per massimizzare le performance ed evitare chiamate al database. Se invece è già stata "risolta", restituirà l'array completo dei dati dell'oggetto correlato. Questo è un comportamento voluto per garantire il massimo controllo sulle performance di serializzazione.

Vedremo comunque le entità auto-referenziate nell’apposito paragrafo.
Di seguito un esempio di un’entità base. 

```php
namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class SampleBaseEntity extends BaseEntity
{
    protected int $id;
    protected SampleReferencedEntity $referencedEntity;

    #[\Override]
    protected function setEncryptedProperties(): void
    {

    }

    #[\Override]
    protected function setPropertyDefaultValue(): void
    {

    }
}
```

## Entità referenziate

Per entità referenziata si intende una particolare entità utilizzata come tipo per una proprietà presente in un’altra entità.

```php
namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;

class SampleReferencedEntity extends ReferencedEntity
{
    protected int $id;

    #[\Override]
    protected function setEncryptedProperties(): void
    {

    }

    #[\Override]
    protected function setPropertyDefaultValue(): void
    {

    }
}
```

L'entità dipendente da quella referenziata (ovvero quella che abbia un campo il cui tipo sia l'entità referenziata stessa) può essere di qualsiasi tipologia: nell'esempio corrente abbiamo optato per una BaseEntity, che riportiamo di seguito per completezza.

```php
namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;

class SampleDependentEntity extends BaseEntity
{
    protected int $id;
    protected SampleReferencedEntity $sampleReferencedEntity;

    #[\Override]
    protected function setEncryptedProperties(): void
    {

    }

    #[\Override]
    protected function setPropertyDefaultValue(): void
    {

    }
}
```

È presente nell’ambito di tale classe una funzionalità particolare che permette, richiamandola come se fosse una proprietà dichiarata all’interno dell’entità, di ottenere una collezione di entità che la referenziano mediante una particolare proprietà.

Nell’utilizzo di tale funzionalità si possono presentare due diverse casistiche:

* Nel caso in cui vi sia un’unica proprietà nell’entità dipendente che referenzia la l’entità in oggetto (come nel caso della classe vista in precedenza `SampleDependentEntity` la cui proprietà `$sampleReferencedEntity` è di tipo `SampleReferencedEntity`) è sufficiente richiamare il nome dell’entità che rappresenta la chiave esterna seguito dalla parola chiave `Collection`.
  
  ```php
  $sampleReferencedEntity = new SampleReferencedEntity();
  $sampleReferencedEntity->sampleDependentEntityCollection;
  ```

* Nel caso in cui vi siano più proprietà nell’entità originaria che referenziano l’entità originale (esempio riportato nel blocco di codice successivo, insieme all'esempio di utilizzo) è necessario aggiungere alla sintassi indicata per la prima casistica il nome della proprietà della classe originaria alla quale il collegamento fa riferimento.
  
  ```php
  namespace SismaFramework\Sample\Entities;
  
  use SismaFramework\Orm\BaseClasses\BaseEntity
  
  class SampleMultipleDependentEntity extends BaseEntity
  {
      protected int $id;
      protected SampleReferencedEntity $sampleReferencedEntityOne;
      protected SampleReferencedEntity $sampleReferencedEntityTwo;
  
      #[\Override]
      protected function setEncryptedProperties(): void
      {
  
      }
  
      #[\Override]
      protected function setPropertyDefaultValue(): void
      {
  
      }
  }
  ```
  
  Di seguito l'esempio citato: si può notare come, per richiamare dalla classe referenziante la collezione di entità referenziate, è necessario indicare il nome dell'entità dipendente (`sampleReferencedEntity`) seguito dalla parola chiave `Collection` e dal nome della proprietà della classe originaria alla quale il collegamento fa riferimento (`sampleReferencedEntityOne` o `sampleReferencedEntityTwo`).
  
  ```php
  $sampleMultipleDependentEntity = new SampleMultipleDependentEntity();
  $sampleMultipleDependentEntity->sampleReferencedEntityCollectionSampleReferencedEntityOne;
  $sampleMultipleDependentEntity->sampleReferencedEntityCollectionSampleReferencedEntityTwo;
  ```

Le collezioni sono gestite tramite un’estensione proprietaria della classe nativa ArrayObject denominata `SismaCollection`.
In modo simile vengono gestiti alcuni metodi predefiniti che permetto di settare una collezione, aggiungere un’entità ad una collezione esistente e contare il numero di elementi di una collezione.

I tre metodi vengono attivati anteponendo al nome della collezione (che segue la sintassi esposta in precedenza) le seguenti parole chiave:

* `set`: accetta esclusivamente oggetti di tipo `SismaCollection `ed attribuisce il valore passato come parametro alla collezione alla quale il nome del metodo fa riferimento. Nel caso la collezione contenga già elementi, essi vengono sostituiti con quelli passati come parametro al metodo. 
  
  ```php
  $sampleReferencedEntity = new SampleReferencedEntity();
  $sampleReferencedEntity ->setSampleDependentEntityCollection($sampleDependentEntityCollection);
  ```

* `add`: accetta esclusivamente oggetti il cui tipo deve corrispondere all’entità che possiede la chiave esterna (la proprietà di referenza) alla quale si sta facendo riferimento. Aggiunge alla collezione esistente l’oggetto passato come parametro. 
  
  ```php
  $sampleReferencedEntity= new SampleReferencedEntity();
  $sampleReferencedEntity ->addSampleDependentEntityCollection($sampleDependentEntity);
  ```

* count: non accetta parametri e conteggia il numero di entità che referenziano l’entità che sta chiamando il metodo tramite il collegamento al quale il nome del metodo si riferisce. 
  
  ```php
  $sampleReferencedEntity= new SampleReferencedEntity();
  $sampleReferencedEntity ->counSampleDependentEntityCollection();
  ```

L’auto-determinazione delle relazioni inverse delle entità viene gestita in automatico e sarà illustrata nell’apposito paragrafo.

## Entità auto-referenziate

Caso particolare di entità referenziata è l’entità auto-referenziata, ovvero che presenta una o più proprietà tipizzate come oggetti del suo stesso tipo. 

```php
namespace SismaFramework\Sample\Entities;

use SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity;

class SampleSelfReferencedEntity extends SelfReferencedEntity
{

    protected int $id;
    protected ?SampleSelfReferencedEntity $parentSampleSelfReferencedEntity = null;

    #[\Override]
    protected function setEncryptedProperties(): void
    {

    }

    #[\Override]
    protected function setPropertyDefaultValue(): void
    {

    }
}
```

Tramite la parola chiave parent anteposta al nome del tipo dell’entità è possibile, per quella determinata proprietà, usufruire di un meccanismo tramite il quale le relative collezioni referenziate possono essere ottenute mediante la semplice sintassi sonCollection.

```php
$sampleSelfReferencedEntity = new SampleSelfReferencedEntity();
$sampleSelfReferencedEntity->sonCollection;
```

Per ovvie problematiche di eventuali violazioni di integrità referenziale le proprietà auto-referenziate devono necessariamente essere dichiarate nullabili ed inizializzate e null in fase di dichiarazione.

Allo stesso modo è possibile sfruttare i metodi automatici sopra esposti tramite le apposite parole chiave. 

```php
$sampleSelfReferencedEntity = new SampleSelfReferencedEntity();
$sampleSelfReferencedEntity->setSonCollection($sampleSelfReferencedEntityCollection);
$sampleSelfReferencedEntity->addSonCollection($sampleSelfReferencedEntity);
$sampleSelfReferencedEntity->countSonCollection();
```

Qualora la classe abbia più di una proprietà auto-referenziata, tale meccanismo può essere sfruttato esclusivamente da una di esse. Per le altre bisogna sfruttare i meccanismi esposti per le entità referenziate.

---

[Indice](index.md) | Precedente: [ORM](orm.md) | Successivo: [Modelli](orm-models.md)

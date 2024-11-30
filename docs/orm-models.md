# Modelli

I modelli contengono alcuni metodi predefiniti che rappresentano le operazioni di base da effettuare sulle tabelle. Per ogni entità presente nel progetto è necessario implementarne anche il modello. In fase di implementazione il modello richiede di specificare implementare il metodo astratto `getEntityName()` nel quale specificare l’entità alla quale il modello si riferisce. 

```php
namespace SismaFramework\Sample\Models;

use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Sample\Entities\SampleBaseEntity;

class SampleBaseEntityModel extends BaseModel
{

    protected function getEntityName(): string
    {
        return SampleBaseEntity::class;
    }

    ...

}
```

E presente un secondo metodo astratto, `appendSearchCondition()`, che permette di specificare la composizione della porzione di query che si occupa delle ricerche testuali all’interno della tabella, nel caso venga valorizzato il campo searchKey nell’apposito metodo (si vedano le specifiche del metodo `getEntityCollection() `più avanti). 

```php
namespace SismaFramework\Sample\Models;

use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Sample\Entities\SampleBaseEntity;

class SampleBaseEntityModel extends BaseModel
{

    ...

    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        ...
    }

}
```

## Modelli per entità semplici

I modelli per entità semplici servono da corredo alle entità che non presentano chiavi esterne (o per i quali si è certi di non doverne ottenere informazioni per mezzo di chiavi esterne).
Implementa i seguenti metodi standard

* `getEntityById()`: questo metodo serve ad ottenere un’entità a partire dal suo id e restituisce un’entità della tipologia implementata nel model.
  
  ```php
  $sampleBaseEntityModel= new  SampleBaseEntityModel();
  $sampleBaseEntity = $sampleBaseEntityModel->getEntityById(1);
  ```

* `getEntityCollection()`: questo metodo serve ad ottenere tutte le entità di un determinato tipo memorizzate sul database sotto forma di una collezione.
  
  ```php
  $sampleBaseEntityModel= new  SampleBaseEntityModel();
  $sampleBaseEntityeCollection = $sampleBaseEntityModel->getEntityCollection();
  ```
  
  è possibile specificare alcuni parametri per filtrare e/o ordinare e/o limitare il risultato ottenuto e, nello speficico e nel seguente ordine:
  
  * `searchKey`: campo testuale che sfrutta la logica specificata nel metodo appendSearchCondition() per effettuare ricerche testuali sui campi della tabella;
  
  * `order`: serve a specificare l’ordinamento dei risultati; accetta un array associativo la cui chiave deve essere il nome del campo ed il valore la direzione dell’oridinamento (campionato all’interno dell’enumerazione Indexing);
  
  * `offset`: indica il numero di elementi da escludere all’inizio della ricerca.
  
  * `limit`: indica il numero massimo di elementi che può contenere la collezione.
    
    ```php
    public function getEntityCollection(?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
    {
        ...
    }
    ```

* `getOtherEntityById()`: questo metodo, simile al precedente, data un’entità specifica, restituisce tutte le entità memorizzate sul database sotto forma di collezione, escludendo però quella passata come parametro.
  
  ```php
  $sampleBaseEntityModel= new  SampleBaseEntityModel();
  $sampleBaseEntity = $sampleBaseEntityModel->getEntityById(1);
  $sampleBaseEntityCollection = $sampleBaseEntityModel->getOtherEntityCollection($sampleBaseEntity);
  ```

* `countEntityCollection()`: questo metodo, data un’eventuale chiave di ricerca testuale, restituisce il numero di entità che soddisfano il requisito richiesto. In assenza di chiave di ricerca testuale il metodo restituisce il numero di tutti i record presenti nella tabella.
  
  ```php
  $sampleBaseEntityModel= new  SampleBaseEntityModel();
  $sampleBaseEntityCollectionNumber = $sampleBaseEntityModel->countEntityCollection();
  ```

* `deleteEntityById()`: questo metodo elimina un’entità partendo dall’id di quest’ultima, restituendo un valore booleano che indica la riuscita dell'operazione.
  
  ```php
  $sampleBaseEntityModel= new  SampleBaseEntityModel();
  $sampleBaseEntityModel->deleteEntityById(1);
  ```

## Modelli per entità dipendenti

Nei casi in cui le entità abbiano chiavi esterne e si voglia sfruttarle per effettuare interrogazioni al database, è possibile scegliere di estendere, per il relativo modello, la classe ReferencedModel: essa fornisce metodi predefiniti aggiuntivi che vanno ad implementare tali interrogazioni:

* `getEntityCollectionByEntity()`: questo metodo, dato un array associativo di entità ed una serie di altri parametri aggiuntivi, restituisce una collezione di entità che soddisfano i requisiti indicati.
  
  ```php
  $sampleDependentEntityModel= new SampleDependentEntityModel;
  $sampleDependentEntityCollection = $sampleDependentEntityModel->getEntityCollectionByEntity();
  ```
  
  La sintassi per il popolamento dell’array associativo delle entità è `[nomeProprietà => entità]` mentre i parametri aggiuntivi sono i medesimi già descritti nell’ambito del metodo `getEntityCollection()` nel paragrafo relativo ai modelli per entità semplici.
  
  ```php
  public function getEntityCollectionByEntity(array $referencedEntities, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null): SismaCollection
  {
      ...
  }
  ```

* `countEntityCollectionByEntity()`: questo metodo, dato un array associativo di entità (che utilizza la sintassi illustrata in precedenza) ed un’eventuale chiave di ricerca testuale (che sfrutta sempre in meccanismo di ricerca testuale illustrato in precedenza), restituisce un valore intero che indica il numero di entità che soddisfano i requisiti indicati.
  
  ```php
  $sampleDependentEntityModel= new SampleDependentEntityModel;
  $sampleDependentEntityCollectionNumber = $sampleDependentEntityModel->countEntityCollectionByEntity();
  ```

* `deleteEntityCollectionByEntity()`: questo metodo, dato un array associativo di entità (che utilizza la sintassi illustrata in precedenza) ed un’eventuale chiave di ricerca testuale (che sfrutta sempre in meccanismo di ricerca testuale illustrato in precedenza), provvede ad eliminare le entità che soddisfano i requisiti indicati. Restituisce un valore booleano che indica il successo od il fallimento dell’operazione.
  
  ```php
  $sampleDependentEntityModel= new SampleDependentEntityModel;
  $success = $sampleDependentEntityModel->deleteEntityCollectionByEntity();
  ```

Esiste una funzionalità che, per mezzo di una specifica sintassi, permette di automatizzare i processi illustrati in precedenza. Essa prevede l'utilizzo in sequenza di una serie di parole chiave di seguito illustrate:

* parola chiave di attivazione (`get`, `count` o `delete`) serve ad indicate al sistema quale dei metodi precedentemente illustrati richiamare;

* la parola chiave `By` funge da separatore;

* l'entità che dovrà essere passato come parametro in formato *camelCase* (`nomeEntità`); è possibile gestirne anche più di una ed in tal caso verrà utilizzato il separatore `And`.

```php
$sampleDependentEntityModel= new SampleDependentEntityModel;
$sampleDependentEntityCollection = $sampleDependentEntityModel->getByBaseSample($sampleBaseEntity);
$sampleDependentEntityCollectionNumber = $sampleDependentEntityModel->countByBaseSample($sampleBaseEntity);
$success = $sampleDependentEntityModel->deleteByBaseSample($sampleBaseEntity);
```

## Modelli per entità auto-referenziate

Relativamente alle entità auto-referenziate sono disponibili ulteriori specifici metodi che permettono di effettuare le stesse medesime interrogazioni struttando la chiave autoreferenziata come condizione:

* `getEntityCollectionByParent()`: questo metodo, data un'entità genitore ed una serie di altri parametri aggiuntivi (i medesimi già descritti nell’ambito del metodo `getEntityCollection()`), restituisce una collezione di entità che soddisfano i requisiti indicati.
  
  ```php
  $selfReferencedSampleModel = new SelfReferencedSampleModel;
  $selfReferencedSampleCollection = $selfReferencedSampleModel->getEntityCollectionByParent();
  ```

* `countEntityCollectionByParent()`: questo metodo, data un'entità genitore ed un’eventuale chiave di ricerca testuale (che sfrutta sempre in meccanismo di ricerca testuale illustrato in precedenza), restituisce un valore intero che indica il numero di entità che soddisfano i requisiti indicati.
  
  ```php
  $selfReferencedSampleModel = new SelfReferencedSampleModel();
  $selfReferencedSampleCollectionNumber = $selfReferencedSampleModel->countEntityCollectionByParent();
  ```

* `deleteEntityCollectionByParent()`: questo metodo, data un'entità genitore ed un’eventuale chiave di ricerca testuale (che sfrutta sempre in meccanismo di ricerca testuale illustrato in precedenza), provvede ad eliminare le entità che soddisfano i requisiti indicati. Restituisce un valore booleano che indica il successo od il fallimento dell’operazione.
  
  ```php
  $selfReferencedSampleModel = new SelfReferencedSampleModel();
  $success = $selfReferencedSampleModel ->deleteEntityCollectionByParent();
  ```

Sono inoltre implementati i metodi che permettono di combinare quelli illustrati nel paragrafo *Modelli per entità con referenze* (che permettono di utilizzare le chiavi esterne come condizione) con quelle sopra illustrate:

* `getEntityCollectionByParentAndEntity()`: questo metodo, dato un array associativo di entità (che utilizza la sintassi illustrata in precedenza), un'entità genitore ed una serie di altri parametri aggiuntivi (i medesimi già descritti nell’ambito del metodo `getEntityCollection()`), restituisce una collezione di entità che soddisfano i requisiti indicati.
  
  ```php
  $selfReferencedSampleModel = new SelfReferencedSampleModel;
  $selfReferencedSampleCollection = $selfReferencedSampleModel->getEntityCollectionByParentAndEntity();
  ```

* `countEntityCollectionByParentAndEntity()`: questo metodo, dato un array associativo di entità (che utilizza la sintassi illustrata in precedenza),un'entità genitore ed un’eventuale chiave di ricerca testuale (che sfrutta sempre in meccanismo di ricerca testuale illustrato in precedenza), restituisce un valore intero che indica il numero di entità che soddisfano i requisiti indicati.
  
  ```php
  $selfReferencedSampleModel = new SelfReferencedSampleModel();
  $selfReferencedSampleCollectionNumber = $selfReferencedSampleModel->countEntityCollectionByParentAndEntity();
  ```

* `deleteEntityCollectionByParentAndEntity()`: uesto metodo, dato un array associativo di entità (che utilizza la sintassi illustrata in precedenza),un'entità genitore ed un’eventuale chiave di ricerca testuale (che sfrutta sempre in meccanismo di ricerca testuale illustrato in precedenza), provvede ad eliminare le entità che soddisfano i requisiti indicati. Restituisce un valore booleano che indica il successo od il fallimento dell’operazione.
  
  ```php
  $selfReferencedSampleModel = new SelfReferencedSampleModel();
  $success = $selfReferencedSampleModel ->deleteEntityCollectionByParentAndEntity();
  ```

Anche in questo caso è presente la funzionalità che permette di automatizzare le chiamate a queste funzioni tramite una specifica sintassi che verrà esaminata di seguito:

* Parola chiave di attivazione (`get`, `count` o `delete`) come illustrato nel paragrafo precedente serve ad indicate al sistema quale dei metodi precedentemente illustrati richiamare;
* la parola chiave `ByParentAnd` funge da separatore;
* l'entità che dovrà essere passato come parametro in formato *camelCase* (`nomeEntità`); è possibile gestirne anche più di una ed in tal caso verrà utilizzato il separatore `And`.

```php
$selfReferencedSampleModel = new SelfReferencedSampleModel;
$selfReferencedSampleCollection = $selfReferencedSampleModel ->getByParentAndBaseSample($parentSelfReferencedSample, $baseSample);
$selfReferencedSampleCollectionNumber = $selfReferencedSampleModel ->countByParentAndBaseSample($parentSelfReferencedSample, $baseSample);
$success = $selfReferencedSampleModel ->deleteByParentAndBaseSample($parentSelfReferencedSample, $baseSample);
```

---

[Indice](index.md) | Precedente: [Entità](orm-entities.md) | Successivo: [Funzionalità aggiuntive](orm-additional-features.md)

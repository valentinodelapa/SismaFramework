# Introduzione all'ODM (Object Document Mapper)

L'**Object-Document Mapper (ODM)** di SismaFramework ti permette di interagire con database documentali (come MongoDB) utilizzando oggetti PHP, con la stessa filosofia del [Data Mapper ORM](orm.md) ma applicata al paradigma dei documenti.

L'ODM è un sistema **parallelo e indipendente** dall'ORM: puoi usarli entrambi nella stessa applicazione per gestire dati relazionali con MySQL e dati documentali con MongoDB senza conflitti.

## Prerequisiti

Per usare l'ODM con MongoDB sono necessari:

1. **Estensione PHP** `ext-mongodb` (installazione via PECL o package manager di sistema):
   ```bash
   pecl install mongodb
   # oppure su Debian/Ubuntu:
   apt install php-mongodb
   ```

2. **Libreria Composer** `mongodb/mongodb`:
   ```bash
   composer require mongodb/mongodb
   ```

> L'ODM è progettato in modo che solo i due file concreti (`AdapterMongodb` e `ResultSetMongodb`) dipendano dal driver MongoDB. Tutti gli altri livelli sono agnostici rispetto al database documentale scelto.

## I Componenti Chiave

L'ODM si basa su tre tipi di classi principali:

1. **Documento (`BaseDocument`):** rappresenta un documento del database. A differenza delle Entità dell'ORM, non ha proprietà PHP dichiarate staticamente: i campi sono flessibili e vengono gestiti tramite un array interno, rispecchiando la natura schema-less dei database documentali.

2. **Modello (`BaseDocumentModel`):** il repository che costruisce le query e coordina le operazioni di lettura/scrittura. Equivalente di `BaseModel` nell'ORM.

3. **DocumentMapper:** il coordinatore della persistenza. Decide se eseguire un insert o un update, e delega all'adapter concreto.

---

## Configurazione

Aggiungi le seguenti costanti al tuo file di configurazione PHP:

```php
// Tipo di adapter ODM da usare
define('DEFAULT_ODM_ADAPTER_TYPE', \SismaFramework\Odm\Enumerations\OdmAdapterType::mongodb);

// Credenziali MongoDB
define('ODM_DATABASE_HOST',     'localhost');
define('ODM_DATABASE_PORT',     '27017');
define('ODM_DATABASE_NAME',     'my_database');
define('ODM_DATABASE_USERNAME', 'my_user');
define('ODM_DATABASE_PASSWORD', 'my_password');
```

---

## Creare un Documento

Crea una classe che estende `BaseDocument`. Definisci i valori di default dei campi in `setPropertyDefaultValue()` e restituisci il nome della collezione MongoDB in `getCollectionName()`.

```php
namespace MyModule\App\Documents;

use SismaFramework\Odm\BaseClasses\BaseDocument;

class ArticleDocument extends BaseDocument
{
    #[\Override]
    public function getCollectionName(): string
    {
        return 'articles'; // nome della collezione MongoDB
    }

    #[\Override]
    protected function setPropertyDefaultValue(): void
    {
        $this->status    = 'draft';
        $this->viewCount = 0;
        $this->tags      = [];
    }
}
```

I campi sono accessibili come proprietà PHP normali:

```php
$article = new ArticleDocument();
$article->title   = 'Il mio primo articolo';
$article->content = 'Contenuto dell\'articolo...';
$article->tags    = ['php', 'mongodb', 'odm'];
```

### Campi Embedded

I documenti possono contenere documenti annidati (embedded) semplicemente assegnando array o altri oggetti ai campi:

```php
$article->author = [
    'name'  => 'Valentino',
    'email' => 'valentino@example.com',
];
```

---

## Creare un Modello

Il modello espone i metodi di accesso al database. Estende `BaseDocumentModel` e implementa `getDocumentName()` per indicare quale documento gestisce.

```php
namespace MyModule\App\DocumentModels;

use SismaFramework\Odm\BaseClasses\BaseDocumentModel;
use MyModule\App\Documents\ArticleDocument;

class ArticleDocumentModel extends BaseDocumentModel
{
    #[\Override]
    public function getDocumentName(): string
    {
        return ArticleDocument::class;
    }

    // Metodo personalizzato opzionale
    public function getPublishedArticles(): \SismaFramework\Orm\CustomTypes\SismaCollection
    {
        $query = (new \SismaFramework\Odm\HelperClasses\DocumentQuery())
            ->where('status', \SismaFramework\Odm\Enumerations\FilterOperator::equal, 'published')
            ->orderBy('publishedAt', \SismaFramework\Odm\Enumerations\OdmIndexing::desc);
        return $this->getDocumentCollection($query);
    }
}
```

---

## Operazioni CRUD

### Creare e salvare un documento

```php
$model   = new ArticleDocumentModel();
$article = new ArticleDocument();

$article->title   = 'Titolo';
$article->content = 'Contenuto';
$article->status  = 'published';

$model->save($article);

// Dopo il salvataggio, $article->_id contiene l'ObjectId assegnato da MongoDB
echo $article->_id; // es. "664f3a2c1b2e4f0012ab3d45"
```

### Leggere documenti

```php
// Tutti i documenti
$articles = $model->getDocumentCollection();

// Con filtro, ordinamento e paginazione
$query = (new DocumentQuery())
    ->where('status', FilterOperator::equal, 'published')
    ->orderBy('publishedAt', OdmIndexing::desc)
    ->limit(10)
    ->offset(20);
$articles = $model->getDocumentCollection($query);

// Per ID
$article = $model->getDocumentById('664f3a2c1b2e4f0012ab3d45');

// Conteggio
$total = $model->countDocumentCollection($query);
```

### Aggiornare un documento

```php
$article = $model->getDocumentById('664f3a2c1b2e4f0012ab3d45');
$article->title = 'Titolo aggiornato';
$model->save($article); // esegue un UPDATE perché _id è già presente
```

> **Change tracking automatico:** il documento tiene traccia delle modifiche tramite la proprietà `$modified`. Se non hai modificato nulla dopo un `getDocumentById()`, il metodo `save()` non esegue alcuna query.

### Eliminare un documento

```php
$model->deleteDocumentById('664f3a2c1b2e4f0012ab3d45');
```

---

## DocumentQuery in Dettaglio

`DocumentQuery` è il query builder dell'ODM. Non produce SQL né array MongoDB: accumula le condizioni in una struttura neutra che ogni adapter compila nel proprio formato nativo.

### Operatori di filtro (`FilterOperator`)

| Case | MongoDB | Significato |
|---|---|---|
| `equal` | `$eq` | uguale |
| `notEqual` | `$ne` | diverso |
| `greater` | `$gt` | maggiore di |
| `greaterOrEqual` | `$gte` | maggiore o uguale |
| `less` | `$lt` | minore di |
| `lessOrEqual` | `$lte` | minore o uguale |
| `in` | `$in` | contenuto nell'array |
| `notIn` | `$nin` | non contenuto nell'array |
| `like` | `$regex` | espressione regolare (case-insensitive) |
| `notLike` | `$not` | negazione di regex |
| `isNull` | `$eq null` | campo assente o null |
| `isNotNull` | `$ne null` | campo presente e non null |

### Condizioni multiple

```php
// AND: tutte le condizioni devono essere vere
$query = (new DocumentQuery())
    ->where('status', FilterOperator::equal, 'active')
    ->andWhere('viewCount', FilterOperator::greater, 100);

// OR: almeno una condizione deve essere vera
$query = (new DocumentQuery())
    ->where('status', FilterOperator::equal, 'published')
    ->orWhere('status', FilterOperator::equal, 'featured');
```

### Ordinamento e paginazione

```php
$query = (new DocumentQuery())
    ->orderBy('publishedAt', OdmIndexing::desc)
    ->orderBy('title', OdmIndexing::asc)
    ->limit(10)
    ->offset(0);
```

---

## Architettura dell'ODM

L'ODM è progettato per essere **agnostico rispetto al database documentale**. Aggiungere il supporto per un nuovo store (es. Firestore, DynamoDB) richiede solo:

1. Aggiungere un case a `OdmAdapterType` (es. `case firestore = 'firestore'`)
2. Aggiungere la traduzione degli operatori nei `match` di `FilterOperator` e `LogicalOperator`
3. Creare `AdapterFirestore` che estende `BaseOdmAdapter`
4. Creare `ResultSetFirestore` che estende `BaseDocumentResultSet`

Nessuna modifica a `BaseDocument`, `DocumentMapper`, `DocumentQuery` o `BaseDocumentModel`.

```
Odm/
├── Traits/OdmKeyword.php              — forza ogni enum a tradursi per ogni adapter
├── Enumerations/
│   ├── OdmAdapterType.php             — mongodb (+ futuri adapter)
│   ├── FilterOperator.php             — operatori di filtro neutri
│   ├── LogicalOperator.php            — and, or, not
│   └── OdmIndexing.php                — asc=1, desc=-1
├── BaseClasses/
│   ├── BaseDocument.php               — documento base con change tracking
│   ├── BaseDocumentResultSet.php      — iteratore risultati, agnostico dal driver
│   ├── BaseOdmAdapter.php             — contratto astratto per tutti gli adapter
│   └── BaseDocumentModel.php          — repository base
├── HelperClasses/
│   ├── DocumentQuery.php              — AST neutro (non SQL, non MongoDB)
│   └── DocumentMapper.php             — coordinatore della persistenza
├── Adapters/
│   └── AdapterMongodb.php             ← unico file con dipendenze MongoDB
└── ResultSets/
    └── ResultSetMongodb.php           ← unico file che conosce BSON
```

---

## Differenze rispetto all'ORM

| Aspetto | ORM (`BaseEntity`) | ODM (`BaseDocument`) |
|---|---|---|
| Schema | Fisso, definito da `protected` properties | Flessibile, array interno `$data` |
| Primary key | `int $id` (auto-increment SQL) | `string $_id` (ObjectId MongoDB) |
| Relazioni | FK tipizzate, lazy loading | Embedded documents o riferimenti manuali |
| Query builder | `Query` (frammenti SQL) | `DocumentQuery` (AST neutro) |
| Tipi custom | `SismaDateTime`, `SismaDate`, enum | Qualsiasi valore PHP serializzabile |
| Transazioni | ACID via PDO | Multi-documento (richiede replica set) |
| Change tracking | Via `__set` su proprietà dichiarate | Via `__set` su array interno |

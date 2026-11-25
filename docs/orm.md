# Introduzione all'ORM (Data Mapper)

L'**Object-Relational Mapper (ORM)** di SismaFramework è un potente componente che ti permette di interagire con il database utilizzando oggetti PHP, invece di scrivere query SQL manualmente.

L'ORM si basa sul pattern **Data Mapper**, che garantisce una netta separazione tra la logica di business (gli oggetti del tuo dominio) e la logica di persistenza dei dati (come vengono salvati e recuperati dal database).

Per tecniche avanzate e patterns complessi, consulta la [guida ORM avanzata](advanced-orm.md). Per la documentazione API completa, vedi [API Reference](api-reference.md#orm-classes).

## I Componenti Chiave

L'interazione con l'ORM si basa su due tipi di classi principali:

1. **Entità (Entity):**
   Le entità sono classi PHP che rappresentano una riga di una tabella del database. Contengono le proprietà (che corrispondono alle colonne della tabella) e i metodi che definiscono la logica di business.

2. **Modelli (Model):**
   I modelli sono le classi responsabili della comunicazione con il database. Forniscono metodi per eseguire operazioni CRUD e per costruire query. Un modello sa come recuperare i dati dal database e popolarli in un'entità.

## Le Entità in Dettaglio

Le entità sono il cuore del tuo modello di dominio. Devono estendere una delle classi base fornite dall'ORM (`BaseEntity`, `ReferencedEntity`, `SelfReferencedEntity`).

### Proprietà e Tipi

Le proprietà di un'entità devono essere dichiarate `protected` e la **tipizzazione è obbligatoria**. I tipi supportati sono:

- Tipi nativi (`int`, `string`, `float`, `bool`).
- `SismaDateTime`, `SismaDate`, `SismaTime` per date e orari.
- `SismaJson` per le colonne JSON (vedi [Colonne JSON](#colonne-json)).
- `BackedEnum` per i vocabolari chiusi.
- Altre classi `Entity` per le relazioni (chiavi esterne).

```php
namespace MyModule\App\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaDateTime;

class Post extends BaseEntity
{
    protected int $id;
    protected string $title;
    protected ?string $content = null;
    protected SismaDateTime $publicationDate;
    protected User $author; // Relazione con l'entità User

    protected function setEncryptedProperties(): void { }
    protected function setPropertyDefaultValue(): void { }
}

// Questa entità mappa automaticamente alla tabella 'post' (singolare)
// SismaFramework utilizza convenzioni con nomi di tabelle al singolare
```

### Colonne JSON

Una proprietà tipizzata `SismaJson` mappa una colonna `JSON` (MySQL, MariaDB) o testuale contenente un oggetto o un array JSON. Il valore letto dal database viene decodificato in un array associativo; in scrittura viene serializzato con `JSON_UNESCAPED_UNICODE`, `JSON_UNESCAPED_SLASHES` e `JSON_PRESERVE_ZERO_FRACTION`.

```php
use SismaFramework\Orm\CustomTypes\SismaJson;

class Product extends BaseEntity
{
    protected int $id;
    protected SismaJson $attributes;
    protected ?SismaJson $metadata = null;
    // ...
}

$product->attributes = new SismaJson(['color' => 'red', 'sizes' => ['S', 'M']]);
$product->attributes = SismaJson::fromJson('{"color":"red"}');

echo $product->attributes['color'];
echo $product->attributes->get('weight', 0);
foreach ($product->attributes as $key => $value) { /* ... */ }
```

`SismaJson` è **immutabile**: per modificarne il contenuto si usano `with()` e `without()`, che restituiscono una nuova istanza da riassegnare alla proprietà. La riassegnazione passa dal tracciamento delle modifiche dell'entità, che altrimenti non rileverebbe un cambiamento fatto sull'oggetto in place. L'assegnazione tramite indice (`$json['key'] = ...`) solleva una `LogicException`.

```php
$product->attributes = $product->attributes->with('color', 'blue');
$product->attributes = $product->attributes->without('sizes');
```

Il confronto usato dal tracciamento delle modifiche (`equals()`) ignora l'ordine delle chiavi degli oggetti, che MySQL riordina in fase di salvataggio, ma non quello degli elementi delle liste, ed è stretto sui tipi (`1` e `"1"` sono diversi).

Limitazioni:
- sono supportati solo oggetti e array JSON: un JSON scalare (`"testo"`, `5`) solleva una `JsonException` in `fromJson()` e una `InvalidArgumentException` durante l'idratazione dell'entità e il parsing di form e argomenti delle action. Fa eccezione il letterale JSON `null`, che su una proprietà nullable (`?SismaJson`) diventa `null`, come il `NULL` SQL; un successivo salvataggio della colonna scrive quindi `NULL` SQL al posto del letterale JSON;
- poiché la decodifica produce array PHP, un oggetto vuoto `{}` viene riscritto come `[]`, e un oggetto con chiavi numeriche consecutive a partire da `0` viene riscritto come lista;
- una proprietà `SismaJson` cifrata con `addEncryptedProperty()` viene salvata come stringa cifrata, che non è JSON valido: la colonna deve essere di tipo testuale e non `JSON`;
- le query sul contenuto del documento sono supportate dalla 12.7.0: vedi [Query su Colonne JSON](advanced-orm.md#query-su-colonne-json).

### Proprietà Cifrate

Le proprietà dichiarate in `setEncryptedProperties()` con `addEncryptedProperty()` vengono cifrate con `Encryptor::encryptString()` al salvataggio e decifrate alla lettura, in modo trasparente per il codice che usa l'entità.

```php
class Certificate extends BaseEntity
{
    protected int $id;
    protected string $certificate;
    protected ?string $privateKey = null;
    protected ?string $privateKeyInitializationVector = null;

    protected function setEncryptedProperties(): void
    {
        $this->addEncryptedProperty('privateKey');
    }

    protected function setPropertyDefaultValue(): void { }
}
```

- **Vettore d'inizializzazione per proprietà** (v12.11.0+): per ogni proprietà cifrata l'entità dichiara una proprietà con lo stesso nome seguito dal suffisso `InitializationVector` (configurabile con `ENCRYPTED_PROPERTY_INITIALIZATION_VECTOR_SUFFIX`), e la tabella la colonna corrispondente, binaria e nullable (`private_key_initialization_vector`). L'ORM genera un nuovo IV a ogni salvataggio della proprietà e lo scrive insieme al valore cifrato: proprietà diverse e versioni successive della stessa proprietà non condividono mai l'IV. **Senza vettore d'inizializzazione la cifratura non avviene**: le proprietà dichiarate cifrate vengono salvate in chiaro, senza errori né avvisi.
- **Vettore d'inizializzazione condiviso** (deprecato dalla 12.11.0, sarà rimosso nella 13.0.0): una proprietà cifrata senza vettore proprio usa la proprietà `initializationVector` (colonna `initialization_vector`), condivisa da tutte le proprietà cifrate dell'entità e rigenerata a ogni salvataggio. Se l'entità è stata caricata solo in parte e una delle proprietà cifrate che usano l'IV condiviso non è inizializzata, l'IV caricato viene mantenuto, perché rigenerarlo renderebbe illeggibile la proprietà non riscritta; se invece l'IV condiviso non è stato caricato, ne viene generato uno nuovo e le proprietà cifrate non caricate non sono più leggibili. Con un IV condiviso, due proprietà della stessa riga con lo stesso valore, o con gli stessi primi 16 byte, producono lo stesso testo cifrato, o lo stesso primo blocco.
- **Migrazione dall'IV condiviso**: aggiungere alla tabella le colonne `<proprietà>_initialization_vector` (nullable) e all'entità le proprietà corrispondenti, lasciando per il momento `initializationVector`. Le righe esistenti, che hanno il vettore proprio a `NULL`, vengono decifrate con l'IV condiviso; al primo salvataggio ogni proprietà viene ricifrata con il proprio IV. Quando nessuna riga ha più il vettore proprio a `NULL` per le proprietà valorizzate, `initializationVector` e la sua colonna possono essere rimossi.
- **Query sulle colonne cifrate**: `Query::appendConditionOnEncryptedColumn()` riceve il nome della colonna del vettore d'inizializzazione: con il vettore per proprietà va indicata la colonna propria (`private_key_initialization_vector`), non `initialization_vector`.
- **Passphrase**: la cifratura usa `ENCRYPTION_PASSPHRASE`; con la passphrase vuota le proprietà vengono salvate in chiaro.
- **Valori nulli** (v12.8.1+): una proprietà cifrata che vale `null` viene salvata come `NULL` e letta come `null`, senza cifratura né decifratura. Prima della 12.8.1 il salvataggio e la lettura di un valore nullo sollevavano un `TypeError`.

### Lazy Loading (Caricamento Pigro)

Una delle caratteristiche più potenti dell'ORM è il **Lazy Loading**. Quando carichi un'entità, le sue relazioni (es. `$post->author`) non vengono caricate immediatamente. L'ORM carica l'oggetto `User` correlato solo nel momento esatto in cui provi ad accedervi (es. `echo $post->author->username;`). Questo ottimizza drasticamente le performance, evitando query inutili.

### Relazioni Inverse (Collezioni)

Da un'entità referenziata (es. `User`), puoi accedere a tutte le entità che la referenziano (es. tutti i `Post` di quell'utente) tramite una "proprietà magica". La convenzione è `nomeEntitàDipendenteCollection`.

```php
$user = $userModel->getEntityById(1);

// Accede a una collezione di tutti i post di questo utente.
// La query viene eseguita solo in questo momento.
$userPosts = $user->postCollection;

foreach ($userPosts as $post) {
    echo $post->title;
}
```

Esistono anche metodi magici per manipolare queste collezioni: `set...()`, `add...()`, e `count...()`.

## I Modelli in Dettaglio

Un modello è la porta d'accesso al database per una specifica entità. Deve estendere una delle classi base (`BaseModel`, `DependentModel`, `SelfDependentModel`) e specificare a quale entità è associato.

```php
namespace MyModule\App\Models;

use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\HelperClasses\Query;
use MyModule\App\Entities\Post;

class PostModel extends BaseModel
{
    protected function getEntityName(): string
    {
        return Post::class;
    }

    // Usato per le ricerche testuali
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        $query->appendOpenBlock()
            ->appendCondition('title', ComparisonOperator::like, Placeholder::placeholder)
            ->appendOr()
            ->appendCondition('content', ComparisonOperator::like, Placeholder::placeholder)
            ->appendCloseBlock();
        $bindValues[] = '%' . $searchKey . '%';
        $bindValues[] = '%' . $searchKey . '%';
        $bindTypes[] = DataType::typeString;
        $bindTypes[] = DataType::typeString;
    }
}
```

`appendSearchCondition()` viene chiamato dopo `setWhere()` e, quando la ricerca si combina con altre condizioni, dopo `appendAnd()`: il metodo aggiunge solo le proprie condizioni, racchiudendo in un blocco quelle in OR. I valori si legano con un placeholder per ogni occorrenza, nello stesso ordine delle condizioni.

### Metodi di Base (`BaseModel`)

Ogni modello eredita un set di metodi standard:

- `getEntityById(int $id)`: Trova un'entità tramite il suo ID.
- `getEntityCollection(?string $searchKey, ?array $order, ?int $offset, ?int $limit)`: Recupera una collezione di entità, con opzioni di filtro, ordinamento e paginazione.
- `countEntityCollection(?string $searchKey)`: Conta le entità, opzionalmente filtrate.
- `deleteEntityById(int $id)`: Elimina un'entità dal suo ID.

**Esempio di utilizzo in un Controller:**

```php
use MyModule\App\Models\PostModel;

// ... in un'action del controller ...
$postModel = new PostModel($this->dataMapper);

// Trova il post con ID 42
$post = $postModel->getEntityById(42);

// Trova tutti i post che contengono "SismaFramework", ordinati per data
$posts = $postModel->getEntityCollection('SismaFramework', ['publicationDate' => 'DESC']);
```

### Metodi per Relazioni (`DependentModel`)

Se un modello estende `DependentModel`, ottiene metodi per interrogare il database basandosi sulle relazioni.

- `getBy{PropertyName}(...)` (metodo magico, vedi sezione successiva): Trova entità che corrispondono a una o più entità referenziate.
- `getOtherEntityCollectionByEntity(BaseEntity $excludedEntity, string $propertyName, ?BaseEntity $baseEntity = null)`: Trova le entità collegate a `$baseEntity` tramite la proprietà `$propertyName`, escludendo `$excludedEntity`. Con `$baseEntity` a `null` trova le entità in cui la relazione è assente (v12.9.1+; fino alla 12.9.0 il parametro non ammetteva `null` e la query generata non era valida, vedi CHANGELOG 12.9.1).

**Esempio:** Trova tutti i post di un certo utente.

```php
use MyModule\App\Models\PostModel; // Deve estendere DependentModel

$postModel = new PostModel($this->dataMapper);
$user = $userModel->getEntityById(1);

// Trova tutti i post dove 'author' è l'utente con ID 1
$userPosts = $postModel->getByAuthor($user);

// Gli altri post dello stesso autore, escluso quello corrente
$otherPosts = $postModel->getOtherEntityCollectionByEntity($post, 'author', $user); // WHERE id <> ? AND author_id = ?

// Gli altri post senza autore, escluso quello corrente
$otherOrphanPosts = $postModel->getOtherEntityCollectionByEntity($post, 'author'); // WHERE id <> ? AND author_id IS NULL
```

### Metodi Magici (Query Dinamiche)

A partire dalla versione 10.1.0, l'ORM supporta **query dinamiche** su **tutte le proprietà** delle entità, non solo sulle relazioni. Questo sistema di metaprogrammazione genera automaticamente query type-safe basandosi sui nomi dei metodi chiamati.

#### Sintassi

La sintassi generale è: `get|count|delete` + `By` + `NomeProprietà` [+ `And` + `AltraProprietà`]

#### Query su Proprietà Singole

Puoi interrogare il database utilizzando qualsiasi proprietà dell'entità:

```php
// Query su entità referenziate (come prima)
$userPosts = $postModel->getByAuthor($user);
$postCount = $postModel->countByAuthor($user);
$postModel->deleteByAuthor($user);

// Query su tipi builtin (int, string, float, bool) - NUOVO in 10.1.0
$activeUsers = $userModel->getByStatus('active');
$expensiveProducts = $productModel->getByPrice(99.99);
$publishedPosts = $postModel->getByPublished(true);
$totalActiveUsers = $userModel->countByActive(true);

// Query con enum PHP 8.1+
$activePosts = $postModel->getByStatus(PostStatus::Published);
$draftCount = $postModel->countByStatus(PostStatus::Draft);

// Query con tipi custom (SismaDateTime, SismaDate, SismaTime)
$todayPosts = $postModel->getByPublicationDate(new SismaDate('2025-01-15'));
$recentPosts = $postModel->getByCreatedAt(new SismaDateTime('2025-01-01 00:00:00'));
```

#### Query su Proprietà Multiple con AND Logico

Puoi combinare più proprietà con operatore AND:

```php
// Query con due proprietà
$products = $productModel->getByNameAndCategory('iPhone', $electronicsCategory);
$users = $userModel->getByStatusAndRole('active', 'admin');

// Query con tre o più proprietà
$articles = $articleModel->getByAuthorAndCategoryAndStatus($user, $category, ArticleStatus::Published);
```

#### Gestione dei Valori NULL

Le query dinamiche gestiscono correttamente i valori `null` su proprietà nullable:

```php
// Trova entità con proprietà nullable = NULL
// Su una foreign key (proprietà tipizzata come ReferencedEntity) la colonna
// generata ha sempre il suffisso Id, anche con valore null (v12.3.0+; prima
// di questa correzione il valore null generava erroneamente la colonna senza
// suffisso — vedi CHANGELOG 12.2.1):
$orphanPosts = $postModel->getByParentPost(null); // WHERE parent_post_id IS NULL

// Trova utenti senza avatar
$usersWithoutAvatar = $userModel->getByAvatarUrl(null); // WHERE avatar_url IS NULL
```

⚠️ **Nota:** Passare `null` a una proprietà **non-nullable** lancerà un'eccezione `InvalidArgumentException`.

#### Parametri Aggiuntivi (SearchKey, Order, Paginazione)

I metodi magici supportano gli stessi parametri opzionali dei metodi standard:

```php
// Sintassi completa per getBy...()
$posts = $postModel->getByAuthor(
    $author,                              // Valore della proprietà
    'keyword',                            // searchKey (opzionale)
    ['publicationDate' => 'DESC'],        // order (opzionale)
    0,                                    // offset (opzionale)
    20                                    // limit (opzionale)
);

// Con paginazione
$page2Posts = $postModel->getByCategory($category, null, null, 20, 10);
```

#### Query Gerarchiche (SelfDependentModel)

Per modelli auto-referenziati, le query dinamiche supportano anche il parametro `parent`:

```php
$subCategories = $categoryModel->getByParentAndActive($parentCategory, true);
$childCount = $categoryModel->countByParentAndStatus($parentCategory, 'active');
```

`getOtherEntityCollectionByParent(BaseEntity $excludedEntity, ?BaseEntity $parentEntity = null, ?array $order = null)` restituisce le entità sorelle di `$excludedEntity`, cioè quelle con lo stesso padre esclusa l'entità stessa; con `$parentEntity` a `null` restituisce le altre entità radice. Fino alla 12.9.0 la condizione di esclusione veniva generata sulla colonna inesistente `id_id` (vedi CHANGELOG 12.9.1):

```php
$siblingCategories = $categoryModel->getOtherEntityCollectionByParent($category, $parentCategory); // WHERE id <> ? AND parent_category_id = ?
$otherRootCategories = $categoryModel->getOtherEntityCollectionByParent($rootCategory); // WHERE id <> ? AND parent_category_id IS NULL
```

#### Type Safety e Validazione Automatica

Il sistema utilizza **Reflection** per validare automaticamente i tipi:

- Verifica che il valore passato corrisponda al tipo della proprietà
- Gestisce correttamente `null` solo su proprietà nullable
- Supporta istanze di classi (`User`, `SismaDateTime`, enum, etc.)
- Lancia `InvalidArgumentException` in caso di tipo non valido

```php
// ✅ Corretto - tipo compatibile
$posts = $postModel->getByViewCount(1000); // int

// ❌ Errore - tipo non compatibile
$posts = $postModel->getByViewCount('mille'); // InvalidArgumentException
```

#### Metodi Legacy Rimossi

I seguenti metodi erano deprecati dalla versione 10.1.0 e sono stati **rimossi nella v12.0.0**. Utilizza le query dinamiche:

**DependentModel:**
- ~~`getEntityCollectionByEntity()`~~ → `getBy{PropertyName}()`
- ~~`countEntityCollectionByEntity()`~~ → `countBy{PropertyName}()`
- ~~`deleteEntityCollectionByEntity()`~~ → `deleteBy{PropertyName}()`

**SelfDependentModel:**
- ~~`getEntityCollectionByParentAndEntity()`~~ → `getByParentAnd{PropertyName}()`
- ~~`countEntityCollectionByParentAndEntity()`~~ → `countByParentAnd{PropertyName}()`
- ~~`deleteEntityCollectionByParentAndEntity()`~~ → `deleteByParentAnd{PropertyName}()`

**Esempio di migrazione:**

```php
// VECCHIO (rimosso in v12.0.0)
$posts = $postModel->getEntityCollectionByEntity(['author' => $user, 'category' => $category]);

// NUOVO (consigliato)
$posts = $postModel->getByAuthorAndCategory($user, $category);
```

## Query Personalizzate

Per le query che vanno oltre i metodi standard, puoi creare metodi personalizzati all'interno della tua classe `Model` utilizzando l'oggetto `Query`.

Questo ti permette di costruire istruzioni SQL complesse in modo programmatico.

### Esempio: Trovare Post per Titolo e Stato

Supponiamo di voler trovare tutti i post che contengono una certa parola nel titolo e che sono in uno stato specifico (es. `Published`).

**`MyModule/App/Models/PostModel.php`**

```php
namespace MyModule\App\Models;

use SismaFramework\Orm\ExtendedClasses\DependentModel; // o BaseModel
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Placeholder;
use MyModule\App\Enumerations\PostStatus;

class PostModel extends DependentModel
{
    // ... getEntityName() e appendSearchCondition() ...

    public function findPublishedByTitle(string $titleKeyword): SismaCollection
    {
        // 1. Inizializza la query usando il metodo del modello
        $query = $this->initQuery();

        // 2. Costruisci la clausola WHERE
        $query->setWhere()
              ->appendOpenBlock()
                  ->appendCondition('title', ComparisonOperator::like, Placeholder::placeholder)
              ->appendCloseBlock()
              ->appendAnd()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);

        // 3. Imposta l'ordinamento
        $query->setOrderBy(['publicationDate' => 'DESC']);

        // 4. Prepara i valori e i tipi per il binding
        $bindValues = [
            '%' . $titleKeyword . '%',
            PostStatus::Published
        ];

        // I bindTypes sono opzionali - il sistema parsa automaticamente i valori
        $bindTypes = [];

        // 5. Esegui la query e restituisci la collezione
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

### Funzioni di Aggregazione

A partire dalla versione 10.1.0, l'ORM supporta le funzioni di aggregazione SQL (`AVG`, `MAX`, `MIN`, `SUM`, `COUNT`) tramite la classe `Query`. Queste funzioni permettono di eseguire calcoli statistici direttamente nel database.

#### Metodi Disponibili

- `setAVG()` - Calcola la media di una colonna
- `setMax()` - Trova il valore massimo
- `setMin()` - Trova il valore minimo
- `setSum()` - Calcola la somma totale
- `setCount()` - Conta le righe, nella colonna `_numrows`

#### Sintassi

```php
$query->setAVG(
    string|Query $columnOrSubquery,  // Colonna o subquery da aggregare
    ?string $columnAlias = null,      // Alias per il risultato (opzionale)
    bool $distinct = false,           // Applica DISTINCT (opzionale)
    bool $append = false              // Aggiungi alle colonne esistenti (opzionale)
);

$query->setCount(
    string $column,                   // Colonna da contare, '' per COUNT(*)
    bool $distinct = false            // Applica DISTINCT (opzionale)
);
```

Gli stessi parametri di `setAVG()` si applicano a `setMax()`, `setMin()` e `setSum()`.

#### Lettura dei Risultati

`DataMapper::find()` restituisce entità, che non hanno proprietà per i valori calcolati. Le query di aggregazione si eseguono con `select()` dell'adapter, che restituisce righe di tipo `StandardEntity` con una proprietà per ogni colonna, chiamata come l'alias convertito in snake_case:

```php
// Calcolare la media dei prezzi
public function getAveragePrice(): float
{
    $query = $this->initQuery();
    $query->setAVG('price', 'averagePrice');
    $query->close();

    $row = $query->getAdapter()->select($query->getCommandToExecute())->fetch();
    return (float) ($row->average_price ?? 0);
}

// Trovare il prezzo più alto
public function getMaxPrice(): float
{
    $query = $this->initQuery();
    $query->setMax('price', 'maxPrice');
    $query->close();

    $row = $query->getAdapter()->select($query->getCommandToExecute())->fetch();
    return (float) ($row->max_price ?? 0);
}
```

Per i conteggi senza altre colonne sono disponibili anche `countEntityCollection()`, i metodi magici `countBy…()` e `DataMapper::getCount()`.

#### Aggregazioni con DISTINCT

Il parametro `$distinct` aggrega solo i valori distinti:

```php
// Somma dei prezzi distinti (ignora duplicati)
public function getSumOfDistinctPrices(): float
{
    $query = $this->initQuery();
    $query->setSum('price', 'totalDistinctPrices', distinct: true);
    $query->close();

    $row = $query->getAdapter()->select($query->getCommandToExecute())->fetch();
    return (float) ($row->total_distinct_prices ?? 0);
}
```

#### Più Aggregazioni con `$append`

Il parametro `$append` calcola più aggregazioni nella stessa query:

```php
// Statistiche complete sui prezzi
public function getPriceStatistics(): array
{
    $query = $this->initQuery();
    $query->setMin('price', 'minPrice')                // sostituisce le colonne
        ->setMax('price', 'maxPrice', append: true)    // aggiunge alle colonne
        ->setAVG('price', 'avgPrice', append: true)
        ->setSum('price', 'totalPrice', append: true);
    $query->close();

    $row = $query->getAdapter()->select($query->getCommandToExecute())->fetch();
    return [
        'min' => (float) $row->min_price,
        'max' => (float) $row->max_price,
        'average' => (float) $row->avg_price,
        'total' => (float) $row->total_price,
    ];
}
```

⚠️ **Nota:** senza `$append` ogni chiamata sostituisce le colonne precedenti; con `$append = true` l'aggregazione viene aggiunta. Con `$append = true` su una query senza colonne, l'aggregazione si aggiunge a quelle di default della tabella (`tabella.*`).

#### Aggregazioni con Condizioni WHERE

```php
// Media dei prezzi solo per prodotti attivi
public function getAveragePriceForActiveProducts(): float
{
    $query = $this->initQuery();
    $query->setAVG('price', 'avgActivePrice');
    $query->setWhere()
        ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
    $query->close();

    $row = $query->getAdapter()->select($query->getCommandToExecute(), [ProductStatus::active->value], [DataType::typeString])->fetch();
    return (float) ($row->avg_active_price ?? 0);
}
```

`select()` dell'adapter lega i valori così come sono: le enumerazioni si passano con il loro valore (`->value`).

#### Aggregazioni con GROUP BY

```php
// Somma vendite per categoria
public function getSalesByCategory(): array
{
    $query = $this->initQuery();
    $query->setColumns(['category_id'])
        ->setSum('amount', 'totalSales', append: true)
        ->setGroupBy(['category_id'])
        ->setOrderBy(['totalSales' => Indexing::desc]);
    $query->close();

    $salesByCategory = [];
    foreach ($query->getAdapter()->select($query->getCommandToExecute()) as $row) {
        $salesByCategory[$row->category_id] = (float) $row->total_sales;
    }
    return $salesByCategory;
}
```

`setColumns()`, `setGroupBy()` e `setOrderBy()` convertono i nomi in snake_case e ne fanno l'escape come colonne: le chiavi esterne si indicano con il nome della colonna (`category_id`), e gli alias delle aggregazioni si possono usare per l'ordinamento.

#### Espressioni nelle Colonne

`setColumns()` tratta ogni elemento come il nome di una colonna, per cui non accetta espressioni SQL. Le espressioni si aggiungono con `appendColumn()`, che le inserisce così come sono:

```php
// Report vendite mensili
public function getMonthlySalesReport(): array
{
    $query = $this->initQuery();
    $query->setSum('amount', 'totalSales')
        ->appendColumn('YEAR(`sale`.`sale_date`) AS sale_year')
        ->appendColumn('MONTH(`sale`.`sale_date`) AS sale_month')
        ->setGroupBy(['sale_year', 'sale_month'])
        ->setOrderBy(['sale_year' => Indexing::desc, 'sale_month' => Indexing::desc]);
    $query->close();

    $report = [];
    foreach ($query->getAdapter()->select($query->getCommandToExecute()) as $row) {
        $report[] = ['year' => $row->sale_year, 'month' => $row->sale_month, 'total' => (float) $row->total_sales];
    }
    return $report;
}
```

`appendColumn()` non fa l'escape del suo argomento: va usato solo con espressioni scritte nel codice, mai con valori provenienti dall'utente.

### Subquery (Sottoquery)

L'ORM supporta le subquery come colonne, come argomento delle aggregazioni, nell'ordinamento, nelle condizioni WHERE e HAVING e come tabelle in JOIN. Una subquery è una `Query` costruita a parte, con la propria tabella:

```php
use SismaFramework\Orm\HelperClasses\Query;

$commentCount = new Query();
$commentCount->setTable(Comment::class)
    ->setCount('')
    ->setWhere()
    ->appendCondition('post', ComparisonOperator::equal, 'post.id', true);
$commentCount->close();
```

**Regole generali:**
- **Chiusura**: la subquery va chiusa con `close()` prima di essere usata; `appendCondition()`, `appendExistsCondition()` e `appendJoinOnSubquery()` la chiudono automaticamente.
- **Subquery correlate**: per riferirsi a una colonna della query esterna si indica la tabella esterna, o il suo alias, seguita dalla colonna (`'post.id'`), come valore letterale e non come placeholder. Le colonne della subquery vengono qualificate con la tabella della subquery.
- **Stessa tabella**: se la subquery interroga la stessa tabella della query esterna, le si assegna un alias con `setTable(Entity::class, 'alias')`, così che i riferimenti alla tabella esterna non siano ambigui.
- **Ordine dei valori legati**: i placeholder vengono legati nell'ordine in cui compaiono nel testo SQL: prima quelli delle subquery usate come colonne, poi quelli delle subquery in JOIN, poi quelli del WHERE nell'ordine delle condizioni.

#### Subquery come Colonna

```php
public function getPostsOrderedByCommentCount(): SismaCollection
{
    $commentCount = new Query();
    $commentCount->setTable(Comment::class)
        ->setCount('')
        ->setWhere()
        ->appendCondition('post', ComparisonOperator::equal, 'post.id', true);
    $commentCount->close();

    $query = $this->initQuery();
    $query->setSubqueryColumn($commentCount, 'commentCount', true)
        ->setOrderBy(['commentCount' => Indexing::desc]);
    $query->close();

    return $this->dataMapper->find($this->entityName, $query);
}
```

Il valore calcolato serve all'ordinamento: le entità restituite da `find()` non hanno una proprietà corrispondente. Per leggerlo si esegue la query con `select()` dell'adapter.

#### Confronto con una Subquery

`appendCondition()` accetta una `Query` come valore. Con gli operatori di confronto la subquery deve restituire un solo valore:

```php
// Post con più visualizzazioni della media
public function getPopularPosts(): SismaCollection
{
    $averageViews = new Query();
    $averageViews->setTable(Post::class)
        ->setAVG('viewCount');

    $query = $this->initQuery();
    $query->setWhere()
        ->appendCondition('viewCount', ComparisonOperator::greater, $averageViews);
    $query->close();

    return $this->dataMapper->find($this->entityName, $query);
}
```

#### IN e NOT IN con una Subquery

Con `ComparisonOperator::in` e `ComparisonOperator::notIn` la subquery deve restituire una sola colonna:

```php
// Post degli autori verificati
public function getPostsOfVerifiedAuthors(): SismaCollection
{
    $verifiedAuthors = new Query();
    $verifiedAuthors->setTable(User::class)
        ->setColumn('id')
        ->setWhere()
        ->appendCondition('verified', ComparisonOperator::equal, Placeholder::placeholder);

    $query = $this->initQuery();
    $query->setWhere()
        ->appendCondition('author', ComparisonOperator::in, $verifiedAuthors, true);
    $query->close();

    return $this->dataMapper->find($this->entityName, $query, [true], [DataType::typeBoolean]);
}
```

```sql
SELECT `post`.* FROM `post`
WHERE `post`.`author_id` IN (SELECT `id` FROM `user` WHERE `user`.`verified` = ?)
```

#### EXISTS e NOT EXISTS

`appendExistsCondition()` aggiunge una condizione `EXISTS`; preceduto da `appendNot()` diventa `NOT EXISTS`:

```php
// Post con almeno un commento
public function getPostsWithComments(): SismaCollection
{
    $comments = new Query();
    $comments->setTable(Comment::class)
        ->setWhere()
        ->appendCondition('post', ComparisonOperator::equal, 'post.id', true);

    $query = $this->initQuery();
    $query->setWhere()
        ->appendExistsCondition($comments);
    $query->close();

    return $this->dataMapper->find($this->entityName, $query);
}

// Post senza commenti
public function getPostsWithoutComments(): SismaCollection
{
    $comments = new Query();
    $comments->setTable(Comment::class)
        ->setWhere()
        ->appendCondition('post', ComparisonOperator::equal, 'post.id', true);

    $query = $this->initQuery();
    $query->setWhere()
        ->appendNot()
        ->appendExistsCondition($comments);
    $query->close();

    return $this->dataMapper->find($this->entityName, $query);
}
```

#### Subquery come Primo Termine del Confronto

`appendSubqueryCondition()` confronta il risultato della subquery con un valore, nella forma `(subquery) operatore valore`:

```php
// Post con almeno un certo numero di commenti
public function getPostsWithMinimumComments(int $minimumComments): SismaCollection
{
    $commentCount = new Query();
    $commentCount->setTable(Comment::class)
        ->setCount('')
        ->setWhere()
        ->appendCondition('post', ComparisonOperator::equal, 'post.id', true);
    $commentCount->close();

    $query = $this->initQuery();
    $query->setWhere()
        ->appendSubqueryCondition($commentCount, ComparisonOperator::greaterOrEqual, Placeholder::placeholder);
    $query->close();

    return $this->dataMapper->find($this->entityName, $query, [$minimumComments], [DataType::typeInteger]);
}
```

#### JOIN su una Subquery

`appendJoinOnSubquery()` unisce alla query il risultato di una subquery, come una tabella derivata. L'alias viene convertito in snake_case, come quelli delle altre JOIN:

```php
// Autori con il numero dei loro post, solo se almeno $minimumPosts
public function getProlificAuthors(int $minimumPosts): array
{
    $postCount = new Query();
    $postCount->setTable(Post::class)
        ->setCount('')
        ->appendColumn('author_id')
        ->setGroupBy(['author_id']);

    $query = $this->initQuery();
    $query->appendJoinOnSubquery(JoinType::inner, $postCount, 'postCount', 'user.id = post_count.author_id')
        ->setColumns(['name'])
        ->appendColumn('`post_count`.`_numrows` AS post_number');
    $query->setWhere()
        ->appendCondition('post_count._numrows', ComparisonOperator::greaterOrEqual, Placeholder::placeholder);
    $query->close();

    $authors = [];
    foreach ($query->getAdapter()->select($query->getCommandToExecute(), [$minimumPosts], [DataType::typeInteger]) as $row) {
        $authors[$row->name] = $row->post_number;
    }
    return $authors;
}
```

```sql
SELECT `name`, `post_count`.`_numrows` AS post_number FROM `user`
INNER JOIN (SELECT COUNT(*) as _numrows, author_id FROM `post` GROUP BY `author_id`) AS `post_count`
    ON user.id = post_count.author_id
WHERE `post_count`.`_numrows` >= ?
```

Le colonne della tabella derivata non corrispondono a un'entità: il risultato si legge con `select()` dell'adapter. Per filtrare le entità in base a un dato aggregato, senza leggerlo, è sufficiente una subquery correlata con `appendSubqueryCondition()`.

---

[Indice](index.md) | Precedente: [Gestione degli Asset Statici](static-assets.md) | Successivo: [Funzionalità Avanzate dell'ORM](orm-additional-features.md)

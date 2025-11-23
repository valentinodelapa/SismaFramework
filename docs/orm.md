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

### Lazy Loading (Caricamento Pigro)

Una delle caratteristiche più potenti dell'ORM è il **Lazy Loading**. Quando carichi un'entità, le sue relazioni (es. `$post->author`) non vengono caricate immediatamente. L'ORM carica l'oggetto `User` correlato solo nel momento esatto in cui provi ad accedervi (es. `echo $post->author->getUsername();`). Questo ottimizza drasticamente le performance, evitando query inutili.

### Relazioni Inverse (Collezioni)

Da un'entità referenziata (es. `User`), puoi accedere a tutte le entità che la referenziano (es. tutti i `Post` di quell'utente) tramite una "proprietà magica". La convenzione è `nomeEntitàDipendenteCollection`.

```php
$user = $userModel->getEntityById(1);

// Accede a una collezione di tutti i post di questo utente.
// La query viene eseguita solo in questo momento.
$userPosts = $user->postCollection;

foreach ($userPosts as $post) {
    echo $post->getTitle();
}
```

Esistono anche metodi magici per manipolare queste collezioni: `set...()`, `add...()`, e `count...()`.

## I Modelli in Dettaglio

Un modello è la porta d'accesso al database per una specifica entità. Deve estendere una delle classi base (`BaseModel`, `DependentModel`, `SelfReferencedModel`) e specificare a quale entità è associato.

```php
namespace MyModule\App\Models;

use SismaFramework\Orm\BaseClasses\BaseModel;
use MyModule\App\Entities\Post;

class PostModel extends BaseModel
{
    protected static function getEntityName(): string
    {
        return Post::class;
    }

    // Usato per le ricerche testuali
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        $query->where('title LIKE :searchKey OR content LIKE :searchKey');
        $bindValues[':searchKey'] = '%' . $searchKey . '%';
    }
}
```

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

- `getEntityCollectionByEntity(array $referencedEntities, ...)`: Trova entità che corrispondono a una o più entità referenziate.

**Esempio:** Trova tutti i post di un certo utente.

```php
use MyModule\App\Models\PostModel; // Deve estendere DependentModel

$postModel = new PostModel($this->dataMapper);
$user = $userModel->getEntityById(1);

// Trova tutti i post dove 'author' è l'utente con ID 1
$userPosts = $postModel->getEntityCollectionByEntity(['author' => $user]);
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
$orphanPosts = $postModel->getByParentPost(null); // WHERE parent_post IS NULL

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

#### Query Gerarchiche (SelfReferencedModel)

Per modelli auto-referenziati, le query dinamiche supportano anche il parametro `parent`:

```php
$subCategories = $categoryModel->getByParentAndActive($parentCategory, true);
$childCount = $categoryModel->countByParentAndStatus($parentCategory, 'active');
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

#### Metodi Legacy Deprecati

I seguenti metodi sono **deprecati dalla versione 10.1.0** e saranno rimossi nella **v11.0.0**. Utilizza invece le query dinamiche:

**DependentModel:**
- ~~`getEntityCollectionByEntity()`~~ → `getBy{PropertyName}()`
- ~~`countEntityCollectionByEntity()`~~ → `countBy{PropertyName}()`
- ~~`deleteEntityCollectionByEntity()`~~ → `deleteBy{PropertyName}()`

**SelfReferencedModel:**
- ~~`getEntityCollectionByParentAndEntity()`~~ → `getByParentAnd{PropertyName}()`
- ~~`countEntityCollectionByParentAndEntity()`~~ → `countByParentAnd{PropertyName}()`
- ~~`deleteEntityCollectionByParentAndEntity()`~~ → `deleteByParentAnd{PropertyName}()`

**Esempio di migrazione:**

```php
// VECCHIO (deprecato, rimosso in v11.0.0)
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

use SismaFramework\Orm\BaseClasses\DependentModel; // o BaseModel
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\HelperClasses\SismaCollection;
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

A partire dalla versione 10.1.0, l'ORM supporta nativamente le funzioni di aggregazione SQL (`AVG`, `MAX`, `MIN`, `SUM`) tramite la classe `Query`. Queste funzioni permettono di eseguire calcoli statistici direttamente nel database.

#### Metodi Disponibili

- `setAVG()` - Calcola la media di una colonna
- `setMax()` - Trova il valore massimo
- `setMin()` - Trova il valore minimo
- `setSum()` - Calcola la somma totale

#### Sintassi

```php
$query->setAVG(
    string|Query $columnOrSubquery,  // Colonna o subquery da aggregare
    ?string $columnAlias = null,      // Alias per il risultato (opzionale)
    bool $distinct = false,           // Applica DISTINCT (opzionale)
    bool $append = false              // Aggiungi alle colonne esistenti (opzionale)
);
```

Gli stessi parametri si applicano a `setMax()`, `setMin()` e `setSum()`.

#### Aggregazioni Semplici

```php
// Calcolare la media dei prezzi
public function getAveragePrice(): float
{
    $query = $this->initQuery();
    $query->setAVG('price', 'average_price');
    $query->close();
    
    $result = $this->dataMapper->find($this->entityName, $query);
    return $result[0]->averagePrice ?? 0.0;
}

// Trovare il prodotto più costoso
public function getMaxPrice(): float
{
    $query = $this->initQuery();
    $query->setMax('price', 'max_price');
    $query->close();
    
    $result = $this->dataMapper->find($this->entityName, $query);
    return $result[0]->maxPrice ?? 0.0;
}

// Somma totale delle vendite
public function getTotalSales(): float
{
    $query = $this->initQuery();
    $query->setSum('amount', 'total_sales');
    $query->close();
    
    $result = $this->dataMapper->find($this->entityName, $query);
    return $result[0]->totalSales ?? 0.0;
}
```

#### Aggregazioni con DISTINCT

Utilizza il parametro `$distinct` per aggregare solo valori unici:

```php
// Somma dei prezzi distinti (ignora duplicati)
public function getSumOfDistinctPrices(): float
{
    $query = $this->initQuery();
    $query->setSum('price', 'total_distinct_prices', distinct: true);
    $query->close();
    
    $result = $this->dataMapper->find($this->entityName, $query);
    return $result[0]->totalDistinctPrices ?? 0.0;
}

// Media delle valutazioni distinte
public function getAverageDistinctRating(): float
{
    $query = $this->initQuery();
    $query->setAVG('rating', 'avg_rating', distinct: true);
    $query->close();
    
    $result = $this->dataMapper->find($this->entityName, $query);
    return $result[0]->avgRating ?? 0.0;
}
```

#### Multiple Aggregazioni con `$append`

Il parametro `$append` permette di calcolare più aggregazioni nella stessa query:

```php
// Statistiche complete sui prezzi
public function getPriceStatistics(): object
{
    $query = $this->initQuery();
    
    // Prima aggregazione (sostituisce le colonne)
    $query->setMin('price', 'min_price');
    
    // Aggregazioni successive (aggiungono alle colonne)
    $query->setMax('price', 'max_price', append: true);
    $query->setAVG('price', 'avg_price', append: true);
    $query->setSum('price', 'total_price', append: true);
    
    $query->close();
    
    $result = $this->dataMapper->find($this->entityName, $query);
    return $result[0] ?? (object)[
        'minPrice' => 0,
        'maxPrice' => 0,
        'avgPrice' => 0,
        'totalPrice' => 0
    ];
}
```

⚠️ **Nota:** Senza `$append`, ogni chiamata sostituisce le colonne precedenti. Con `$append = true`, le aggregazioni vengono aggiunte.

#### Aggregazioni con Condizioni WHERE

Combina aggregazioni con filtri per calcoli condizionali:

```php
// Media dei prezzi solo per prodotti attivi
public function getAveragePriceForActiveProducts(): float
{
    $query = $this->initQuery();
    $query->setAVG('price', 'avg_active_price');
    $query->setWhere()
          ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
    $query->close();
    
    $result = $this->dataMapper->find(
        $this->entityName, 
        $query, 
        [ProductStatus::Active]
    );
    return $result[0]->avgActivePrice ?? 0.0;
}
```

#### Aggregazioni con GROUP BY

Combina aggregazioni con raggruppamento per analisi dettagliate:

```php
// Somma vendite per categoria
public function getSalesByCategory(): SismaCollection
{
    $query = $this->initQuery();
    $query->setColumns(['category_id']);
    $query->setSum('amount', 'total_sales', append: true);
    $query->setGroupBy(['category_id']);
    $query->setOrderBy(['total_sales' => 'DESC']);
    $query->close();
    
    return $this->dataMapper->find($this->entityName, $query);
}
```

#### Aggregazioni su Subquery

Le funzioni di aggregazione supportano anche subquery come argomento:

```php
// Media del numero di commenti per post
public function getAverageCommentsPerPost(): float
{
    // Subquery per contare commenti per post
    $commentCountQuery = (new CommentModel($this->dataMapper))->initQuery();
    $commentCountQuery->setColumns(['COUNT(*)'])
                     ->setWhere()
                     ->appendCondition('post_id', ComparisonOperator::equal, 'post.id');
    $commentCountQuery->close();
    
    // Aggregazione sulla subquery
    $query = $this->initQuery();
    $query->setAVG($commentCountQuery, 'avg_comments');
    $query->close();
    
    $result = $this->dataMapper->find($this->entityName, $query);
    return $result[0]->avgComments ?? 0.0;
}
```

#### Esempi Pratici Completi

**Dashboard con statistiche prodotti:**

```php
public function getDashboardStats(): object
{
    $query = $this->initQuery();
    
    // Multiple aggregazioni
    $query->setSum('quantity', 'total_quantity')
          ->setAVG('price', 'average_price', append: true)
          ->setMin('price', 'cheapest_price', append: true)
          ->setMax('price', 'most_expensive_price', append: true);
    
    // Solo prodotti in stock
    $query->setWhere()
          ->appendCondition('in_stock', ComparisonOperator::equal, Placeholder::placeholder);
    
    $query->close();
    
    $result = $this->dataMapper->find($this->entityName, $query, [true]);
    return $result[0] ?? (object)[];
}
```

**Report vendite mensili con aggregazioni:**

```php
public function getMonthlySalesReport(): SismaCollection
{
    $query = $this->initQuery();
    
    // Seleziona anno e mese
    $query->setColumns(['YEAR(sale_date) as year', 'MONTH(sale_date) as month']);
    
    // Aggregazioni multiple
    $query->setSum('amount', 'total_sales', append: true)
          ->setAVG('amount', 'avg_sale', append: true)
          ->setMax('amount', 'max_sale', append: true);
    
    // Raggruppa per anno e mese
    $query->setGroupBy(['YEAR(sale_date)', 'MONTH(sale_date)']);
    $query->setOrderBy(['year' => 'DESC', 'month' => 'DESC']);
    
    $query->close();
    
    return $this->dataMapper->find($this->entityName, $query);
}
```

### Subquery (Sottoquery)

L'ORM supporta l'uso di subquery sia come colonne che come condizioni nella clausola WHERE. Le subquery sono query annidate che possono essere utilizzate per creare query complesse.

#### Subquery come Colonna

Puoi utilizzare una subquery come colonna nel SELECT:

```php
public function getPostsWithCommentCount(): SismaCollection
{
    $query = $this->initQuery();

    // Crea la subquery per contare i commenti
    $commentCountQuery = (new CommentModel($this->dataMapper))->initQuery();
    $commentCountQuery->setColumns(['COUNT(*)'])
                     ->setWhere()
                     ->appendCondition('post_id', ComparisonOperator::equal, 'post.id');
    $commentCountQuery->close();

    // Usa la subquery come colonna
    $query->setSubqueryColumn($commentCountQuery, 'comment_count', true);

    $query->close();
    return $this->dataMapper->find($this->entityName, $query);
}
```

#### Subquery nelle Condizioni WHERE

Puoi utilizzare subquery nelle condizioni WHERE per filtri complessi:

```php
public function getPopularPosts(): SismaCollection
{
    $query = $this->initQuery();

    // Subquery per trovare la media dei view_count
    $avgViewsQuery = $this->initQuery();
    $avgViewsQuery->setColumns(['AVG(view_count)']);
    $avgViewsQuery->close();

    // Condizione con subquery
    $query->setWhere()
          ->appendSubqueryCondition($avgViewsQuery, ComparisonOperator::greaterThan);

    $query->close();
    return $this->dataMapper->find($this->entityName, $query);
}
```

#### Subquery con EXISTS

```php
public function getPostsWithComments(): SismaCollection
{
    $query = $this->initQuery();

    // Subquery per verificare esistenza commenti
    $existsQuery = (new CommentModel($this->dataMapper))->initQuery();
    $existsQuery->setColumns(['1'])
               ->setWhere()
               ->appendCondition('post_id', ComparisonOperator::equal, 'post.id');
    $existsQuery->close();

    $query->setWhere()
          ->appendSubqueryCondition($existsQuery, ComparisonOperator::exists);

    $query->close();
    return $this->dataMapper->find($this->entityName, $query);
}
```

---

[Indice](index.md) | Precedente: [Gestione degli Asset Statici](static-assets.md) | Successivo: [Funzionalità Avanzate dell'ORM](orm-additional-features.md)

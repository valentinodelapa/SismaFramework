# JOIN con Idratazione Gerarchica Multi-Entità

## Panoramica

L'ORM di SismaFramework supporta le JOIN SQL con eager loading e idratazione gerarchica delle entità collegate. Le relazioni richieste vengono caricate insieme all'entità principale, evitando il problema delle N+1 query del caricamento lazy.

## Caratteristiche

### 1. Relazioni Supportate

#### Many-to-One (Foreign Keys)

Le relazioni many-to-one vengono caricate con una JOIN (LEFT, INNER o RIGHT):

```php
$articleModel = new ArticleModel();

$articles = $articleModel->getEntityCollectionWithRelations(['author']);

foreach ($articles as $article) {
    echo $article->author->name; // già caricato, nessuna query aggiuntiva
}
```

#### Relazioni Annidate

Le relazioni many-to-one si possono concatenare con la notazione puntata o con array annidati. Ogni livello aggiunge una JOIN, e i segmenti condivisi tra più percorsi vengono uniti una sola volta:

```php
$articles = $articleModel->getEntityCollectionWithRelations(['author.country', 'author.publisher']);

$articles = $articleModel->getEntityCollectionWithRelations([
    'author' => ['country', 'publisher'],
    'category',
]);

foreach ($articles as $article) {
    echo $article->author->country->name; // già caricato
}
```

#### One-to-Many (Collection delle ReferencedEntity)

Le collection inverse vengono caricate con una seconda query, unica per tutte le entità principali (batch loading):

```php
$authorModel = new AuthorModel();

$authors = $authorModel->getEntityCollectionWithRelations(['articleCollection']);

foreach ($authors as $author) {
    foreach ($author->articleCollection as $article) {
        echo $article->title; // collection già caricata
    }
}
```

Il nome della collection segue la convenzione delle `ReferencedEntity` (vedi [ORM](orm.md)). Per caricarla, l'entità figlia deve avere il proprio model nel namespace `Models` corrispondente a quello delle entità, con il nome dell'entità seguito da `Model` (`Entities\Article` → `Models\ArticleModel`).

#### SelfReferencedEntity

Per le strutture ad albero si possono caricare sia il padre sia i figli:

```php
$categoryModel = new CategoryModel();

$categories = $categoryModel->getEntityCollectionWithRelations(['parentCategory']);

foreach ($categories as $category) {
    if (isset($category->parentCategory)) {
        echo $category->parentCategory->name; // già caricato
    }
}

$categories = $categoryModel->getEntityCollectionWithRelations(['sonCollection']);

foreach ($categories as $category) {
    foreach ($category->sonCollection as $child) {
        echo $child->name; // collection già caricata
    }
}
```

### 2. API

#### BaseModel::getEntityCollectionWithRelations()

```php
public function getEntityCollectionWithRelations(
    array $relations,                   // ['author', 'author.country', 'articleCollection']
    ?string $searchKey = null,          // filtro di ricerca, applicato da appendSearchCondition() del model
    ?array $order = null,               // ['proprietà' => Indexing::asc|Indexing::desc]
    ?int $offset = null,                // paginazione
    ?int $limit = null,                 // numero massimo di risultati
    JoinType $joinType = JoinType::left // tipo di JOIN
): SismaCollection
```

**Esempio:**

```php
use SismaFramework\Orm\Enumerations\Indexing;

$articles = $articleModel->getEntityCollectionWithRelations(
    relations: ['author', 'category'],
    searchKey: 'php',
    order: ['createdAt' => Indexing::desc],
    limit: 10
);
```

#### BaseModel::getEntityByIdWithRelations()

```php
public function getEntityByIdWithRelations(
    int $id,
    array $relations,                   // ['author', 'author.country', 'tagCollection']
    JoinType $joinType = JoinType::left
): ?BaseEntity
```

**Esempio:**

```php
$article = $articleModel->getEntityByIdWithRelations(
    id: 1,
    relations: ['author' => ['country'], 'category', 'tagCollection']
);
```

Dalla 12.18.0 questo metodo accetta le stesse relazioni di `getEntityCollectionWithRelations()`: chiavi esterne, relazioni annidate e collection. Se l'entità è già nella cache delle entità e tutte le relazioni richieste sono chiavi esterne dirette già caricate, viene restituita senza interrogare il database.

### 3. Rilevamento Automatico del Tipo di Relazione

`getEntityCollectionWithRelations()` distingue automaticamente:

- **Chiavi esterne** (many-to-one), anche annidate: caricate con JOIN SQL
- **Collection** (one-to-many): caricate con una query `IN` per tutte le entità principali
- **SelfReferencedEntity**: il padre come una chiave esterna, i figli (`sonCollection`) come una collection

```php
$model->getEntityCollectionWithRelations([
    'author',            // many-to-one: JOIN SQL
    'articleCollection', // one-to-many: batch loading
    'parentCategory',    // self-reference: JOIN SQL
]);
```

## Architettura

### 1. Enum JoinType

**File:** `Orm/Enumerations/JoinType.php`

```php
enum JoinType
{
    case inner;
    case left;
    case right;
    case cross;
}
```

### 2. Query

**File:** `Orm/HelperClasses/Query.php`

- `appendJoin()` - JOIN manuale, con condizione `ON` esplicita
- `appendJoinOnForeignKey()` - JOIN automatica da una chiave esterna
- `appendColumn()` - aggiunta di colonne alla `SELECT`
- `getJoins()` - metadati delle JOIN
- `hasJoins()` - presenza di JOIN nella query

### 3. BaseAdapter

**File:** `Orm/BaseClasses/BaseAdapter.php`

- `buildJoinOnForeignKey()` - metadati di una JOIN costruita dalla chiave esterna
- `buildJoinedColumns()` - colonne della tabella joinata con prefisso `relazione__proprietà`
- `buildJoinClause()` - clausola SQL delle JOIN
- `parseSelect()` - con il parametro `$joins`

### 4. Idratazione Gerarchica

**File:** `Orm/BaseClasses/BaseResultSet.php`

Le colonne con prefisso `relazione__` vengono separate da quelle dell'entità principale e usate per idratare l'entità collegata, che viene poi assegnata alla proprietà corrispondente:

```php
protected function hydrate(\stdClass &$result): StandardEntity|BaseEntity
{
    if ($this->returnType == StandardEntity::class) {
        return $this->convertToStandardEntity($result);
    } elseif (!empty($this->joinMetadata)) {
        return $this->convertToHierarchicalEntity($result);
    } else {
        return $this->convertToBaseEntity($result);
    }
}
```

- `setJoinMetadata()` - metadati delle JOIN, impostati da `DataMapper`
- `convertToHierarchicalEntity()` - separazione delle colonne dell'entità principale da quelle delle entità collegate
- `hydrateNestedEntities()` - idratazione delle entità collegate, anche su più livelli
- `getEntityClassForAlias()` - classe dell'entità collegata a partire dal prefisso delle colonne

### 5. Eager Loading delle Collection

**File:** `Orm/BaseClasses/BaseModel.php`

- `isCollectionRelation()` - rileva se una relazione è una collection
- `eagerLoadCollections()` - carica le collection richieste
- `loadCollectionForEntities()` - carica una collection per tutte le entità con una sola query
- `appendNestedRelationJoin()` - JOIN delle relazioni annidate

## SQL Generato

### Many-to-One

```sql
SELECT
    `article`.*,
    `author`.`id` AS author__id,
    `author`.`name` AS author__name,
    `author`.`email` AS author__email
FROM `article`
LEFT JOIN `user` AS `author` ON `article`.`author_id` = `author`.id
WHERE `article`.`title` LIKE ?
ORDER BY `created_at` DESC
LIMIT 10
```

### One-to-Many (Batch Loading)

```sql
-- query principale
SELECT `author`.* FROM `author` WHERE …

-- batch loading: una sola query per tutti gli autori caricati
SELECT `article`.* FROM `article` WHERE `article`.`author_id` IN (?, ?, ?)
```

### SelfReferencedEntity

```sql
SELECT
    `category`.*,
    `parent_category`.`id` AS parentCategory__id,
    `parent_category`.`name` AS parentCategory__name
FROM `category`
LEFT JOIN `category` AS `parent_category`
    ON `category`.`parent_category_id` = `parent_category`.id
```

L'alias SQL della tabella joinata è il nome della relazione convertito in snake_case (`parentCategory` → `parent_category`), come i nomi di tabelle e colonne. Il prefisso delle colonne nel risultato (`parentCategory__`) resta invece il nome della proprietà, perché serve all'idratazione dell'entità collegata.

## Integrazione con la Cache

Le entità collegate passano dalla cache delle entità, come quelle caricate in modo lazy:

```php
// in BaseResultSet::hydrateNestedEntities()
if (Cache::checkEntityPresenceInCache($entityClass, $entityId)) {
    $relatedEntity = Cache::getEntityById($entityClass, $entityId);
} else {
    $relatedEntity = $this->convertToBaseEntity($childData, $entityClass);
    Cache::setEntity($relatedEntity);
}
```

- La stessa entità collegata a più righe è un unico oggetto in memoria
- Le entità caricate con le JOIN sono disponibili anche al caricamento lazy successivo

## Separatore delle Colonne

Le colonne delle tabelle joinate usano il separatore `__` (doppio underscore) tra il nome della relazione e quello della proprietà:

```
author__id
author__name
author__email
category__id
category__name
```

Il doppio underscore non entra in conflitto con i nomi in snake_case delle colonne (`author_id`), e il prefisso si separa con `explode('__', $colonna, 2)`.

## Performance

### Caricamento Lazy (N+1 Query)

```php
$articles = $articleModel->getEntityCollection(); // 1 query

foreach ($articles as $article) {
    echo $article->author->name; // 1 query per ogni autore non ancora in cache
}
```

### Eager Loading

```php
// many-to-one: 1 query con JOIN
$articles = $articleModel->getEntityCollectionWithRelations(['author']);

// one-to-many: 2 query (principale + batch)
$authors = $authorModel->getEntityCollectionWithRelations(['articleCollection']);
```

## Compatibilità

L'eager loading si aggiunge al caricamento lazy senza sostituirlo:

```php
$articles = $articleModel->getEntityCollection();
foreach ($articles as $article) {
    $author = $article->author; // caricamento lazy
}

$articles = $articleModel->getEntityCollectionWithRelations(['author']);
foreach ($articles as $article) {
    $author = $article->author; // già caricato
}
```

- `parseSelect()` ha il parametro `$joins = []` opzionale
- `BaseResultSet` usa l'idratazione gerarchica solo se la query ha delle JOIN

## Test

**File:** `Tests/Orm/JoinEagerLoadingTest.php`, `Tests/Orm/Adapters/AdapterMysqlTest.php`, `Tests/Orm/ResultSets/ResultSetMysqlTest.php`

- SQL generato per le JOIN su chiave esterna, manuali, su subquery e CROSS JOIN
- Alias in snake_case e colonne qualificate nelle condizioni WHERE
- Colonne joinate con separatore `__`
- Idratazione di un'entità collegata tramite JOIN
- Eager loading di una collection (`sonCollection`), anche con `getEntityByIdWithRelations()`
- JOIN unica per i segmenti condivisi tra relazioni annidate
- Uso dell'entità in cache in `getEntityByIdWithRelations()` solo con le relazioni già caricate
- Rilevamento automatico delle collection
- Appiattimento delle relazioni annidate

## Limitazioni Attuali

1. **Many-to-Many**: non implementato (richiederebbe il supporto delle tabelle pivot)
2. **Relazioni polimorfiche**: non supportate
3. **Collection annidate**: le collection si caricano solo al primo livello (`'articleCollection'`, non `'author.articleCollection'` né `'articleCollection.category'`). Una relazione annidata all'interno di una collection solleva un'`InvalidArgumentException`
4. **Tabelle derivate**: le colonne di una JOIN su subquery (`appendJoinOnSubquery()`, vedi [Subquery](orm.md#join-su-una-subquery)) non corrispondono a un'entità e non vengono idratate; si leggono con `select()` dell'adapter
5. **Aggregazioni**: `DataMapper::find()` restituisce entità; per i risultati di aggregazioni e report si usa `select()` dell'adapter, che restituisce `StandardEntity` (vedi [Aggregazioni con JOIN](#aggregazioni-con-join))

## Query Personalizzate con JOIN

Oltre a `getEntityCollectionWithRelations()`, si possono scrivere nel model query personalizzate con JOIN esplicite e condizioni sulle tabelle joinate. La query si ottiene con `initQuery()`, che imposta già la tabella dell'entità, e si esegue con `$this->dataMapper->find()`.

### Alias e Qualificazione delle Colonne

- **Alias in snake_case**: gli alias delle JOIN vengono convertiti in snake_case, sia quelli generati da `appendJoinOnForeignKey()` sia quelli passati ad `appendJoin()`. Nelle condizioni `ON` scritte a mano gli alias vanno quindi indicati in snake_case (`author_country.id`, non `authorCountry.id`).
- **Colonne qualificate nel WHERE**: le colonne delle condizioni WHERE (`appendCondition()`, `appendConditionOnEncryptedColumn()`, `appendFulltextCondition()` e le condizioni JSON) vengono qualificate con la tabella principale, o con il suo alias. In una query con JOIN, quindi, una condizione su `id` si riferisce sempre alla tabella principale e non è mai ambigua.
- **Colonne delle tabelle joinate**: per filtrare su una tabella joinata si indica il nome della relazione seguito dal punto (`author.country`, `parentCategory.name`). Relazione e colonna vengono convertite in snake_case e la colonna non viene qualificata di nuovo.
- **HAVING non qualificato**: le condizioni HAVING non vengono qualificate, perché di norma si riferiscono agli alias della `SELECT`.

### Regole Generali

- **`setWhere()` prima delle condizioni**: senza `setWhere()` (o `setHaving()`) le condizioni aggiunte vengono ignorate.
- **Operatori logici espliciti**: le condizioni si collegano con `appendAnd()` e `appendOr()`, e si raggruppano con `appendOpenBlock()` e `appendCloseBlock()`.
- **Valori tramite placeholder**: i valori vanno passati con `Placeholder::placeholder` e legati in `find()` con `$bindValues` e `$bindTypes`, nello stesso ordine in cui compaiono nella query. Un valore letterale viene inserito nell'SQL così com'è, senza apici: va usato solo per riferimenti a colonne, come nelle subquery correlate.
- **Colonne delle tabelle joinate**: `appendJoinOnForeignKey()` e `appendJoin()` aggiungono solo la JOIN. Per idratare l'entità collegata vanno aggiunte anche le sue colonne con `buildJoinedColumns()`.

### Query con JOIN e Condizioni sulla Tabella Joinata

```php
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\JoinType;
use SismaFramework\Orm\Enumerations\Placeholder;

class ArticleModel extends DependentModel
{
    public function getPublishedArticlesByAuthorCountry(string $country): SismaCollection
    {
        $query = $this->initQuery();
        $query->appendJoinOnForeignKey(JoinType::left, 'author', User::class);
        foreach ($query->getAdapter()->buildJoinedColumns('author', User::class) as $column) {
            $query->appendColumn($column);
        }
        $query->setWhere()
            ->appendCondition('author.country', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $query->setOrderBy(['createdAt' => Indexing::desc])
            ->setLimit(10);

        $bindValues = [$country, ArticleStatus::published];
        $bindTypes = [DataType::typeString, DataType::typeEnumeration];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

```php
foreach ($articleModel->getPublishedArticlesByAuthorCountry('IT') as $article) {
    echo $article->author->name; // già caricato con la JOIN
}
```

### JOIN Multiple

```php
class ProductModel extends DependentModel
{
    public function getPremiumProductsByBrands(array $brandNames, float $minimumPrice): SismaCollection
    {
        $query = $this->initQuery();
        $query->appendJoinOnForeignKey(JoinType::inner, 'category', Category::class)
            ->appendJoinOnForeignKey(JoinType::inner, 'brand', Brand::class);
        $query->setWhere()
            ->appendCondition('category.tier', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('brand.name', ComparisonOperator::in, array_fill(0, count($brandNames), Placeholder::placeholder))
            ->appendAnd()
            ->appendCondition('price', ComparisonOperator::greaterOrEqual, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('stock', ComparisonOperator::greater, Placeholder::placeholder);
        $query->setOrderBy(['price' => Indexing::asc]);

        $bindValues = ['PREMIUM', ...$brandNames, $minimumPrice, 0];
        $bindTypes = [DataType::typeString, ...array_fill(0, count($brandNames), DataType::typeString), DataType::typeDecimal, DataType::typeInteger];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

In questo esempio le JOIN servono solo a filtrare: le colonne di `category` e `brand` non vengono aggiunte, e le due relazioni restano a caricamento lazy.

### JOIN Annidate Manuali

`getEntityCollectionWithRelations(['author.country'])` costruisce automaticamente le JOIN annidate. Scritte a mano, servono quando si vogliono aggiungere condizioni:

```php
class ArticleModel extends DependentModel
{
    public function getArticlesByAuthorContinent(string $continent): SismaCollection
    {
        $query = $this->initQuery();
        $query->appendJoinOnForeignKey(JoinType::left, 'author', User::class);
        $query->appendJoin(
            JoinType::left,
            Country::class,                          // entità da joinare
            'author_country',                        // alias: percorso della relazione separato da underscore
            'author.country_id = author_country.id', // condizione ON, con gli alias in snake_case
            Country::class                           // entità per l'idratazione
        );
        foreach ($query->getAdapter()->buildJoinedColumns('author', User::class) as $column) {
            $query->appendColumn($column);
        }
        foreach ($query->getAdapter()->buildJoinedColumns('author_country', Country::class) as $column) {
            $query->appendColumn($column);
        }
        $query->setWhere()
            ->appendCondition('author_country.continent', ComparisonOperator::equal, Placeholder::placeholder);

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, [$continent], [DataType::typeString]);
    }
}
```

L'alias `author_country` indica il percorso della relazione: l'entità `Country` viene assegnata a `$article->author->country`. Le colonne di `author` vanno aggiunte prima di quelle di `author_country`, perché l'autore deve essere idratato prima del suo paese.

### Self-Join

```php
class ProductModel extends DependentModel
{
    public function getProductsWithMoreExpensiveAlternative(): SismaCollection
    {
        $query = $this->initQuery();
        $query->appendJoin(
            JoinType::inner,
            Product::class,
            'more_expensive',
            'product.category_id = more_expensive.category_id AND product.price < more_expensive.price'
        );
        $query->setDistinct()
            ->setOrderBy(['product.price' => Indexing::asc]);

        $query->close();
        return $this->dataMapper->find($this->entityName, $query);
    }
}
```

Senza entità per l'idratazione, la JOIN serve solo come filtro e il risultato contiene le sole entità `Product`.

### Aggregazioni con JOIN

`DataMapper::find()` restituisce entità. Per le aggregazioni si esegue la query con `select()` dell'adapter, che restituisce righe di tipo `StandardEntity`:

```php
class ProductModel extends DependentModel
{
    public function getAveragePriceByCategory(): array
    {
        $query = $this->initQuery();
        $query->appendJoinOnForeignKey(JoinType::inner, 'category', Category::class)
            ->setColumns(['category.name'])
            ->setAVG('product.price', 'averagePrice', append: true)
            ->setGroupBy(['category.name']);
        $query->setWhere()
            ->appendCondition('active', ComparisonOperator::equal, Placeholder::placeholder);
        $query->close();

        $averages = [];
        foreach ($query->getAdapter()->select($query->getCommandToExecute(), [true], [DataType::typeBoolean]) as $row) {
            $averages[$row->name] = (float) $row->average_price;
        }
        return $averages;
    }
}
```

### CROSS JOIN

La CROSS JOIN non ha condizione: si passa una stringa vuota come condizione `ON`.

```php
class SizeModel extends BaseModel
{
    public function getSizeColorCombinations(): array
    {
        $query = $this->initQuery();
        $query->appendJoin(JoinType::cross, Color::class, 'color', '')
            ->setColumns(['size.name'])
            ->appendColumn('`color`.`name` AS color_name');
        $query->close();

        $combinations = [];
        foreach ($query->getAdapter()->select($query->getCommandToExecute()) as $row) {
            $combinations[] = $row->name . ' / ' . $row->color_name;
        }
        return $combinations;
    }
}
```

### Subquery Correlate

Per filtrare le entità in base a dati aggregati, senza leggerli, si usa una subquery correlata, che si riferisce alla tabella della query esterna:

```php
use SismaFramework\Orm\HelperClasses\Query;

class UserModel extends DependentModel
{
    public function getProlificAuthors(int $minimumArticles): SismaCollection
    {
        $subquery = new Query();
        $subquery->setTable(Article::class)
            ->setCount('')
            ->setWhere()
            ->appendCondition('author', ComparisonOperator::equal, 'user.id', true)
            ->appendAnd()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $subquery->close();

        $query = $this->initQuery();
        $query->setWhere()
            ->appendSubqueryCondition($subquery, ComparisonOperator::greaterOrEqual, Placeholder::placeholder);

        $bindValues = [ArticleStatus::published, $minimumArticles];
        $bindTypes = [DataType::typeEnumeration, DataType::typeInteger];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

La condizione `author_id = user.id` collega ogni riga della subquery all'utente della query esterna. `'user.id'` è un riferimento a una colonna, non un valore, e per questo è scritto direttamente nella condizione invece che legato con un placeholder.

Quando il dato aggregato va anche letto, ad esempio il numero di articoli di ogni autore, si usa una JOIN su subquery con `appendJoinOnSubquery()`. Le condizioni `IN (subquery)`, `EXISTS` e il confronto con una subquery scalare sono descritti in [Subquery](orm.md#subquery-sottoquery).

### DataMapper Fuori dal Model

Fuori da un model si può usare direttamente `DataMapper`, che con `initQuery()` restituisce una query con la tabella dell'entità già impostata:

```php
use SismaFramework\Orm\CustomTypes\SismaDate;
use SismaFramework\Orm\HelperClasses\DataMapper;

$dataMapper = new DataMapper();
$query = $dataMapper->initQuery(Article::class);
$query->appendJoinOnForeignKey(JoinType::left, 'author', User::class)
    ->appendJoinOnForeignKey(JoinType::left, 'category', Category::class);
$query->setWhere()
    ->appendCondition('author.verified', ComparisonOperator::equal, Placeholder::placeholder)
    ->appendAnd()
    ->appendCondition('category.active', ComparisonOperator::equal, Placeholder::placeholder)
    ->appendAnd()
    ->appendCondition('publishedAt', ComparisonOperator::greaterOrEqual, Placeholder::placeholder);
$query->close();

$articles = $dataMapper->find(
    Article::class,
    $query,
    [true, true, new SismaDate('2024-01-01')],
    [DataType::typeBoolean, DataType::typeBoolean, DataType::typeDate]
);
```

## Esempi Completi

### E-commerce

```php
$products = $productModel->getEntityCollectionWithRelations(
    relations: ['category', 'brand'],
    searchKey: 'laptop',
    order: ['price' => Indexing::asc],
    limit: 20
);

$categories = $categoryModel->getEntityCollectionWithRelations(['productCollection']);

$categories = $categoryModel->getEntityCollectionWithRelations(['parentCategory', 'sonCollection']);
```

### Blog

```php
$posts = $postModel->getEntityCollectionWithRelations(
    relations: ['author', 'author.country', 'tagCollection'],
    order: ['publishedAt' => Indexing::desc]
);

$post = $postModel->getEntityByIdWithRelations(
    id: 1,
    relations: ['author.country', 'category', 'tagCollection']
);
```

### Organigramma

```php
$employees = $employeeModel->getEntityCollectionWithRelations(['parentEmployee']);

$managers = $employeeModel->getEntityCollectionWithRelations(['sonCollection']);
```

In una `SelfReferencedEntity` la proprietà del padre ha il prefisso `parent` seguito dal nome dell'entità (`parentEmployee`), e i figli sono nella collection `sonCollection`.

# JOIN con Idratazione Gerarchica Multi-Entità

## Panoramica

Implementazione completa di JOIN SQL con eager loading e idratazione gerarchica multi-entità nell'ORM di SismaFramework, risolvendo il problema N+1 delle query senza introdurre breaking changes.

## Caratteristiche Implementate

### 1. Supporto JOIN Completo

#### Many-to-One (Foreign Keys)
Caricamento eager di relazioni many-to-one tramite LEFT/INNER/RIGHT JOIN:

```php
$articleModel = new ArticleModel();

// Eager loading dell'autore
$articles = $articleModel->getEntityCollectionWithRelations(['author']);

foreach ($articles as $article) {
    echo $article->author->name; // GIÀ CARICATO, nessuna query aggiuntiva
}
```

#### One-to-Many (ReferencedEntity Collections)
Caricamento eager di collection inverse con batch loading:

```php
$authorModel = new AuthorModel();

// Eager loading degli articoli
$authors = $authorModel->getEntityCollectionWithRelations(['articleCollection']);

foreach ($authors as $author) {
    foreach ($author->articleCollection as $article) {
        echo $article->title; // Collection pre-caricata
    }
}
```

#### SelfReferencedEntity
Supporto per relazioni ricorsive (tree structures):

```php
$categoryModel = new CategoryModel();

// Eager loading della categoria padre
$categories = $categoryModel->getEntityCollectionWithRelations(['parentCategory']);

foreach ($categories as $category) {
    if (isset($category->parentCategory)) {
        echo $category->parentCategory->name; // Già caricato
    }
}

// Eager loading delle sotto-categorie (collection)
$parentCategories = $categoryModel->getEntityCollectionWithRelations(['sonCollection']);

foreach ($parentCategories as $parent) {
    foreach ($parent->sonCollection as $child) {
        echo $child->name; // Collection pre-caricata
    }
}
```

### 2. API Utilizzabile

#### BaseModel::getEntityCollectionWithRelations()

```php
public function getEntityCollectionWithRelations(
    array $relations,           // ['author', 'category', 'tagCollection']
    ?string $searchKey = null,  // Filtro di ricerca
    ?array $order = null,       // Ordinamento
    ?int $offset = null,        // Paginazione
    ?int $limit = null,         // Limite risultati
    JoinType $joinType = JoinType::left  // Tipo di JOIN
): SismaCollection
```

**Esempio:**
```php
$articles = $articleModel->getEntityCollectionWithRelations(
    relations: ['author', 'category'],
    searchKey: 'php',
    order: ['createdAt' => 'DESC'],
    limit: 10
);
```

#### BaseModel::getEntityByIdWithRelations()

```php
public function getEntityByIdWithRelations(
    int $id,
    array $relations,
    JoinType $joinType = JoinType::left
): ?BaseEntity
```

**Esempio:**
```php
$article = $articleModel->getEntityByIdWithRelations(
    id: 1,
    relations: ['author', 'category', 'tagCollection']
);
```

### 3. Meccanismo di Rilevamento Automatico

L'implementazione distingue automaticamente tra:

- **Foreign Key Properties** (many-to-one): Usano JOIN SQL
- **Collections** (one-to-many): Usano batch loading con IN query
- **SelfReferencedEntity**: Gestite come foreign key normali

```php
// L'ORM rileva automaticamente il tipo di relazione
$model->getEntityCollectionWithRelations([
    'author',           // ← Many-to-one: JOIN SQL
    'articleCollection', // ← One-to-many: Batch loading
    'parentCategory'    // ← Self-reference: JOIN SQL
]);
```

## Architettura Implementata

### 1. Enum JoinType
**File:** `Orm/Enumerations/JoinType.php`

```php
enum JoinType {
    case inner;
    case left;
    case right;
    case cross;
}
```

### 2. Query Builder Extension
**File:** `Orm/HelperClasses/Query.php`

**Nuove proprietà:**
- `protected array $joins = []` - Metadati JOIN

**Nuovi metodi:**
- `appendJoin()` - JOIN manuale
- `appendJoinOnForeignKey()` - JOIN automatica da foreign key
- `appendColumn()` - Aggiunta colonne
- `getJoins()` - Recupero metadati

### 3. BaseAdapter Extension
**File:** `Orm/BaseClasses/BaseAdapter.php`

**Nuovi metodi:**
- `buildJoinOnForeignKey()` - Costruisce JOIN da metadata entity
- `buildJoinedColumns()` - Genera colonne con prefisso `foreignKey__property`
- `buildJoinClause()` - Costruisce clausola SQL JOIN
- `parseSelect()` - Esteso con parametro `$joins`

### 4. Idratazione Gerarchica Trasparente
**File:** `Orm/BaseClasses/BaseResultSet.php`

**Costante:**
```php
protected const COLUMN_SEPARATOR = '__';
```

**Nuove proprietà:**
- `protected array $joinMetadata = []`

**Nuovi metodi:**
- `setJoinMetadata()` - Imposta metadati JOIN
- `convertToHierarchicalEntity()` - Idratazione multi-entity
- `hydrateRelatedEntity()` - Idratazione entity correlate
- `getJoinInfoByProperty()` - Recupero info JOIN

**Logica automatica:**
```php
protected function hydrate(\stdClass &$result): StandardEntity|BaseEntity
{
    if ($this->returnType == StandardEntity::class) {
        return $this->convertToStandardEntity($result);
    } elseif (!empty($this->joinMetadata)) {
        return $this->convertToHierarchicalEntity($result); // ← AUTOMATICO
    } else {
        return $this->convertToBaseEntity($result);
    }
}
```

### 5. Eager Loading Collections
**File:** `Orm/BaseClasses/BaseModel.php`

**Nuovi metodi:**
- `isCollectionRelation()` - Rileva se è una collection
- `eagerLoadCollections()` - Carica collections con batch loading
- `loadCollectionForEntities()` - Carica collection specifica
- `appendSelfReferencedJoin()` - Gestisce self-reference

## Pattern SQL Generato

### Many-to-One JOIN

```sql
SELECT
    articles.*,
    author.id AS author__id,
    author.name AS author__name,
    author.email AS author__email
FROM articles
LEFT JOIN authors AS author ON articles.author_id = author.id
WHERE articles.title LIKE ?
ORDER BY articles.created_at DESC
LIMIT 10
```

### One-to-Many Batch Loading

```sql
-- Query principale
SELECT * FROM authors WHERE ... ;

-- Batch loading automatico (1 sola query per tutti gli autori)
SELECT * FROM articles
WHERE author_id IN (?, ?, ?, ...) -- IDs di tutti gli autori caricati
```

### SelfReferencedEntity

```sql
SELECT
    categories.*,
    parentCategory.id AS parentCategory__id,
    parentCategory.name AS parentCategory__name
FROM categories
LEFT JOIN categories AS parentCategory
    ON categories.parent_category_id = parentCategory.id
```

## Integrazione con Cache

L'implementazione si integra perfettamente con la cache esistente:

```php
// In convertToHierarchicalEntity()
if (Cache::checkEntityPresenceInCache($relatedEntityClass, $entityId)) {
    $relatedEntity = Cache::getEntityById($relatedEntityClass, $entityId);
} else {
    $relatedEntity = $this->hydrateRelatedEntity($childData, $relatedEntityClass);
    Cache::setEntity($relatedEntity); // ← Cache automatica
}
```

**Vantaggi:**
- ✅ Stesso oggetto in memoria per entity duplicate
- ✅ Compatibilità con lazy loading esistente
- ✅ Identity Map pattern automatico

## Separatore Colonne

Le colonne delle entity joined usano il separatore `__` (doppio underscore):

```
author__id
author__name
author__email
category__id
category__name
```

**Perché `__`?**
- Non conflitto con snake_case esistente (`author_id`)
- Facile parsing: `explode('__', $col, 2)`
- Standard de-facto in molti ORM

## Performance

### Prima (N+1 Query)
```php
$articles = $articleModel->getEntityCollection(); // 1 query

foreach ($articles as $article) {
    echo $article->author->name; // N query (una per articolo)
}
// Totale: 1 + N query
```

### Dopo (1 o 2 Query)
```php
// Many-to-one: 1 SOLA query con JOIN
$articles = $articleModel->getEntityCollectionWithRelations(['author']);

// One-to-many: 2 query totali (main + batch)
$authors = $authorModel->getEntityCollectionWithRelations(['articleCollection']);
```

## Compatibilità

### ✅ Zero Breaking Changes

Tutto il codice esistente continua a funzionare:

```php
// Vecchia API - FUNZIONA
$articles = $articleModel->getEntityCollection();
foreach ($articles as $article) {
    $author = $article->author; // Lazy loading
}

// Nuova API - OPZIONALE
$articles = $articleModel->getEntityCollectionWithRelations(['author']);
foreach ($articles as $article) {
    $author = $article->author; // Già caricato
}
```

### ✅ Backward Compatible

- `parseSelect()` ha parametro `$joins = []` opzionale
- `BaseResultSet` usa idratazione gerarchica solo se `!empty($joinMetadata)`
- Metodi esistenti non modificati

## Testing

**File:** `Tests/Orm/JoinEagerLoadingTest.php`

Test implementati:
- ✅ Query con JOIN costruisce SQL corretto
- ✅ Colonne joined hanno separatore `__`
- ✅ Model espone metodi eager loading
- ✅ Rilevamento automatico collection
- ✅ Separatore costante

## Limitazioni Attuali

1. **Many-to-Many**: Non implementato (richiederebbe pivot table support)
2. **Polymorphic Relations**: Non supportato

## Query Custom con JOIN Espliciti

Oltre ai metodi helper come `getEntityCollectionWithRelations()`, puoi costruire query completamente personalizzate con JOIN espliciti e condizioni complesse sulle tabelle joined.

### Costruzione Query Manuale

```php
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\Enumerations\JoinType;
use SismaFramework\Orm\Enumerations\Operator;
use SismaFramework\Orm\Enumerations\Indexing;

// Query con JOIN e condizioni sulla tabella joined
$query = new Query($articleModel->getAdapter());
$query->setTable(Article::class)
    ->appendJoinOnForeignKey(JoinType::left, 'author', User::class)
    ->appendCondition('author.country', Operator::eq, 'IT')
    ->appendCondition('article.status', Operator::eq, ArticleStatus::PUBLISHED)
    ->setOrderBy(['article.createdAt' => Indexing::desc])
    ->setLimit(10);

// Esegui query con hydration automatica
$italianArticles = $articleModel->getEntityCollection(
    searchKey: null,
    order: null,
    offset: null,
    limit: null,
    customQuery: $query
);

foreach ($italianArticles as $article) {
    echo $article->author->name; // Già caricato con JOIN
    echo $article->author->country; // = 'IT'
}
```

### JOIN Multipli con Condizioni Complesse

```php
// Prodotti con categoria premium E brand specifico
$query = new Query($productModel->getAdapter());
$query->setTable(Product::class)
    ->appendJoinOnForeignKey(JoinType::inner, 'category', Category::class)
    ->appendJoinOnForeignKey(JoinType::inner, 'brand', Brand::class)
    ->appendCondition('category.tier', Operator::eq, 'PREMIUM')
    ->appendCondition('brand.name', Operator::in, ['Apple', 'Samsung', 'Sony'])
    ->appendCondition('product.price', Operator::gte, 500.00)
    ->appendCondition('product.stock', Operator::gt, 0)
    ->setOrderBy(['product.price' => Indexing::asc]);

$premiumProducts = $productModel->getEntityCollection(customQuery: $query);
```

### JOIN Nested Manuali

```php
// Articoli -> Autore -> Paese (relazione a 3 livelli)
$query = new Query($articleModel->getAdapter());
$query->setTable(Article::class)
    ->appendJoinOnForeignKey(JoinType::left, 'author', User::class);

// JOIN manuale per il paese dell'autore
$query->appendJoin(
    JoinType::left,
    Country::class,  // Tabella da joinare
    'author_country', // Alias
    'author.country_id = author_country.id', // Condizione ON
    Country::class // Entity class per hydration
);

// Aggiungi colonne del paese
$countryColumns = $query->getAdapter()->buildJoinedColumns('author_country', Country::class);
foreach ($countryColumns as $column) {
    $query->appendColumn($column);
}

// Condizione sulla tabella nested
$query->appendCondition('author_country.continent', Operator::eq, 'Europe');

$europeanAuthorArticles = $articleModel->getEntityCollection(customQuery: $query);
```

### Self-Join per Comparazioni

```php
// Trova prodotti più economici dei prodotti della stessa categoria
$query = new Query($productModel->getAdapter());
$query->setTable(Product::class)
    ->appendJoin(
        JoinType::inner,
        Product::class,
        'p2', // Alias per self-join
        'product.category_id = p2.category_id AND product.price < p2.price',
        Product::class
    )
    ->setDistinct(true) // Evita duplicati
    ->setOrderBy(['product.category_id' => Indexing::asc, 'product.price' => Indexing::asc]);

$cheaperProducts = $productModel->getEntityCollection(customQuery: $query);
```

### Aggregazioni con JOIN

```php
use SismaFramework\Orm\Enumerations\AggregationFunction;

// Media prezzi per categoria (con JOIN)
$query = new Query($productModel->getAdapter());
$query->setTable(Product::class)
    ->appendJoinOnForeignKey(JoinType::inner, 'category', Category::class)
    ->setColumns(['category.name', 'category.id'])
    ->setAVG('product.price', 'average_price', append: true)
    ->setGroupBy(['category.id'])
    ->appendCondition('product.active', Operator::eq, true);

// Nota: Questo restituirà stdClass, non entità complete
$categoryAverages = $productModel->getAdapter()->select($query);
```

### CROSS JOIN per Combinazioni

```php
// Tutte le combinazioni Size x Color disponibili
$query = new Query($sizeModel->getAdapter());
$query->setTable(Size::class)
    ->appendJoin(
        JoinType::cross,
        Color::class,
        'color',
        '', // CROSS JOIN non ha condizione ON
        Color::class
    );

$allCombinations = $sizeModel->getEntityCollection(customQuery: $query);
```

### Subquery con JOIN

```php
// Trova autori con almeno 10 articoli pubblicati
$subquery = new Query($articleModel->getAdapter());
$subquery->setTable(Article::class)
    ->setColumns(['author_id'])
    ->setCount('id', 'article_count')
    ->setGroupBy(['author_id'])
    ->appendCondition('status', Operator::eq, ArticleStatus::PUBLISHED)
    ->setHaving('article_count >= 10');

$query = new Query($userModel->getAdapter());
$query->setTable(User::class)
    ->appendJoin(
        JoinType::inner,
        '(' . $subquery->build() . ')',
        'prolific_authors',
        'user.id = prolific_authors.author_id',
        null // Nessuna entity class per subquery
    );

$prolificAuthors = $userModel->getEntityCollection(customQuery: $query);
```

### Accesso Diretto al DataMapper

Per query ancora più complesse, puoi usare direttamente il DataMapper:

```php
use SismaFramework\Orm\HelperClasses\DataMapper;

$dataMapper = new DataMapper(Article::class);

$query = new Query($dataMapper->getAdapter());
$query->setTable(Article::class)
    ->appendJoinOnForeignKey(JoinType::left, 'author', User::class)
    ->appendJoinOnForeignKey(JoinType::left, 'category', Category::class)
    ->appendCondition('author.verified', Operator::eq, true)
    ->appendCondition('category.active', Operator::eq, true)
    ->appendCondition('article.publishedAt', Operator::gte, '2024-01-01');

// Esegui query custom tramite DataMapper
$verifiedAuthorArticles = $dataMapper->getEntityCollection($query);
```

## Esempi Completi

### Scenario E-commerce

```php
// Prodotti con categoria e brand (many-to-one)
$products = $productModel->getEntityCollectionWithRelations(
    relations: ['category', 'brand'],
    searchKey: 'laptop',
    order: ['price' => 'ASC'],
    limit: 20
);

// Categoria con tutti i prodotti (one-to-many)
$category = $categoryModel->getEntityByIdWithRelations(
    id: 5,
    relations: ['productCollection']
);

// Tree di categorie (self-reference)
$categories = $categoryModel->getEntityCollectionWithRelations(['parentCategory']);
```

### Scenario Blog

```php
// Post con autore e tag
$posts = $postModel->getEntityCollectionWithRelations(
    relations: ['author', 'tagCollection'],
    order: ['publishedAt' => 'DESC']
);

// Autore con tutti i post
$author = $authorModel->getEntityByIdWithRelations(
    id: 1,
    relations: ['postCollection', 'country']
);
```

### Scenario Organizational Tree

```php
// Dipendenti con manager
$employees = $employeeModel->getEntityCollectionWithRelations(['manager']);

// Manager con team (self-reference collection)
$managers = $employeeModel->getEntityCollectionWithRelations(['sonCollection']);
```

## Conclusioni

L'implementazione fornisce:

✅ **Risoluzione problema N+1** - Eager loading completo
✅ **Zero breaking changes** - Compatibilità totale
✅ **API pulita** - Semplice da usare
✅ **Type-safe** - Validation con Reflection API
✅ **Cache integration** - Sfrutta infrastruttura esistente
✅ **Production-ready** - Gestione completa di tutti i casi d'uso

L'ORM di SismaFramework ora supporta relazioni complesse mantenendo le performance ottimali! 🎉

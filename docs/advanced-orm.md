# Advanced ORM

Questa guida copre gli aspetti avanzati dell'ORM di SismaFramework, incluse tecniche per query complesse, ottimizzazione delle performance, gestione delle relazioni e patterns architetturali per applicazioni enterprise.

## Panoramica

L'ORM di SismaFramework implementa il pattern **Data Mapper**, offrendo:
- **Mappatura automatica** tra oggetti e database
- **Lazy loading** automatico per le relazioni
- **Query builder** per condizioni complesse
- **Cache intelligente** per performance ottimali
- **Metodi magici** per query basate su relazioni
- **Gestione transazioni** per operazioni atomiche

---

## Query Avanzate con Data Mapper

### Query Builder con Condizioni Complesse

```php
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\Enumerations\DataType;

class AdvancedPostService
{
    private PostModel $postModel;
    private DataMapper $dataMapper;

    public function findPostsByComplexCriteria(
        string $titlePattern,
        \DateTime $fromDate,
        array $authorIds,
        bool $isPublished = true
    ): SismaCollection {
        $query = $this->postModel->initQuery();
        $bindValues = [];
        $bindTypes = [];

        $query->setWhere()
            // Titolo contiene pattern
            ->appendCondition('title', ComparisonOperator::like, Placeholder::placeholder)
            ->appendAnd()
            // Data pubblicazione maggiore di
            ->appendCondition('published_at', ComparisonOperator::greaterThan, Placeholder::placeholder)
            ->appendAnd()
            // Autore in lista
            ->appendCondition('author_id', ComparisonOperator::in, Placeholder::placeholder)
            ->appendAnd()
            // Status pubblicato
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);

        $bindValues = [
            "%{$titlePattern}%",
            $fromDate->format('Y-m-d H:i:s'),
            $authorIds,
            $isPublished ? 'published' : 'draft'
        ];

        $bindTypes = [
            DataType::typeString,
            DataType::typeString,
            DataType::typeArrayInteger,
            DataType::typeString
        ];

        $query->setOrderBy(['published_at' => 'DESC']);
        $query->close();

        return $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);
    }

    public function findPostsWithLogicalGrouping(): SismaCollection
    {
        $query = $this->postModel->initQuery();

        // WHERE (status = 'published' OR status = 'featured')
        //   AND (view_count > 100 OR comment_count > 10)
        $query->setWhere()
            ->appendOpenBlock()
                ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
                ->appendOr()
                ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendCloseBlock()
            ->appendAnd()
            ->appendOpenBlock()
                ->appendCondition('view_count', ComparisonOperator::greaterThan, Placeholder::placeholder)
                ->appendOr()
                ->appendCondition('comment_count', ComparisonOperator::greaterThan, Placeholder::placeholder)
            ->appendCloseBlock();

        $bindValues = ['published', 'featured', 100, 10];
        $bindTypes = [
            DataType::typeString,
            DataType::typeString,
            DataType::typeInteger,
            DataType::typeInteger
        ];

        $query->close();
        return $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);
    }
}
```

### Fulltext Search

```php
class PostSearchService
{
    public function searchPosts(string $searchTerms): SismaCollection
    {
        $query = $this->postModel->initQuery();

        // Usa fulltext search su colonne indicizzate
        $query->setWhere()
            ->appendFulltextCondition(['title', 'content'], Placeholder::placeholder);

        $bindValues = [$searchTerms];
        $bindTypes = [DataType::typeString];

        $query->close();
        return $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);
    }

    public function searchWithRelevanceScore(string $searchTerms): SismaCollection
    {
        $query = $this->postModel->initQuery();

        // Aggiunge score di relevance come colonna
        $query->setFulltextIndexColumn(['title', 'content'], Placeholder::placeholder, 'relevance_score', true)
            ->setWhere()
            ->appendFulltextCondition(['title', 'content'], Placeholder::placeholder);

        $bindValues = [$searchTerms, $searchTerms];
        $bindTypes = [DataType::typeString, DataType::typeString];

        $query->setOrderBy(['relevance_score' => 'DESC']);
        $query->close();

        return $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);
    }
}
```

---

## Metodi Magici e Relazioni

### Metodi Magici in BaseEntity

Le entità di SismaFramework utilizzano i **metodi magici** di PHP per implementare funzionalità avanzate in modo trasparente. Questi metodi intercettano operazioni su proprietà per gestire lazy loading, tracking delle modifiche e validazione.

#### `__get($name)` - Magic Getter

Intercetta l'accesso in lettura alle proprietà per abilitare il lazy loading:

```php
// Esempio di utilizzo
$post = $postModel->getEntityById(1);
$authorName = $post->author->name; // Trigger di __get('author')

// Cosa succede internamente:
// 1. Verifica che la proprietà 'author' esista nella classe finale
// 2. Chiama forceForeignKeyPropertySet('author')
// 3. Se author è un ID non convertito, carica l'entità User dal database
// 4. Ritorna l'istanza di User caricata
```

**Comportamento:**
- Valida che la proprietà esista ed appartenga alla classe finale (non a BaseEntity)
- Attiva automaticamente il lazy loading per foreign keys
- Lancia `InvalidPropertyException` se la proprietà non esiste

**Quando viene chiamato:**
- Accesso diretto: `$post->author`
- Isset check: `isset($post->author)` (chiama anche `__isset`)
- In metodi getter: `$post->getAuthor()`

#### `__set($name, $value)` - Magic Setter

Intercetta l'assegnazione alle proprietà per gestire il tracking delle modifiche:

```php
// Esempio di utilizzo
$post->author = 5; // Trigger di __set('author', 5)
$post->author = $userEntity; // Trigger di __set('author', $userEntity)
$post->author = null; // Trigger di __set('author', null)

// Cosa succede internamente:
// 1. Verifica che la proprietà esista nella classe finale
// 2. Chiama switchSettingType('author', $value)
// 3. Determina il tipo di valore (int, BaseEntity, null)
// 4. Gestisce la conversione e il tracking delle modifiche
```

**Comportamento:**
- Valida che la proprietà esista
- Delega a `switchSettingType()` per la gestione specifica del tipo
- Aggiorna il flag `$modified` quando necessario
- Rimuove l'entità da `ProcessedEntitiesCollection` se modificata
- Lancia `InvalidPropertyException` se la proprietà non esiste

**Gestione dei tipi:**
- **Intero (ID)**: Memorizza in `$foreignKeyIndexes` per lazy loading
- **BaseEntity**: Assegna direttamente l'entità
- **null**: Rimuove la relazione
- **Altri tipi**: Assegna direttamente e traccia le modifiche

#### `__isset($name)` - Magic Isset

Intercetta i controlli di esistenza delle proprietà:

```php
// Esempio di utilizzo
if (isset($post->author)) {
    echo $post->author->name;
}

// Cosa succede internamente:
// 1. Verifica che la proprietà esista nella classe finale
// 2. Attiva il lazy loading se necessario
// 3. Ritorna true/false in base allo stato della proprietà
```

**Comportamento:**
- Valida che la proprietà esista
- Attiva il lazy loading prima di verificare isset()
- Permette controlli consistenti anche con lazy loading

**Casi d'uso:**
- Verificare se una relazione è impostata prima di accedervi
- Controlli condizionali in cicli e template
- Validazione dell'esistenza di dati

#### Tracking delle Modifiche

Il sistema di tracking monitora tutte le modifiche alle proprietà per determinare se l'entità deve essere salvata:

```php
$post = $postModel->getEntityById(1);
echo $post->modified; // false - appena caricata

$post->title = "Nuovo Titolo";
echo $post->modified; // true - proprietà modificata

$post->author = 5;
echo $post->modified; // true - foreign key modificata
```

**Quando una proprietà è considerata modificata:**

1. **Proprietà builtin/enum**: Valore diverso dal precedente
2. **Foreign key (int)**: ID diverso dal precedente
3. **Foreign key (entity)**: Entità diversa dalla precedente
4. **CustomDateTime**: Date diverse (usa `equals()`)
5. **null**: Proprietà precedentemente valorizzata

**Integrazione con DataMapper:**

Il tracking si integra con `ProcessedEntitiesCollection` per evitare duplicazioni nel salvataggio:

```php
// Prima modifica: entità rimossa dalla collection
$post->title = "Titolo A";
// $processedEntitiesCollection->remove($post) chiamato automaticamente

// Il DataMapper può ora salvare l'entità anche se già processata
$dataMapper->save($post);
```

#### Esempio Completo

```php
class EntityMagicMethodsDemo
{
    public function demonstrateMagicMethods(): void
    {
        $postModel = new PostModel($this->dataMapper);

        // Carica post (lazy loading pronto)
        $post = $postModel->getEntityById(1);

        // __isset: verifica esistenza autore
        if (isset($post->author)) {
            // __get: attiva lazy loading e carica User
            echo "Autore: " . $post->author->name;
        }

        // __set: assegna nuovo autore (tracking attivato)
        $post->author = 5;
        echo $post->modified; // true

        // __set: assegna entità completa
        $newAuthor = new User();
        $newAuthor->id = 5;
        $newAuthor->name = "Mario Rossi";
        $post->author = $newAuthor; // Nessuna query, entità già in memoria

        // __set: rimuove relazione
        $post->author = null;

        // Salva le modifiche
        $this->dataMapper->save($post);
    }

    public function demonstratePerformanceImpact(): void
    {
        $posts = $this->postModel->getEntityCollection();

        // ❌ INEFFICIENTE: __get chiamato in loop, N+1 queries
        foreach ($posts as $post) {
            if (isset($post->author)) { // __isset + __get
                echo $post->author->name; // Lazy loading per ogni post
            }
        }

        // ✅ EFFICIENTE: Batch loading
        $this->batchLoadAuthors($posts);
        foreach ($posts as $post) {
            if (isset($post->author)) {
                echo $post->author->name; // Nessuna query, già caricato
            }
        }
    }
}
```

### Metodi Magici dei Model (Dynamic Query Methods)

I `DependentModel` e `SelfReferencedModel` di SismaFramework implementano il metodo magico `__call()` per generare **dinamicamente** metodi di query basati sulle relazioni tra entità. Questi metodi **non esistono fisicamente** nel codice ma vengono creati al volo interpretando il nome del metodo chiamato.

#### Come Funziona il Parsing

Il metodo `__call()` intercetta chiamate a metodi inesistenti e li interpreta seguendo questa convenzione di naming:

```
{azione}By{Proprietà}[And{AltraProprietà}]*
```

**Azioni supportate:**
- `get` → Ritorna `SismaCollection`
- `count` → Ritorna `int`
- `delete` → Ritorna `bool`

**Esempi di metodi generati automaticamente:**

```php
// Esempio di entità Post con foreign keys
class Post extends DependentEntity
{
    public int $id;
    public string $title;
    public User $author;        // Foreign key
    public Category $category;  // Foreign key
    public Status $status;      // Enum
}

class PostModel extends DependentModel
{
    // NESSUN METODO DEFINITO ESPLICITAMENTE!
    // Tutti i metodi sotto vengono generati al volo da __call()
}

// Utilizzo:
$postModel = new PostModel($dataMapper);
$user = $userModel->getEntityById(5);
$category = $categoryModel->getEntityById(3);

// 1. Query per singola foreign key
$posts = $postModel->getByAuthor($user);
// SELECT * FROM post WHERE author_id = 5

// 2. Count per singola foreign key
$count = $postModel->countByAuthor($user);
// SELECT COUNT(*) FROM post WHERE author_id = 5

// 3. Delete per singola foreign key
$deleted = $postModel->deleteByAuthor($user);
// DELETE FROM post WHERE author_id = 5

// 4. Query per multiple foreign keys (AND)
$posts = $postModel->getByAuthorAndCategory($user, $category);
// SELECT * FROM post WHERE author_id = 5 AND category_id = 3

// 5. Count per multiple foreign keys
$count = $postModel->countByAuthorAndCategory($user, $category);
// SELECT COUNT(*) FROM post WHERE author_id = 5 AND category_id = 3

// 6. Delete per multiple foreign keys
$deleted = $postModel->deleteByAuthorAndCategory($user, $category);
// DELETE FROM post WHERE author_id = 5 AND category_id = 3

// 7. Query con foreign key null
$orphanedPosts = $postModel->getByCategory(null);
// SELECT * FROM post WHERE category_id IS NULL
```

#### Anatomia del Parsing

Il metodo `__call()` esegue questi step:

1. **Split del nome**: `getByAuthorAndCategory` → `['get', 'AuthorAndCategory']`
2. **Estrae azione**: `get` → determina l'operazione (get/count/delete)
3. **Estrae entità**: `AuthorAndCategory` → `['Author', 'Category']`
4. **Valida parametri**: Verifica che gli argomenti siano istanze delle entità corrette
5. **Converte in property names**: `Author` → `author`, `Category` → `category`
6. **Costruisce query**: Crea condizioni WHERE per ogni foreign key
7. **Esegue query**: Delega al DataMapper

#### Signature dei Metodi Generati

```php
// Pattern generale:
public function {azione}By{Proprietà}[And{AltraProprietà}]*(
    BaseEntity|null ${proprietà},
    [BaseEntity|null ${altraProprietà},]*
    ?string $searchKey = null,
    ?array $order = null,      // Solo per get*
    ?int $offset = null,        // Solo per get*
    ?int $limit = null          // Solo per get*
): SismaCollection|int|bool;

// Esempi concreti:
public function getByAuthor(
    User|null $author,
    ?string $searchKey = null,
    ?array $order = null,
    ?int $offset = null,
    ?int $limit = null
): SismaCollection;

public function countByAuthorAndCategory(
    User|null $author,
    Category|null $category,
    ?string $searchKey = null
): int;

public function deleteByStatus(
    Status|null $status,
    ?string $searchKey = null
): bool;
```

#### Validazione degli Argomenti

Il sistema valida automaticamente che gli argomenti passati corrispondano al tipo delle foreign keys:

```php
// ✅ VALIDO
$user = new User();
$posts = $postModel->getByAuthor($user);

// ✅ VALIDO (null per IS NULL)
$posts = $postModel->getByAuthor(null);

// ❌ INVALIDO - TypeError
$category = new Category();
$posts = $postModel->getByAuthor($category); // Expects User, got Category
// Lancia InvalidArgumentException
```

#### Combinazione con searchKey

Tutti i metodi magici supportano il parametro opzionale `$searchKey` per filtrare ulteriormente:

```php
class PostModel extends DependentModel
{
    protected function appendSearchCondition(
        Query &$query,
        string $searchKey,
        array &$bindValues,
        array &$bindTypes
    ): void {
        // Ricerca nel titolo
        $query->appendCondition('title', ComparisonOperator::like, Placeholder::placeholder);
        $bindValues[] = "%{$searchKey}%";
        $bindTypes[] = DataType::typeString;
    }
}

// Utilizzo:
$posts = $postModel->getByAuthor($user, 'framework');
// SELECT * FROM post WHERE author_id = 5 AND title LIKE '%framework%'

$count = $postModel->countByCategory($category, 'tutorial');
// SELECT COUNT(*) FROM post WHERE category_id = 3 AND title LIKE '%tutorial%'
```

#### Esempi Pratici

```php
class RelationalQueryService
{
    public function getUserContent(User $user): array
    {
        $postModel = new PostModel($this->dataMapper);
        $commentModel = new CommentModel($this->dataMapper);

        // Metodi magici generati dinamicamente!
        $userPosts = $postModel->getByAuthor($user);
        $userComments = $commentModel->getByAuthor($user);

        $stats = [
            'posts_count' => $postModel->countByAuthor($user),
            'comments_count' => $commentModel->countByAuthor($user),
            'posts' => $userPosts,
            'comments' => $userComments
        ];

        return $stats;
    }

    public function getPostsByCategory(Category $category): SismaCollection
    {
        $postModel = new PostModel($this->dataMapper);

        // Metodo magico: getByCategory()
        return $postModel->getByCategory($category);
    }

    public function getCommentsByPost(Post $post): SismaCollection
    {
        $commentModel = new CommentModel($this->dataMapper);

        // Metodo magico: getByPost()
        return $commentModel->getByPost($post);
    }

    public function getFeaturedPostsByAuthor(User $author): SismaCollection
    {
        $postModel = new PostModel($this->dataMapper);
        $featuredStatus = Status::Featured;

        // Metodo magico con AND: getByAuthorAndStatus()
        return $postModel->getByAuthorAndStatus($author, $featuredStatus);
    }

    public function bulkDeleteUserContent(User $user): bool
    {
        $postModel = new PostModel($this->dataMapper);
        $commentModel = new CommentModel($this->dataMapper);

        $this->dataMapper->beginTransaction();

        try {
            // Metodi magici: deleteByAuthor()
            $postModel->deleteByAuthor($user);
            $commentModel->deleteByAuthor($user);

            $this->dataMapper->commit();
            return true;

        } catch (\Exception $e) {
            $this->dataMapper->rollback();
            throw $e;
        }
    }

    public function getOrphanedComments(): SismaCollection
    {
        $commentModel = new CommentModel($this->dataMapper);

        // Metodo magico con null: getByPost(null)
        // Trova commenti dove post_id IS NULL
        return $commentModel->getByPost(null);
    }
}
```

#### Vantaggi dei Metodi Magici

✅ **Zero boilerplate**: Nessun metodo da scrivere manualmente
✅ **Type-safe**: Validazione automatica dei tipi
✅ **Consistenza**: Naming convention uniforme
✅ **Flessibilità**: Supporta combinazioni multiple con AND
✅ **Null-safe**: Gestione automatica di IS NULL

#### Limitazioni

⚠️ **Nessun IDE autocomplete**: I metodi non esistono fisicamente
⚠️ **Solo AND logic**: Non supporta OR tra foreign keys
⚠️ **Convention rigida**: Naming deve seguire lo schema esatto
⚠️ **Debugging**: Stack trace meno chiaro per errori nei metodi magici

#### Best Practices

1. **Documenta i metodi disponibili** nel PHPDoc del Model:
```php
/**
 * @method SismaCollection getByAuthor(User $author, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null)
 * @method int countByAuthor(User $author, ?string $searchKey = null)
 * @method bool deleteByAuthor(User $author, ?string $searchKey = null)
 * @method SismaCollection getByCategory(Category $category, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null)
 */
class PostModel extends DependentModel
{
    // ...
}
```

2. **Usa nomi di proprietà chiari** nelle entità
3. **Testa i metodi magici** con unit tests
4. **Considera metodi espliciti** per logiche complesse
```

### Lazy Loading: Come Funziona Internamente

Il lazy loading in SismaFramework è implementato attraverso i **metodi magici** di PHP (`__get`, `__set`, `__isset`) nella classe `BaseEntity`. Questo meccanismo consente di caricare le relazioni solo quando vengono effettivamente accessate, migliorando significativamente le performance.

#### Meccanismo Interno

Quando un'entità viene caricata dal database, le **foreign key** vengono inizialmente memorizzate come **semplici ID** nell'array `$foreignKeyIndexes`, senza caricare le entità correlate. Solo quando si accede alla proprietà, il metodo magico `__get()` intercetta la richiesta e:

1. **Verifica** se la proprietà è una foreign key non ancora convertita
2. **Carica** l'entità correlata dal database usando il DataMapper
3. **Converte** l'ID in un'istanza dell'entità
4. **Rimuove** l'ID dall'array `$foreignKeyIndexes`
5. **Ritorna** l'entità caricata

Questo processo è completamente trasparente per il developer.

#### Stati di una Foreign Key

Una foreign key può trovarsi in tre stati:

```php
// Stato 1: ID non convertito (lazy loading pronto)
$post->authorId = 5;
// Internamente: $foreignKeyIndexes['author'] = 5

// Stato 2: Entità caricata
$author = $post->author; // Trigger del lazy loading
// Internamente: $author è ora un'istanza di User, foreignKeyIndexes['author'] rimosso

// Stato 3: Cache hit
$post->authorId = 5; // Stesso ID già in cache
// L'entità viene recuperata dalla cache invece che dal database
```

#### Il Ruolo del Metodo `switchSettingType()`

Il metodo `switchSettingType()` nella classe `BaseEntity` gestisce tre scenari quando si imposta una foreign key:

**1. Assegnazione di un ID intero:**
```php
$post->author = 5;

// Internamente:
// - Se l'entità User con ID=5 è già in cache, la recupera immediatamente
// - Altrimenti, memorizza l'ID in $foreignKeyIndexes['author'] = 5
// - La proprietà $author viene unset (lazy loading attivato)
// - Al primo accesso, __get() caricherà l'entità dal database
```

**2. Assegnazione di un'entità:**
```php
$author = new User();
$author->id = 5;
$post->author = $author;

// Internamente:
// - L'entità viene assegnata direttamente alla proprietà
// - L'ID viene rimosso da $foreignKeyIndexes se presente
// - Il sistema di tracking 'modified' viene aggiornato
```

**3. Assegnazione null:**
```php
$post->author = null;

// Internamente:
// - La proprietà viene impostata a null
// - L'ID viene rimosso da $foreignKeyIndexes
// - Il sistema di tracking 'modified' viene aggiornato
```

#### Vantaggi del Lazy Loading

✅ **Performance**: Carica solo i dati effettivamente necessari
✅ **Semplicità**: API trasparente, nessun metodo speciale da chiamare
✅ **Memoria**: Riduce il footprint in memoria per relazioni non utilizzate
✅ **Caching**: Integrazione automatica con il sistema di cache delle entità

#### Problemi Comuni e Soluzioni

**⚠️ Problema N+1:**
```php
// ❌ INEFFICIENTE: Query per ogni post
$posts = $postModel->getEntityCollection();
foreach ($posts as $post) {
    echo $post->author->name; // Query separata per ogni autore!
}
```

**✅ Soluzione - Batch Loading:**
```php
class LazyLoadingExample
{
    public function demonstrateLazyLoading(): void
    {
        $postModel = new PostModel($this->dataMapper);

        // Carica solo il post
        $post = $postModel->getEntityById(1);

        // Lazy loading automatico dell'autore
        $authorName = $post->author->name; // Query automatica

        // Lazy loading della collezione commenti
        $comments = $post->commentCollection; // Query automatica
    }

    public function optimizedBatchLoading(): void
    {
        $postModel = new PostModel($this->dataMapper);
        $userModel = new UserModel($this->dataMapper);

        // Carica tutti i post
        $posts = $postModel->getEntityCollection();

        // Estrae IDs degli autori
        $authorIds = [];
        foreach ($posts as $post) {
            if (isset($post->authorId)) {
                $authorIds[] = $post->authorId;
            }
        }
        $authorIds = array_unique($authorIds);

        // Carica tutti gli autori in una query (batch loading)
        $authors = $userModel->getEntityCollectionByIds($authorIds);

        // Crea mappa per lookup veloce O(1)
        $authorMap = [];
        foreach ($authors as $author) {
            $authorMap[$author->id] = $author;
        }

        // Usa i dati pre-caricati
        foreach ($posts as $post) {
            if (isset($post->authorId) && isset($authorMap[$post->authorId])) {
                $author = $authorMap[$post->authorId];
                echo $author->name; // Nessuna query aggiuntiva
            }
        }
    }
}
```

#### Integrazione con Cache

Il lazy loading si integra automaticamente con il sistema di cache delle entità:

```php
use SismaFramework\Orm\HelperClasses\Cache;

// Prima richiesta: carica dal database e mette in cache
$post->author; // SELECT * FROM user WHERE id = 5

// Successive richieste: recupera dalla cache
$post->author; // Nessuna query - cache hit!

// Verifica presenza in cache
if (Cache::checkEntityPresenceInCache(User::class, 5)) {
    $user = Cache::getEntityById(User::class, 5);
}
```

#### Best Practices

1. **Usa batch loading** quando itteri su collezioni
2. **Profila le query** per identificare problemi N+1
3. **Considera eager loading** per relazioni sempre necessarie
4. **Monitora la cache** per ottimizzare il hit rate
5. **Documenta le dipendenze** delle entità per il team
```

---

## Performance e Ottimizzazioni

### Caching Intelligente

```php
class CachedPostService
{
    private PostModel $postModel;
    private CacheInterface $cache;

    public function getPopularPosts(int $limit = 10): SismaCollection
    {
        $cacheKey = "popular_posts_{$limit}";

        $cachedPosts = $this->cache->get($cacheKey);
        if ($cachedPosts !== null) {
            return $cachedPosts;
        }

        $query = $this->postModel->initQuery();
        $query->setOrderBy(['view_count' => 'DESC'])
              ->setLimit($limit);
        $query->close();

        $posts = $this->dataMapper->find('Post', $query);

        $this->cache->set($cacheKey, $posts, 3600); // Cache 1 ora
        return $posts;
    }

    public function invalidatePostCache(Post $post): void
    {
        // Invalida cache correlate
        $this->cache->delete("post_{$post->getId()}");
        $this->cache->delete("popular_posts_*");
        $this->cache->delete("author_posts_{$post->getAuthorId()}");
    }
}
```

### Batch Operations

```php
class BatchOperationService
{
    public function bulkUpdateViewCounts(array $postIds): bool
    {
        // ❌ INEFFICIENTE: Query singole
        foreach ($postIds as $id) {
            $post = $this->postModel->getEntityById($id);
            $post->incrementViewCount();
            $this->dataMapper->save($post);
        }

        // ✅ EFFICIENTE: Query batch
        $query = $this->postModel->initQuery();
        $query->setWhere()
              ->appendCondition('id', ComparisonOperator::in, Placeholder::placeholder);

        // Per operazioni batch personalizzate, usa deleteBatch o SQL raw tramite l'adapter
        $query->close();
        $bindValues = [$postIds];
        $bindTypes = [DataType::typeArrayInteger];

        // Esempio: usa l'adapter direttamente per SQL personalizzato
        $sql = "UPDATE post SET view_count = view_count + 1 WHERE id IN (?)";
        return $this->dataMapper->getAdapter()->execute($sql, $bindValues, $bindTypes);
    }

    public function createMultiplePosts(array $postsData): SismaCollection
    {
        $posts = new SismaCollection('Post');

        $this->dataMapper->beginTransaction();

        try {
            foreach ($postsData as $data) {
                $post = new Post();
                $post->setTitle($data['title']);
                $post->setContent($data['content']);
                $post->setAuthorId($data['author_id']);

                $this->dataMapper->save($post);
                $posts->append($post);
            }

            $this->dataMapper->commit();
            return $posts;

        } catch (\Exception $e) {
            $this->dataMapper->rollback();
            throw $e;
        }
    }
}
```

### Query Count e Aggregazioni

```php
class StatisticsService
{
    public function getContentStatistics(): array
    {
        $postModel = new PostModel($this->dataMapper);
        $userModel = new UserModel($this->dataMapper);
        $commentModel = new CommentModel($this->dataMapper);

        return [
            'total_posts' => $postModel->countEntityCollection(),
            'published_posts' => $this->countPublishedPosts(),
            'total_users' => $userModel->countEntityCollection(),
            'total_comments' => $commentModel->countEntityCollection(),
            'posts_per_author' => $this->getPostsPerAuthor()
        ];
    }

    private function countPublishedPosts(): int
    {
        $query = $this->postModel->initQuery();
        $query->setWhere()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);

        $bindValues = ['published'];
        $bindTypes = [DataType::typeString];

        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }

    private function getPostsPerAuthor(): SismaCollection
    {
        // Per aggregazioni, crea un'entità specifica per i risultati
        $query = $this->postModel->initQuery();
        $query->setColumns(['author_id', 'COUNT(*) as post_count'])
              ->setGroupBy(['author_id'])
              ->setOrderBy(['post_count' => 'DESC']);

        $query->close();

        // Restituisce sempre una SismaCollection di entità tipizzate
        return $this->dataMapper->find(AuthorPostStats::class, $query);
    }
}
```

---

## Architetture Enterprise

### Repository Pattern

```php
interface PostRepositoryInterface
{
    public function findById(int $id): ?Post;
    public function findByAuthor(User $author): SismaCollection;
    public function findPublished(): SismaCollection;
    public function findByDateRange(\DateTime $from, \DateTime $to): SismaCollection;
    public function save(Post $post): bool;
    public function delete(Post $post): bool;
}

class PostRepository implements PostRepositoryInterface
{
    private PostModel $model;
    private DataMapper $dataMapper;

    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
        $this->model = new PostModel($dataMapper);
    }

    public function findById(int $id): ?Post
    {
        return $this->model->getEntityById($id);
    }

    public function findByAuthor(User $author): SismaCollection
    {
        return $this->model->getByAuthor($author);
    }

    public function findPublished(): SismaCollection
    {
        $query = $this->model->initQuery();
        $query->setWhere()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
              ->appendAnd()
              ->appendCondition('published_at', ComparisonOperator::lessThanOrEqual, Placeholder::placeholder);

        $bindValues = ['published', new \DateTime()];
        $bindTypes = [DataType::typeString, DataType::typeDateTime];

        $query->setOrderBy(['published_at' => 'DESC']);
        $query->close();

        return $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);
    }

    public function findByDateRange(\DateTime $from, \DateTime $to): SismaCollection
    {
        $query = $this->model->initQuery();
        $query->setWhere()
              ->appendCondition('published_at', ComparisonOperator::greaterThanOrEqual, Placeholder::placeholder)
              ->appendAnd()
              ->appendCondition('published_at', ComparisonOperator::lessThanOrEqual, Placeholder::placeholder);

        $bindValues = [$from, $to];
        $bindTypes = [DataType::typeDateTime, DataType::typeDateTime];

        $query->close();
        return $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);
    }

    public function save(Post $post): bool
    {
        return $this->dataMapper->save($post);
    }

    public function delete(Post $post): bool
    {
        return $this->dataMapper->delete($post);
    }
}
```

### Service Layer con Domain Logic

```php
class BlogService
{
    private PostRepository $postRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;

    public function publishPost(int $postId, int $authorId): bool
    {
        $post = $this->postRepository->findById($postId);
        $author = $this->userRepository->findById($authorId);

        if (!$post || !$author) {
            throw new \InvalidArgumentException('Post or Author not found');
        }

        if ($post->getAuthorId() !== $authorId) {
            throw new \UnauthorizedAccessException('Author mismatch');
        }

        // Domain logic
        $post->publish();

        $success = $this->postRepository->save($post);

        if ($success) {
            // Side effects
            $this->notificationService->notifySubscribers($post);
        }

        return $success;
    }

    public function getAuthorStatistics(User $author): array
    {
        $posts = $this->postRepository->findByAuthor($author);

        $stats = [
            'total_posts' => $posts->count(),
            'published_posts' => 0,
            'draft_posts' => 0,
            'total_views' => 0
        ];

        foreach ($posts as $post) {
            if ($post->isPublished()) {
                $stats['published_posts']++;
            } else {
                $stats['draft_posts']++;
            }
            $stats['total_views'] += $post->getViewCount();
        }

        return $stats;
    }
}
```

---

## Transazioni e Consistenza

### Gestione Transazioni

```php
class TransactionalService
{
    private DataMapper $dataMapper;

    public function transferPostOwnership(Post $post, User $newOwner): bool
    {
        $this->dataMapper->beginTransaction();

        try {
            // Aggiorna il post
            $post->setAuthorId($newOwner->getId());
            $this->dataMapper->save($post);

            // Log del trasferimento
            $transferLog = new OwnershipTransfer();
            $transferLog->setPostId($post->getId());
            $transferLog->setOldOwnerId($post->getAuthorId());
            $transferLog->setNewOwnerId($newOwner->getId());
            $transferLog->setTransferDate(new \DateTime());

            $this->dataMapper->save($transferLog);

            // Aggiorna statistiche
            $this->updateAuthorStatistics($post->getAuthorId(), -1);
            $this->updateAuthorStatistics($newOwner->getId(), +1);

            $this->dataMapper->commit();
            return true;

        } catch (\Exception $e) {
            $this->dataMapper->rollback();
            error_log("Transfer failed: " . $e->getMessage());
            return false;
        }
    }

    private function updateAuthorStatistics(int $userId, int $delta): void
    {
        $userStats = $this->getUserStats($userId);
        $userStats->setPostCount($userStats->getPostCount() + $delta);
        $this->dataMapper->save($userStats);
    }
}
```

### Data Validation e Business Rules

```php
class PostValidationService
{
    public function validatePost(Post $post): array
    {
        $errors = [];

        // Validazione base
        if (empty($post->getTitle())) {
            $errors[] = 'Title is required';
        }

        if (strlen($post->getTitle()) > 255) {
            $errors[] = 'Title too long';
        }

        // Validazione business rules
        if ($post->isPublished() && empty($post->getContent())) {
            $errors[] = 'Content required for published posts';
        }

        // Validazione relazioni
        if (!$this->isValidAuthor($post->getAuthorId())) {
            $errors[] = 'Invalid author';
        }

        return $errors;
    }

    private function isValidAuthor(int $authorId): bool
    {
        $userModel = new UserModel($this->dataMapper);
        $author = $userModel->getEntityById($authorId);

        return $author !== null && $author->isActive();
    }
}
```

---

## Considerazioni sui JOIN

### Limitazioni e Alternative

L'ORM di SismaFramework **non supporta JOIN espliciti** nel query builder. Questo è una scelta architetturale del pattern Data Mapper che ha sia vantaggi che svantaggi:

**✅ Vantaggi:**
- **Semplicità**: API più semplice e meno prone ad errori
- **Purezza del Domain**: Le entità rappresentano sempre record singoli
- **Lazy Loading**: Relazioni caricate solo quando necessarie
- **Cache Friendly**: Entità cachiate individualmente

**⚠️ Limitazioni:**
- **Performance**: Possibile problema N+1
- **Query Complesse**: Aggregazioni multi-tabella più difficili
- **Reporting**: Query di reporting richiedono SQL raw

**💡 Alternative e Workaround:**

```php
class ReportingService
{
    // 1. SQL Raw per query complesse - usa l'adapter direttamente
    public function getPostsWithAuthorNames(): array
    {
        $sql = "
            SELECT p.*, u.name as author_name
            FROM post p
            INNER JOIN user u ON p.author_id = u.id
            WHERE p.status = 'published'
            ORDER BY p.published_at DESC
        ";

        $adapter = BaseAdapter::getDefault();
        $result = $adapter->select($sql);
        return $result ? $result->fetchAll() : [];
    }

    // 2. Caricamento batch per evitare N+1
    public function getPostsWithAuthorsOptimized(): array
    {
        $postModel = new PostModel($this->dataMapper);
        $userModel = new UserModel($this->dataMapper);

        // Carica post
        $posts = $postModel->getEntityCollection();

        // Estrae author IDs
        $authorIds = array_unique(
            array_map(fn($post) => $post->getAuthorId(), $posts->toArray())
        );

        // Carica autori in batch
        $authors = $userModel->getEntityCollectionByIds($authorIds);
        $authorMap = $authors->indexBy('id');

        // Combina risultati
        $result = [];
        foreach ($posts as $post) {
            $result[] = [
                'post' => $post,
                'author' => $authorMap[$post->getAuthorId()]
            ];
        }

        return $result;
    }

    // 3. View materializzate per reporting
    public function getPostStatistics(): array
    {
        // Usa una view pre-calcolata tramite l'adapter
        $sql = "SELECT * FROM post_statistics_view";
        $adapter = BaseAdapter::getDefault();
        $result = $adapter->select($sql);
        return $result ? $result->fetchAll() : [];
    }
}
```

### Quando Usare SQL Raw

È appropriato usare SQL raw quando:
- Query di **reporting complesse** con aggregazioni
- **Performance critiche** dove JOIN è necessario
- **Bulk operations** su grandi dataset
- **Stored procedures** esistenti

---

[Indice](index.md) | Precedente: [Configuration Reference](configuration-reference.md) | Successivo: [Performance Guide](performance.md)
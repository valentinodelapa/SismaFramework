# Performance Guide

Questa guida fornisce best practices e tecniche avanzate per ottimizzare le performance delle applicazioni basate su SismaFramework, con particolare attenzione al pattern Data Mapper implementato nell'ORM.

## Panoramica

SismaFramework è progettato per performance ottimali out-of-the-box, ma una configurazione e un utilizzo appropriati possono migliorare significativamente le performance dell'applicazione.

### Metriche di Performance Target

- **Response Time**: < 200ms per pagine semplici, < 500ms per pagine complesse
- **Throughput**: > 1000 req/sec su hardware standard
- **Memory Usage**: < 50MB per request in media
- **Database Queries**: < 10 query per page load

---

## Database Performance

### Schema Design e Indici

```sql
-- Indici essenziali per performance
CREATE INDEX idx_user_email ON user(email);
CREATE INDEX idx_post_author_status ON post(author_id, status);
CREATE INDEX idx_post_published_date ON post(published_at DESC);

-- Indici compositi per query complesse
CREATE INDEX idx_post_search ON post(status, category_id, published_at);

-- Indici FULLTEXT per ricerca testuale
CREATE FULLTEXT INDEX idx_post_content ON post(title, content);

-- Indici per relazioni frequenti
CREATE INDEX idx_comment_post ON comment(post_id);
CREATE INDEX idx_comment_author ON comment(author_id);
```

### Ottimizzazione Query ORM

#### Query Efficaci vs Inefficaci

```php
class OptimizedPostService
{
    private PostModel $postModel;
    private UserModel $userModel;

    // ✅ GOOD: Query mirate con condizioni specifiche
    public function getPublishedPosts(): SismaCollection
    {
        $query = $this->postModel->initQuery();
        $query->setWhere()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
              ->appendAnd()
              ->appendCondition('published_at', ComparisonOperator::lessThanOrEqual, Placeholder::placeholder);

        $bindValues = ['published', new \DateTime()];
        $bindTypes = [DataType::typeString, DataType::typeDateTime];

        $query->setOrderBy(['published_at' => 'DESC'])
              ->setLimit(20);
        $query->close();

        return $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);
    }

    // ❌ BAD: Carica tutto e filtra in PHP
    public function getPublishedPostsBad(): SismaCollection
    {
        $allPosts = $this->postModel->getEntityCollection();
        $publishedPosts = new SismaCollection('Post');

        foreach ($allPosts as $post) {
            if ($post->getStatus() === 'published' && $post->getPublishedAt() <= new \DateTime()) {
                $publishedPosts->append($post);
            }
        }

        return $publishedPosts;
    }

    // ✅ GOOD: Usa COUNT invece di caricare entità
    public function getPostCount(): int
    {
        return $this->postModel->countEntityCollection();
    }

    // ❌ BAD: Carica entità solo per contarle
    public function getPostCountBad(): int
    {
        $posts = $this->postModel->getEntityCollection();
        return $posts->count();
    }
}
```

#### Gestione del Problema N+1

```php
class N1ProblemSolution
{
    // ❌ PROBLEMA N+1: Una query per ogni post
    public function displayPostsWithAuthorsN1(): void
    {
        $posts = $this->postModel->getEntityCollection();

        foreach ($posts as $post) {
            echo $post->getTitle() . ' by ' . $post->getAuthor()->getName(); // Query per ogni post!
        }
    }

    // ✅ SOLUZIONE 1: Batch loading con lookup map
    public function displayPostsWithAuthorsOptimized(): void
    {
        // 1. Carica tutti i post
        $posts = $this->postModel->getEntityCollection();

        // 2. Estrae author IDs univoci
        $authorIds = array_unique(
            array_map(fn($post) => $post->getAuthorId(), $posts->toArray())
        );

        // 3. Carica tutti gli autori in una sola query
        $query = $this->userModel->initQuery();
        $query->setWhere()
              ->appendCondition('id', ComparisonOperator::in, Placeholder::placeholder);
        $query->close();

        $authors = $this->dataMapper->find('User', $query, [$authorIds], [DataType::typeArrayInteger]);

        // 4. Crea mappa per lookup O(1)
        $authorMap = [];
        foreach ($authors as $author) {
            $authorMap[$author->getId()] = $author;
        }

        // 5. Usa i dati pre-caricati
        foreach ($posts as $post) {
            $author = $authorMap[$post->getAuthorId()];
            echo $post->getTitle() . ' by ' . $author->getName(); // Nessuna query aggiuntiva
        }
    }

    // ✅ SOLUZIONE 2: SQL Raw per performance critiche
    public function displayPostsWithAuthorsSQLRaw(): array
    {
        $sql = "
            SELECT
                p.id, p.title, p.content, p.published_at,
                u.id as author_id, u.name as author_name, u.email as author_email
            FROM post p
            INNER JOIN user u ON p.author_id = u.id
            WHERE p.status = 'published'
            ORDER BY p.published_at DESC
            LIMIT 20
        ";

        return $this->dataMapper->findRaw($sql);
    }
}
```

### Batch Operations

```php
class BatchOperationService
{
    // ✅ EFFICIENTE: Operazioni batch
    public function bulkUpdateViewCounts(array $postIds): bool
    {
        $sql = "UPDATE post SET view_count = view_count + 1 WHERE id IN (?)";
        return $this->dataMapper->executeBatch($sql, [$postIds], [DataType::typeArrayInteger]);
    }

    // ✅ EFFICIENTE: Insert batch con transazioni
    public function createMultiplePosts(array $postsData): bool
    {
        $this->dataMapper->beginTransaction();

        try {
            foreach ($postsData as $data) {
                $post = new Post();
                $post->setTitle($data['title']);
                $post->setContent($data['content']);
                $post->setAuthorId($data['author_id']);
                $post->setStatus('draft');

                $this->dataMapper->save($post);
            }

            $this->dataMapper->commit();
            return true;

        } catch (\Exception $e) {
            $this->dataMapper->rollback();
            return false;
        }
    }

    // ✅ EFFICIENTE: Delete batch per cleanup
    public function deleteOldDrafts(\DateTime $cutoffDate): int
    {
        $query = $this->postModel->initQuery();
        $query->setWhere()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
              ->appendAnd()
              ->appendCondition('created_at', ComparisonOperator::lessThan, Placeholder::placeholder);

        $bindValues = ['draft', $cutoffDate];
        $bindTypes = [DataType::typeString, DataType::typeDateTime];

        $query->close();
        return $this->dataMapper->deleteBatch($query, $bindValues, $bindTypes);
    }
}
```

---

## Caching Strategies

### ORM Cache Nativo

```php
class ORMCacheOptimization
{
    public function demonstrateORMCache(): void
    {
        // Il cache ORM è automatico per getEntityById()
        $post1 = $this->postModel->getEntityById(1); // Query al database
        $post2 = $this->postModel->getEntityById(1); // Cache hit - nessuna query

        // Controllo manuale del cache
        if (Cache::checkEntityPresenceInCache('Post', 1)) {
            $cachedPost = Cache::getEntityById('Post', 1);
        }

        // Disabilita cache temporaneamente per operazioni specifiche
        $this->dataMapper->setOrmCacheStatus(false);
        $freshPost = $this->postModel->getEntityById(1); // Sempre una query
        $this->dataMapper->setOrmCacheStatus(true);
    }

    public function cacheWarmup(array $postIds): void
    {
        // Pre-carica entità nel cache
        foreach ($postIds as $id) {
            $this->postModel->getEntityById($id);
        }
    }
}
```

### Application Level Caching

```php
class ApplicationCacheService
{
    private CacheInterface $cache;
    private int $defaultTtl = 3600;

    public function getCachedPopularPosts(int $limit = 10): SismaCollection
    {
        $cacheKey = "popular_posts_{$limit}";

        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = $this->postModel->initQuery();
        $query->setWhere()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);

        $bindValues = ['published'];
        $bindTypes = [DataType::typeString];

        $query->setOrderBy(['view_count' => 'DESC'])
              ->setLimit($limit);
        $query->close();

        $posts = $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);

        $this->cache->set($cacheKey, $posts, $this->defaultTtl);
        return $posts;
    }

    public function getCachedUserStats(User $user): array
    {
        $cacheKey = "user_stats_{$user->getId()}";

        $stats = $this->cache->get($cacheKey);
        if ($stats !== null) {
            return $stats;
        }

        $postModel = new PostModel($this->dataMapper);
        $commentModel = new CommentModel($this->dataMapper);

        $stats = [
            'posts_count' => $postModel->countByAuthor($user),
            'comments_count' => $commentModel->countByAuthor($user),
            'last_activity' => $this->getLastActivity($user)
        ];

        $this->cache->set($cacheKey, $stats, 1800); // 30 minuti
        return $stats;
    }

    public function invalidateUserCache(User $user): void
    {
        $userId = $user->getId();

        // Invalida cache correlate
        $this->cache->delete("user_stats_{$userId}");
        $this->cache->delete("user_posts_{$userId}");
        $this->cache->delete("popular_posts_*"); // Se l'utente ha post popolari
    }
}
```

### Query Result Caching

```php
class QueryResultCache
{
    public function getCachedSearchResults(string $searchTerms): SismaCollection
    {
        $cacheKey = 'search_' . md5($searchTerms);

        $cachedResults = $this->cache->get($cacheKey);
        if ($cachedResults !== null) {
            return $cachedResults;
        }

        $query = $this->postModel->initQuery();
        $query->setWhere()
              ->appendFulltextCondition(['title', 'content'], Placeholder::placeholder);

        $bindValues = [$searchTerms];
        $bindTypes = [DataType::typeString];

        $query->setLimit(50);
        $query->close();

        $results = $this->dataMapper->find('Post', $query, $bindValues, $bindTypes);

        $this->cache->set($cacheKey, $results, 1800);
        return $results;
    }

    public function getCachedAggregateData(): array
    {
        $cacheKey = 'site_statistics';

        $stats = $this->cache->get($cacheKey);
        if ($stats !== null) {
            return $stats;
        }

        $stats = [
            'total_posts' => $this->postModel->countEntityCollection(),
            'total_users' => $this->userModel->countEntityCollection(),
            'posts_today' => $this->getPostsToday(),
            'most_active_authors' => $this->getMostActiveAuthors()
        ];

        $this->cache->set($cacheKey, $stats, 3600); // 1 ora
        return $stats;
    }
}
```

---

## Memory Management

### Efficient Data Processing

```php
class MemoryEfficientService
{
    // ✅ GOOD: Process grandi dataset con paginazione
    public function processAllPosts(callable $processor): void
    {
        $offset = 0;
        $batchSize = 100;

        do {
            $query = $this->postModel->initQuery();
            $query->setOrderBy(['id' => 'ASC'])
                  ->setOffset($offset)
                  ->setLimit($batchSize);
            $query->close();

            $posts = $this->dataMapper->find('Post', $query);

            foreach ($posts as $post) {
                $processor($post);
            }

            $offset += $batchSize;

            // Libera memoria
            unset($posts);
            gc_collect_cycles();

        } while ($posts->count() === $batchSize);
    }

    // ✅ GOOD: Streaming per export di grandi volumi
    public function exportPostsToCsv(string $filename): void
    {
        $handle = fopen($filename, 'w');
        fputcsv($handle, ['ID', 'Title', 'Author', 'Published At']);

        $this->processAllPosts(function(Post $post) use ($handle) {
            fputcsv($handle, [
                $post->getId(),
                $post->getTitle(),
                $post->getAuthor()->getName(),
                $post->getPublishedAt()?->format('Y-m-d H:i:s')
            ]);
        });

        fclose($handle);
    }

    // ✅ GOOD: Elaborazione batch con controllo memoria
    public function batchUpdatePosts(array $updates): void
    {
        $memoryLimit = 50 * 1024 * 1024; // 50MB
        $batch = [];

        foreach ($updates as $update) {
            $batch[] = $update;

            if (memory_get_usage() > $memoryLimit || count($batch) >= 100) {
                $this->processBatch($batch);
                $batch = [];
                gc_collect_cycles();
            }
        }

        if (!empty($batch)) {
            $this->processBatch($batch);
        }
    }
}
```

### Memory Profiling

```php
class MemoryProfiler
{
    private array $checkpoints = [];

    public function checkpoint(string $label): void
    {
        $this->checkpoints[$label] = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'entity_cache_size' => $this->getEntityCacheSize(),
            'time' => microtime(true)
        ];
    }

    public function getReport(): array
    {
        $report = [];
        $previous = null;

        foreach ($this->checkpoints as $label => $checkpoint) {
            $report[$label] = $checkpoint;

            if ($previous) {
                $report[$label]['memory_diff'] = $checkpoint['memory_usage'] - $previous['memory_usage'];
                $report[$label]['time_diff'] = $checkpoint['time'] - $previous['time'];
            }

            $previous = $checkpoint;
        }

        return $report;
    }

    private function getEntityCacheSize(): int
    {
        // Stima approssimativa della dimensione cache entità
        return count(Cache::getAllCachedEntities()) * 1024; // Stima 1KB per entità
    }
}
```

---

## ORM-Specific Optimizations

### Smart Query Building

```php
class SmartQueryBuilder
{
    // ✅ GOOD: Query condizionali per filtri dinamici
    public function buildDynamicPostQuery(array $filters): Query
    {
        $query = $this->postModel->initQuery();
        $bindValues = [];
        $bindTypes = [];
        $hasWhere = false;

        if (isset($filters['status'])) {
            if (!$hasWhere) {
                $query->setWhere();
                $hasWhere = true;
            } else {
                $query->appendAnd();
            }

            $query->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
            $bindValues[] = $filters['status'];
            $bindTypes[] = DataType::typeString;
        }

        if (isset($filters['author_id'])) {
            if (!$hasWhere) {
                $query->setWhere();
                $hasWhere = true;
            } else {
                $query->appendAnd();
            }

            $query->appendCondition('author_id', ComparisonOperator::equal, Placeholder::placeholder);
            $bindValues[] = $filters['author_id'];
            $bindTypes[] = DataType::typeInteger;
        }

        if (isset($filters['date_from'])) {
            if (!$hasWhere) {
                $query->setWhere();
                $hasWhere = true;
            } else {
                $query->appendAnd();
            }

            $query->appendCondition('published_at', ComparisonOperator::greaterThanOrEqual, Placeholder::placeholder);
            $bindValues[] = $filters['date_from'];
            $bindTypes[] = DataType::typeDateTime;
        }

        $query->close();
        return $query;
    }

    // ✅ GOOD: Query con FULLTEXT ottimizzate
    public function buildSearchQuery(string $searchTerms, array $filters = []): Query
    {
        $query = $this->postModel->initQuery();

        // Fulltext search come condizione primaria
        $query->setWhere()
              ->appendFulltextCondition(['title', 'content'], Placeholder::placeholder);

        $bindValues = [$searchTerms];
        $bindTypes = [DataType::typeString];

        // Filtri aggiuntivi
        if (isset($filters['status'])) {
            $query->appendAnd()
                  ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
            $bindValues[] = $filters['status'];
            $bindTypes[] = DataType::typeString;
        }

        $query->setOrderBy(['published_at' => 'DESC']);
        $query->close();

        return $query;
    }
}
```

### Connection Optimization

```php
// config/database.php
return [
    'connections' => [
        'default' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'sisma_app',
            'username' => 'user',
            'password' => 'password',
            'options' => [
                PDO::ATTR_PERSISTENT => true,        // Connection pooling
                PDO::ATTR_EMULATE_PREPARES => false, // Vere prepared statements
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES'",
                PDO::ATTR_TIMEOUT => 30,
            ]
        ],

        // Read replica per query SELECT
        'read' => [
            'driver' => 'mysql',
            'host' => 'read-replica.example.com',
            'database' => 'sisma_app',
            'username' => 'read_user',
            'password' => 'read_password',
            'options' => [
                PDO::ATTR_PERSISTENT => true,
            ]
        ]
    ],

    // ORM Cache configuration
    'ormCache' => true,
    'cacheDriver' => 'redis',
    'cacheTimeout' => 3600
];
```

---

## Performance Monitoring

### ORM Performance Tracking

```php
class ORMPerformanceMonitor
{
    private array $queryLog = [];
    private int $queryCount = 0;
    private float $totalQueryTime = 0;

    public function logQuery(string $entityName, string $method, float $executionTime, int $resultCount = 0): void
    {
        $this->queryCount++;
        $this->totalQueryTime += $executionTime;

        $this->queryLog[] = [
            'entity' => $entityName,
            'method' => $method,
            'execution_time' => $executionTime,
            'result_count' => $resultCount,
            'memory_usage' => memory_get_usage(true),
            'timestamp' => microtime(true)
        ];

        // Alert su query lente
        if ($executionTime > 0.1) {
            error_log("Slow ORM query: {$entityName}::{$method} took {$executionTime}s");
        }
    }

    public function getPerformanceStats(): array
    {
        return [
            'total_queries' => $this->queryCount,
            'total_time' => $this->totalQueryTime,
            'average_time' => $this->queryCount > 0 ? $this->totalQueryTime / $this->queryCount : 0,
            'slow_queries' => $this->getSlowQueries(),
            'memory_peak' => memory_get_peak_usage(true),
            'cache_hits' => Cache::getHitCount(),
            'cache_misses' => Cache::getMissCount()
        ];
    }

    private function getSlowQueries(float $threshold = 0.05): array
    {
        return array_filter($this->queryLog, fn($query) => $query['execution_time'] > $threshold);
    }

    public function detectN1Problems(): array
    {
        $entityAccess = [];

        foreach ($this->queryLog as $query) {
            if ($query['method'] === 'getEntityById') {
                $entityAccess[$query['entity']][] = $query;
            }
        }

        $n1Problems = [];
        foreach ($entityAccess as $entity => $queries) {
            if (count($queries) > 10) { // Soglia per sospetto N+1
                $n1Problems[$entity] = [
                    'query_count' => count($queries),
                    'total_time' => array_sum(array_column($queries, 'execution_time'))
                ];
            }
        }

        return $n1Problems;
    }
}
```

### Application Performance Metrics

```php
class PerformanceMiddleware
{
    private ORMPerformanceMonitor $ormMonitor;

    public function handle(Request $request, callable $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Reset monitors
        $this->ormMonitor->reset();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'total_time' => $endTime - $startTime,
            'memory_used' => $endMemory - $startMemory,
            'memory_peak' => memory_get_peak_usage(true),
            'orm_stats' => $this->ormMonitor->getPerformanceStats(),
            'n1_problems' => $this->ormMonitor->detectN1Problems()
        ];

        // Log metriche in development
        if (Config::getInstance()->debug) {
            $response->setHeader('X-Debug-Time', $metrics['total_time']);
            $response->setHeader('X-Debug-Memory', $metrics['memory_peak']);
            $response->setHeader('X-Debug-Queries', $metrics['orm_stats']['total_queries']);
        }

        // Alert su performance problems
        if ($metrics['total_time'] > 1.0) {
            error_log("Slow request: {$request->getPath()} took {$metrics['total_time']}s");
        }

        if (!empty($metrics['n1_problems'])) {
            error_log("N+1 problem detected: " . json_encode($metrics['n1_problems']));
        }

        return $response;
    }
}
```

---

## Production Optimizations

### Environment Configuration

```php
// config/production.php
return [
    'debug' => false,
    'ormCache' => true,
    'cacheTimeout' => 7200,

    'database' => [
        'persistent_connections' => true,
        'query_cache' => true,
        'prepared_statements' => true
    ],

    'performance' => [
        'gzip_compression' => true,
        'static_asset_cache' => 31536000, // 1 anno
        'entity_cache_size' => 10000,
        'query_result_cache' => true
    ]
];
```

### Best Practices Summary

**✅ DO's:**
- **Usa gli indici appropriati** per query frequenti
- **Implementa caching** per dati letti spesso
- **Evita il problema N+1** con batch loading
- **Usa COUNT** invece di caricare entità per contare
- **Implementa paginazione** per grandi dataset
- **Monitora le performance** dell'ORM
- **Usa transazioni** per operazioni multiple

**❌ DON'Ts:**
- **Non ignorare i warning N+1**
- **Non caricare entità** solo per accedere a un campo
- **Non fare query** dentro i loop quando evitabile
- **Non disabilitare il cache ORM** senza motivo
- **Non usare SELECT \*** se servono pochi campi
- **Non dimenticare** la paginazione sui large dataset

---

## Considerazioni sull'Architettura

### Il Ruolo Cruciale del Lazy Loading

Il **lazy loading automatico** è un aspetto fondamentale che mitiga significativamente la mancanza di JOIN espliciti:

```php
class LazyLoadingAdvantages
{
    public function demonstrateLazyLoading(): void
    {
        // Carica solo il post (1 query)
        $post = $this->postModel->getEntityById(1);

        // Lazy loading automatico dell'autore (1 query aggiuntiva)
        $authorName = $post->getAuthor()->getName();

        // Lazy loading della collezione commenti (1 query aggiuntiva)
        $comments = $post->getCommentCollection();

        // Lazy loading di relazioni annidate
        foreach ($comments as $comment) {
            // Ogni autore viene caricato automaticamente quando necessario
            echo $comment->getAuthor()->getName();
        }

        // TOTALE: Tipicamente 3-4 query invece di 1 complessa con JOIN
        // Ma con semantica molto più chiara e manutenibile
    }

    public function lazyLoadingWithCaching(): void
    {
        $posts = $this->postModel->getEntityCollection();

        foreach ($posts as $post) {
            // Primo accesso: query al database
            $author1 = $post->getAuthor();

            // Accessi successivi allo stesso autore: cache hit
            $sameAuthor = $this->postModel->getEntityById($post->getId())->getAuthor();
            // ↑ Nessuna query aggiuntiva grazie al cache ORM
        }
    }
}
```

### Analisi del Design: JOIN vs Lazy Loading

**✅ Vantaggi del Design Data Mapper con Lazy Loading:**

1. **Semantica naturale**: `$post->getAuthor()` è intuitivo e type-safe
2. **Cache automatico**: Entità caricate una volta, riutilizzate ovunque
3. **Lazy by default**: Performance ottimali - dati caricati solo se usati
4. **Manutenibilità**: Cambiare lo schema DB non rompe il codice business
5. **Testabilità**: Facile mockare singole entità
6. **Type safety**: L'IDE sa esattamente che tipo restituisce ogni relazione

**✅ Lazy Loading mitiga i problemi di performance:**

```php
// Con lazy loading, questo è efficiente:
$user = $userModel->getEntityById(1);
if ($user->hasPermission('admin')) {
    $posts = $user->getPostCollection(); // Query solo se necessaria
}

// VS con JOIN forzato:
// SELECT u.*, p.* FROM user u LEFT JOIN post p ON u.id = p.author_id WHERE u.id = 1
// ↑ Spreco di memoria se i post non servono
```

**⚠️ Limitazioni (ma gestibili):**

1. **N+1 potenziale**: Risolvibile con batch loading
2. **Query complesse**: Gestibili con SQL raw quando necessario
3. **Aggregazioni**: Risolvibili con subquery o SQL raw

**💡 Il Lazy Loading è la "killer feature":**

Il lazy loading automatico rende il design **superiore ai JOIN espliciti** per la maggior parte dei casi perché:

- **Evita over-fetching**: Carica solo i dati effettivamente usati
- **Semantic clarity**: Il codice esprime chiaramente le dipendenze
- **Auto-optimization**: Il framework ottimizza automaticamente con cache
- **Graceful degradation**: Performance degradano gradualmente, non crash

### Strategie di Ottimizzazione

**Per casi normali (95%)**: Il lazy loading è perfetto
```php
$post = $this->postModel->getEntityById(1);
echo $post->getAuthor()->getName(); // 2 query totali, ottimo
```

**Per casi con pattern prevedibili**: Batch loading
```php
// Pre-carica autori per evitare N+1
$this->preloadAuthorsForPosts($posts);
```

**Per reporting/analytics**: SQL raw
```php
// Query complesse con JOIN espliciti quando necessario
$this->dataMapper->findRaw("SELECT ...");
```

**Conclusione**: Il design **privilegia developer experience e manutenibilità** over performance raw, che è quasi sempre la scelta vincente per applicazioni business. Il lazy loading rende questo trade-off molto più favorevole di quanto appaia superficialmente.

---

[Indice](index.md) | Precedente: [Advanced ORM](advanced-orm.md)
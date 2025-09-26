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

### Query Basate su Relazioni

```php
class RelationalQueryService
{
    public function getUserContent(User $user): array
    {
        $postModel = new PostModel($this->dataMapper);
        $commentModel = new CommentModel($this->dataMapper);

        // Metodi magici per relazioni
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

        // Relazione con categoria
        return $postModel->getByCategory($category);
    }

    public function getCommentsByPost(Post $post): SismaCollection
    {
        $commentModel = new CommentModel($this->dataMapper);

        return $commentModel->getByPost($post);
    }

    public function bulkDeleteUserContent(User $user): bool
    {
        $postModel = new PostModel($this->dataMapper);
        $commentModel = new CommentModel($this->dataMapper);

        $this->dataMapper->beginTransaction();

        try {
            // Elimina tutti i post dell'utente
            $postModel->deleteByAuthor($user);

            // Elimina tutti i commenti dell'utente
            $commentModel->deleteByAuthor($user);

            $this->dataMapper->commit();
            return true;

        } catch (\Exception $e) {
            $this->dataMapper->rollback();
            throw $e;
        }
    }
}
```

### Lazy Loading Avanzato

```php
class LazyLoadingExample
{
    public function demonstrateLazyLoading(): void
    {
        $postModel = new PostModel($this->dataMapper);

        // Carica solo il post
        $post = $postModel->getEntityById(1);

        // Lazy loading automatico dell'autore
        $authorName = $post->getAuthor()->getName(); // Query automatica

        // Lazy loading della collezione commenti
        $comments = $post->getCommentCollection(); // Query automatica

        // ⚠️ ATTENZIONE: Problema N+1 nel loop
        $posts = $postModel->getEntityCollection();
        foreach ($posts as $post) {
            echo $post->getAuthor()->getName(); // Query per ogni post!
        }
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
            $authorIds[] = $post->getAuthorId();
        }
        $authorIds = array_unique($authorIds);

        // Carica tutti gli autori in una query
        $authors = $userModel->getEntityCollectionByIds($authorIds);

        // Crea mappa per lookup veloce
        $authorMap = [];
        foreach ($authors as $author) {
            $authorMap[$author->getId()] = $author;
        }

        // Usa i dati pre-caricati
        foreach ($posts as $post) {
            $author = $authorMap[$post->getAuthorId()];
            echo $author->getName(); // Nessuna query aggiuntiva
        }
    }
}
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
# Advanced ORM

Questa guida copre gli aspetti avanzati dell'ORM di SismaFramework, incluse tecniche per query complesse, ottimizzazione delle performance, relazioni avanzate e patterns architetturali per applicazioni enterprise.

## Panoramica

L'ORM di SismaFramework implementa il pattern **Data Mapper**, offrendo:
- **Mappatura implicita** tra oggetti e database
- **Lazy loading** automatico per le relazioni
- **Query builder** fluido e type-safe
- **Cache intelligente** per performance ottimali
- **Supporto per relazioni complesse** (1:1, 1:N, N:N, self-reference)

---

## Query Builder Avanzato

### Query Complesse con Join

```php
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\LogicalOperator;

class AdvancedQueryService
{
    private DataMapper $dataMapper;

    public function findUsersWithPosts(): array
    {
        $query = $this->dataMapper->createQuery()
            ->select(['u.*', 'p.title', 'p.published_at'])
            ->from('users', 'u')
            ->innerJoin('posts', 'p', 'u.id = p.author_id')
            ->where('p.published_at', ComparisonOperator::isNotNull)
            ->andWhere('u.active', ComparisonOperator::equal, true)
            ->orderBy('p.published_at', 'DESC')
            ->limit(50);

        return $query->execute();
    }

    public function findPostsWithStats(): array
    {
        return $this->dataMapper->createQuery()
            ->select([
                'p.*',
                'COUNT(c.id) as comment_count',
                'AVG(r.rating) as avg_rating'
            ])
            ->from('posts', 'p')
            ->leftJoin('comments', 'c', 'p.id = c.post_id')
            ->leftJoin('ratings', 'r', 'p.id = r.post_id')
            ->groupBy(['p.id'])
            ->having('comment_count', ComparisonOperator::greaterThan, 5)
            ->execute();
    }
}
```

### Subquery e CTE (Common Table Expressions)

```php
class SubqueryService
{
    public function findTopAuthors(): array
    {
        // Subquery per contare post per autore
        $subquery = $this->dataMapper->createQuery()
            ->select(['author_id', 'COUNT(*) as post_count'])
            ->from('posts')
            ->where('published_at', ComparisonOperator::greaterThan, '2023-01-01')
            ->groupBy(['author_id']);

        // Query principale
        return $this->dataMapper->createQuery()
            ->select(['u.*', 'stats.post_count'])
            ->from('users', 'u')
            ->innerJoin("({$subquery->toSql()})", 'stats', 'u.id = stats.author_id')
            ->orderBy('stats.post_count', 'DESC')
            ->limit(10)
            ->execute();
    }

    public function findPostsAboveAverage(): array
    {
        // CTE per calcolare media
        $avgQuery = $this->dataMapper->createQuery()
            ->select(['AVG(view_count) as avg_views'])
            ->from('posts');

        return $this->dataMapper->createQuery()
            ->with('avg_stats', $avgQuery)
            ->select(['p.*'])
            ->from('posts', 'p')
            ->crossJoin('avg_stats', 'avg')
            ->where('p.view_count', ComparisonOperator::greaterThan, 'avg.avg_views')
            ->execute();
    }
}
```

---

## Relazioni Avanzate

### Self-Referencing Hierarchies

```php
// Entità per strutture gerarchiche
class Category extends BaseEntity
{
    protected ?int $id = null;
    protected string $name;
    protected ?int $parentId = null;
    protected ?Category $parent = null;

    // Lazy-loaded collection
    protected ?Collection $childrenCollection = null;

    public function getChildren(): Collection
    {
        if ($this->childrenCollection === null) {
            $this->childrenCollection = $this->dataMapper
                ->getModel(Category::class)
                ->findByParentId($this->id);
        }
        return $this->childrenCollection;
    }

    public function getAllDescendants(): Collection
    {
        $descendants = new Collection();
        $this->collectDescendants($descendants);
        return $descendants;
    }

    private function collectDescendants(Collection $collection): void
    {
        foreach ($this->getChildren() as $child) {
            $collection->add($child);
            $child->collectDescendants($collection);
        }
    }

    public function getPath(): array
    {
        $path = [];
        $current = $this;

        while ($current !== null) {
            array_unshift($path, $current);
            $current = $current->getParent();
        }

        return $path;
    }
}

// Model con query specializzate
class CategoryModel extends BaseModel
{
    public function findRootCategories(): Collection
    {
        return $this->createQuery()
            ->where('parent_id', ComparisonOperator::isNull)
            ->orderBy('name')
            ->execute();
    }

    public function findByLevel(int $level): Collection
    {
        // CTE ricorsiva per calcolare livelli
        $cte = "
            WITH RECURSIVE category_levels AS (
                SELECT id, name, parent_id, 0 as level
                FROM categories
                WHERE parent_id IS NULL

                UNION ALL

                SELECT c.id, c.name, c.parent_id, cl.level + 1
                FROM categories c
                INNER JOIN category_levels cl ON c.parent_id = cl.id
            )
        ";

        return $this->dataMapper->createRawQuery($cte . "
            SELECT * FROM category_levels WHERE level = ?
        ", [$level])->execute();
    }

    public function moveSubtree(Category $category, ?Category $newParent): void
    {
        $this->dataMapper->beginTransaction();

        try {
            // Aggiorna parent
            $category->setParentId($newParent?->getId());
            $this->dataMapper->save($category);

            // Aggiorna path cache se presente
            $this->updatePathCache($category);

            $this->dataMapper->commit();
        } catch (\Exception $e) {
            $this->dataMapper->rollback();
            throw $e;
        }
    }
}
```

### Many-to-Many con Attributi

```php
// Entità per relazione N:N con attributi
class UserRole extends BaseEntity
{
    protected ?int $id = null;
    protected int $userId;
    protected int $roleId;
    protected \DateTime $assignedAt;
    protected ?\DateTime $expiresAt = null;
    protected bool $isActive = true;

    // Relazioni
    protected ?User $user = null;
    protected ?Role $role = null;
}

// Service per gestire ruoli utente
class UserRoleService
{
    private DataMapper $dataMapper;

    public function assignRole(User $user, Role $role, ?\DateTime $expiresAt = null): UserRole
    {
        // Verifica se esiste già
        $existing = $this->findUserRole($user, $role);
        if ($existing && $existing->isActive()) {
            throw new \InvalidArgumentException('Role already assigned');
        }

        $userRole = new UserRole();
        $userRole->setUserId($user->getId());
        $userRole->setRoleId($role->getId());
        $userRole->setAssignedAt(new \DateTime());
        $userRole->setExpiresAt($expiresAt);

        $this->dataMapper->save($userRole);

        return $userRole;
    }

    public function findActiveRoles(User $user): Collection
    {
        return $this->dataMapper->createQuery()
            ->select(['r.*', 'ur.assigned_at', 'ur.expires_at'])
            ->from('user_roles', 'ur')
            ->innerJoin('roles', 'r', 'ur.role_id = r.id')
            ->where('ur.user_id', ComparisonOperator::equal, $user->getId())
            ->andWhere('ur.is_active', ComparisonOperator::equal, true)
            ->andWhere(function($query) {
                $query->where('ur.expires_at', ComparisonOperator::isNull)
                      ->orWhere('ur.expires_at', ComparisonOperator::greaterThan, new \DateTime());
            })
            ->execute();
    }

    public function revokeRole(User $user, Role $role): void
    {
        $userRole = $this->findUserRole($user, $role);
        if ($userRole) {
            $userRole->setIsActive(false);
            $this->dataMapper->save($userRole);
        }
    }
}
```

---

## Performance Optimization

### Query Optimization

```php
class OptimizedQueryService
{
    // N+1 Problem Solution: Eager Loading
    public function findPostsWithAuthors(): Collection
    {
        // ❌ Problema N+1: una query per i post + N query per gli autori
        $posts = $this->postModel->findAll();
        foreach ($posts as $post) {
            $author = $post->getAuthor(); // Query aggiuntiva per ogni post
        }

        // ✅ Soluzione: Single query con JOIN
        return $this->dataMapper->createQuery()
            ->select(['p.*', 'u.name as author_name', 'u.email as author_email'])
            ->from('posts', 'p')
            ->innerJoin('users', 'u', 'p.author_id = u.id')
            ->execute();
    }

    // Batch Operations
    public function updateViewCounts(array $postIds): void
    {
        // ❌ Query multiple
        foreach ($postIds as $id) {
            $this->dataMapper->execute(
                "UPDATE posts SET view_count = view_count + 1 WHERE id = ?",
                [$id]
            );
        }

        // ✅ Single query
        $placeholders = str_repeat('?,', count($postIds) - 1) . '?';
        $this->dataMapper->execute(
            "UPDATE posts SET view_count = view_count + 1 WHERE id IN ({$placeholders})",
            $postIds
        );
    }

    // Query con Index Hints
    public function findRecentPostsOptimized(): Collection
    {
        return $this->dataMapper->createQuery()
            ->select(['*'])
            ->from('posts USE INDEX (idx_published_at)')
            ->where('published_at', ComparisonOperator::greaterThan, '-30 days')
            ->orderBy('published_at', 'DESC')
            ->limit(100)
            ->execute();
    }
}
```

### Caching Strategies

```php
class CachedQueryService
{
    private CacheInterface $cache;
    private DataMapper $dataMapper;

    public function findPopularPosts(int $limit = 10): Collection
    {
        $cacheKey = "popular_posts_{$limit}";

        return $this->cache->remember($cacheKey, 3600, function() use ($limit) {
            return $this->dataMapper->createQuery()
                ->select(['*'])
                ->from('posts')
                ->orderBy('view_count', 'DESC')
                ->limit($limit)
                ->execute();
        });
    }

    // Cache invalidation smart
    public function updatePost(Post $post): void
    {
        $this->dataMapper->save($post);

        // Invalida cache correlate
        $this->cache->forget("popular_posts_*");
        $this->cache->forget("post_{$post->getId()}");
        $this->cache->forget("author_posts_{$post->getAuthorId()}");
    }

    // Query result caching con hash
    public function complexSearch(array $criteria): Collection
    {
        $cacheKey = 'search_' . md5(serialize($criteria));

        return $this->cache->remember($cacheKey, 1800, function() use ($criteria) {
            $query = $this->dataMapper->createQuery()
                ->from('posts', 'p');

            foreach ($criteria as $field => $value) {
                $query->andWhere("p.{$field}", ComparisonOperator::like, "%{$value}%");
            }

            return $query->execute();
        });
    }
}
```

---

## Advanced Patterns

### Repository Pattern

```php
interface PostRepositoryInterface
{
    public function findById(int $id): ?Post;
    public function findByAuthor(User $author): Collection;
    public function findPublished(): Collection;
    public function findByTag(Tag $tag): Collection;
    public function search(PostSearchCriteria $criteria): Collection;
}

class PostRepository implements PostRepositoryInterface
{
    private DataMapper $dataMapper;
    private PostModel $model;

    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
        $this->model = new PostModel($dataMapper);
    }

    public function findById(int $id): ?Post
    {
        return $this->model->find($id);
    }

    public function findByAuthor(User $author): Collection
    {
        return $this->model->findByAuthorId($author->getId());
    }

    public function findPublished(): Collection
    {
        return $this->dataMapper->createQuery()
            ->from('posts')
            ->where('status', ComparisonOperator::equal, 'published')
            ->where('published_at', ComparisonOperator::lessThanOrEqual, new \DateTime())
            ->orderBy('published_at', 'DESC')
            ->execute();
    }

    public function search(PostSearchCriteria $criteria): Collection
    {
        $query = $this->dataMapper->createQuery()->from('posts', 'p');

        if ($criteria->getTitle()) {
            $query->andWhere('p.title', ComparisonOperator::like, "%{$criteria->getTitle()}%");
        }

        if ($criteria->getAuthorId()) {
            $query->andWhere('p.author_id', ComparisonOperator::equal, $criteria->getAuthorId());
        }

        if ($criteria->getTags()) {
            $query->innerJoin('post_tags', 'pt', 'p.id = pt.post_id')
                  ->innerJoin('tags', 't', 'pt.tag_id = t.id')
                  ->whereIn('t.name', $criteria->getTags());
        }

        return $query->execute();
    }
}

// Value Object per criteri di ricerca
class PostSearchCriteria
{
    private ?string $title = null;
    private ?int $authorId = null;
    private array $tags = [];
    private ?\DateTime $fromDate = null;
    private ?\DateTime $toDate = null;

    // Getters e setters...

    public static function create(): self
    {
        return new self();
    }

    public function withTitle(string $title): self
    {
        $clone = clone $this;
        $clone->title = $title;
        return $clone;
    }

    public function withAuthor(int $authorId): self
    {
        $clone = clone $this;
        $clone->authorId = $authorId;
        return $clone;
    }

    public function withTags(array $tags): self
    {
        $clone = clone $this;
        $clone->tags = $tags;
        return $clone;
    }
}
```

### Unit of Work Pattern

```php
class UnitOfWork
{
    private array $newEntities = [];
    private array $dirtyEntities = [];
    private array $deletedEntities = [];
    private DataMapper $dataMapper;

    public function registerNew(BaseEntity $entity): void
    {
        $this->newEntities[spl_object_hash($entity)] = $entity;
    }

    public function registerDirty(BaseEntity $entity): void
    {
        $this->dirtyEntities[spl_object_hash($entity)] = $entity;
    }

    public function registerDeleted(BaseEntity $entity): void
    {
        $this->deletedEntities[spl_object_hash($entity)] = $entity;
    }

    public function commit(): void
    {
        $this->dataMapper->beginTransaction();

        try {
            // Insert new entities
            foreach ($this->newEntities as $entity) {
                $this->dataMapper->save($entity);
            }

            // Update dirty entities
            foreach ($this->dirtyEntities as $entity) {
                $this->dataMapper->save($entity);
            }

            // Delete entities
            foreach ($this->deletedEntities as $entity) {
                $this->dataMapper->delete($entity);
            }

            $this->dataMapper->commit();
            $this->clear();

        } catch (\Exception $e) {
            $this->dataMapper->rollback();
            throw $e;
        }
    }

    private function clear(): void
    {
        $this->newEntities = [];
        $this->dirtyEntities = [];
        $this->deletedEntities = [];
    }
}

// Utilizzo con Service Layer
class BlogService
{
    private UnitOfWork $unitOfWork;
    private PostRepository $postRepository;
    private CommentRepository $commentRepository;

    public function publishPostWithComments(Post $post, array $comments): void
    {
        // Preparare entità
        $post->setStatus('published');
        $post->setPublishedAt(new \DateTime());
        $this->unitOfWork->registerDirty($post);

        foreach ($comments as $commentData) {
            $comment = new Comment();
            $comment->setPostId($post->getId());
            $comment->setContent($commentData['content']);
            $comment->setAuthorId($commentData['author_id']);
            $this->unitOfWork->registerNew($comment);
        }

        // Atomic commit
        $this->unitOfWork->commit();
    }
}
```

---

## Event System Integration

### Domain Events

```php
interface DomainEventInterface
{
    public function getOccurredOn(): \DateTime;
    public function getEventName(): string;
}

class PostPublishedEvent implements DomainEventInterface
{
    private Post $post;
    private \DateTime $occurredOn;

    public function __construct(Post $post)
    {
        $this->post = $post;
        $this->occurredOn = new \DateTime();
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getOccurredOn(): \DateTime
    {
        return $this->occurredOn;
    }

    public function getEventName(): string
    {
        return 'post.published';
    }
}

// Entity con eventi
class Post extends BaseEntity
{
    private array $domainEvents = [];

    public function publish(): void
    {
        if ($this->status !== 'published') {
            $this->status = 'published';
            $this->publishedAt = new \DateTime();

            $this->recordEvent(new PostPublishedEvent($this));
        }
    }

    private function recordEvent(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function popEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}

// Event Dispatcher
class EventDispatcher
{
    private array $listeners = [];

    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(DomainEventInterface $event): void
    {
        $eventName = $event->getEventName();

        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                $listener($event);
            }
        }
    }
}

// Service con event handling
class PostService
{
    private DataMapper $dataMapper;
    private EventDispatcher $eventDispatcher;

    public function publishPost(Post $post): void
    {
        $post->publish();
        $this->dataMapper->save($post);

        // Dispatch eventi
        foreach ($post->popEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
```

---

## Database Migrations

### Migration System

```php
abstract class Migration
{
    protected DataMapper $dataMapper;

    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    abstract public function up(): void;
    abstract public function down(): void;
    abstract public function getVersion(): string;
}

class CreatePostsTableMigration extends Migration
{
    public function up(): void
    {
        $sql = "
            CREATE TABLE posts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                author_id INT NOT NULL,
                status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                published_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_author_id (author_id),
                INDEX idx_status_published (status, published_at),
                FULLTEXT idx_content (title, content)
            )
        ";

        $this->dataMapper->execute($sql);
    }

    public function down(): void
    {
        $this->dataMapper->execute("DROP TABLE posts");
    }

    public function getVersion(): string
    {
        return '2024_01_15_create_posts_table';
    }
}

class MigrationRunner
{
    private DataMapper $dataMapper;
    private array $migrations = [];

    public function addMigration(Migration $migration): void
    {
        $this->migrations[$migration->getVersion()] = $migration;
    }

    public function migrate(): void
    {
        $this->createMigrationsTable();
        $executed = $this->getExecutedMigrations();

        foreach ($this->migrations as $version => $migration) {
            if (!in_array($version, $executed)) {
                echo "Running migration: {$version}\n";
                $migration->up();
                $this->recordMigration($version);
            }
        }
    }

    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                version VARCHAR(255) PRIMARY KEY,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->dataMapper->execute($sql);
    }
}
```

---

## Performance Monitoring

### Query Profiling

```php
class QueryProfiler
{
    private array $queries = [];
    private float $totalTime = 0;

    public function startQuery(string $sql, array $params = []): string
    {
        $queryId = uniqid();
        $this->queries[$queryId] = [
            'sql' => $sql,
            'params' => $params,
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage(),
        ];

        return $queryId;
    }

    public function endQuery(string $queryId): void
    {
        if (isset($this->queries[$queryId])) {
            $query = &$this->queries[$queryId];
            $query['end_time'] = microtime(true);
            $query['duration'] = $query['end_time'] - $query['start_time'];
            $query['memory_end'] = memory_get_usage();
            $query['memory_used'] = $query['memory_end'] - $query['memory_start'];

            $this->totalTime += $query['duration'];
        }
    }

    public function getSlowQueries(float $threshold = 0.1): array
    {
        return array_filter($this->queries, function($query) use ($threshold) {
            return isset($query['duration']) && $query['duration'] > $threshold;
        });
    }

    public function generateReport(): array
    {
        return [
            'total_queries' => count($this->queries),
            'total_time' => $this->totalTime,
            'average_time' => $this->totalTime / count($this->queries),
            'slow_queries' => $this->getSlowQueries(),
            'memory_peak' => memory_get_peak_usage(),
        ];
    }
}

// Integration con DataMapper
class ProfiledDataMapper extends DataMapper
{
    private QueryProfiler $profiler;

    public function execute(string $sql, array $params = []): mixed
    {
        $queryId = $this->profiler->startQuery($sql, $params);

        try {
            $result = parent::execute($sql, $params);
            return $result;
        } finally {
            $this->profiler->endQuery($queryId);
        }
    }
}
```

---

[Indice](index.md) | Precedente: [Configuration Reference](configuration-reference.md) | Successivo: [Performance Guide](performance.md)
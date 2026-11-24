# Performance Guide

Questa guida fornisce best practices e tecniche per ottimizzare le performance delle applicazioni basate su SismaFramework, con particolare attenzione al pattern Data Mapper implementato nell'ORM.

## Panoramica

SismaFramework è progettato per avere buone performance senza configurazioni particolari, ma un utilizzo appropriato dell'ORM e del database può migliorarle in modo significativo.

### Obiettivi di Riferimento

- **Tempo di risposta**: < 200ms per pagine semplici, < 500ms per pagine complesse
- **Memoria**: < 50MB per richiesta in media
- **Query al database**: < 10 query per pagina

Il numero di query, la memoria e il tempo d'esecuzione di ogni richiesta sono visibili nella barra di debug (vedi [Monitoraggio](#monitoraggio)).

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

Le query personalizzate si scrivono come metodi del model (vedi [Advanced ORM](advanced-orm.md#query-avanzate-con-data-mapper)): `initQuery()` è `protected` e non può essere chiamato da un servizio esterno.

#### Query Efficaci vs Inefficaci

```php
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Placeholder;

class PostModel extends DependentModel
{
    // ✅ GOOD: il database filtra, ordina e limita
    public function getPublishedPosts(int $limit = 20): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('publishedAt', ComparisonOperator::lessOrEqual, Placeholder::placeholder);
        $query->setOrderBy(['publishedAt' => Indexing::desc])
            ->setLimit($limit);

        $bindValues = [PostStatus::published, new SismaDateTime()];
        $bindTypes = [DataType::typeEnumeration, DataType::typeDate];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

```php
// ❌ BAD: carica tutte le entità e filtra in PHP
$publishedPosts = new SismaCollection(Post::class);
foreach ($postModel->getEntityCollection() as $post) {
    if ($post->status === PostStatus::published) {
        $publishedPosts->append($post);
    }
}

// ✅ GOOD: COUNT sul database
$postCount = $postModel->countEntityCollection();
$publishedCount = $postModel->countByStatus(PostStatus::published);

// ❌ BAD: carica le entità solo per contarle
$postCount = $postModel->getEntityCollection()->count();
```

#### Gestione del Problema N+1

```php
// ❌ PROBLEMA N+1: una query per ogni autore non ancora in cache
foreach ($postModel->getEntityCollection() as $post) {
    echo $post->title . ' by ' . $post->author->name;
}

// ✅ SOLUZIONE: eager loading, gli autori arrivano con la stessa query tramite JOIN
foreach ($postModel->getEntityCollectionWithRelations(['author']) as $post) {
    echo $post->title . ' by ' . $post->author->name;
}

// ✅ Collection: una sola query aggiuntiva per tutti gli autori
foreach ($authorModel->getEntityCollectionWithRelations(['postCollection']) as $author) {
    echo $author->name . ': ' . $author->postCollection->count() . ' post';
}
```

Le relazioni annidate, le collection e le query personalizzate con JOIN sono descritte in [JOIN con Idratazione Gerarchica](join-eager-loading.md).

### Operazioni Batch

```php
class PostModel extends DependentModel
{
    // ✅ una sola UPDATE per tutti gli ID
    public function incrementViewCounts(array $postIds): bool
    {
        $placeholders = implode(', ', array_fill(0, count($postIds), '?'));
        $sql = "UPDATE post SET view_count = view_count + 1 WHERE id IN ({$placeholders})";
        return $this->initQuery()->getAdapter()->execute($sql, $postIds, array_fill(0, count($postIds), DataType::typeInteger));
    }

    // ✅ una sola DELETE per tutte le bozze vecchie
    public function deleteOldDrafts(SismaDateTime $cutoffDate): bool
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('createdAt', ComparisonOperator::less, Placeholder::placeholder);

        return $this->dataMapper->deleteBatch($query, [PostStatus::draft, $cutoffDate], [DataType::typeEnumeration, DataType::typeDate]);
    }
}
```

Per inserire più entità in modo atomico, i salvataggi si raccolgono in una transazione:

```php
$dataMapper = new DataMapper();
$dataMapper->startTransaction();
foreach ($postsData as $data) {
    $post = new Post();
    $post->title = $data['title'];
    $post->content = $data['content'];
    $post->author = $data['author_id'];
    $post->status = PostStatus::draft;
    $dataMapper->save($post);
}
$dataMapper->commitTransaction();
```

Se un salvataggio fallisce, il `DataMapper` annulla l'intera transazione e solleva una `DataMapperException` (vedi [Gestione Transazioni](advanced-orm.md#gestione-transazioni)).

---

## Caching Strategies

### Cache delle Entità

Con la costante `ORM_CACHE` attiva (vedi [Configurazione](configuration-reference.md)), ogni entità caricata viene registrata nella cache delle entità per la durata della richiesta:

```php
use SismaFramework\Orm\HelperClasses\Cache;

$post = $postModel->getEntityById(1);     // query al database
$samePost = $postModel->getEntityById(1); // cache hit: nessuna query, stesso oggetto

if (Cache::checkEntityPresenceInCache(Post::class, 1)) {
    $cachedPost = Cache::getEntityById(Post::class, 1);
}
```

- La cache restituisce sempre la stessa istanza per la stessa entità, anche quando è caricata tramite lazy loading o JOIN
- `getEntityById()` legge dalla cache senza interrogare il database; le altre query vengono eseguite, ma le entità già in cache vengono sostituite con l'istanza in memoria, che contiene le eventuali modifiche non ancora salvate
- `Cache::clearEntityCache()` svuota la cache, ad esempio per rileggere dal database dati modificati da un altro processo o per liberare memoria nelle elaborazioni lunghe (vedi [Gestione della Memoria](#gestione-della-memoria))

### Cache Applicativa

La cache delle entità vale solo per la singola richiesta. Per conservare i dati tra richieste diverse si usa un sistema di cache applicativo, ad esempio un'implementazione di `Psr\SimpleCache\CacheInterface` (PSR-16) fornita dall'applicazione. Nella cache applicativa vanno salvati valori semplici (scalari, array) e non le entità, che dipendono dallo stato della richiesta:

```php
use Psr\SimpleCache\CacheInterface;

class SiteStatisticsService
{
    public function __construct(
        private CacheInterface $cache,
        private PostModel $postModel = new PostModel(),
        private UserModel $userModel = new UserModel()
    ) {
    }

    public function getStatistics(): array
    {
        $statistics = $this->cache->get('site_statistics');
        if ($statistics === null) {
            $statistics = [
                'total_posts' => $this->postModel->countEntityCollection(),
                'published_posts' => $this->postModel->countByStatus(PostStatus::published),
                'total_users' => $this->userModel->countEntityCollection(),
            ];
            $this->cache->set('site_statistics', $statistics, 3600);
        }
        return $statistics;
    }

    public function getPopularPostIds(int $limit = 10): array
    {
        $postIds = $this->cache->get("popular_post_ids_{$limit}");
        if ($postIds === null) {
            $postIds = [];
            foreach ($this->postModel->getEntityCollection(null, ['viewCount' => Indexing::desc], null, $limit) as $post) {
                $postIds[] = $post->id;
            }
            $this->cache->set("popular_post_ids_{$limit}", $postIds, 3600);
        }
        return $postIds;
    }

    public function invalidate(): void
    {
        $this->cache->deleteMultiple(['site_statistics', 'popular_post_ids_10']);
    }
}
```

---

## Gestione della Memoria

### Elaborazione di Grandi Quantità di Dati

Le entità caricate restano nella cache delle entità fino alla fine della richiesta. Nelle elaborazioni che scorrono molte righe, come gli export o i comandi della console, conviene procedere a pagine e svuotare la cache a ogni pagina:

```php
use SismaFramework\Orm\HelperClasses\Cache;

class PostExportService
{
    public function __construct(private PostModel $postModel = new PostModel())
    {
    }

    public function processAllPosts(callable $processor, int $batchSize = 100): void
    {
        $offset = 0;
        do {
            $posts = $this->postModel->getEntityCollectionWithRelations(['author'], null, ['id' => Indexing::asc], $offset, $batchSize);
            foreach ($posts as $post) {
                $processor($post);
            }
            $loadedPosts = $posts->count();
            $offset += $batchSize;
            unset($posts);
            Cache::clearEntityCache();
        } while ($loadedPosts === $batchSize);
    }

    public function exportPostsToCsv(string $filename): void
    {
        $handle = fopen($filename, 'w');
        fputcsv($handle, ['ID', 'Title', 'Author', 'Published At']);

        $this->processAllPosts(function (Post $post) use ($handle) {
            fputcsv($handle, [
                $post->id,
                $post->title,
                $post->author->name,
                $post->publishedAt?->format('Y-m-d H:i:s'),
            ]);
        });

        fclose($handle);
    }
}
```

- La paginazione con `offset` e `limit` limita le entità in memoria a una pagina per volta
- L'eager loading di `author` evita una query per ogni autore
- `Cache::clearEntityCache()` rilascia le entità della pagina elaborata, che altrimenti resterebbero referenziate dalla cache

### Misurare la Memoria

La barra di debug riporta la memoria utilizzata da ogni richiesta. Per misurare un'elaborazione specifica si usano le funzioni di PHP:

```php
$memoryBefore = memory_get_usage();
$postExportService->exportPostsToCsv($filename);
$memoryPeak = memory_get_peak_usage();
```

---

## Ottimizzazioni Specifiche dell'ORM

### Query con Filtri Dinamici

```php
use SismaFramework\Orm\HelperClasses\Query;

class PostModel extends DependentModel
{
    public function searchPosts(array $filters): SismaCollection
    {
        $query = $this->initQuery();
        $conditions = [];
        $bindValues = [];
        $bindTypes = [];

        if (isset($filters['status'])) {
            $conditions[] = fn(Query $query) => $query->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
            $bindValues[] = $filters['status'];
            $bindTypes[] = DataType::typeEnumeration;
        }
        if (isset($filters['author'])) {
            $conditions[] = fn(Query $query) => $query->appendCondition('author', ComparisonOperator::equal, Placeholder::placeholder, true);
            $bindValues[] = $filters['author'];
            $bindTypes[] = DataType::typeEntity;
        }
        if (isset($filters['dateFrom'])) {
            $conditions[] = fn(Query $query) => $query->appendCondition('publishedAt', ComparisonOperator::greaterOrEqual, Placeholder::placeholder);
            $bindValues[] = $filters['dateFrom'];
            $bindTypes[] = DataType::typeDate;
        }

        if ($conditions !== []) {
            $query->setWhere();
            foreach ($conditions as $index => $appendCondition) {
                if ($index > 0) {
                    $query->appendAnd();
                }
                $appendCondition($query);
            }
        }
        $query->setOrderBy(['publishedAt' => Indexing::desc]);

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

Ogni filtro presente aggiunge una condizione e il relativo valore legato, nello stesso ordine; le condizioni vengono unite con `appendAnd()` solo dalla seconda in poi.

### Ricerca Fulltext con Filtri

```php
class PostModel extends DependentModel
{
    public function searchPublishedPosts(string $searchTerms): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendFulltextCondition(['title', 'content'], Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $query->setLimit(50);

        $bindValues = [$searchTerms, PostStatus::published];
        $bindTypes = [DataType::typeString, DataType::typeEnumeration];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

La condizione fulltext usa l'indice `FULLTEXT` sulle stesse colonne, nello stesso ordine (vedi [Schema Design e Indici](#schema-design-e-indici)).

### Cache dei Metadati di Reflection

L'ORM legge la struttura delle entità (proprietà, tipi, foreign key) tramite Reflection durante l'istanziazione, l'accesso alle proprietà, il salvataggio e l'idratazione dei risultati. Dalla versione 12.10.0 gli oggetti `\ReflectionClass` e `\ReflectionProperty` di ogni classe di entità sono creati una sola volta e riutilizzati per tutta la durata della richiesta: idratare migliaia di righe della stessa entità non ricrea la reflection per ogni riga e per ogni colonna.

La cache è automatica e non richiede configurazione. Chi estende il framework e ha bisogno della struttura di un'entità può usare gli stessi metodi statici di `BaseEntity`, che accettano il nome della classe o un'istanza:

```php
use SismaFramework\Orm\BaseClasses\BaseEntity;

$reflectionClass = BaseEntity::getReflectionClass(Post::class);
$reflectionProperty = BaseEntity::getReflectionProperty($post, 'title');

// Solo le proprietà dichiarate in classi non astratte (le colonne dell'entità), indicizzate per nome
foreach (BaseEntity::getFinalClassReflectionProperties(Post::class) as $propertyName => $reflectionProperty) {
    // ...
}
```

Chiamate successive per la stessa classe restituiscono le stesse istanze.

### Connessione al Database

La connessione è configurata con le costanti `DATABASE_*` (vedi [Configurazione](configuration-reference.md)) e viene aperta solo alla prima query della richiesta: le pagine che non accedono al database non aprono connessioni. La connessione è unica e condivisa da tutti i model e i `DataMapper` della richiesta.

---

## Monitoraggio

### Barra di Debug

Con `DEVELOPMENT_ENVIRONMENT` attivo, la barra di debug mostra per ogni richiesta:
- le **query eseguite**, con il loro numero e il testo SQL
- la **memoria** utilizzata
- il **tempo d'esecuzione**
- le righe di **log**, gli errori dei **filtri dei form** e le **variabili** passate alla vista

Un numero di query che cresce con il numero di righe mostrate indica di solito un problema N+1: query quasi identiche ripetute su `id` diversi della stessa tabella. La soluzione è l'eager loading della relazione (vedi [Gestione del Problema N+1](#gestione-del-problema-n1)).

In produzione `DEVELOPMENT_ENVIRONMENT` va disattivato: la barra di debug non viene mostrata e gli errori non espongono dettagli (vedi [Deployment](deployment.md)).

---

## Ottimizzazioni per la Produzione

### Configurazione

```php
// Config/config.php in produzione
const DEVELOPMENT_ENVIRONMENT = false;
const ORM_CACHE = true;
```

Le altre impostazioni di produzione (credenziali, HTTPS, cache delle risorse statiche) sono descritte in [Deployment](deployment.md) e in [Configurazione](configuration-reference.md).

### Best Practices Summary

**✅ DO's:**
- **Usa gli indici appropriati** per le query frequenti
- **Usa l'eager loading** per le relazioni lette in un ciclo
- **Usa COUNT** invece di caricare le entità per contarle
- **Implementa la paginazione** per i grandi insiemi di dati
- **Svuota la cache delle entità** nelle elaborazioni lunghe
- **Controlla la barra di debug** durante lo sviluppo
- **Usa le transazioni** per le operazioni multiple

**❌ DON'Ts:**
- **Non accedere alle relazioni in un ciclo** senza eager loading
- **Non caricare entità** solo per contarle o per leggere un campo di un'altra tabella
- **Non eseguire query** dentro i cicli quando un'unica query può bastare
- **Non disattivare `ORM_CACHE`** senza motivo
- **Non dimenticare** la paginazione sui grandi insiemi di dati

---

## Lazy Loading ed Eager Loading

L'ORM offre due modi di caricare le relazioni, da scegliere caso per caso.

**Lazy loading**: la relazione viene caricata alla prima lettura della proprietà. È la scelta giusta quando la relazione serve solo in alcuni casi:

```php
$user = $userModel->getEntityById(1);
if ($user->role === UserRole::admin) {
    $posts = $user->postCollection; // query solo se necessaria
}
```

**Eager loading**: la relazione viene caricata insieme all'entità principale. È la scelta giusta quando la relazione viene letta per tutte le entità di una collezione:

```php
$posts = $postModel->getEntityCollectionWithRelations(['author', 'commentCollection']);
foreach ($posts as $post) {
    echo $post->author->name . ' (' . $post->commentCollection->count() . ' commenti)';
}
```

In entrambi i casi le entità passano dalla cache delle entità: un'entità già caricata non viene letta di nuovo dal database.

**Per reporting e analisi** si esegue la query, costruita con il query builder o scritta in SQL, con `select()` dell'adapter, che restituisce righe di tipo `StandardEntity` invece di entità (vedi [Query di Reporting](advanced-orm.md#query-di-reporting)).

---

[Indice](index.md) | Precedente: [Advanced ORM](advanced-orm.md)

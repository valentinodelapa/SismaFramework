# Advanced ORM

Questa guida copre gli aspetti avanzati dell'ORM di SismaFramework: query complesse, metodi magici, lazy loading, ottimizzazione delle performance, transazioni e organizzazione del codice di accesso ai dati.

## Panoramica

L'ORM di SismaFramework implementa il pattern **Data Mapper**, offrendo:
- **Mappatura automatica** tra oggetti e database
- **Lazy loading** automatico per le relazioni
- **Eager loading** con JOIN per evitare il problema N+1 (vedi [JOIN con Idratazione Gerarchica](join-eager-loading.md))
- **Query builder** per condizioni complesse
- **Cache delle entità** per non ricaricare le entità già lette
- **Metodi magici** per query basate sulle proprietà delle entità
- **Gestione transazioni** per operazioni atomiche

---

## Query Avanzate con Data Mapper

Le query personalizzate si scrivono come metodi del model: `initQuery()` restituisce una `Query` con la tabella dell'entità già impostata, e `$this->dataMapper->find()` la esegue restituendo una `SismaCollection` di entità. `initQuery()` è `protected`, per cui le query vanno costruite all'interno del model e non da un servizio esterno.

Regole da seguire:
- **`setWhere()` prima delle condizioni**: senza `setWhere()` (o `setHaving()`) le condizioni aggiunte vengono ignorate.
- **Operatori logici espliciti**: le condizioni si collegano con `appendAnd()` e `appendOr()`, e si raggruppano con `appendOpenBlock()` e `appendCloseBlock()`.
- **Valori tramite placeholder**: i valori si indicano con `Placeholder::placeholder` e si legano in `find()` con `$bindValues` e `$bindTypes`, nello stesso ordine in cui compaiono nella query. Un valore letterale viene inserito nell'SQL così com'è, senza apici: va usato solo per riferimenti a colonne.
- **Nomi delle proprietà**: colonne e ordinamenti si indicano con i nomi delle proprietà (`publishedAt`), che vengono convertiti in snake_case. Per le chiavi esterne si passa `true` come ultimo argomento di `appendCondition()`, che aggiunge il suffisso `_id`.

### Query Builder con Condizioni Complesse

```php
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Indexing;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\ExtendedClasses\DependentModel;

class PostModel extends DependentModel
{
    public function findPostsByComplexCriteria(string $titlePattern, SismaDateTime $fromDate, array $authors, PostStatus $status): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendCondition('title', ComparisonOperator::like, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('publishedAt', ComparisonOperator::greater, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('author', ComparisonOperator::in, array_fill(0, count($authors), Placeholder::placeholder), true)
            ->appendAnd()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $query->setOrderBy(['publishedAt' => Indexing::desc]);

        $bindValues = ["%{$titlePattern}%", $fromDate, ...$authors, $status];
        $bindTypes = [
            DataType::typeString,
            DataType::typeDate,
            ...array_fill(0, count($authors), DataType::typeEntity),
            DataType::typeEnumeration,
        ];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    public function findPostsWithLogicalGrouping(): SismaCollection
    {
        $query = $this->initQuery();

        // WHERE (status = ? OR status = ?) AND (view_count > ? OR comment_count > ?)
        $query->setWhere()
            ->appendOpenBlock()
                ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
                ->appendOr()
                ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendCloseBlock()
            ->appendAnd()
            ->appendOpenBlock()
                ->appendCondition('viewCount', ComparisonOperator::greater, Placeholder::placeholder)
                ->appendOr()
                ->appendCondition('commentCount', ComparisonOperator::greater, Placeholder::placeholder)
            ->appendCloseBlock();

        $bindValues = [PostStatus::published, PostStatus::featured, 100, 10];
        $bindTypes = [
            DataType::typeEnumeration,
            DataType::typeEnumeration,
            DataType::typeInteger,
            DataType::typeInteger,
        ];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

Con `ComparisonOperator::in` serve un placeholder per ogni valore, e ogni valore va legato singolarmente. Le entità passate come valori di una chiave esterna si legano con `DataType::typeEntity`, le date (`SismaDate`, `SismaDateTime`, `SismaTime`) con `DataType::typeDate` e le enumerazioni con `DataType::typeEnumeration`.

### Fulltext Search

La ricerca fulltext utilizza l'enumerazione `TextSearchMode` (introdotta nella 11.3.0) per scegliere la modalità di ricerca MySQL:

- `TextSearchMode::inNaturaLanguageMode` (default) → `IN NATURAL LANGUAGE MODE`
- `TextSearchMode::withQueryExpansion` → `WITH QUERY EXPANSION`
- `TextSearchMode::inBooleanMode` → `IN BOOLEAN MODE`

```php
use SismaFramework\Orm\Enumerations\TextSearchMode;

class PostModel extends DependentModel
{
    public function searchPosts(string $searchTerms): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendFulltextCondition(['title', 'content'], Placeholder::placeholder, TextSearchMode::inBooleanMode);

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, [$searchTerms], [DataType::typeString]);
    }

    public function searchWithRelevanceScore(string $searchTerms): SismaCollection
    {
        $query = $this->initQuery();

        // Firma: setFulltextIndexColumn(array $columns, $value, TextSearchMode $textSearchMode, ?string $columnAlias, bool $append)
        $query->setFulltextIndexColumn(['title', 'content'], Placeholder::placeholder, TextSearchMode::inNaturaLanguageMode, 'relevance_score', true)
            ->setWhere()
            ->appendFulltextCondition(['title', 'content'], Placeholder::placeholder, TextSearchMode::inNaturaLanguageMode);
        $query->setOrderBy(['relevance_score' => Indexing::desc]);

        $bindValues = [$searchTerms, $searchTerms];
        $bindTypes = [DataType::typeString, DataType::typeString];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

Il primo valore legato è quello della colonna di rilevanza, che nella query precede la condizione. Il punteggio serve all'ordinamento: l'entità non ha una proprietà corrispondente e non lo riporta.

> **Nota (12.0.0):** prima della 12.0.0 il parametro `$textSearchMode` era l'ultimo argomento di `setFulltextIndexColumn()`; da questa versione precede `$columnAlias` e `$append`. Il comando `sisma upgrade` corregge automaticamente le chiamate esistenti.

### Query su Colonne JSON

Dalla 12.7.0 il query builder interroga il contenuto delle colonne JSON (vedi [Colonne JSON](orm.md#colonne-json)). L'SQL generato usa `JSON_EXTRACT`, `JSON_UNQUOTE`, `JSON_CONTAINS`, `JSON_CONTAINS_PATH` e `JSON_TYPE`, disponibili sia in MySQL sia in MariaDB; gli operatori `->` e `->>`, che MariaDB non supporta, non vengono usati.

| Metodo | Uso |
|---|---|
| `appendJsonCondition($column, $path, $operator, $value, $jsonValueType)` | confronta un valore del documento |
| `appendJsonContainsCondition($column, $jsonValueType, $value, $path)` | verifica che il documento, o il valore al path, contenga un valore |
| `appendJsonPathExistsCondition($column, $path)` | verifica che il path esista nel documento |
| `appendJsonNullCondition($column, $path)` | verifica che il path esista e valga `null` |
| `setJsonExtractColumn($column, $path, $jsonValueType, $columnAlias, $append)` | seleziona un valore del documento come colonna |
| `appendOrderByJsonPath($column, $path, $indexing, $jsonValueType)` | ordina per un valore del documento |

Le condizioni si comportano come `appendCondition()`: vanno nel `WHERE` dopo `setWhere()` e nell'`HAVING` dopo `setHaving()`, e si combinano con `appendAnd()`, `appendOr()`, `appendNot()` e i blocchi.

#### Path

Il path segue la sintassi JSON di MySQL e si può passare in due modi:

- **come stringa**, inserita come letterale nell'SQL: è ammessa solo la forma `$`, seguita da chiavi `.nome` (lettere, cifre e `_`, senza cifra iniziale) e indici `[n]`, per esempio `$.sizes[0].label`. Un path diverso, con caratteri speciali, chiavi tra virgolette o caratteri jolly, solleva un'`AdapterException` quando si costruisce la query;
- **come `Placeholder::placeholder`**, legato come parametro: è ammesso qualunque path, e il suo valore va inserito nei `$bindValues` nella posizione in cui compare nell'SQL, cioè **prima** del valore del confronto.

#### Tipo del valore: `JsonValueType`

Il documento restituisce i valori come testo: senza indicazioni un confronto `>` tra `'9'` e `'10.5'` sarebbe lessicografico, e un booleano `true` estratto è la stringa `'true'`, diversa dall'`1` con cui MySQL rappresenta un booleano SQL. L'enum `JsonValueType` indica il tipo del valore nel documento, e il builder genera l'SQL che converte di conseguenza; i valori si legano nei `$bindValues` come per qualunque altra condizione.

| `JsonValueType` | Valore estratto | Valore cercato da `appendJsonContainsCondition` | Valore da legare |
|---|---|---|---|
| `string` (default) | `JSON_UNQUOTE(...)` | `JSON_QUOTE(?)` | stringa, `typeString` |
| `integer` | `CAST(... AS SIGNED)` | `CAST(? AS CHAR)` | intero, `typeInteger` |
| `decimal` | `CAST(... AS DECIMAL(65,30))` | `CAST(? AS CHAR)` | float, `typeDecimal` |
| `boolean` | `(... = 'true')`, che vale `1` o `0` | `IF(?, 'true', 'false')` | bool, `typeBoolean` |
| `json` | `JSON_EXTRACT(...)` senza conversione | `?` invariato | `SismaJson` o stringa JSON, `typeJson` |

`JsonValueType::json` non è ammesso in `appendJsonCondition()`, dove solleva un'`AdapterException`: il confronto tra documenti non è affidabile, e per cercare un oggetto o un array va usato `appendJsonContainsCondition()`. In `appendJsonContainsCondition()` il tipo è obbligatorio, perché un tipo errato fa fallire la ricerca senza errori.

```php
use SismaFramework\Orm\CustomTypes\SismaJson;
use SismaFramework\Orm\Enumerations\JsonValueType;

class ProductModel extends BaseModel
{
    public function getDiscountedRedProducts(float $minimumPrice): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendAnd()
            ->appendJsonCondition('attributes', '$.color', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendAnd()
            ->appendJsonCondition('attributes', '$.price', ComparisonOperator::greaterOrEqual, Placeholder::placeholder, JsonValueType::decimal)
            ->appendAnd()
            ->appendJsonCondition('attributes', '$.onSale', ComparisonOperator::equal, Placeholder::placeholder, JsonValueType::boolean);
        $query->appendOrderByJsonPath('attributes', '$.price', Indexing::asc, JsonValueType::decimal);

        $bindValues = ['active', 'red', $minimumPrice, true];
        $bindTypes = [DataType::typeString, DataType::typeString, DataType::typeDecimal, DataType::typeBoolean];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    public function getProductsWithTags(array $tags): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendJsonContainsCondition('attributes', JsonValueType::json, Placeholder::placeholder, '$.tags');

        $bindValues = [new SismaJson($tags)];
        $bindTypes = [DataType::typeJson];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

Per cercare un solo elemento di una lista basta indicarne il tipo: `appendJsonContainsCondition('attributes', JsonValueType::string, Placeholder::placeholder, '$.tags')` con il valore `'sale'` legato come `typeString`.

#### Chiave assente e valore `null`

Una chiave assente e una chiave con valore `null` sono situazioni diverse: estraendo la prima si ottiene il `NULL` SQL, estraendo la seconda la stringa `'null'`. Di conseguenza `appendJsonCondition()` con `ComparisonOperator::isNull` trova solo le chiavi **assenti**. Per gli altri casi:

| Documenti cercati | Condizione |
|---|---|
| chiave assente | `appendNot()` + `appendJsonPathExistsCondition()` |
| chiave presente, qualunque valore | `appendJsonPathExistsCondition()` |
| chiave presente con valore `null` | `appendJsonNullCondition()` |
| chiave assente o con valore `null` | `appendJsonCondition()` con `isNull`, `appendOr()`, `appendJsonNullCondition()` |

#### Selezione di valori estratti

`setJsonExtractColumn()` aggiunge alla `SELECT` un valore del documento con un alias. L'idratazione di un'entità valorizza solo le proprietà esistenti, per cui la colonna estratta è utile soprattutto con `StandardEntity`, nelle aggregazioni o come alias per l'ordinamento.

#### Valori legati come `typeJson`

Un valore legato con `DataType::typeJson` deve essere già JSON, cioè un `SismaJson` o una stringa JSON. Un booleano o un array legati come `typeJson` sollevano un'`AdapterException`: senza il controllo `true` arriverebbe al database come `'1'`, `false` come stringa vuota, e un array non sarebbe convertibile.

---

## Metodi Magici e Relazioni

### Metodi Magici in BaseEntity

Le entità di SismaFramework utilizzano i **metodi magici** di PHP per implementare funzionalità avanzate in modo trasparente. Le proprietà delle entità sono `protected`, e i metodi magici ne intercettano lettura, scrittura e verifica per gestire lazy loading, tracking delle modifiche e validazione.

#### `__get($name)` - Magic Getter

Intercetta l'accesso in lettura alle proprietà per abilitare il lazy loading:

```php
$post = $postModel->getEntityById(1);
$authorName = $post->author->name; // __get('author')

// Cosa succede internamente:
// 1. Verifica che la proprietà 'author' esista nella classe finale
// 2. Chiama forceForeignKeyPropertySet('author')
// 3. Se author è un ID non convertito, carica l'entità User dal database
// 4. Restituisce l'istanza di User
```

**Comportamento:**
- Valida che la proprietà esista e appartenga alla classe finale (non a `BaseEntity`)
- Attiva automaticamente il lazy loading per le foreign key
- Lancia `InvalidPropertyException` se la proprietà non esiste

#### `__set($name, $value)` - Magic Setter

Intercetta l'assegnazione alle proprietà per gestire il tracking delle modifiche:

```php
$post->author = 5;           // __set('author', 5)
$post->author = $userEntity; // __set('author', $userEntity)
$post->author = null;        // __set('author', null)

// Cosa succede internamente:
// 1. Verifica che la proprietà esista nella classe finale
// 2. Chiama switchSettingType('author', $value)
// 3. Determina il tipo di valore (int, BaseEntity, null)
// 4. Gestisce la conversione e il tracking delle modifiche
```

**Comportamento:**
- Valida che la proprietà esista
- Delega a `switchSettingType()` la gestione specifica del tipo
- Aggiorna il flag `$modified` quando necessario
- Rimuove l'entità da `ProcessedEntitiesCollection` se modificata
- Lancia `InvalidPropertyException` se la proprietà non esiste

**Gestione dei tipi:**
- **Intero (ID)**: memorizzato in `$foreignKeyIndexes` per il lazy loading, o sostituito dall'entità se è già in cache
- **BaseEntity**: assegnata direttamente
- **null**: rimuove la relazione
- **Altri tipi**: assegnati direttamente, con il tracking delle modifiche

#### `__isset($name)` - Magic Isset

Intercetta i controlli di esistenza delle proprietà:

```php
if (isset($post->author)) {
    echo $post->author->name;
}

// Cosa succede internamente:
// 1. Verifica che la proprietà esista nella classe finale
// 2. Attiva il lazy loading se necessario
// 3. Restituisce true/false in base allo stato della proprietà
```

**Comportamento:**
- Attiva il lazy loading prima di verificare `isset()`, per cui il controllo è coerente anche per le relazioni non ancora caricate
- Per le proprietà inesistenti restituisce `false`

#### Tracking delle Modifiche

Il sistema di tracking monitora le modifiche alle proprietà per determinare se l'entità deve essere aggiornata al salvataggio:

```php
$post = $postModel->getEntityById(1);
echo $post->modified; // false - appena caricata

$post->title = "Nuovo Titolo";
echo $post->modified; // true - proprietà modificata

$post->author = 5;
echo $post->modified; // true - foreign key modificata
```

**Quando una proprietà è considerata modificata:**

1. **Proprietà builtin o enum**: valore diverso dal precedente
2. **Foreign key (int)**: ID diverso dal precedente
3. **Foreign key (entità)**: entità diversa dalla precedente
4. **Tipi custom** (`SismaDate`, `SismaDateTime`, `SismaTime`, `SismaJson`): valore diverso secondo `equals()`
5. **null**: proprietà precedentemente valorizzata

**Integrazione con DataMapper:**

Il tracking si integra con `ProcessedEntitiesCollection`, che evita di salvare più volte la stessa entità durante un salvataggio a cascata:

```php
$post->title = "Titolo A";
// l'entità viene rimossa da ProcessedEntitiesCollection

$dataMapper->save($post); // l'entità modificata viene salvata di nuovo
```

#### Esempio Completo

```php
$postModel = new PostModel();
$post = $postModel->getEntityById(1);

// __isset e __get: verifica l'autore e lo carica in modo lazy
if (isset($post->author)) {
    echo "Autore: " . $post->author->name;
}

// __set con un ID: tracking attivato
$post->author = 5;
echo $post->modified; // true

// __set con un'entità già in memoria: nessuna query
$userModel = new UserModel();
$post->author = $userModel->getEntityById(5);

// __set con null: rimuove la relazione (la proprietà deve essere nullable)
$post->category = null;

$dataMapper = new DataMapper();
$dataMapper->save($post);
```

### Metodi Magici dei Model (Dynamic Query Methods)

Tutti i model di SismaFramework ereditano da `BaseModel` il metodo magico `__call()`, che genera **dinamicamente** metodi di query basati sulle proprietà dell'entità. Questi metodi **non esistono fisicamente** nel codice ma vengono interpretati a partire dal nome del metodo chiamato. `SelfDependentModel` aggiunge i metodi basati sull'entità padre (vedi [ORM](orm.md)).

#### Come Funziona il Parsing

Il metodo `__call()` intercetta le chiamate a metodi inesistenti e le interpreta secondo questa convenzione:

```
{azione}By{Proprietà}[And{AltraProprietà}]*
```

**Azioni supportate:**
- `get` → restituisce `SismaCollection`
- `count` → restituisce `int`
- `delete` → restituisce `bool`

Le proprietà possono essere foreign key, scalari, enumerazioni o tipi custom.

**Esempi di metodi generati automaticamente:**

```php
class Post extends BaseEntity
{
    protected int $id;
    protected string $title;
    protected User $author;               // foreign key
    protected ?Category $category = null; // foreign key nullable
    protected PostStatus $status;         // enumerazione

    // setPropertyDefaultValue() e setEncryptedProperties() omessi
}

class PostModel extends DependentModel
{
    // getEntityName() e appendSearchCondition() omessi:
    // nessun metodo getBy/countBy/deleteBy definito esplicitamente
}

$postModel = new PostModel();
$user = $userModel->getEntityById(5);
$category = $categoryModel->getEntityById(3);

// 1. Query per una foreign key
$posts = $postModel->getByAuthor($user);
// SELECT … FROM post WHERE post.author_id = ?

// 2. Conteggio per una foreign key
$count = $postModel->countByAuthor($user);
// SELECT COUNT(*) … FROM post WHERE post.author_id = ?

// 3. Cancellazione per una foreign key
$deleted = $postModel->deleteByAuthor($user);
// DELETE FROM post WHERE post.author_id = ?

// 4. Query per più proprietà (AND)
$posts = $postModel->getByAuthorAndCategory($user, $category);
// SELECT … FROM post WHERE post.author_id = ? AND post.category_id = ?

// 5. Query per una proprietà non relazionale
$posts = $postModel->getByStatus(PostStatus::published);
// SELECT … FROM post WHERE post.status = ?

// 6. Query con foreign key null (solo per le proprietà nullable)
$uncategorizedPosts = $postModel->getByCategory(null);
// SELECT … FROM post WHERE post.category_id IS NULL
```

#### Anatomia del Parsing

Il metodo `__call()` esegue questi passaggi:

1. **Divisione del nome**: `getByAuthorAndCategory` → `['get', 'AuthorAndCategory']`
2. **Azione**: `get` determina l'operazione (get/count/delete)
3. **Proprietà**: `AuthorAndCategory` → `['author', 'category']`
4. **Validazione**: verifica che ogni argomento sia del tipo della proprietà corrispondente, o `null` se la proprietà è nullable
5. **Query**: costruisce una condizione WHERE per ogni proprietà, unite in AND
6. **Esecuzione**: delega a `getEntityCollectionByProperties()`, `countEntityCollectionByProperties()` o `deleteEntityCollectionByProperties()`

#### Signature dei Metodi Generati

```php
// Pattern generale:
public function {azione}By{Proprietà}[And{AltraProprietà}]*(
    mixed ${proprietà},
    [mixed ${altraProprietà},]*
    ?string $searchKey = null,
    ?array $order = null,      // solo per get*
    ?int $offset = null,       // solo per get*
    ?int $limit = null         // solo per get*
): SismaCollection|int|bool;

// Esempi concreti:
public function getByAuthor(
    User $author,
    ?string $searchKey = null,
    ?array $order = null,
    ?int $offset = null,
    ?int $limit = null
): SismaCollection;

public function countByAuthorAndCategory(
    User $author,
    ?Category $category,
    ?string $searchKey = null
): int;

public function deleteByStatus(
    PostStatus $status,
    ?string $searchKey = null
): bool;
```

#### Validazione degli Argomenti

Il sistema verifica che gli argomenti corrispondano al tipo delle proprietà:

```php
// ✅ VALIDO
$posts = $postModel->getByAuthor($user);

// ✅ VALIDO: category è nullable, null diventa IS NULL
$posts = $postModel->getByCategory(null);

// ❌ INVALIDO: author non è nullable
$posts = $postModel->getByAuthor(null);
// Lancia InvalidArgumentException

// ❌ INVALIDO: tipo errato
$posts = $postModel->getByAuthor($category);
// Lancia InvalidArgumentException
```

#### Combinazione con searchKey

Tutti i metodi magici accettano il parametro opzionale `$searchKey`, che il model applica con `appendSearchCondition()`:

```php
class PostModel extends DependentModel
{
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        $query->appendOpenBlock()
            ->appendCondition('title', ComparisonOperator::like, Placeholder::placeholder)
            ->appendOr()
            ->appendCondition('content', ComparisonOperator::like, Placeholder::placeholder)
            ->appendCloseBlock();
        $bindValues[] = "%{$searchKey}%";
        $bindValues[] = "%{$searchKey}%";
        $bindTypes[] = DataType::typeString;
        $bindTypes[] = DataType::typeString;
    }
}

// Utilizzo:
$posts = $postModel->getByAuthor($user, 'framework');
// SELECT … FROM post WHERE post.author_id = ? AND (post.title LIKE ? OR post.content LIKE ?)
```

`appendSearchCondition()` viene chiamato dopo `setWhere()` e, quando ci sono condizioni sulle proprietà, dopo `appendAnd()`: il metodo deve aggiungere solo le proprie condizioni. Le condizioni in OR vanno racchiuse in un blocco, altrimenti la precedenza degli operatori le separa dalle condizioni sulle proprietà.

#### Esempi Pratici

```php
class RelationalQueryService
{
    public function getUserContent(User $user): array
    {
        $postModel = new PostModel();
        $commentModel = new CommentModel();

        return [
            'posts_count' => $postModel->countByAuthor($user),
            'comments_count' => $commentModel->countByAuthor($user),
            'posts' => $postModel->getByAuthor($user),
            'comments' => $commentModel->getByAuthor($user),
        ];
    }

    public function getFeaturedPostsByAuthor(User $author): SismaCollection
    {
        $postModel = new PostModel();
        return $postModel->getByAuthorAndStatus($author, PostStatus::featured);
    }

    public function getUncategorizedPosts(): SismaCollection
    {
        $postModel = new PostModel();
        return $postModel->getByCategory(null);
    }
}
```

#### Vantaggi dei Metodi Magici

✅ **Zero boilerplate**: nessun metodo da scrivere manualmente
✅ **Validazione dei tipi**: gli argomenti vengono verificati
✅ **Consistenza**: naming convention uniforme
✅ **Flessibilità**: combinazioni di più proprietà in AND
✅ **Null-safe**: `IS NULL` per le proprietà nullable

#### Limitazioni

⚠️ **Nessun autocompletamento nell'IDE**: i metodi non esistono fisicamente
⚠️ **Solo AND**: non supportano OR tra le proprietà
⚠️ **Convenzione rigida**: il nome deve seguire lo schema esatto
⚠️ **Debugging**: stack trace meno chiari per gli errori nei metodi magici

#### Best Practices

1. **Documenta i metodi disponibili** nel PHPDoc del model:
```php
/**
 * @method SismaCollection getByAuthor(User $author, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null)
 * @method int countByAuthor(User $author, ?string $searchKey = null)
 * @method bool deleteByAuthor(User $author, ?string $searchKey = null)
 * @method SismaCollection getByCategory(?Category $category, ?string $searchKey = null, ?array $order = null, ?int $offset = null, ?int $limit = null)
 */
class PostModel extends DependentModel
{
    // ...
}
```

2. **Usa nomi di proprietà chiari** nelle entità
3. **Testa i metodi magici** con unit test
4. **Scrivi metodi espliciti** per le logiche complesse

### Metodi Magici in ReferencedEntity (Gestione Collezioni)

Le entità che estendono `ReferencedEntity` hanno metodi magici aggiuntivi per gestire le **collezioni inverse** (one-to-many). Questi metodi sono interpretati da `__call()` e permettono di manipolare le entità figlie senza codice boilerplate.

#### Come Funzionano le Collezioni

Quando un'entità è referenziata da altre entità (es. `User` referenziato da `Post.author`), la `ReferencedEntity` espone una collezione inversa accessibile tramite una naming convention:

```php
// Naming convention per le collezioni:
// {nomeEntitàFiglia}Collection{NomeProprietàForeignKey}

// Esempio: User ha molti Post tramite Post.author
$user->postCollectionAuthor; // SismaCollection di Post

// Se c'è una sola foreign key, il suffisso può essere omesso:
$user->postCollection; // equivalente se Post ha solo 'author' come FK verso User
```

#### Metodi Magici Disponibili

**1. `set{NomeCollezione}(SismaCollection $collection)` - Sostituisce la collezione**

```php
$user = $userModel->getEntityById(1);

$newPosts = new SismaCollection(Post::class);
$newPosts->append($post1);
$newPosts->append($post2);

$user->setPostCollectionAuthor($newPosts);

// Internamente:
// - la collezione viene assegnata
// - per ogni post della collezione viene impostato $post->author = $user
```

**2. `add{NomeEntità}(BaseEntity $entity)` - Aggiunge un'entità alla collezione**

```php
$user = $userModel->getEntityById(1);
$newPost = new Post();
$newPost->title = "Nuovo articolo";

$user->addPost($newPost);

// Internamente:
// - se esiste già un post con lo stesso ID, viene sostituito
// - altrimenti viene aggiunto alla collezione
// - viene impostato $newPost->author = $user
```

`add{NomeEntità}()` usa la forma abbreviata della collezione (`postCollection`), per cui è disponibile solo quando l'entità figlia ha una sola foreign key verso l'entità referenziata.

**3. `count{NomeCollezione}()` - Conta le entità nella collezione**

```php
$user = $userModel->getEntityById(1);

$postCount = $user->countPostCollectionAuthor();

// Internamente:
// - viene creato il model corrispondente (PostModel)
// - viene chiamato il metodo magico countBy{ForeignKey}() (es. countByAuthor())
// - restituisce il numero di risultati
```

#### Accesso Diretto alle Collezioni

Oltre ai metodi magici, le collezioni sono accessibili tramite `__get()`, `__set()` e `__isset()`:

```php
// Lettura (lazy loading se necessario)
$posts = $user->postCollectionAuthor;

// Scrittura diretta
$user->postCollectionAuthor = $newPostsCollection;

// Verifica esistenza
if (isset($user->postCollectionAuthor)) {
    foreach ($user->postCollectionAuthor as $post) {
        echo $post->title;
    }
}
```

#### Esempio Completo

```php
$userModel = new UserModel();
$user = $userModel->getEntityById($userId);

// 1. Conta i post (query COUNT)
echo "L'utente ha {$user->countPostCollectionAuthor()} post";

// 2. Accedi alla collezione (lazy loading)
foreach ($user->postCollectionAuthor as $post) {
    echo $post->title;
}

// 3. Aggiungi un nuovo post
$newPost = new Post();
$newPost->title = "Post aggiunto tramite metodo magico";
$newPost->content = "Contenuto...";
$user->addPost($newPost);

// 4. Salva l'utente e, a cascata, i post della collezione
$dataMapper = new DataMapper();
$dataMapper->save($user);
```

#### Validazione Automatica

```php
// ✅ VALIDO
$user->setPostCollectionAuthor(new SismaCollection(Post::class));

// ❌ INVALIDO: non è una SismaCollection
$user->setPostCollectionAuthor([$post]);
// Lancia InvalidArgumentException

// ❌ INVALIDO: collezione di un tipo diverso assegnata alla proprietà
$user->postCollectionAuthor = new SismaCollection(Comment::class);
// Lancia InvalidArgumentException

// ❌ INVALIDO: non è un'entità
$user->addPost("stringa");
// Lancia InvalidArgumentException
```

#### Differenza tra `count{NomeCollezione}()` e `->count()`

```php
// countPostCollectionAuthor(): query COUNT sul database,
// non carica le entità in memoria
$count = $user->countPostCollectionAuthor();

// postCollectionAuthor->count(): conta gli elementi caricati,
// richiede prima il caricamento di tutte le entità
$count = $user->postCollectionAuthor->count();
```

Per le collezioni grandi è preferibile `count{NomeCollezione}()`, che non carica le entità in memoria.

### Lazy Loading: Come Funziona Internamente

Il lazy loading in SismaFramework è implementato attraverso i **metodi magici** di PHP (`__get`, `__set`, `__isset`) nella classe `BaseEntity`. Le relazioni vengono caricate solo quando vengono effettivamente lette.

#### Meccanismo Interno

Quando un'entità viene caricata dal database, le **foreign key** vengono memorizzate come **semplici ID** nell'array `$foreignKeyIndexes`, senza caricare le entità correlate (a meno che non siano già in cache). Quando si legge la proprietà, `__get()`:

1. **Verifica** se la proprietà è una foreign key non ancora convertita
2. **Carica** l'entità correlata dal database usando il DataMapper
3. **Converte** l'ID in un'istanza dell'entità
4. **Rimuove** l'ID dall'array `$foreignKeyIndexes`
5. **Restituisce** l'entità caricata

#### Stati di una Foreign Key

```php
// Stato 1: ID non convertito (lazy loading pronto)
$post->author = 5;
// Internamente: $foreignKeyIndexes['author'] = 5

// Stato 2: entità caricata
$author = $post->author; // lazy loading
// Internamente: $author è un'istanza di User, foreignKeyIndexes['author'] rimosso

// Stato 3: cache hit
$post->author = 5; // User con ID 5 già in cache
// L'entità viene recuperata dalla cache invece che dal database
```

#### Il Ruolo del Metodo `switchSettingType()`

Il metodo `switchSettingType()` di `BaseEntity` gestisce tre scenari quando si imposta una foreign key:

**1. Assegnazione di un ID intero:**
```php
$post->author = 5;

// Internamente:
// - se User con ID 5 è già in cache, viene assegnato subito
// - altrimenti l'ID viene memorizzato in $foreignKeyIndexes['author']
// - la proprietà $author viene rimossa con unset (lazy loading attivato)
// - al primo accesso, __get() caricherà l'entità dal database
```

**2. Assegnazione di un'entità:**
```php
$post->author = $userModel->getEntityById(5);

// Internamente:
// - l'entità viene assegnata direttamente alla proprietà
// - l'ID viene rimosso da $foreignKeyIndexes se presente
// - il flag 'modified' viene aggiornato
```

**3. Assegnazione null:**
```php
$post->category = null;

// Internamente:
// - la proprietà viene impostata a null
// - l'ID viene rimosso da $foreignKeyIndexes
// - il flag 'modified' viene aggiornato
```

#### Vantaggi del Lazy Loading

✅ **Performance**: carica solo i dati effettivamente necessari
✅ **Semplicità**: API trasparente, nessun metodo speciale da chiamare
✅ **Memoria**: riduce l'occupazione di memoria per le relazioni non utilizzate
✅ **Caching**: integrazione automatica con la cache delle entità

#### Problema N+1 ed Eager Loading

```php
// ❌ N+1 query: una query per ogni autore non ancora in cache
$posts = $postModel->getEntityCollection();
foreach ($posts as $post) {
    echo $post->author->name;
}

// ✅ Eager loading: gli autori vengono caricati con una JOIN nella stessa query
$posts = $postModel->getEntityCollectionWithRelations(['author']);
foreach ($posts as $post) {
    echo $post->author->name; // nessuna query aggiuntiva
}
```

Per le relazioni annidate, le collection e le query personalizzate con JOIN vedi [JOIN con Idratazione Gerarchica](join-eager-loading.md).

#### Integrazione con Cache

Il lazy loading si integra automaticamente con la cache delle entità:

```php
use SismaFramework\Orm\HelperClasses\Cache;

// Prima lettura: carica dal database e mette in cache
$post->author; // SELECT … FROM user WHERE user.id = ?

// Letture successive dello stesso autore, anche da altre entità: cache hit
$otherPost->author; // nessuna query

// Verifica presenza in cache
if (Cache::checkEntityPresenceInCache(User::class, 5)) {
    $user = Cache::getEntityById(User::class, 5);
}
```

#### Best Practices

1. **Usa l'eager loading** quando iteri su collezioni e accedi alle relazioni
2. **Profila le query** con la barra di debug per individuare i problemi N+1
3. **Lascia il lazy loading** per le relazioni lette solo in alcuni casi
4. **Documenta le dipendenze** delle entità per il team

---

## Performance e Ottimizzazioni

### Cache delle Entità

Con la costante di configurazione `ORM_CACHE` attiva, ogni entità caricata viene registrata nella cache delle entità (identity map) per la durata della richiesta: una seconda lettura della stessa entità, tramite lazy loading o tramite JOIN, restituisce lo stesso oggetto senza interrogare il database.

```php
$post = $postModel->getEntityById(1);    // query
$samePost = $postModel->getEntityById(1); // nessuna query, stesso oggetto
```

La cache vale per la singola richiesta: per conservare dati tra richieste diverse si usa un sistema di cache applicativo, salvando valori semplici (array, scalari) e non le entità, che dipendono dallo stato della richiesta.

### Operazioni Batch

```php
class PostModel extends DependentModel
{
    public function deletePostsByIds(array $postIds): bool
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendCondition('id', ComparisonOperator::in, array_fill(0, count($postIds), Placeholder::placeholder));

        return $this->dataMapper->deleteBatch($query, $postIds, array_fill(0, count($postIds), DataType::typeInteger));
    }

    public function incrementViewCounts(array $postIds): bool
    {
        $placeholders = implode(', ', array_fill(0, count($postIds), '?'));
        $sql = "UPDATE post SET view_count = view_count + 1 WHERE id IN ({$placeholders})";
        return $this->initQuery()->getAdapter()->execute($sql, $postIds, array_fill(0, count($postIds), DataType::typeInteger));
    }
}
```

`deleteBatch()` esegue una sola `DELETE` invece di cancellare le entità una alla volta. Per gli aggiornamenti in blocco si esegue SQL diretto tramite l'adapter, sempre con i valori legati.

Per creare più entità in modo atomico si usa una transazione (vedi [Gestione Transazioni](#gestione-transazioni)):

```php
$dataMapper = new DataMapper();
$dataMapper->startTransaction();
foreach ($postsData as $data) {
    $post = new Post();
    $post->title = $data['title'];
    $post->content = $data['content'];
    $post->author = $data['author_id'];
    $dataMapper->save($post);
}
$dataMapper->commitTransaction();
```

### Conteggi e Aggregazioni

```php
$postModel = new PostModel();

$statistics = [
    'total_posts' => $postModel->countEntityCollection(),
    'published_posts' => $postModel->countByStatus(PostStatus::published),
    'posts_per_author' => $postModel->getPostCountPerAuthor(),
];
```

`DataMapper::find()` restituisce entità. Per le aggregazioni si esegue la query con `select()` dell'adapter, che restituisce righe di tipo `StandardEntity`:

```php
class PostModel extends DependentModel
{
    public function getPostCountPerAuthor(): array
    {
        $query = $this->initQuery();
        $query->setCount('')
            ->appendColumn('author_id')
            ->setGroupBy(['author_id'])
            ->setOrderBy(['_numrows' => Indexing::desc]);
        $query->close();

        $postCountPerAuthor = [];
        foreach ($query->getAdapter()->select($query->getCommandToExecute()) as $row) {
            $postCountPerAuthor[$row->author_id] = $row->_numrows;
        }
        return $postCountPerAuthor;
    }
}
```

`setCount()` genera la colonna `COUNT(…) as _numrows`; le altre funzioni di aggregazione (`setAVG()`, `setMax()`, `setMin()`, `setSum()`) accettano un alias per la colonna calcolata.

---

## Organizzazione del Codice di Accesso ai Dati

### Repository Pattern

Le query personalizzate restano nel model, e il repository le espone al resto dell'applicazione:

```php
interface PostRepositoryInterface
{
    public function findById(int $id): ?Post;
    public function findByAuthor(User $author): SismaCollection;
    public function findPublished(): SismaCollection;
    public function save(Post $post): bool;
    public function delete(Post $post): bool;
}

class PostRepository implements PostRepositoryInterface
{
    private PostModel $model;

    public function __construct(private DataMapper $dataMapper = new DataMapper())
    {
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
        return $this->model->getPublishedBefore(new SismaDateTime());
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

class PostModel extends DependentModel
{
    public function getPublishedBefore(SismaDateTime $date): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder)
            ->appendAnd()
            ->appendCondition('publishedAt', ComparisonOperator::lessOrEqual, Placeholder::placeholder);
        $query->setOrderBy(['publishedAt' => Indexing::desc]);

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, [PostStatus::published, $date], [DataType::typeEnumeration, DataType::typeDate]);
    }
}
```

### Service Layer con Domain Logic

```php
class BlogService
{
    public function __construct(
        private PostRepository $postRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService
    ) {
    }

    public function publishPost(int $postId, int $authorId): bool
    {
        $post = $this->postRepository->findById($postId);
        $author = $this->userRepository->findById($authorId);

        if ($post === null || $author === null) {
            throw new \InvalidArgumentException('Post or Author not found');
        }

        if ($post->author->id !== $author->id) {
            throw new \DomainException('Author mismatch');
        }

        $post->status = PostStatus::published;
        $post->publishedAt = new SismaDateTime();

        $success = $this->postRepository->save($post);
        if ($success) {
            $this->notificationService->notifySubscribers($post);
        }
        return $success;
    }

    public function getAuthorStatistics(User $author): array
    {
        $posts = $this->postRepository->findByAuthor($author);

        $statistics = [
            'total_posts' => $posts->count(),
            'published_posts' => 0,
            'draft_posts' => 0,
            'total_views' => 0,
        ];

        foreach ($posts as $post) {
            if ($post->status === PostStatus::published) {
                $statistics['published_posts']++;
            } else {
                $statistics['draft_posts']++;
            }
            $statistics['total_views'] += $post->viewCount;
        }

        return $statistics;
    }
}
```

---

## Transazioni e Consistenza

### Gestione Transazioni

`DataMapper::save()` è già atomico: il salvataggio di un'entità e, a cascata, delle entità collegate avviene in una transazione, annullata se uno dei salvataggi fallisce. Per rendere atomiche più operazioni si apre una transazione esplicita:

```php
class OwnershipTransferService
{
    public function __construct(private DataMapper $dataMapper = new DataMapper())
    {
    }

    public function transferPostOwnership(Post $post, User $newOwner): void
    {
        $oldOwner = $post->author;

        $this->dataMapper->startTransaction();

        $post->author = $newOwner;
        $this->dataMapper->save($post);

        $transferLog = new OwnershipTransfer();
        $transferLog->post = $post;
        $transferLog->oldOwner = $oldOwner;
        $transferLog->newOwner = $newOwner;
        $transferLog->transferDate = new SismaDateTime();
        $this->dataMapper->save($transferLog);

        $this->dataMapper->commitTransaction();
    }
}
```

**Comportamento:**
- `startTransaction()` apre la transazione e restituisce `true`; se una transazione è già aperta restituisce `false` e i salvataggi successivi entrano in quella esistente
- i salvataggi eseguiti dentro la transazione non la chiudono: viene confermata solo da `commitTransaction()`
- se un salvataggio fallisce, il `DataMapper` annulla l'intera transazione e solleva una `DataMapperException`
- il `DataMapper` non espone un metodo di rollback: se dentro la transazione si verifica un'eccezione diversa da un salvataggio fallito, la transazione resta aperta e il database la annulla alla chiusura della connessione. In quel caso non vanno eseguite altre scritture nella stessa richiesta

### Data Validation e Business Rules

```php
class PostValidationService
{
    public function validatePost(Post $post): array
    {
        $errors = [];

        if (empty($post->title)) {
            $errors[] = 'Title is required';
        } elseif (strlen($post->title) > 255) {
            $errors[] = 'Title too long';
        }

        if ($post->status === PostStatus::published && empty($post->content)) {
            $errors[] = 'Content required for published posts';
        }

        if ($post->author->active === false) {
            $errors[] = 'Invalid author';
        }

        return $errors;
    }
}
```

Per la validazione dei dati provenienti dai form vedi [Form](forms.md).

---

## Query di Reporting

### JOIN e SQL Diretto

Il query builder supporta le JOIN (vedi [JOIN con Idratazione Gerarchica](join-eager-loading.md)), sia per l'eager loading delle relazioni sia nelle query personalizzate. Per le query di reporting che non restituiscono entità si esegue la query, costruita con il query builder o scritta in SQL, tramite `select()` dell'adapter, che restituisce righe di tipo `StandardEntity`:

```php
use SismaFramework\Orm\BaseClasses\BaseAdapter;

class ReportingService
{
    public function getPublishedPostsWithAuthorNames(): array
    {
        $sql = "
            SELECT p.title, u.name AS author_name
            FROM post p
            INNER JOIN user u ON p.author_id = u.id
            WHERE p.status = ?
            ORDER BY p.published_at DESC
        ";

        $rows = [];
        foreach (BaseAdapter::getDefault()->select($sql, [PostStatus::published->value], [DataType::typeString]) as $row) {
            $rows[] = ['title' => $row->title, 'author' => $row->author_name];
        }
        return $rows;
    }

    public function getPostStatistics(): array
    {
        $rows = [];
        foreach (BaseAdapter::getDefault()->select("SELECT * FROM post_statistics_view") as $row) {
            $rows[] = get_object_vars($row);
        }
        return $rows;
    }
}
```

### Quando Usare SQL Diretto

È appropriato usare SQL diretto per:
- query di **reporting complesse** con aggregazioni su più tabelle
- costrutti non supportati dal query builder, come le JOIN su subquery, `IN (subquery)` ed `EXISTS`
- **operazioni in blocco** su grandi insiemi di dati
- **stored procedure** e viste esistenti

Anche con l'SQL diretto i valori vanno passati come valori legati, mai concatenati nella stringa della query.

---

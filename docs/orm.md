# Introduzione all'ORM (Data Mapper)

L'**Object-Relational Mapper (ORM)** di SismaFramework è un potente componente che ti permette di interagire con il database utilizzando oggetti PHP, invece di scrivere query SQL manualmente.

L'ORM si basa sul pattern **Data Mapper**, che garantisce una netta separazione tra la logica di business (gli oggetti del tuo dominio) e la logica di persistenza dei dati (come vengono salvati e recuperati dal database).

## I Componenti Chiave

L'interazione con l'ORM si basa su due tipi di classi principali:

1.  **Entità (Entity):**
    Le entità sono classi PHP che rappresentano una riga di una tabella del database. Contengono le proprietà (che corrispondono alle colonne della tabella) e i metodi che definiscono la logica di business.

2.  **Modelli (Model):**
    I modelli sono le classi responsabili della comunicazione con il database. Forniscono metodi per eseguire operazioni CRUD e per costruire query. Un modello sa come recuperare i dati dal database e popolarli in un'entità.

## Le Entità in Dettaglio

Le entità sono il cuore del tuo modello di dominio. Devono estendere una delle classi base fornite dall'ORM (`BaseEntity`, `ReferencedEntity`, `SelfReferencedEntity`).

### Proprietà e Tipi

Le proprietà di un'entità devono essere dichiarate `protected` e la **tipizzazione è obbligatoria**. I tipi supportati sono:
- Tipi nativi (`int`, `string`, `float`, `bool`).
- `SismaDatetime` per le date.
- `BackedEnum` per i vocabolari chiusi.
- Altre classi `Entity` per le relazioni (chiavi esterne).

```php
namespace MyModule\App\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\HelperClasses\SismaDatetime;

class Post extends BaseEntity
{
    protected int $id;
    protected string $title;
    protected ?string $content = null;
    protected SismaDatetime $publicationDate;
    protected User $author; // Relazione con l'entità User

    protected function setEncryptedProperties(): void { }
    protected function setPropertyDefaultValue(): void { }
}
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

-   `getEntityById(int $id)`: Trova un'entità tramite il suo ID.
-   `getEntityCollection(?string $searchKey, ?array $order, ?int $offset, ?int $limit)`: Recupera una collezione di entità, con opzioni di filtro, ordinamento e paginazione.
-   `countEntityCollection(?string $searchKey)`: Conta le entità, opzionalmente filtrate.
-   `deleteEntityById(int $id)`: Elimina un'entità dal suo ID.

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

-   `getEntityCollectionByEntity(array $referencedEntities, ...)`: Trova entità che corrispondono a una o più entità referenziate.

**Esempio:** Trova tutti i post di un certo utente.
```php
use MyModule\App\Models\PostModel; // Deve estendere DependentModel

$postModel = new PostModel($this->dataMapper);
$user = $userModel->getEntityById(1);

// Trova tutti i post dove 'author' è l'utente con ID 1
$userPosts = $postModel->getEntityCollectionByEntity(['author' => $user]);
```

### Metodi Magici

Per semplificare ulteriormente le query basate su relazioni, puoi usare una sintassi magica.

```php
// Equivalente a getEntityCollectionByEntity(['author' => $user])
$userPosts = $postModel->getByAuthor($user);

// Equivalente a countEntityCollectionByEntity(['author' => $user])
$postCount = $postModel->countByAuthor($user);

// Equivalente a deleteEntityCollectionByEntity(['author' => $user])
$postModel->deleteByAuthor($user);
```

La sintassi è `get|count|delete` + `By` + `NomeProprietàInCamelCase`.

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
        $bindTypes = []; // Il DataMapper può inferire i tipi più comuni
        
        // 5. Esegui la query e restituisci la collezione
        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
```

---

[Indice](index.md) | Precedente: [Gestione dei Form](forms.md) | Successivo: [Funzionalità Avanzate dell'ORM](orm-additional-features.md)

# Guida Introduttiva: Creare un Mini-Blog

Questa guida ti accompagnerà nella creazione di una semplice applicazione di blog da zero utilizzando SismaFramework. Imparerai a:

* Configurare un nuovo progetto.
* Creare un modulo personalizzato.
* Definire entità e relazioni con l'ORM.
* Popolare il database con dati di prova (fixtures).
* Creare controller e viste per mostrare i contenuti.

Alla fine di questa guida, avrai una pagina che elenca tutti gli articoli del blog e una pagina per visualizzare ogni singolo articolo.

## 1. Prerequisiti

Prima di iniziare, assicurati di aver completato la [guida di installazione](installation.md) e la [configurazione base](installation.md#passo-3-configura-i-file-principali). Chiameremo il nostro progetto `mini-blog`.

Se hai già un progetto SismaFramework configurato, puoi procedere direttamente al passo successivo. Altrimenti, [installa il framework](installation.md) prima di continuare.

## 2. Creare il Modulo `Blog`

SismaFramework è modulare. Creiamo un modulo `Blog` per contenere tutta la logica della nostra applicazione.

1. **Crea la struttura delle cartelle**: All'interno della root del progetto (`mini-blog/`), crea le seguenti cartelle:
   
   ```
   Blog/
   └── Application/
       ├── Controllers/
       ├── Entities/
       ├── Fixtures/
       ├── Models/
       └── Views/
           └── post/
   ```

2. **Registra il modulo**: Apri `Config/config.php` e aggiungi `Blog` all'array `MODULE_FOLDERS`.

   ```php
   // in Config/config.php
   const MODULE_FOLDERS = [
       'Blog',           // Il tuo nuovo modulo
   ];
   ```

   📝 **Nota**: Il modulo `'SismaFramework'` (presente nel config di default) contiene l'applicazione di esempio del framework (cartella `Sample/`). Puoi scegliere di:
   - **Mantenerlo** se vuoi accedere agli esempi durante lo sviluppo
   - **Rimuoverlo** se preferisci un setup più pulito

   ```php
   // Opzione A: Con esempi del framework
   const MODULE_FOLDERS = [
       'SismaFramework', // Opzionale: per accedere agli esempi
       'Blog',
   ];

   // Opzione B: Solo i tuoi moduli (setup pulito)
   const MODULE_FOLDERS = [
       'Blog',
   ];
   ```

## 3. Creare le Entità

Le entità rappresentano i dati della nostra applicazione. Creeremo due entità: `User` e `Post`.

**`Blog/Application/Entities/User.php`**

```php
<?php
namespace Blog\Application\Entities;

use SismaFramework\Orm\ExtendedClasses\ReferencedEntity;

class User extends ReferencedEntity
{
    protected int $id;
    protected string $username;
    protected string $email;

    protected function setEncryptedProperties(): void {}
    protected function setPropertyDefaultValue(): void {}
}
```

**`Blog/Application/Entities/Post.php`**

```php
<?php
namespace Blog\Application\Entities;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\CustomTypes\SismaDateTime;

class Post extends BaseEntity
{
    protected int $id;
    protected string $title;
    protected string $content;
    protected SismaDateTime $publicationDate;
    protected User $author; // Relazione con l'entità User

    protected function setEncryptedProperties(): void {}
    protected function setPropertyDefaultValue(): void {}
}
```

## 4. Creare i Modelli

I modelli ci aiuteranno a interagire con il database per recuperare le entità.

**`Blog/Application/Models/UserModel.php`**

```php
<?php
namespace Blog\Application\Models;

use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Orm\HelperClasses\Query;
use Blog\Application\Entities\User;

class UserModel extends BaseModel
{
    protected static function getEntityName(): string
    {
        return User::class;
    }
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void {}
}
```

**`Blog/Application/Models/PostModel.php`**

```php
<?php
namespace Blog\Application\Models;

use SismaFramework\Orm\ExtendedClasses\DependentModel;
use SismaFramework\Orm\HelperClasses\Query;
use Blog\Application\Entities\Post;

class PostModel extends DependentModel
{
    protected static function getEntityName(): string
    {
        return Post::class;
    }
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void {}
}
```

## 5. Creare lo Schema del Database

SismaFramework non gestisce (ancora) automaticamente le migrazioni del database. Dovrai creare le tabelle manualmente. Esegui queste query SQL sul tuo database:

```sql
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `publication_date` datetime NOT NULL,
  `author_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `post_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 6. Popolare il Database con le Fixtures

Creiamo delle fixture per inserire un utente e alcuni articoli di prova. Questo sistema gestisce automaticamente l'ordine di creazione.

#### Fixture per l'Utente

**`Blog/Application/Fixtures/UserFixture.php`**

```php
<?php
namespace Blog\Application\Fixtures;

use SismaFramework\Core\BaseClasses\BaseFixture;
use Blog\Application\Entities\User;

class UserFixture extends BaseFixture
{
    protected function setDependencies(): void {}

    public function setEntity(): void
    {
        $user = new User();
        $user->setUsername('Mario Rossi');
        $user->setEmail('mario.rossi@example.com');
        $this->addEntity($user);
    }
}
```

#### Fixture per gli Articoli

**`Blog/Application/Fixtures/PostFixture.php`**

```php
<?php
namespace Blog\Application\Fixtures;

use SismaFramework\Core\BaseClasses\BaseFixture;
use Blog\Application\Entities\Post;
use SismaFramework\Orm\CustomTypes\SismaDateTime;

class PostFixture extends BaseFixture
{
    protected function setDependencies(): void
    {
        $this->addDependency(UserFixture::class);
    }

    public function setEntity(): void
    {
        $user = $this->getEntityByFixtureName(UserFixture::class);

        for ($i = 1; $i <= 3; $i++) {
            $post = new Post();
            $post->setTitle('Il mio articolo di prova #' . $i);
            $post->setContent('Questo è il contenuto dell\'articolo di test...');
            $post->setPublicationDate(new SismaDateTime());
            $post->setAuthor($user);
            $this->addEntity($post);
        }
    }
}
```

Per eseguire la fixture, visita l'URL `/fixtures` nel tuo browser (assicurati che `DEVELOPMENT_ENVIRONMENT` sia `true` in `config.php`).

Il framework eseguirà prima `UserFixture` e poi `PostFixture`, garantendo che l'utente esista prima di creare i post che lo referenziano.

## 7. Creare il Controller

Il controller gestirà le richieste per visualizzare gli articoli.

**`Blog/Application/Controllers/PostController.php`**

```php
<?php
namespace Blog\Application\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use Blog\Application\Models\PostModel;
use Blog\Application\Entities\Post;

class PostController extends BaseController
{
    // URL: /post/index
    public function index(): Response
    {
        $postModel = new PostModel($this->dataMapper);
        $this->vars['posts'] = $postModel->getEntityCollection();
        $this->vars['pageTitle'] = 'Il Mio Blog';
        return $this->render->generateView('post/index', $this->vars);
    }

    // URL: /post/show/id/1
    public function show(Post $post): Response
    {
        $this->vars['post'] = $post;
        $this->vars['pageTitle'] = $post->getTitle();
        return $this->render->generateView('post/show', $this->vars);
    }
}
```

## 8. Creare le Viste

Infine, creiamo i file HTML per visualizzare i dati.

**`Blog/Application/Views/post/index.php`** (Elenco articoli)

```php
<h1><?= htmlspecialchars($pageTitle) ?></h1>

<?php foreach ($posts as $post): ?>
    <article>
        <h2>
            <a href="/post/show/id/<?= $post->getId() ?>">
                <?= htmlspecialchars($post->getTitle()) ?>
            </a>
        </h2>
        <p>Scritto da: <?= htmlspecialchars($post->getAuthor()->getUsername()) ?></p>
    </article>
<?php endforeach; ?>
```

**`Blog/Application/Views/post/show.php`** (Dettaglio articolo)

```php
<h1><?= htmlspecialchars($post->getTitle()) ?></h1>
<p><em>Scritto da: <?= htmlspecialchars($post->getAuthor()->getUsername()) ?></em></p>
<hr>
<div>
    <?= nl2br(htmlspecialchars($post->getContent())) ?>
</div>
<hr>
<a href="/post/index">Torna alla lista</a>
```

## Conclusione

Congratulazioni! Hai appena creato la tua prima applicazione con SismaFramework.

Visita `/post/index` nel tuo browser per vedere la lista degli articoli. Cliccando su un titolo, verrai reindirizzato alla pagina di dettaglio.

Da qui, puoi esplorare funzionalità più avanzate come:

* Creare un form per aggiungere nuovi articoli (Gestione dei Form).
* Implementare un sistema di login per proteggere la creazione degli articoli (Componente di Sicurezza).
* Aggiungere stili CSS (Gestione degli Asset Statici).

Buon divertimento con SismaFramework!

* * *

[Indice](index.md) | Successivo: [Introduzione al Framework](overview.md)

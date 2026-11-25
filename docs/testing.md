# Testing

SismaFramework è testato con PHPUnit, e la stessa impostazione si usa per i test delle applicazioni. Questa guida descrive la struttura della suite del framework e i pattern per testare i componenti di un'applicazione: helper, entità, query, model, form, voter e integrazione con il database.

## Panoramica del Testing

- **Unit test**: verificano una classe isolando le sue dipendenze. Il framework permette di sostituire configurazione, adapter del database e `DataMapper` con stub e mock di PHPUnit, quindi la maggior parte dei test non richiede un database.
- **Test di integrazione**: eseguono query reali su un database di test. L'ORM supporta MySQL/MariaDB: non esiste un adapter in memoria.

## Struttura dei Test

La suite del framework è organizzata come i sorgenti, con un modulo di supporto, `TestsApplication`, che contiene entità, model, form, voter e fixture usati dai test:

```
Tests/
├── bootstrap.php              # Autoload di Composer e del framework
├── Config/
│   └── config.php            # Mappe di autoload per i test
├── Console/
├── Core/
├── Orm/
└── Security/
TestsApplication/
├── Entities/
├── Forms/
├── Models/
├── Voters/
└── ...
```

---

## Configurazione Ambiente Test

### Bootstrap File

`Tests/bootstrap.php` carica l'autoload di Composer (per PHPUnit) e registra l'autoloader che risolve le classi a partire dalla cartella che contiene il framework:

```php
<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
spl_autoload_register(function (string $className) {
    $actualClassPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (file_exists($actualClassPath)) {
        require_once($actualClassPath);
    }
});
```

### Configurazione nei Test

I componenti del framework leggono la configurazione da `Config::getInstance()`, e la maggior parte accetta anche un `Config` nel costruttore. Nei test si sostituisce la configurazione con uno stub che restituisce solo i valori necessari, e lo si registra con `Config::setInstance()` per i componenti che usano l'istanza globale:

```php
use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;

class PostTest extends TestCase
{
    private Config $configStub;

    protected function setUp(): void
    {
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')
            ->willReturnMap([
                ['defaultPrimaryKeyPropertyName', 'id'],
                ['ormCache', false],
            ]);
        Config::setInstance($this->configStub);
    }
}
```

Le chiavi sono i nomi delle costanti di `Config/config.php` in camelCase (`DEFAULT_PRIMARY_KEY_PROPERTY_NAME` → `defaultPrimaryKeyPropertyName`).

### PHPUnit Configuration

La configurazione di PHPUnit del framework (`phpunit.xml`) usa il bootstrap e raccoglie in un'unica suite i test delle cartelle `Tests/Console`, `Tests/Core`, `Tests/Orm` e `Tests/Security`. Per un'applicazione la struttura è analoga:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="Tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Blog">
            <directory suffix="Test.php">./Tests/Blog</directory>
        </testsuite>
    </testsuites>

    <php>
        <!-- Solo per i test di integrazione: Config/config.php legge le credenziali del database dalle variabili d'ambiente -->
        <env name="DATABASE_HOST" value="localhost"/>
        <env name="DATABASE_NAME" value="blog_test"/>
        <env name="DATABASE_USERNAME" value="test_user"/>
        <env name="DATABASE_PASSWORD" value="test_password"/>
        <env name="DATABASE_PORT" value="3306"/>
    </php>
</phpunit>
```

---

## Testing di Base

### Test di Helper Classes

```php
<?php
namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\NotationManager;

class NotationManagerTest extends TestCase
{
    public function testConvertToKebabCase(): void
    {
        $this->assertEquals('user-profile-controller', NotationManager::convertToKebabCase('UserProfileController'));
    }

    public function testConvertToCamelCase(): void
    {
        $this->assertEquals('userProfileAction', NotationManager::convertToCamelCase('user-profile-action'));
    }

    #[DataProvider('conversionProvider')]
    public function testMultipleConversions(string $input, string $expectedKebab, string $expectedCamel): void
    {
        $this->assertEquals($expectedKebab, NotationManager::convertToKebabCase($input));
        $this->assertEquals($expectedCamel, NotationManager::convertToCamelCase($expectedKebab));
    }

    public static function conversionProvider(): array
    {
        return [
            ['UserController', 'user-controller', 'userController'],
            ['BlogPostService', 'blog-post-service', 'blogPostService'],
            ['APIEndpoint', 'a-p-i-endpoint', 'aPIEndpoint'],
        ];
    }
}
```

### Test di Custom Types

```php
<?php
namespace SismaFramework\Tests\Core\CustomTypes;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\CustomTypes\FormFilterError;

class FormFilterErrorTest extends TestCase
{
    private FormFilterError $formError;

    protected function setUp(): void
    {
        $this->formError = new FormFilterError();
    }

    public function testSetAndGetError(): void
    {
        $this->formError->usernameError = true;

        $this->assertTrue($this->formError->usernameError);
    }

    public function testSetAndGetCustomMessage(): void
    {
        $this->formError->usernameCustomMessage = 'Username is required';

        $this->assertEquals('Username is required', $this->formError->usernameCustomMessage);
    }

    public function testGetErrorsToArray(): void
    {
        $this->formError->usernameError = true;
        $this->formError->usernameCustomMessage = 'Username required';
        $this->formError->emailError = false;

        $this->assertEquals([
            'usernameError' => true,
            'emailError' => false,
            'usernameCustomMessage' => 'Username required',
        ], $this->formError->getErrorsToArray());
    }

    public function testAutoCreateFormFilterError(): void
    {
        // Una proprietà senza suffisso è il form annidato omonimo: se non esiste, restituisce un FormFilterError vuoto
        $this->assertInstanceOf(FormFilterError::class, $this->formError->nonExistentField);
    }
}
```

---

## Testing dell'ORM

### Test di Entità

Le entità ricevono `DataMapper`, `ProcessedEntitiesCollection` e `Config` nel costruttore, tutti facoltativi. Le proprietà si leggono e si scrivono direttamente:

```php
use SismaFramework\Orm\HelperClasses\DataMapper;
use Blog\Application\Entities\Post;

public function testModifiedFlag(): void
{
    $post = new Post($this->createStub(DataMapper::class), null, $this->configStub);
    $this->assertFalse($post->modified);

    $post->title = 'Nuovo titolo';

    $this->assertTrue($post->modified);
    $this->assertSame('Nuovo titolo', $post->title);
}
```

### Test di Query Builder

`AdapterMysql` genera l'SQL senza aprire la connessione, che viene stabilita solo alla prima esecuzione: le query si possono verificare confrontando il testo generato.

```php
use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\Adapters\AdapterMysql;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\HelperClasses\Query;

class PostQueryTest extends TestCase
{
    public function testSelectWithCondition(): void
    {
        $adapter = new AdapterMysql();
        $query = new Query($adapter);
        $query->setTable('post')
            ->setWhere()
            ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $query->close();

        $this->assertSame('SELECT `post`.* FROM `post` WHERE `post`.`status` = ? ', $query->getCommandToExecute());
    }
}
```

### Test di Model e DataMapper

Per isolare il database si sostituisce l'adapter con un mock: `BaseAdapter::setDefault()` lo rende l'adapter delle query create durante il test, e lo si passa anche al `DataMapper`.

```php
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\HelperClasses\DataMapper;
use Blog\Application\Models\PostModel;

public function testGetEntityCollectionWithoutResults(): void
{
    $adapterMock = $this->createMock(BaseAdapter::class);
    BaseAdapter::setDefault($adapterMock);
    $adapterMock->expects($this->once())
        ->method('select')
        ->willReturn(null);

    $postModel = new PostModel(new DataMapper($adapterMock, null, $this->configStub), $this->configStub);
    $posts = $postModel->getEntityCollection();

    $this->assertInstanceOf(SismaCollection::class, $posts);
    $this->assertCount(0, $posts);
}
```

Allo stesso modo, un mock che si aspetta `execute()` verifica i salvataggi di `DataMapper::save()` e le cancellazioni di `delete()`. Per restituire entità, `select()` restituisce un mock di `BaseResultSet`, che è un `Iterator`.

---

## Testing dei Controller

Le action generano la risposta con `RenderService`, che include le viste dei moduli registrati e stampa l'output: per testarle servono la struttura dei moduli e una configurazione completa. Conviene quindi tenere la logica nei model, nei form e nei servizi, testati come negli esempi di questa guida, e lasciare ai controller il solo coordinamento. La suite del framework verifica il dispatch completo di una richiesta in `Tests/Core/HelperClasses/DispatcherTest.php`, simulando la richiesta tramite `$_SERVER`.

---

## Testing dei Form

Il form riceve una `Request`, il cui array `input` contiene i dati inviati. Nei test lo si valorizza direttamente:

```php
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\HelperClasses\DataMapper;
use Blog\Application\Forms\PostForm;

public function testShortContentIsRejected(): void
{
    $request = new Request();
    $request->input = [
        'title' => 'Titolo valido',
        'content' => 'Breve',
        'submitted' => '1',
    ];

    $form = new PostForm(null, $this->createStub(DataMapper::class));
    $form->handleRequest($request);

    $this->assertTrue($form->isSubmitted());
    $this->assertFalse($form->isValid());
    $this->assertTrue($form->getFilterErrors()->contentError);
    $this->assertFalse($form->getFilterErrors()->titleError);
}
```

---

## Testing di Sicurezza

### Test di Voter

`isAllowed()` crea il Voter, gli passa soggetto, tipo di accesso e utente e restituisce il risultato di `checkVote()`:

```php
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Security\Enumerations\AccessControlEntry;
use Blog\Application\Entities\Post;
use Blog\Application\Entities\User;
use Blog\Application\Voters\PostVoter;

public function testAuthorCanEdit(): void
{
    $dataMapperStub = $this->createStub(DataMapper::class);
    $author = new User($dataMapperStub, null, $this->configStub);
    $author->id = 1;
    $post = new Post($dataMapperStub, null, $this->configStub);
    $post->author = $author;

    $this->assertTrue(PostVoter::isAllowed($post, AccessControlEntry::check, $author));
}

public function testOtherUserCannotEdit(): void
{
    $dataMapperStub = $this->createStub(DataMapper::class);
    $author = new User($dataMapperStub, null, $this->configStub);
    $author->id = 1;
    $otherUser = new User($dataMapperStub, null, $this->configStub);
    $otherUser->id = 2;
    $post = new Post($dataMapperStub, null, $this->configStub);
    $post->author = $author;

    $this->assertFalse(PostVoter::isAllowed($post, AccessControlEntry::check, $otherUser));
}
```

Le Permission si testano allo stesso modo, verificando che `isAllowed()` sollevi `AccessDeniedException` quando il Voter risponde `false`.

---

## Database Testing

I test di integrazione usano un database MySQL/MariaDB dedicato, con le tabelle dell'applicazione. `Config/config.php` legge le credenziali dalle variabili d'ambiente `DATABASE_*`, che si impostano nella sezione `<php>` di `phpunit.xml` come nell'esempio sopra. Questi test usano la configurazione reale, senza stub.

```php
use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\HelperClasses\DataMapper;
use Blog\Application\Entities\Post;
use Blog\Application\Entities\User;
use Blog\Application\Models\PostModel;

class PostIntegrationTest extends TestCase
{
    private DataMapper $dataMapper;
    private User $author;

    protected function setUp(): void
    {
        $this->dataMapper = new DataMapper();
        $this->author = new User($this->dataMapper);
        $this->author->username = 'Autore di prova';
        $this->author->email = 'autore@example.com';
        $this->dataMapper->save($this->author);
    }

    protected function tearDown(): void
    {
        $this->dataMapper->delete($this->author);
    }

    public function testSaveAndRetrieve(): void
    {
        $post = new Post($this->dataMapper);
        $post->title = 'Test';
        $post->content = 'Contenuto di prova';
        $post->publicationDate = new SismaDateTime();
        $post->author = $this->author;

        $this->assertTrue($this->dataMapper->save($post));
        $this->assertGreaterThan(0, $post->id);

        $retrieved = (new PostModel($this->dataMapper))->getEntityById($post->id);
        $this->assertSame('Test', $retrieved->title);

        $this->dataMapper->delete($post);
    }
}
```

Ogni test deve lasciare il database come l'ha trovato, cancellando le righe che ha creato. Con `ORM_CACHE` attivo, `getEntityById()` restituisce l'istanza già in memoria: per verificare quanto scritto nel database si svuota la cache con `Cache::clearEntityCache()`.

---

## Test con Fixtures

Le [fixture](data-fixtures.md) popolano il database di test con dati noti. `FixturesManager` esegue tutte le fixture dei moduli registrati, nell'ordine delle dipendenze:

```php
use SismaFramework\Console\Services\Fixtures\FixturesManager;
use SismaFramework\Orm\HelperClasses\DataMapper;

public static function setUpBeforeClass(): void
{
    (new FixturesManager(new DataMapper()))->run();
}
```

---

## Best Practices per Testing

### 1. Organizzazione dei Test

```php
use PHPUnit\Framework\Attributes\DataProvider;

class PostServiceTest extends TestCase
{
    protected function setUp(): void { }

    // Test di successo
    public function testPublishSuccess(): void { }

    // Test di errore
    public function testPublishWithoutTitle(): void { }

    // Test con data provider
    #[DataProvider('statusProvider')]
    public function testStatusTransition(PostStatus $from, PostStatus $to, bool $expected): void { }

    public static function statusProvider(): array { }
}
```

### 2. Mocking Efficace

Le classi del framework ricevono le dipendenze nel costruttore, con un default: nei test si passano stub e mock.

```php
public function testPublishSavesPost(): void
{
    $dataMapperMock = $this->createMock(DataMapper::class);
    $dataMapperMock->expects($this->once())
        ->method('save')
        ->with($this->isInstanceOf(Post::class))
        ->willReturn(true);

    $service = new PostPublisher($dataMapperMock);

    $this->assertTrue($service->publish($this->post));
}
```

### 3. Stato Globale

`Config`, l'adapter di default, la cache delle entità (`Cache`) e la sessione (`$_SESSION`) sono condivisi tra i test: ogni test che li modifica deve impostarli nel proprio `setUp()`, così che l'ordine di esecuzione non influisca sui risultati.

---

## Comandi Utili

### Esecuzione Test

```bash
# Tutti i test
vendor/bin/phpunit

# Test specifici
vendor/bin/phpunit Tests/Core/HelperClasses/NotationManagerTest.php

# Test con coverage (richiede Xdebug o PCOV)
vendor/bin/phpunit --coverage-html coverage/

# Test con filtro
vendor/bin/phpunit --filter testConvertToKebabCase
```

### Debug dei Test

```php
public function testWithDebug(): void
{
    $result = NotationManager::convertToKebabCase('UserProfile');

    // Assertions con messaggi custom
    $this->assertEquals('user-profile', $result, 'Conversione in kebab-case errata');
}

public function testOutput(): void
{
    // Verifica dell'output prodotto dal codice testato
    $this->expectOutputString('Expected output');
    echo 'Expected output';
}
```

---

[Indice](index.md) | Precedente: [Gestione Errori e Logging](error-handling-and-logging.md) | Successivo: [Best Practices](best-practices.md)
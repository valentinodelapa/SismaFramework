# Testing

SismaFramework è progettato per supportare pratiche di testing robuste attraverso PHPUnit e fornisce strumenti specifici per testare tutti i componenti del framework. Questa guida copre configurazione, best practices e patterns comuni per i test.

## Panoramica del Testing

SismaFramework supporta diversi tipi di test:

- **Unit Tests**: Test di singole classi e metodi
- **Integration Tests**: Test di interazione tra componenti
- **Functional Tests**: Test di funzionalità end-to-end
- **Database Tests**: Test specifici per l'ORM e DataMapper

## Struttura dei Test

```
Tests/
├── bootstrap.php              # Bootstrap per i test
├── Config/
│   └── config.php            # Configurazione test
├── Core/
│   ├── BaseClasses/          # Test classi base
│   ├── HelperClasses/        # Test helper
│   └── CustomTypes/          # Test tipi personalizzati
├── Orm/
│   ├── HelperClasses/        # Test ORM
│   └── BaseClasses/          # Test entità e modelli
└── Security/                 # Test componenti sicurezza
```

---

## Configurazione Ambiente Test

### Bootstrap File

Il file `Tests/bootstrap.php` configura l'autoloader per i test:

```php
<?php
// Tests/bootstrap.php

spl_autoload_register(function (string $className) {
    $actualClassPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR .
                      str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (file_exists($actualClassPath)) {
        require_once($actualClassPath);
    }
});
```

### Configurazione Test

Creare un file di configurazione specifico per i test:

```php
<?php
// Tests/Config/config.php

// Database di test
const DATABASE_HOST = 'localhost';
const DATABASE_NAME = 'sisma_test';
const DATABASE_USERNAME = 'test_user';
const DATABASE_PASSWORD = 'test_password';

// Ambiente di test
const DEVELOPMENT_ENVIRONMENT = true;
const LOG_ERRORS = false;

// Moduli per test
const MODULE_FOLDERS = ['TestsApplication'];

// Fixtures
const FIXTURES = 'fixtures';
```

### PHPUnit Configuration

Creare `phpunit.xml` nella root del progetto:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.0/phpunit.xsd"
         bootstrap="Tests/bootstrap.php"
         cacheResult="false"
         colors="true">
    <testsuites>
        <testsuite name="Core">
            <directory>Tests/Core</directory>
        </testsuite>
        <testsuite name="ORM">
            <directory>Tests/Orm</directory>
        </testsuite>
        <testsuite name="Security">
            <directory>Tests/Security</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory suffix=".php">./Core</directory>
            <directory suffix=".php">./Orm</directory>
            <directory suffix=".php">./Security</directory>
        </include>
        <exclude>
            <directory>./Tests</directory>
            <directory>./vendor</directory>
        </exclude>
    </coverage>

    <php>
        <env name="DB_CONNECTION" value="testing"/>
    </php>
</phpunit>
```

---

## Testing di Base

### Test di Helper Classes

```php
<?php
namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\NotationManager;

class NotationManagerTest extends TestCase
{
    public function testConvertToKebabCase(): void
    {
        $input = 'UserProfileController';
        $expected = 'user-profile-controller';

        $result = NotationManager::convertToKebabCase($input);

        $this->assertEquals($expected, $result);
    }

    public function testConvertToCamelCase(): void
    {
        $input = 'user-profile-action';
        $expected = 'userProfileAction';

        $result = NotationManager::convertToCamelCase($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider conversionProvider
     */
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
use SismaFramework\Core\CustomTypes\FormFilterErrorCollection;

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
        $message = 'Username is required';
        $this->formError->usernameCustomMessage = $message;

        $this->assertEquals($message, $this->formError->usernameCustomMessage);
    }

    public function testGetErrorsToArray(): void
    {
        $this->formError->usernameError = true;
        $this->formError->usernameCustomMessage = 'Username required';
        $this->formError->emailError = false;

        $expected = [
            'usernameError' => true,
            'usernameCustomMessage' => 'Username required',
            'emailError' => false,
        ];

        $result = $this->formError->getErrorsToArray();

        $this->assertEquals($expected, $result);
    }

    public function testAutoCreateFormFilterError(): void
    {
        // Accesso a proprietà inesistente deve creare FormFilterError vuoto
        $result = $this->formError->nonExistentField;

        $this->assertInstanceOf(FormFilterError::class, $result);
    }
}
```

---

## Testing dell'ORM

### Test di Entità

```php
<?php
namespace SismaFramework\Tests\Orm\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\TestsApplication\Entities\BaseSample;

class BaseEntityTest extends TestCase
{
    private BaseSample $entity;

    protected function setUp(): void
    {
        $this->entity = new BaseSample();
    }

    public function testSetAndGetId(): void
    {
        $id = 123;
        $this->entity->setId($id);

        $this->assertEquals($id, $this->entity->getId());
    }

    public function testEntityInitialization(): void
    {
        $this->assertNull($this->entity->getId());
        $this->assertInstanceOf(BaseSample::class, $this->entity);
    }

    public function testPropertyTypes(): void
    {
        $reflection = new \ReflectionClass($this->entity);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $type = $property->getType();
            $this->assertNotNull($type, "Property {$property->getName()} should have a type");
        }
    }
}
```

### Test di Query Builder

```php
<?php
namespace SismaFramework\Tests\Orm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\Enumerations\ComparisonOperator;

class QueryTest extends TestCase
{
    private $adapterMock;
    private Query $query;

    protected function setUp(): void
    {
        $this->adapterMock = $this->createMock(BaseAdapter::class);
        $this->query = new Query($this->adapterMock);
    }

    public function testSelectDistinct(): void
    {
        $this->adapterMock->expects($this->once())
            ->method('allColumns')
            ->willReturn('*');

        $this->adapterMock->expects($this->once())
            ->method('parseSelect')
            ->with(true, ['*'], '', [], [], [], [], 0, 0);

        $this->query->setDistinct()->close();

        $this->assertEquals('', $this->query->getCommandToExecute());
    }

    public function testSelectCount(): void
    {
        $this->adapterMock->expects($this->once())
            ->method('opCOUNT')
            ->with('id')
            ->willReturn('COUNT(id)');

        $this->query->setCount('id')->close();

        $this->assertEquals('', $this->query->getCommandToExecute());
    }

    public function testWhereCondition(): void
    {
        $this->adapterMock->expects($this->once())
            ->method('parseWhere')
            ->with(['name = ?'], ['John']);

        $this->query
            ->where('name', ComparisonOperator::equal, 'John')
            ->close();
    }
}
```

### Test di DataMapper

```php
<?php
namespace SismaFramework\Tests\Orm\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Models\BaseSampleModel;

class DataMapperTest extends TestCase
{
    private DataMapper $dataMapper;
    private \PDO $pdoMock;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->dataMapper = new DataMapper();

        // Inject mock PDO se necessario
        $reflection = new \ReflectionClass($this->dataMapper);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue($this->dataMapper, $this->pdoMock);
    }

    public function testSaveNewEntity(): void
    {
        $entity = new BaseSample();
        $entity->setName('Test Entity');

        $statementMock = $this->createMock(\PDOStatement::class);
        $statementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($statementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('1');

        $result = $this->dataMapper->save($entity);

        $this->assertTrue($result);
        $this->assertEquals(1, $entity->getId());
    }

    public function testFindById(): void
    {
        $model = new BaseSampleModel($this->dataMapper);

        $statementMock = $this->createMock(\PDOStatement::class);
        $statementMock->expects($this->once())
            ->method('execute')
            ->with([1])
            ->willReturn(true);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id' => 1,
                'name' => 'Test Entity'
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($statementMock);

        $result = $model->find(1);

        $this->assertInstanceOf(BaseSample::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('Test Entity', $result->getName());
    }
}
```

---

## Testing dei Controller

### Test di Controller Base

```php
<?php
namespace SismaFramework\Tests\Core\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Controllers\SampleController;

class BaseControllerTest extends TestCase
{
    private SampleController $controller;
    private DataMapper $dataMapperMock;

    protected function setUp(): void
    {
        $this->dataMapperMock = $this->createMock(DataMapper::class);
        $this->controller = new SampleController($this->dataMapperMock);
    }

    public function testControllerInitialization(): void
    {
        $this->assertInstanceOf(BaseController::class, $this->controller);

        // Verificare che le variabili di base siano impostate
        $reflection = new \ReflectionClass($this->controller);
        $varsProperty = $reflection->getProperty('vars');
        $varsProperty->setAccessible(true);
        $vars = $varsProperty->getValue($this->controller);

        $this->assertArrayHasKey('controllerUrl', $vars);
        $this->assertArrayHasKey('actionUrl', $vars);
        $this->assertArrayHasKey('rootUrl', $vars);
    }

    public function testDataMapperInjection(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $dataMapperProperty = $reflection->getProperty('dataMapper');
        $dataMapperProperty->setAccessible(true);
        $dataMapper = $dataMapperProperty->getValue($this->controller);

        $this->assertSame($this->dataMapperMock, $dataMapper);
    }
}
```

### Test Funzionali con Action

```php
<?php
namespace SismaFramework\Tests\Functional;

use PHPUnit\Framework\TestCase;
use SismaFramework\TestsApplication\Controllers\SampleController;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

class ControllerActionTest extends TestCase
{
    private SampleController $controller;

    protected function setUp(): void
    {
        $this->controller = new SampleController();
    }

    public function testIndexAction(): void
    {
        // Simulare una richiesta GET
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/sample/index';

        // Mock output buffering per catturare la vista
        ob_start();
        $response = $this->controller->index();
        $output = ob_get_clean();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('Sample', $output);
    }

    public function testActionWithParameters(): void
    {
        $testId = 123;

        // Test action che accetta parametri
        $response = $this->controller->show($testId);

        $this->assertInstanceOf(Response::class, $response);
    }
}
```

---

## Testing dei Form

### Test di Validazione Form

```php
<?php
namespace SismaFramework\Tests\Core\BaseClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\TestsApplication\Forms\BaseSampleForm;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\Core\CustomTypes\FormFilterError;

class FormValidationTest extends TestCase
{
    private BaseSampleForm $form;
    private BaseSample $entity;

    protected function setUp(): void
    {
        $this->entity = new BaseSample();
        $this->form = new BaseSampleForm($this->entity);
    }

    public function testValidForm(): void
    {
        // Simulare dati validi
        $_POST = [
            'name' => 'Valid Name',
            'email' => 'valid@example.com'
        ];

        $this->form->handleRequest();

        $this->assertTrue($this->form->isValid());
        $this->assertTrue($this->form->isSubmitted());
    }

    public function testInvalidForm(): void
    {
        // Simulare dati non validi
        $_POST = [
            'name' => '', // Nome vuoto
            'email' => 'invalid-email' // Email non valida
        ];

        $this->form->handleRequest();

        $this->assertFalse($this->form->isValid());

        $errors = $this->form->getErrors();
        $this->assertInstanceOf(FormFilterError::class, $errors);

        // Verificare errori specifici
        $this->assertTrue($errors->nameError);
        $this->assertTrue($errors->emailError);
    }

    public function testCSRFProtection(): void
    {
        // Simulare richiesta senza token CSRF
        $_POST = [
            'name' => 'Test Name'
            // Manca csrf_token
        ];

        $this->form->handleRequest();

        $this->assertFalse($this->form->isValid());

        $errors = $this->form->getErrors();
        $this->assertTrue($errors->csrfError);
    }
}
```

---

## Testing di Sicurezza

### Test di Voter

```php
<?php
namespace SismaFramework\Tests\Security;

use PHPUnit\Framework\TestCase;
use SismaFramework\Security\BaseClasses\BaseVoter;
use SismaFramework\Security\BaseClasses\BasePermission;
use SismaFramework\Security\Enumerations\AccessControlEntry;
use SismaFramework\TestsApplication\Entities\BaseSample;

class VoterTest extends TestCase
{
    private BaseVoter $voter;
    private BasePermission $permission;

    protected function setUp(): void
    {
        $this->voter = new class extends BaseVoter {
            public function vote(BasePermission $permission, $subject = null): AccessControlEntry {
                if ($subject instanceof BaseSample) {
                    return match ($permission->getAction()) {
                        'view' => AccessControlEntry::allow,
                        'edit' => $this->getCurrentUser()->getId() === $subject->getOwnerId()
                            ? AccessControlEntry::allow
                            : AccessControlEntry::deny,
                        default => AccessControlEntry::deny,
                    };
                }

                return AccessControlEntry::deny;
            }

            protected function getCurrentUser() {
                // Mock user per test
                return (object)['id' => 1];
            }
        };

        $this->permission = new class extends BasePermission {
            private string $action;

            public function __construct(string $action) {
                $this->action = $action;
            }

            public function getAction(): string {
                return $this->action;
            }
        };
    }

    public function testViewPermissionAllowed(): void
    {
        $entity = new BaseSample();
        $permission = new $this->permission('view');

        $result = $this->voter->vote($permission, $entity);

        $this->assertEquals(AccessControlEntry::allow, $result);
    }

    public function testEditPermissionForOwner(): void
    {
        $entity = new BaseSample();
        $entity->setOwnerId(1); // Stesso ID dell'user mocckato

        $permission = new $this->permission('edit');

        $result = $this->voter->vote($permission, $entity);

        $this->assertEquals(AccessControlEntry::allow, $result);
    }

    public function testEditPermissionForNonOwner(): void
    {
        $entity = new BaseSample();
        $entity->setOwnerId(2); // ID diverso dall'user mockato

        $permission = new $this->permission('edit');

        $result = $this->voter->vote($permission, $entity);

        $this->assertEquals(AccessControlEntry::deny, $result);
    }
}
```

---

## Database Testing

### Test con Database In-Memory

```php
<?php
namespace SismaFramework\Tests\Database;

use PHPUnit\Framework\TestCase;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\TestsApplication\Entities\BaseSample;
use SismaFramework\TestsApplication\Models\BaseSampleModel;

class DatabaseIntegrationTest extends TestCase
{
    private DataMapper $dataMapper;
    private \PDO $pdo;

    protected function setUp(): void
    {
        // Creare database SQLite in-memory per i test
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Creare tabelle di test
        $this->createTestTables();

        $this->dataMapper = new DataMapper();
        // Inject PDO di test
        $reflection = new \ReflectionClass($this->dataMapper);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue($this->dataMapper, $this->pdo);
    }

    private function createTestTables(): void
    {
        $sql = "
            CREATE TABLE base_sample (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->pdo->exec($sql);
    }

    public function testSaveAndRetrieve(): void
    {
        $entity = new BaseSample();
        $entity->setName('Test Entity');
        $entity->setDescription('Test Description');

        // Salvare entità
        $result = $this->dataMapper->save($entity);
        $this->assertTrue($result);
        $this->assertNotNull($entity->getId());

        // Recuperare entità
        $model = new BaseSampleModel($this->dataMapper);
        $retrieved = $model->find($entity->getId());

        $this->assertInstanceOf(BaseSample::class, $retrieved);
        $this->assertEquals($entity->getName(), $retrieved->getName());
        $this->assertEquals($entity->getDescription(), $retrieved->getDescription());
    }

    public function testQuery(): void
    {
        // Inserire dati di test
        $entities = [
            ['name' => 'Entity 1', 'description' => 'First entity'],
            ['name' => 'Entity 2', 'description' => 'Second entity'],
            ['name' => 'Entity 3', 'description' => 'Third entity'],
        ];

        foreach ($entities as $data) {
            $entity = new BaseSample();
            $entity->setName($data['name']);
            $entity->setDescription($data['description']);
            $this->dataMapper->save($entity);
        }

        // Testare query
        $model = new BaseSampleModel($this->dataMapper);
        $results = $model->findAll();

        $this->assertCount(3, $results);
        $this->assertEquals('Entity 1', $results[0]->getName());
    }
}
```

---

## Test con Fixtures

### Setup con Data Fixtures

```php
<?php
namespace SismaFramework\Tests\Fixtures;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Services\Fixtures\FixturesManager;
use SismaFramework\Orm\HelperClasses\DataMapper;

class FixtureTest extends TestCase
{
    private DataMapper $dataMapper;
    private FixturesManager $fixturesManager;

    protected function setUp(): void
    {
        $this->dataMapper = new DataMapper();
        $this->fixturesManager = new FixturesManager($this->dataMapper);

        // Caricare fixtures di test
        $this->fixturesManager->run();
    }

    public function testFixturesLoaded(): void
    {
        $this->assertTrue($this->fixturesManager->extecuted());
    }

    public function testDataFromFixtures(): void
    {
        $model = new BaseSampleModel($this->dataMapper);
        $entities = $model->findAll();

        // Verificare che le fixtures abbiano caricato i dati
        $this->assertGreaterThan(0, count($entities));
    }
}
```

---

## Best Practices per Testing

### 1. Organizzazione dei Test

```php
// Struttura consigliata per i test
class UserServiceTest extends TestCase
{
    // Setup e teardown
    protected function setUp(): void { }
    protected function tearDown(): void { }

    // Test di successo
    public function testCreateUserSuccess(): void { }

    // Test di errore
    public function testCreateUserWithInvalidData(): void { }

    // Test con data provider
    /**
     * @dataProvider userDataProvider
     */
    public function testUserValidation(array $data, bool $expected): void { }

    // Data provider
    public static function userDataProvider(): array { }
}
```

### 2. Mocking Efficace

```php
class ServiceTest extends TestCase
{
    public function testServiceWithMocks(): void
    {
        // Mock delle dipendenze
        $repositoryMock = $this->createMock(UserRepository::class);
        $repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class))
            ->willReturn(true);

        // Inject mock nel servizio
        $service = new UserService($repositoryMock);

        // Test del comportamento
        $result = $service->createUser(['name' => 'Test']);
        $this->assertTrue($result);
    }
}
```

### 3. Test di Integrazione

```php
class IntegrationTest extends TestCase
{
    use DatabaseTransactions; // Rollback automatico

    public function testCompleteWorkflow(): void
    {
        // Test end-to-end che verifica l'intera catena
        $response = $this->makeRequest('POST', '/users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->assertEquals(201, $response->getStatusCode());

        // Verificare che l'utente sia stato creato nel database
        $user = $this->userRepository->findByEmail('test@example.com');
        $this->assertNotNull($user);
    }
}
```

---

## Comandi Utili

### Esecuzione Test

```bash
# Tutti i test
vendor/bin/phpunit

# Test specifici
vendor/bin/phpunit Tests/Core/HelperClasses/NotationManagerTest.php

# Test con coverage
vendor/bin/phpunit --coverage-html coverage/

# Test per suite specifica
vendor/bin/phpunit --testsuite Core

# Test con filtro
vendor/bin/phpunit --filter testConvertToKebabCase
```

### Debug dei Test

```php
// Aggiungere debug nei test
class DebugTest extends TestCase
{
    public function testWithDebug(): void
    {
        $result = $this->someMethod();

        // Output di debug durante i test
        var_dump($result);
        $this->expectOutputString('Expected output');

        // Assertions con messaggi custom
        $this->assertEquals($expected, $result, 'Custom error message');
    }
}
```

---

[Indice](index.md) | Precedente: [Gestione Errori e Logging](error-handling-and-logging.md) | Successivo: [Best Practices](best-practices.md)
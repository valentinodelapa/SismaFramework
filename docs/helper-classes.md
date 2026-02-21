# Helper Classes

Le Helper Classes di SismaFramework forniscono funzionalità di supporto essenziali per il funzionamento del framework. Queste classi contengono metodi statici e utility che semplificano operazioni comuni come la gestione dei moduli, conversioni di nomenclatura e routing.

## Panoramica delle Helper Classes

| Classe | Scopo | Utilizzo Principale |
|--------|-------|-------------------|
| [ModuleManager](#modulemanager) | Gestione moduli | Configurazione e caricamento moduli |
| [NotationManager](#notationmanager) | Conversioni nomenclatura | Conversioni camelCase ↔ kebab-case ↔ snake_case |
| [Router](#router) | Gestione routing | URL generation e redirection |
| [BufferManager](#buffermanager) | Gestione output buffer | Controllo dell'output PHP |


---

## ModuleManager

Il `ModuleManager` è responsabile della gestione dell'architettura modulare di SismaFramework. Controlla quale modulo è attivo, gestisce la lista dei moduli registrati e facilita la personalizzazione della visualizzazione.

### Concetti Chiave

- **Modulo Applicazione**: Il modulo principale che gestisce la logica corrente
- **Modulo Visualizzazione**: Modulo personalizzato per la presentazione
- **Lista Moduli**: Configurazione di tutti i moduli disponibili

### Utilizzi Comuni

#### Gestione del Modulo Corrente
```php
// Impostare il modulo corrente
ModuleManager::setApplicationModule('Blog');

// Ottenere il modulo corrente
$currentModule = ModuleManager::getApplicationModule(); // "Blog"

// Impostare automaticamente dal nome della classe
ModuleManager::setApplicationModuleByClassName('Blog\\Controllers\\PostController');
```

#### Lavorare con la Lista dei Moduli
```php
// Ottenere tutti i moduli registrati
$modules = ModuleManager::getModuleList();
// Risultato: ['Core', 'Blog', 'User', 'Shop']

// Iterare sui moduli per operazioni batch
foreach (ModuleManager::getModuleList() as $module) {
    echo "Caricando risorse del modulo: {$module}\n";
}
```

#### Personalizzazione della Visualizzazione
```php
// Usare un tema personalizzato
ModuleManager::setCustomVisualizationModule('CustomTheme');

// Tornare alla visualizzazione standard
ModuleManager::unsetCustomVisualizationModule();
```

### Casi d'Uso Avanzati

#### Plugin o Estensioni Dinamiche
```php
class PluginLoader {
    public static function loadPlugins() {
        $plugins = ['Analytics', 'SEO', 'Cache'];

        foreach ($plugins as $plugin) {
            if (self::isPluginAvailable($plugin)) {
                ModuleManager::setApplicationModule($plugin);
                // Carica funzionalità del plugin
            }
        }
    }
}
```

---

## NotationManager

Il `NotationManager` gestisce le conversioni tra diverse convenzioni di nomenclatura. È fondamentale per mantenere la coerenza tra URL (kebab-case), nomi di classi (PascalCase), proprietà (camelCase) e tabelle database (snake_case).

### Schema delle Conversioni

```
StudlyCaps/PascalCase: UserProfile
camelCase: userProfile
kebab-case: user-profile
snake_case: user_profile
UPPER_SNAKE_CASE: USER_PROFILE
```

### Utilizzi nel Framework

#### Routing Automatico
```php
// URL: /user-profile/show-details
// Viene convertito automaticamente in:
$controllerClass = NotationManager::convertToStudlyCaps('user-profile') . 'Controller';
// Risultato: "UserProfileController"

$actionMethod = NotationManager::convertToCamelCase('show-details');
// Risultato: "showDetails"
```

#### Mappatura ORM
```php
// Nome classe entità → Nome tabella
$tableName = NotationManager::convertEntityNameToTableName('Blog\\Entities\\BlogPost');
// Risultato: "blog_post"

// Nome colonna → Nome proprietà
$propertyName = NotationManager::convertColumnNameToPropertyName('created_at');
// Risultato: "createdAt"
```

### Esempi Pratici

#### Generazione Dinamica di URL
```php
class UrlGenerator {
    public static function generateControllerUrl(string $controllerName): string {
        return '/' . NotationManager::convertToKebabCase($controllerName);
    }
}

// Utilizzo
$url = UrlGenerator::generateControllerUrl('UserProfileController');
// Risultato: "/user-profile"
```

#### Creazione Automatica di Migrazioni Database
```php
class MigrationGenerator {
    public static function generateTableName(string $entityClass): string {
        return NotationManager::convertEntityNameToTableName($entityClass);
    }

    public static function generateColumnName(string $propertyName): string {
        return NotationManager::convertToSnakeCase($propertyName);
    }
}
```

---

## Router

Il `Router` gestisce la navigazione dell'applicazione, generazione di URL e redirection. Mantiene lo stato dell'URL corrente e fornisce metodi per costruire nuovi URL.

### Stato Interno del Router

Il Router mantiene diverse informazioni sull'URL corrente:
- **Meta URL**: Prefisso base dell'applicazione
- **Controller URL**: Parte dell'URL relativa al controller
- **Action URL**: Parte dell'URL relativa all'action
- **Actual Clean URL**: URL completo "pulito"

### Utilizzi Comuni

#### Redirection
```php
// Redirect semplice
Router::redirect('user/profile');

// Redirect con parametri
Router::redirect('post/show/id/123');

// Redirect con request personalizzata
$request = new Request();
Router::redirect('dashboard', $request);
```

#### Generazione URL
```php
// Ottenere l'URL root dell'applicazione
$rootUrl = Router::getRootUrl();
// Risultato: "https://example.com/app"

// Costruire URL per asset
$assetUrl = Router::getRootUrl() . '/assets/css/style.css';

// Ottenere l'URL corrente
$currentUrl = Router::getActualCleanUrl();
```

#### Gestione Meta URL
```php
// Impostare un prefisso per l'applicazione (CONCATENA al valore esistente)
Router::concatenateMetaUrl('/api/v1');

// Tutte le successive chiamate includeranno questo prefisso
$rootUrl = Router::getRootUrl(); // "https://example.com/api/v1"

// Sovrascrivere completamente il meta URL (v10.1.0+)
Router::setMetaUrl('/api/v2');
$rootUrl = Router::getRootUrl(); // "https://example.com/api/v2"

// Reset del meta URL
Router::resetMetaUrl();
```

**Differenza tra `setMetaUrl()` e `concatenateMetaUrl()`:**

- **`setMetaUrl()`**: Sostituisce completamente il meta URL esistente
- **`concatenateMetaUrl()`**: Aggiunge al meta URL esistente

```php
// Esempio della differenza
Router::setMetaUrl('/app');
Router::concatenateMetaUrl('/api');
$url = Router::getRootUrl(); // "https://example.com/app/api"

// vs

Router::setMetaUrl('/app');
Router::setMetaUrl('/api');  // Sostituisce '/app'
$url = Router::getRootUrl(); // "https://example.com/api"
```

### Casi d'Uso Avanzati

#### Sistema di Breadcrumb
```php
class BreadcrumbManager {
    public static function generateBreadcrumb(): array {
        return [
            'root' => Router::getRootUrl(),
            'controller' => Router::getControllerUrl(),
            'action' => Router::getActionUrl()
        ];
    }
}
```

#### API Versioning
```php
class ApiRouter extends Router {
    public static function setApiVersion(string $version): void {
        // Usa setMetaUrl() per sovrascrivere direttamente (v10.1.0+)
        parent::setMetaUrl("/api/{$version}");
    }
}

// Utilizzo
ApiRouter::setApiVersion('v2');
$apiUrl = ApiRouter::getRootUrl(); // "https://example.com/api/v2"

// Cambiare versione
ApiRouter::setApiVersion('v3');
$apiUrl = ApiRouter::getRootUrl(); // "https://example.com/api/v3" (sostituisce v2)
```

---

## BufferManager

Il `BufferManager` fornisce un controllo granulare sull'output buffer di PHP. È particolarmente utile per catturare output, gestire template e controllare il flusso di dati verso il browser.

### Utilizzi Comuni

#### Cattura dell'Output
```php
// Catturare output in una variabile
BufferManager::start();
echo "Questo contenuto verrà catturato";
include 'template.php';
$content = BufferManager::getContents();
BufferManager::end();

// Ora $content contiene tutto l'output
```

#### Template System
```php
class TemplateRenderer {
    public static function render(string $templatePath, array $vars = []): string {
        BufferManager::start();

        extract($vars);
        include $templatePath;

        $content = BufferManager::getContents();
        BufferManager::end();

        return $content;
    }
}

// Utilizzo
$html = TemplateRenderer::render('user/profile.php', ['user' => $user]);
```

#### Controllo dell'Output in Debug
```php
class DebugOutput {
    public static function captureDebugInfo(): string {
        BufferManager::start();

        var_dump($_POST);
        var_dump($_SESSION);
        phpinfo();

        $debugInfo = BufferManager::getContents();
        BufferManager::clear(); // Pulisce senza inviare

        return $debugInfo;
    }
}
```

---

## Best Practices per l'Uso degli Helper

### 1. Caching delle Conversioni
```php
class CachedNotationManager {
    private static array $cache = [];

    public static function convertToKebabCase(string $input): string {
        if (!isset(self::$cache[$input])) {
            self::$cache[$input] = NotationManager::convertToKebabCase($input);
        }
        return self::$cache[$input];
    }
}
```

### 2. Wrapper per Routing Specifico
```php
class AppRouter extends Router {
    public static function redirectToLogin(): Response {
        return parent::redirect('auth/login');
    }

    public static function redirectToDashboard(): Response {
        return parent::redirect('user/dashboard');
    }
}
```

### 3. Gestione Buffer con Try/Finally
```php
class SafeBufferManager {
    public static function safeCapture(callable $callback): string {
        BufferManager::start();
        try {
            $callback();
            return BufferManager::getContents();
        } finally {
            BufferManager::end();
        }
    }
}
```

---

## Troubleshooting Comuni

### Problema: ModuleManager non trova i moduli
**Soluzione**: Verificare che i moduli siano registrati correttamente in `config.php`:
```php
const MODULE_FOLDERS = ['Core', 'YourModule'];
```

### Problema: NotationManager conversioni sbagliate
**Soluzione**: Verificare che l'input sia nel formato corretto. Ad esempio:
```php
// SBAGLIATO
NotationManager::convertToKebabCase('already-kebab-case');

// CORRETTO
NotationManager::convertToKebabCase('CamelCaseString');
```

### Problema: Router redirect non funziona
**Soluzione**: Assicurarsi che non ci sia output prima del redirect:
```php
// SBAGLIATO
echo "Debug info";
Router::redirect('home');

// CORRETTO
Router::redirect('home');
exit; // Fermare l'esecuzione dopo redirect
```

---

[Indice](index.md) | Precedente: [API Reference](api-reference.md) | Successivo: [Custom Types](custom-types.md)
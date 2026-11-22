# Traits per Enumerazioni

I **Traits** di SismaFramework estendono le funzionalità delle [enumerazioni](enumerations.md) PHP 8.3+, fornendo capacità avanzate per localizzazione, gestione gerarchica e creazione di dropdown dinamici.

## `LocalizableEnumeration`

Il trait di base per le enumerazioni i cui case hanno label nel file di localizzazione. Le regole con cui vengono letti i valori (la chiave riservata `label`, i percorsi, i valori `null`) sono descritte in [Localizzazione delle Enumerazioni](internationalization.md#localizzazione-delle-enumerazioni).

È adatto agli enum che hanno bisogno delle label ma non vanno offerti come scelte di una select, come i messaggi d'errore.

```php
use SismaFramework\Core\Traits\LocalizableEnumeration;

enum FormError: string
{
    use LocalizableEnumeration;

    case missingName = 'MN';
    case invalidDate = 'ID';

    public function getMessage(string ...$arguments): string
    {
        return vsprintf($this->getFriendlyLabel(), $arguments);
    }
}
```

### Metodi Disponibili

#### `getFriendlyLabel(string|array $path = [], Localizator $localizator = new Localizator()): string`
Restituisce la label del case o, se è indicato un percorso, dell'elemento che si trova in quel punto. Il percorso è una stringa per un solo livello o un array di chiavi per più livelli.

#### `getLocalizedAttribute(string|array $path, Localizator $localizator = new Localizator()): string|array|null`
Restituisce il valore grezzo che si trova nel punto indicato dal percorso.

In entrambi i metodi il `Localizator` determina la lingua: senza indicarlo si usa la lingua della richiesta o quella di configurazione (vedi [Scelta della Lingua](internationalization.md#scelta-della-lingua)).

## `SelectableEnumeration`

Estende `LocalizableEnumeration` con la creazione di array di scelte per i form `<select>`. Un enum selezionabile dispone quindi anche di `getFriendlyLabel()` e `getLocalizedAttribute()`.

### Funzionalità Principali

```php
use SismaFramework\Core\Traits\SelectableEnumeration;

enum Status: string
{
    use SelectableEnumeration;

    case active = 'active';
    case inactive = 'inactive';
    case pending = 'pending';
}

$label = Status::active->getFriendlyLabel(); // "Attivo" (dal file di lingua)

$choices = Status::getChoiceFromEnumerations();
// ['Attivo' => 'active', 'Inattivo' => 'inactive', 'In Attesa' => 'pending']
```

### Metodi Disponibili

#### `getChoiceFromEnumerations(string|array $path = [], Localizator $localizator = new Localizator()): array`
Metodo statico che restituisce un array associativo `[label => valore]` per tutti i case dell'enum, ideale per form HTML. Con un percorso, la label di ogni case viene letta in quel punto: ad esempio `getChoiceFromEnumerations('female')` costruisce le scelte con le label femminili.

> **Nota**: dalla versione 13.0.0 i trait delle enumerazioni non accettano più un `Language`, deprecato nella 12.17.0. Usa la lingua della richiesta oppure passa un `Localizator`: `getFriendlyLabel($language)` diventa `getFriendlyLabel([], new Localizator($language))`; vedi [UPGRADING.md](../UPGRADING.md#da-12x-a-13x).

## `ReferencedSelectableEnumeration`

Estende `SelectableEnumeration` per gestire enum che hanno riferimenti ad altre enumerazioni, permettendo filtri dinamici.

### Esempio Pratico

```php
use SismaFramework\Core\Traits\ReferencedSelectableEnumeration;

enum City: string
{
    use ReferencedSelectableEnumeration;

    case rome = 'rome';
    case milan = 'milan';
    case turin = 'turin';
    case naples = 'naples';

    public function getRegion(): Region
    {
        return match($this) {
            self::rome, self::naples => Region::south,
            self::milan, self::turin => Region::north,
        };
    }
}

enum Region: string
{
    case north = 'north';
    case south = 'south';
}

// Ottenere città per regione specifica
$northCities = City::rome->getRegionChoiceBy(new Localizator(), Region::north);
// ['Milano' => 'milan', 'Torino' => 'turin']
```

### Funzionalità Magic Methods

Il trait utilizza `__call()` per creare metodi dinamici nel formato `{metodo}ChoiceBy()`, che ricevono un `Localizator` e il valore da filtrare:
- `getRegionChoiceBy($localizator, $region)` → filtro per regione
- `getCategoryChoiceBy($localizator, $category)` → filtro per categoria

## `SelfReferencedEnumeration`

Permette di creare enum con strutture gerarchiche padre-figlio.

### Implementazione

```php
use SismaFramework\Core\Traits\SelfReferencedEnumeration;

enum MenuCategory: string
{
    use SelfReferencedEnumeration;

    case products = 'products';
    case electronics = 'electronics';
    case computers = 'computers';
    case laptops = 'laptops';

    public function getParent(): ?self
    {
        return match($this) {
            self::electronics => self::products,
            self::computers => self::electronics,
            self::laptops => self::computers,
            self::products => null,
        };
    }
}

// Uso
$category = MenuCategory::laptops;
$parent = $category->getParent(); // MenuCategory::computers
$root = MenuCategory::products->getParent(); // null
```

## `MultipleSelfReferencedEnumeration`

Gestisce relazioni padre-figlio dove un elemento può avere più figli.

### Esempio Avanzato

```php
use SismaFramework\Core\Traits\MultipleSelfReferencedEnumeration;

enum Permission: string
{
    use MultipleSelfReferencedEnumeration;

    case admin = 'admin';
    case user_management = 'user_management';
    case user_create = 'user_create';
    case user_edit = 'user_edit';
    case user_delete = 'user_delete';

    public function getSons(): array
    {
        return match($this) {
            self::admin => [self::user_management],
            self::user_management => [
                self::user_create,
                self::user_edit,
                self::user_delete
            ],
            default => [],
        };
    }
}

// Ottenere dropdown con i figli di un permesso
$userManagementChoices = Permission::user_management->getChoiceByMultipleParent();
// ['Crea Utente' => 'user_create', 'Modifica Utente' => 'user_edit', 'Elimina Utente' => 'user_delete']
```

`getChoiceByMultipleParent()` accetta un `Localizator` per indicare una lingua diversa da quella della richiesta.

## Integrazione con Form HTML

### Esempio Completo di Dropdown Localizzato

```php
// Nel controller
$statuses = Status::getChoiceFromEnumerations();
$this->vars['statusChoices'] = $statuses;

// Nella vista
echo '<select name="status">';
foreach ($statusChoices as $label => $value) {
    echo '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
}
echo '</select>';
```

### Form con Filtri Dinamici

```php
// JavaScript per dropdown dinamici
echo '<select id="region" onchange="updateCities()">';
foreach (Region::getChoiceFromEnumerations() as $label => $value) {
    echo '<option value="' . $value . '">' . $label . '</option>';
}
echo '</select>';

echo '<select id="cities"></select>';

// La funzione updateCities() chiamerà un endpoint AJAX che userà:
// City::rome->getRegionChoiceBy(new Localizator(), $selectedRegion)
```

## Pattern di Localizzazione

### File di Lingua per Trait

Nel file `Application/Locales/it_IT.json`, sotto il nome breve dell'enumerazione con l'iniziale minuscola:

```json
{
    "enumerations": {
        "status": {
            "active": "Attivo",
            "inactive": "Inattivo",
            "pending": "In Attesa"
        },
        "permission": {
            "admin": "Amministratore",
            "user_management": "Gestione Utenti",
            "user_create": "Crea Utente"
        }
    }
}
```

## Best Practices

### 1. Convenzioni di Naming
- Metodi `getXxx()` per relazioni: ritornano singoli valori
- Metodi `getSons()` per array di figli
- Usa nomi descrittivi per le relazioni

### 2. Lingua
- Imposta la lingua della richiesta con `Localizator::setLanguage()` invece di passarla a ogni chiamata
- Passa un `Localizator` solo quando serve una lingua diversa da quella della richiesta

### 3. Manutenibilità
- Definisci sempre un case `default` nei match
- Usa enum separati per logiche di dominio diverse
- Mantieni le gerarchie semplici (max 3-4 livelli)

---

[Indice](index.md) | Precedente: [Enumerazioni](enumerations.md) | Successivo: [Configuration Reference](configuration-reference.md)
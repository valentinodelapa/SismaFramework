# Traits per Enumerazioni

I **Traits** di SismaFramework estendono le funzionalità delle [enumerazioni](enumerations.md) PHP 8.1+, fornendo capacità avanzate per localizzazione, gestione gerarchica e creazione di dropdown dinamici.

## `SelectableEnumeration`

Il trait fondamentale che aggiunge supporto per la localizzazione automatica e la creazione di array per form `<select>`.

### Funzionalità Principali

```php
use SismaFramework\Core\Traits\SelectableEnumeration;
use SismaFramework\Core\Enumerations\Language;

enum Status: string
{
    use SelectableEnumeration;

    case active = 'active';
    case inactive = 'inactive';
    case pending = 'pending';
}

// Ottenere etichetta localizzata
$language = Language::it_IT;
$label = Status::active->getFriendlyLabel($language); // "Attivo" (dal file di lingua)

// Creare array per dropdown
$choices = Status::getChoiceFromEnumerations($language);
// ['Attivo' => 'active', 'Inattivo' => 'inactive', 'In Attesa' => 'pending']
```

### Metodi Disponibili

#### `getFriendlyLabel(Language $language): string`
Restituisce l'etichetta localizzata per il case enum corrente.

#### `getChoiceFromEnumerations(Language $language): array`
Metodo statico che restituisce un array associativo `[etichetta => valore]` per tutti i case dell'enum, ideale per form HTML.

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
$northCities = City::getRegionChoiceBy($language, Region::north);
// ['Milano' => 'milan', 'Torino' => 'turin']
```

### Funzionalità Magic Methods

Il trait utilizza `__call()` per creare metodi dinamici nel formato `{metodo}ChoiceBy()`:
- `getRegionChoiceBy($language, $region)` → filtro per regione
- `getCategoryChoiceBy($language, $category)` → filtro per categoria

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

    public function getParent(): self
    {
        return match($this) {
            self::electronics => self::products,
            self::computers => self::electronics,
            self::laptops => self::computers,
            default => self::products,
        };
    }
}

// Uso
$category = MenuCategory::laptops;
$parent = $category->getParent(); // MenuCategory::computers
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
$language = Language::it_IT;
$userManagementChoices = Permission::user_management->getChoiceByMultipleParent($language);
// ['Crea Utente' => 'user_create', 'Modifica Utente' => 'user_edit', 'Elimina Utente' => 'user_delete']
```

## Integrazione con Form HTML

### Esempio Completo di Dropdown Localizzato

```php
// Nel controller
$statuses = Status::getChoiceFromEnumerations($language);
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
foreach (Region::getChoiceFromEnumerations($language) as $label => $value) {
    echo '<option value="' . $value . '">' . $label . '</option>';
}
echo '</select>';

echo '<select id="cities"></select>';

// La funzione updateCities() chiamerà un endpoint AJAX che userà:
// City::getRegionChoiceBy($language, $selectedRegion)
```

## Pattern di Localizzazione

### File di Lingua per Trait

Nel file `Lang/it_IT.php`:

```php
return [
    'enumerations' => [
        'Status' => [
            'active' => 'Attivo',
            'inactive' => 'Inattivo',
            'pending' => 'In Attesa'
        ],
        'Permission' => [
            'admin' => 'Amministratore',
            'user_management' => 'Gestione Utenti',
            'user_create' => 'Crea Utente'
        ]
    ]
];
```

## Best Practices

### 1. Convenzioni di Naming
- Metodi `getXxx()` per relazioni: ritornano singoli valori
- Metodi `getSons()` per array di figli
- Usa nomi descrittivi per le relazioni

### 2. Performance
- I trait utilizzano caching interno per evitare ricalcoli
- Le chiamate a `getFriendlyLabel()` sono ottimizzate

### 3. Manutenibilità
- Definisci sempre un case `default` nei match
- Usa enum separati per logiche di dominio diverse
- Mantieni le gerarchie semplici (max 3-4 livelli)

---

[Indice](index.md) | Precedente: [Enumerazioni](enumerations.md) | Successivo: [Sicurezza](security.md)
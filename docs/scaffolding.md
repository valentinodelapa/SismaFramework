# Scaffolding

La libreria è dotata di un meccanismo di scaffolding che, data un'entità, ne genera i seguenti relativi componenti con le implementazioni di base descritte:

- `Controller`: viene generato implementando il pattern CRUD con i metodi `index`, `create`, `update` e `delete`

- `Model`: nel comando può essere indicata la tipologia della classe astratta che lo stesso dovrà estendere (`BaseModel`, `DependentModel` o `SelfReferencedModel`). Qualora la scelta non venga esplicitata, il sistema effettuerà un controllo tramite il quale stabilirà in autonomia la classe astratta da estendere.

- `Form`: nell'implementazione base, verranno implementati i filtri standard per tutte le proprietà dell'entità. Il sistema analizza automaticamente il tipo di ogni proprietà e genera il filtro appropriato (vedi sezione "Generazione Automatica dei Filtri" sotto).

- `Views`: nella cartella Views del modulo, verrà creata la cartella che si riferisce al controller ed, al suo interno, verranno creati (vuoti) i files relativi alle view `index`, `create` e `update`

Tale comando presuppone che l'entità di riferimento sia stata precedentemente creata manualmente dallo sviluppatore.

## Generazione Automatica dei Filtri

Il sistema di scaffolding analizza ogni proprietà dell'entità tramite **Reflection** e genera automaticamente il filtro di validazione appropriato nel Form. La mappatura avviene secondo la seguente logica:

### Tipi Supportati e Filtri Generati

| Tipo Proprietà | FilterType Generato | Descrizione |
|----------------|---------------------|-------------|
| `int` | `FilterType::isInteger` | Valida numeri interi |
| `float` | `FilterType::isFloat` | Valida numeri decimali |
| `string` | `FilterType::isString` | Valida stringhe di testo |
| `bool` | `FilterType::isBoolean` | Valida valori booleani |
| `BaseEntity` (e sottoclassi) | `FilterType::isEntity` | Valida entità referenziate |
| `BackedEnum` | `FilterType::isEnumeration` | Valida enum PHP 8.1+ |
| `SismaDate` | `FilterType::isDate` | Valida date |
| `SismaDateTime` | `FilterType::isDatetime` | Valida date con orario |
| `SismaTime` | `FilterType::isTime` | Valida orari |

### Gestione Proprietà Nullable

Se una proprietà è dichiarata **nullable** (es. `?string`, `?int`), il sistema aggiunge automaticamente il flag `true` come terzo parametro del filtro per permettere valori nulli:

```php
// Proprietà non-nullable
protected string $title;
// Genera: $this->addFilterFieldMode("title", FilterType::isString);

// Proprietà nullable
protected ?string $description;
// Genera: $this->addFilterFieldMode("description", FilterType::isString, [], true);
```

### Esempio di Form Generato

Data questa entità:

```php
class Product extends BaseEntity
{
    protected int $id;
    protected string $name;
    protected ?string $description;
    protected float $price;
    protected bool $inStock;
    protected SismaDateTime $createdAt;
    protected Category $category;
    protected ProductStatus $status; // BackedEnum
}
```

Il sistema genererà automaticamente questo codice nel Form:

```php
protected function setFilterFieldsMode(): void
{
    $this->addFilterFieldMode("id", FilterType::isInteger)
         ->addFilterFieldMode("name", FilterType::isString)
         ->addFilterFieldMode("description", FilterType::isString, [], true)
         ->addFilterFieldMode("price", FilterType::isFloat)
         ->addFilterFieldMode("inStock", FilterType::isBoolean)
         ->addFilterFieldMode("createdAt", FilterType::isDatetime)
         ->addFilterFieldMode("category", FilterType::isEntity)
         ->addFilterFieldMode("status", FilterType::isEnumeration);
}
```

### Personalizzazione Post-Generazione

Dopo la generazione, è possibile personalizzare i filtri manualmente, ad esempio:

- Aggiungendo validazioni di lunghezza per le stringhe
- Specificando range per i numeri
- Aggiungendo filtri custom nel metodo `customFilter()`
- Implementando logica di validazione complessa

**Esempio di personalizzazione:**

```php
protected function setFilterFieldsMode(): void
{
    $this->addFilterFieldMode("id", FilterType::isInteger)
         ->addFilterFieldMode("name", FilterType::isLimitString, [3, 100]) // Min 3, Max 100 caratteri
         ->addFilterFieldMode("description", FilterType::isString, [], true)
         ->addFilterFieldMode("price", FilterType::isFloat)
         ->addFilterFieldMode("inStock", FilterType::isBoolean)
         ->addFilterFieldMode("createdAt", FilterType::isDatetime)
         ->addFilterFieldMode("category", FilterType::isEntity)
         ->addFilterFieldMode("status", FilterType::isEnumeration);
}
```

## Funzionamento

Lo scaffolding viene eseguito tramite un comando da lanciare dalla riga di comando dalla root del progetto.

### Sintassi del comando

Il comando per avviare il processo di scaffolding è il seguente:

```bash
php SismaFramework/Console/sisma scaffold <entity> <module> [options]
```

### Argomenti

Il comando richiede due argomenti obbligatori:

- `<entity>`: Il nome della classe dell'entità per la quale generare i file (es. `Product`).
- `<module>`: Il nome del modulo in cui si trova l'entità e dove verranno creati i nuovi file (es. `Catalog`).

### Opzioni

È possibile personalizzare il comportamento del comando tramite le seguenti opzioni:

- `--force`: Se i file da generare (Controller, Model, Form, Views) esistono già, questa opzione forza la loro sovrascrittura. In assenza di questa opzione, il comando si interromperà con un errore per prevenire la perdita di dati.

- `--type=<TIPO>`: Permette di specificare esplicitamente il tipo di modello da generare. I valori accettati sono:
  
  - `BaseModel`
  - `DependentModel`
  - `SelfReferencedModel`
    Se questa opzione non viene fornita, il sistema analizzerà l'entità per determinare automaticamente il tipo di modello più appropriato. Ad esempio, se l'entità estende `SelfReferencedEntity`, verrà generato un `SelfReferencedModel`. Se ha proprietà che sono altre entità, verrà generato un `DependentModel`. Altrimenti, verrà generato un `BaseModel`.

- `--template=<PERCORSO>`: Permette di specificare un percorso a una cartella contenente template personalizzati per la generazione dei file. Se questa opzione non viene specificata, verranno utilizzati i template predefiniti del framework. La cartella deve avere la seguente struttura:
  
  - `Controller.tpl`
  - `Form.tpl`
  - `Model.tpl`
  - `Views/`
    - `create.tpl`
    - `index.tpl`
    - `update.tpl`

### Prerequisiti e Struttura delle Cartelle

Per il corretto funzionamento dello scaffolding, è necessario che:

1. La classe dell'entità esista già all'interno del modulo specificato, nel percorso `<NomeModulo>/Application/Entities/<NomeEntita>.php`.
2. Il modulo abbia la struttura di cartelle standard del framework. In particolare, devono esistere le cartelle `Controllers`, `Models` e `Forms` all'interno di `<NomeModulo>/Application/`.

Qualora la struttura delle cartelle del modulo non fosse presente, il comando restituirà un errore, suggerendo di utilizzare l'opzione `--force` per crearla automaticamente.

### Esempio di utilizzo

Supponiamo di aver creato un'entità `Product` nel modulo `Catalog`. Per generare il controller, il modello, il form e le viste di base, si può eseguire il seguente comando dalla root del progetto:

```bash
php SismaFramework/Console/sisma scaffold Product Catalog
```

Questo comando creerà i seguenti file:

- `Catalog/Application/Controllers/ProductController.php`
- `Catalog/Application/Models/ProductModel.php`
- `Catalog/Application/Forms/ProductForm.php`
- `Catalog/Application/Views/product/index.php` (e gli altri file delle viste)

Se i file esistono già e si desidera sovrascriverli, si può usare:

```bash
php SismaFramework/Console/sisma scaffold Product Catalog --force
```

Per forzare un tipo di modello specifico:

```bash
php SismaFramework/Console/sisma scaffold Product Catalog --type=DependentModel
```

Per utilizzare template personalizzati:

```bash
php SismaFramework/Console/sisma scaffold Product Catalog --template=/path/to/custom/templates
```

# Scaffolding

La libreria è dotata di un meccanismo di scaffolding che, data un'entità, ne genenera i seguenti relativi componenti con le implementazioni di base descritte:

- `Controller`: viene generato implementando il pattend CRUD con i metodi `index`, `create`, `update `e `delete`

- `Model`: nel comando può essere indicata la tipologia la classe astratta che lo stesso dovrà estendere (`BaseModel`, `DependentModel` o `SelfReferencedModel`). Qualora la scenta non venga esplicitata, il sistema effettuerà un controllo tramikte il quale stabilirà il autonomia la classe astratta da estendere.

- `Form`: nell'implementazione base, verranno implementati i filtri standard per tutte le proprietà dell'entità.

- `Views`: nella cartella Views del modulo, verrà creata la cartella che si riferisce al controller ed, al suo interno, verranno creati (vuoti) i files relativi alle view `index`, `create` e `update`

Tale comando prosuppone che l'entità di riferimento sia stata precedentemente creata manualmente dallo sviluppatore.

## Funzionamento

Lo scaffolding viene eseguito tramite un comando da lanciare dalla riga di comando dalla root del progetto.

### Sintassi del comando

Il comando per avviare il processo di scaffolding è il seguente:

```bash
php SismaFramework/Console/sisma scaffold <entity> <module> [options]
```

### Argomenti

Il comando richiede due argomenti obbligatori:

- `<entity>`: Il nome della classe dell'entità per la quale generare i file (es. `ProductEntity`).
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
php SismaFramework/Console/sisma scaffold ProductEntity Catalog --force
```

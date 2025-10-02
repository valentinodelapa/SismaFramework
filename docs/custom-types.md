# Custom Types

SismaFramework definisce diversi tipi personalizzati per gestire casi d'uso specifici e migliorare la type safety del codice. Questi tipi forniscono funzionalità specializzate per la gestione degli errori nei form e strutture dati avanzate nell'ORM.

## Panoramica dei Custom Types

| Tipo | Namespace | Scopo | Utilizzo Principale |
|------|-----------|-------|-------------------|
| [FormFilterError](#formfiltererror) | `Core\CustomTypes` | Gestione errori validazione | Sistema form e validazione |
| [FormFilterErrorCollection](#formfiltererrorcollection) | `Core\CustomTypes` | Collezione errori form | Aggregazione errori multipli |
| [SismaStandardClass](#sismastandardclass) | `Orm\CustomTypes` | Estensione stdClass | Compatibilità ORM |

---

## FormFilterError

Il `FormFilterError` è una classe specializzata per la gestione degli errori di validazione nei form. Utilizza proprietà magiche per fornire un'interfaccia fluida e intuitiva per accedere agli errori e ai messaggi personalizzati.

### Caratteristiche Principali

- **Accesso Magico**: Usa `__get()` e `__set()` per accesso dinamico agli errori
- **Placeholder System**: Utilizza suffissi specifici per differenziare tipi di errore
- **Supporto Gerarchico**: Gestisce errori nested per form complessi
- **Auto-Creazione**: Crea automaticamente oggetti quando non esistono

### Placeholder Constants

```php
const ERROR_PLACEHOLDER = 'Error';
const CUSTOM_MESSAGE_PLACEHOLDER = 'CustomMessage';
const COLLECTION_PLACEHOLDER = 'Collection';
```

### Utilizzi Comuni

#### Gestione Errori Base
```php
$formError = new FormFilterError();

// Impostare errori
$formError->usernameError = true;
$formError->emailError = false;

// Impostare messaggi personalizzati
$formError->usernameCustomMessage = "Username già esistente";
$formError->emailCustomMessage = "Email non valida";

// Leggere errori
if ($formError->usernameError) {
    echo $formError->usernameCustomMessage;
}
```

#### Gestione Form Nested
```php
// Per form con entità correlate
$formError = new FormFilterError();

// Generare errori da un array di form
$entityFromForm = [
    'user' => $userForm,
    'profile' => $profileForm,
    'addresses' => [$address1Form, $address2Form]
];

$formError->generateFormFilterErrorFromForm($entityFromForm);

// Accesso agli errori nested
if ($formError->user->usernameError) {
    echo "Errore nel username";
}

// Accesso a collezioni di errori
foreach ($formError->addresses as $index => $addressError) {
    if ($addressError->streetError) {
        echo "Errore nell'indirizzo {$index}";
    }
}
```

#### Esportazione Errori
```php
$formError = new FormFilterError();
$formError->usernameError = true;
$formError->usernameCustomMessage = "Username richiesto";

// Convertire in array per JSON o template
$errorArray = $formError->getErrorsToArray();
/* Risultato:
[
    'usernameError' => true,
    'usernameCustomMessage' => 'Username richiesto'
]
*/

// Utilizzare in template Twig/Smarty
echo json_encode($errorArray);
```

### Integrazione con BaseForm

```php
class UserForm extends BaseForm {
    private FormFilterError $filterErrors;

    public function getFilterErrors(): FormFilterError {
        return $this->filterErrors;
    }

    public function validate(): bool {
        $this->filterErrors = new FormFilterError();

        if (empty($this->entity->username)) {
            $this->filterErrors->usernameError = true;
            $this->filterErrors->usernameCustomMessage = "Username obbligatorio";
        }

        if (!filter_var($this->entity->email, FILTER_VALIDATE_EMAIL)) {
            $this->filterErrors->emailError = true;
            $this->filterErrors->emailCustomMessage = "Email non valida";
        }

        return !$this->hasErrors();
    }

    private function hasErrors(): bool {
        $errors = $this->filterErrors->getErrorsToArray();
        return !empty(array_filter($errors, fn($error) => $error === true));
    }
}
```

---

## FormFilterErrorCollection

`FormFilterErrorCollection` estende `ArrayObject` per fornire una collezione tipizzata di oggetti `FormFilterError`. Gestisce automaticamente la creazione di oggetti mancanti e supporta l'iterazione sicura.

### Caratteristiche

- **Estende ArrayObject**: Supporta tutte le operazioni di array standard
- **Auto-Creation**: Restituisce automaticamente `FormFilterError` vuoti per chiavi inesistenti
- **Type Safety**: Garantisce che tutti gli elementi siano `FormFilterError`

### Utilizzi Comuni

#### Gestione Collezioni di Form
```php
$errorCollection = new FormFilterErrorCollection();

// Aggiungere errori alla collezione
$userError = new FormFilterError();
$userError->usernameError = true;
$errorCollection->append($userError);

$profileError = new FormFilterError();
$profileError->bioError = true;
$errorCollection->append($profileError);

// Iterazione sicura
foreach ($errorCollection as $index => $formError) {
    $errors = $formError->getErrorsToArray();
    if (!empty($errors)) {
        echo "Form {$index} ha errori: " . json_encode($errors);
    }
}
```

#### Accesso Sicuro agli Errori
```php
$errorCollection = new FormFilterErrorCollection();

// Anche se l'indice non esiste, restituisce FormFilterError vuoto
$error = $errorCollection[999]; // Non genera errore
$error->someFieldError = true; // Funziona comunque

// Verificare se ci sono errori reali
if ($errorCollection[0]->usernameError) {
    echo "Errore nel primo form";
}
```

#### Integrazione con Form Master-Detail
```php
class OrderFormManager {
    private FormFilterErrorCollection $itemErrors;

    public function __construct() {
        $this->itemErrors = new FormFilterErrorCollection();
    }

    public function validateOrderItems(array $orderItems): bool {
        $hasErrors = false;

        foreach ($orderItems as $index => $item) {
            $itemForm = new OrderItemForm($item);
            if (!$itemForm->isValid()) {
                $this->itemErrors[$index] = $itemForm->getFilterErrors();
                $hasErrors = true;
            }
        }

        return !$hasErrors;
    }

    public function getItemErrors(): FormFilterErrorCollection {
        return $this->itemErrors;
    }
}

// Utilizzo
$orderManager = new OrderFormManager();
if (!$orderManager->validateOrderItems($orderItems)) {
    $errors = $orderManager->getItemErrors();

    // Mostrare errori per item specifico
    if ($errors[0]->quantityError) {
        echo "Quantità non valida per il primo item";
    }
}
```

---

## SismaStandardClass

`SismaStandardClass` è una semplice estensione della classe `stdClass` di PHP, progettata per fornire compatibilità e identificazione nel contesto dell'ORM di SismaFramework.

### Caratteristiche

- **Estensione Minimale**: Mantiene tutte le funzionalità di `stdClass`
- **Identificazione Type**: Permette di identificare oggetti specifici del framework
- **Compatibilità ORM**: Utilizzata internamente dall'ORM per operazioni specifiche

### Utilizzi nel Framework

#### Risultati Query Grezzi
```php
// L'ORM potrebbe utilizzare SismaStandardClass per risultati non mappati
class DataMapper {
    public function executeRawQuery(string $sql): array {
        $results = [];
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $obj = new SismaStandardClass();
            foreach ($row as $key => $value) {
                $obj->$key = $value;
            }
            $results[] = $obj;
        }

        return $results;
    }
}
```

#### Strutture Dati Temporanee
```php
// Utilizzare per aggregazioni o calcoli temporanei
class ReportGenerator {
    public function generateSalesReport(): array {
        $report = new SismaStandardClass();
        $report->totalSales = $this->calculateTotalSales();
        $report->averageOrder = $this->calculateAverageOrder();
        $report->topProducts = $this->getTopProducts();

        return $report;
    }
}
```

---

## Patterns di Utilizzo Avanzati

### 1. Form Validation Chain
```php
class ValidationChain {
    private FormFilterError $errors;

    public function __construct() {
        $this->errors = new FormFilterError();
    }

    public function validate(string $field, mixed $value, array $rules): self {
        foreach ($rules as $rule => $param) {
            if (!$this->applyRule($field, $value, $rule, $param)) {
                $this->errors->{$field . 'Error'} = true;
                $this->errors->{$field . 'CustomMessage'} = $this->getRuleMessage($rule, $param);
                break;
            }
        }
        return $this;
    }

    public function getErrors(): FormFilterError {
        return $this->errors;
    }
}

// Utilizzo
$validator = new ValidationChain();
$validator
    ->validate('email', $userInput['email'], ['required' => true, 'email' => true])
    ->validate('age', $userInput['age'], ['required' => true, 'min' => 18]);

$errors = $validator->getErrors();
```

### 2. Error Aggregation Service
```php
class ErrorAggregationService {
    public static function aggregateFormErrors(array $forms): FormFilterErrorCollection {
        $collection = new FormFilterErrorCollection();

        foreach ($forms as $index => $form) {
            if (!$form->isValid()) {
                $collection[$index] = $form->getFilterErrors();
            }
        }

        return $collection;
    }

    public static function hasAnyErrors(FormFilterErrorCollection $collection): bool {
        foreach ($collection as $formError) {
            $errors = $formError->getErrorsToArray();
            if (!empty(array_filter($errors, fn($error) => $error === true))) {
                return true;
            }
        }
        return false;
    }
}
```

### 3. Dynamic Object Builder
```php
class DynamicObjectBuilder {
    public static function buildFromArray(array $data): SismaStandardClass {
        $obj = new SismaStandardClass();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $obj->$key = self::buildFromArray($value);
            } else {
                $obj->$key = $value;
            }
        }

        return $obj;
    }
}

// Utilizzo
$data = [
    'user' => ['name' => 'John', 'email' => 'john@example.com'],
    'settings' => ['theme' => 'dark', 'notifications' => true]
];

$obj = DynamicObjectBuilder::buildFromArray($data);
echo $obj->user->name; // "John"
echo $obj->settings->theme; // "dark"
```

---

## Best Practices

### 1. Nomenclatura Consistente
```php
// Sempre usare i placeholder corretti
$formError->fieldnameError = true;           // ✓ Corretto
$formError->fieldnameCustomMessage = "...";  // ✓ Corretto
$formError->fieldname_error = true;          // ✗ Sbagliato
```

### 2. Inizializzazione Sicura
```php
// Sempre inizializzare FormFilterError nei form
class UserForm extends BaseForm {
    private FormFilterError $filterErrors;

    public function __construct() {
        $this->filterErrors = new FormFilterError(); // ✓ Inizializzazione
        parent::__construct();
    }
}
```

### 3. Gestione Errori Gerarchici
```php
// Per form complessi, strutturare gli errori logicamente
$formError = new FormFilterError();

// Livello principale
$formError->generateFormFilterErrorFromForm([
    'user' => $userForm,
    'company' => $companyForm,
    'contacts' => [$contact1Form, $contact2Form]
]);

// Accesso strutturato
if ($formError->user->emailError) {
    // Gestire errore email utente
}

if ($formError->contacts[0]->phoneError) {
    // Gestire errore telefono primo contatto
}
```

---

[Indice](index.md) | Precedente: [Helper Classes](helper-classes.md) | Successivo: [Enumerations](enumerations.md)
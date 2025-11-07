# Guida all'Aggiornamento di SismaFramework

Questa guida fornisce istruzioni dettagliate per aggiornare SismaFramework tra versioni major.

## Indice

- [Da 9.x a 10.x](#da-9x-a-10x)

---

## Da 9.x a 10.x

La versione 10.0.0 introduce alcune modifiche che rompono la retrocompatibilità. Questa sezione fornisce una guida completa per la migrazione.

### Breaking Changes

#### 1. CallableController::checkCompatibility() è ora statico

**Impatto**: Medio  
**Componenti interessati**: Controller che implementano `CallableController`

Il metodo `checkCompatibility()` nell'interfaccia `CallableController` è stato modificato da metodo di istanza a metodo statico.

**Prima (9.x)**:
```php
class MyController extends BaseController implements CallableController
{
    public function checkCompatibility(array $arguments): bool
    {
        return count($arguments) === 2;
    }
}
```

**Dopo (10.x)**:
```php
class MyController extends BaseController implements CallableController
{
    public static function checkCompatibility(array $arguments): bool
    {
        return count($arguments) === 2;
    }
}
```

**Azione richiesta**:
- Aggiungere la keyword `static` alla firma del metodo `checkCompatibility()`
- Se il metodo accede a proprietà di istanza (`$this`), refactorizzare per utilizzare solo i parametri passati

---

#### 2. Rimozione dell'interfaccia CrudInterface

**Impatto**: Basso  
**Componenti interessati**: Controller che implementano `CrudInterface`

L'interfaccia `CrudInterface` è stata rimossa in quanto non forniva valore aggiunto rispetto a `BaseController`.

**Prima (9.x)**:
```php
class PostController extends BaseController implements CrudInterface
{
    // Implementazione
}
```

**Dopo (10.x)**:
```php
class PostController extends BaseController
{
    // Implementazione (nessuna modifica ai metodi)
}
```

**Azione richiesta**:
- Rimuovere `implements CrudInterface` dalla dichiarazione della classe
- Nessuna modifica ai metodi è necessaria

---

#### 3. Language::getFriendlyLabel() richiede file di localizzazione

**Impatto**: Alto  
**Componenti interessati**: Tutti i progetti che utilizzano `Language::getFriendlyLabel()`

Il metodo `getFriendlyLabel()` non utilizza più valori hardcoded ma richiede la presenza di file di localizzazione nella directory `config/locales/`.

**Prima (9.x)**:
```php
// Funzionava anche senza file di localizzazione
$label = Language::getFriendlyLabel('it');
```

**Dopo (10.x)**:
```php
// Richiede il file config/locales/it.json con:
// {
//   "language": {
//     "friendly_label": "Italiano"
//   }
// }
$label = Language::getFriendlyLabel('it');
```

**Azione richiesta**:
1. Creare la directory `config/locales/` se non esiste
2. Per ogni lingua supportata, creare un file JSON (es. `it.json`, `en.json`)
3. Aggiungere la struttura richiesta:
```json
{
  "language": {
    "friendly_label": "Nome Lingua"
  }
}
```

**Esempio di file di localizzazione**:

`config/locales/it.json`:
```json
{
  "language": {
    "friendly_label": "Italiano"
  }
}
```

`config/locales/en.json`:
```json
{
  "language": {
    "friendly_label": "English"
  }
}
```

---

### Miglioramenti non Breaking

#### Lazy Loading della connessione database

La connessione al database viene ora stabilita solo quando effettivamente necessaria, migliorando le performance per operazioni che non richiedono accesso al database.

**Nessuna azione richiesta** - funziona automaticamente.

---

#### Refactoring del DataMapper

Il sistema ORM è stato refactorizzato per migliorare la manutenibilità e la testabilità:
- Introdotto `TransactionManager` per la gestione delle transazioni
- Introdotto `QueryExecutor` per l'esecuzione delle query
- Migliorata la separazione delle responsabilità

**Nessuna azione richiesta** - l'API pubblica rimane invariata.

---

### Checklist di Migrazione

Utilizza questa checklist per assicurarti di aver completato tutti i passaggi necessari:

- [ ] **Controller con CallableController**
  - [ ] Aggiunto `static` a tutti i metodi `checkCompatibility()`
  - [ ] Verificato che `checkCompatibility()` non utilizzi `$this`
  
- [ ] **Controller con CrudInterface**
  - [ ] Rimosso `implements CrudInterface` dalle dichiarazioni delle classi
  
- [ ] **Localizzazione**
  - [ ] Creata directory `config/locales/`
  - [ ] Creati file JSON per ogni lingua supportata
  - [ ] Aggiunta struttura `language.friendly_label` in ogni file
  - [ ] Testato `Language::getFriendlyLabel()` per tutte le lingue
  
- [ ] **Testing**
  - [ ] Eseguiti tutti i test unitari
  - [ ] Eseguiti test di integrazione
  - [ ] Verificato funzionamento in ambiente di staging

---

### Supporto e Risorse

- **Changelog completo**: Vedi [CHANGELOG.md](CHANGELOG.md)
- **Policy di supporto**: Vedi [SECURITY.md](SECURITY.md)
- **Issue tracker**: [GitHub Issues](https://github.com/tuouser/sismaframework/issues)

Per domande o problemi durante la migrazione, apri una issue sul repository GitHub.

# Contribuire a SismaFramework

Grazie per il tuo interesse nel contribuire a SismaFramework! Questo documento ti guiderà attraverso il processo di contribuzione.

## Codice di Condotta

Partecipando a questo progetto, ti impegni a mantenere un ambiente rispettoso e collaborativo. Trattiamo tutti i contributori con rispetto, indipendentemente dal loro livello di esperienza.

## Come Contribuire

### Segnalare Bug

Se trovi un bug, apri una [Issue](https://github.com/valentinodelapa/SismaFramework/issues) includendo:

- **Titolo chiaro e descrittivo**
- **Passi per riprodurre il problema**
- **Comportamento atteso vs comportamento effettivo**
- **Versione di SismaFramework e PHP**
- **Sistema operativo e web server utilizzato**

### Proporre Nuove Funzionalità

Prima di iniziare a lavorare su una nuova funzionalità:

1. Controlla le [Issue esistenti](https://github.com/valentinodelapa/SismaFramework/issues) per vedere se è già stata proposta
2. Apri una nuova Issue per discutere la tua idea
3. Attendi feedback prima di iniziare l'implementazione

### Contribuire con Codice

#### 1. Setup dell'Ambiente

SismaFramework utilizza **Git submodule** come metodo di installazione, non Composer. Per contribuire:

```bash
# Fork del repository su GitHub, poi:
git clone https://github.com/TUO-USERNAME/SismaFramework.git
cd SismaFramework
composer install
```

#### 2. Crea un Branch

Crea un branch dal branch `develop`:

```bash
git checkout develop
git pull origin develop
git checkout -b feature/nome-funzionalita
# oppure
git checkout -b fix/descrizione-bug
```

**Convenzioni per i nomi dei branch:**
- `feature/` - nuove funzionalità
- `fix/` - correzione bug
- `docs/` - modifiche alla documentazione
- `refactor/` - refactoring del codice

#### 3. Scrivi il Codice

Segui le [convenzioni del framework](docs/conventions.md):

- **PHP 8.3+** con tipizzazione forte
- **CamelCase** per classi e metodi
- **snake_case** per le tabelle del database (al singolare)
- Usa `BackedEnum` per le enumerazioni
- Aggiungi l'attributo `#[\Override]` quando sovrascrivi metodi

#### 4. Esegui i Test

Prima di inviare una PR, assicurati che tutti i test passino:

```bash
# Esegui tutti i test
vendor/bin/phpunit

# Esegui test con coverage
vendor/bin/phpunit --coverage-html coverage/

# Esegui una suite specifica
vendor/bin/phpunit --testsuite Core
```

Il target di code coverage è **>80%**. Se aggiungi nuove funzionalità, includi i test corrispondenti.

#### 5. Invia una Pull Request

1. Fai push del tuo branch:
   ```bash
   git push origin feature/nome-funzionalita
   ```

2. Apri una Pull Request verso il branch `develop`

3. Nella descrizione della PR, includi:
   - Descrizione delle modifiche
   - Issue correlate (es. "Fixes #123")
   - Screenshot se ci sono modifiche visibili
   - Note per i reviewer

### Contribuire alla Documentazione

La documentazione si trova nella cartella `docs/`. Per contribuire:

1. I file sono in formato Markdown
2. Segui la struttura esistente
3. Aggiorna `docs/index.md` se aggiungi nuovi file
4. Testa i link interni

### Traduzioni

Attualmente la documentazione è in italiano. Le traduzioni in altre lingue sono benvenute:

1. Crea una cartella `docs-{lingua}/` (es. `docs-en/`)
2. Traduci i file mantenendo la stessa struttura
3. Aggiorna i link interni

## Struttura del Progetto

```
SismaFramework/
├── Autoload/           # Sistema di autoloading
├── Config/             # Configurazione del framework
├── Console/            # Comandi CLI (install, scaffold)
├── Core/               # Componenti core del framework
├── Orm/                # Object-Relational Mapper
├── Security/           # Autenticazione e autorizzazione
├── Structural/         # Controller strutturali
├── Tests/              # Test suite
├── TestsApplication/   # Applicazione di test
├── Sample/             # Modulo di esempio e documentazione
├── docs/               # Documentazione
└── Public/             # Entry point pubblico
```

## Linee Guida per il Codice

### Stile

- Indentazione: 4 spazi (no tab)
- Lunghezza massima riga: 120 caratteri
- Una classe per file
- Ordine dei metodi: `public`, `protected`, `private`

### Documentazione del Codice

```php
/**
 * Breve descrizione del metodo.
 *
 * Descrizione più dettagliata se necessaria.
 *
 * @param string $param Descrizione del parametro
 * @return bool Descrizione del valore di ritorno
 * @throws ExceptionType Quando viene lanciata
 */
public function metodo(string $param): bool
{
    // ...
}
```

### Commit Messages

Usa messaggi di commit chiari e descrittivi:

```
tipo: breve descrizione

Descrizione più dettagliata se necessaria.
Spiega il cosa e il perché, non il come.

Fixes #123
```

**Tipi di commit:**
- `feat`: nuova funzionalità
- `fix`: correzione bug
- `docs`: documentazione
- `style`: formattazione (no cambi logica)
- `refactor`: refactoring
- `test`: aggiunta o modifica test
- `chore`: manutenzione

## Segnalare Vulnerabilità di Sicurezza

**Non aprire Issue pubbliche per vulnerabilità di sicurezza.**

Consulta [SECURITY.md](SECURITY.md) per le istruzioni su come segnalare responsabilmente le vulnerabilità.

## Domande?

Se hai domande sul processo di contribuzione, apri una Issue con l'etichetta `question`.

---

Grazie per contribuire a rendere SismaFramework migliore!

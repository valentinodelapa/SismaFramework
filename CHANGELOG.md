# Changelog

All notable changes to this project will be documented in this file.

## [9.1.3] - 2025-09-28 - Ampliamento Copertura Test e Correzioni Interne

### 🔧 Correzioni

* **Correzione Nomenclatura Classe `Communication`**: È stata corretta la denominazione della classe da `Comunication` a `Communication` e del relativo enum da `ComunicationProtocol` a `CommunicationProtocol` per allinearli alla nomenclatura inglese standard. Inoltre, il metodo `getComunicationProtocol()` è stato rinominato in `getCommunicationProtocol()`. **Nota**: Non si tratta di una *breaking change* poiché queste classi sono utilizzate esclusivamente internamente dal framework (nelle classi `Session` e `Router`) e non fanno parte dell'API pubblica esposta agli sviluppatori.

### 🧪 Test

* **Significativo Ampliamento Copertura Test**: È stata aggiunta una copertura di test completa per componenti critici del framework:
  - **Enumerazioni Core**: `ResponseType`, `RequestType`, `Language`, `ContentType`, `CommunicationProtocol`
  - **Enumerazioni ORM**: `DataType`, `ComparisonOperator`, `LogicalOperator`
  - **Classi HTTP**: `Communication` con test completi per la logica di rilevamento protocollo HTTPS/HTTP
  - **Classi ORM**: `BaseResultSet` con test per l'implementazione Iterator pattern, `BaseModel`, `DependentModel`, `SelfReferencedModel` con test per metodi magic, gestione collezioni e operazioni CRUD
  - I test coprono funzionalità avanzate come i metodi `getFriendlyLabel()` e `getISO6391Label()` di `Language`, le conversioni MIME in `ContentType`, gli adapter SQL per gli operatori ORM, e la logica complessa di gestione delle relazioni nelle classi Model.

### 📝 Documentazione

* **Correzioni Minori**: Aggiornati riferimenti interni e migliorata la coerenza terminologica nella documentazione.

## [9.1.2] - 2025-09-26 - Correzione Link Trasversali Documentazione

### 🔧 Correzioni

* **Correzione Link Trasversali Documentazione**: Risolti 4 link rotti nella documentazione:
  - `overview.md`: `security-component.md` → `security.md`
  - `getting-started.md`: `installation.md#configurazione` → `installation.md#passo-3-configura-i-file-principali`
  - `enumerations.md`: Aggiunta sezione mancante `FilterType` con documentazione completa
  - `api-reference.md`: Aggiunta sezione mancante `ORM Classes` con `DataMapper`, `BaseModel`, `StandardEntity`

### 📝 Documentazione

* **Miglioramento Architettura Cross-References**: Tutti i link trasversali nella documentazione sono ora corretti e funzionanti, migliorando la navigazione tra le sezioni correlate.

## [9.1.1] - 2025-09-26 - Correzione Localizator e Ampliamento Test

### 🔧 Correzioni

* **Correzione `unsetLanguage()` in `Localizator`**: È stato corretto un bug critico nella classe `Localizator` dove il metodo `unsetLanguage()` tentava di eseguire un'operazione di `unset` su una proprietà statica. Tale operazione non è permessa in PHP e causava un `Fatal error`. La logica è stata modificata per impostare la proprietà a `null`, ripristinandone correttamente lo stato iniziale e garantendo il corretto funzionamento del reset della lingua.
* **Correzione Nomenclatura in `BaseFixture`**: È stato corretto un errore di battitura nel metodo `setDependecies()`, rinominandolo in `setDependencies()`. Questo allinea il metodo alla documentazione e alle convenzioni del framework. Le classi fixture personalizzate che estendono `BaseFixture` devono essere aggiornate per utilizzare il nome corretto del metodo. **Nota sul versioning**: Sebbene questa sia tecnicamente una *breaking change*, è stata classificata come `PATCH` perché le classi `Fixture` sono considerate strumenti di sviluppo e non fanno parte dell'API pubblica consumata da un'applicazione in produzione.

### 🧪 Test

* **Ampliamento Copertura Test**: È stata significativamente aumentata la copertura dei test unitari per diversi componenti chiave del framework, incluso `Localizator`, migliorando la robustezza del codice e aiutando a prevenire future regressioni.

### 📝 Documentazione

* **Aggiornamento Documentazione `Data Fixtures`**: La documentazione relativa alle `Data Fixtures` (`data-fixtures.md`) è stata aggiornata per riflettere la correzione del nome del metodo in `setDependencies()`, garantendo coerenza con il codice.
* **Miglioramenti Vari**: Sono state apportate diverse correzioni minori e miglioramenti alla leggibilità in vari file della documentazione per aumentare la chiarezza e l'accuratezza generale.

## [9.1.0] - 2025-09-18 - Miglioramento Iniezione Dati nel Form

### 🚀 Miglioramenti

* **Migliorata Iniezione Dati nel Form**: È stato introdotto un nuovo meccanismo per l'iniezione di dati esterni (es. dalla sessione o altri servizi) all'interno del componente `BaseForm`. Questo migliora la flessibilità e la testabilità dei form, consentendo di popolare i dati in modo più pulito e disaccoppiato dalla superglobale `$_POST`. La funzionalità esistente rimane invariata per garantire la retrocompatibilità.

### 📝 Documentazione

* **Aggiornata Documentazione Form**: La documentazione relativa ai form (`forms.md`) è stata aggiornata per descrivere in dettaglio la nuova funzionalità `addRequest()` e per correggere alcuni esempi di codice, rendendoli più chiari e coerenti con le best practice del framework.
* **Correzione Link di Navigazione**: Sono stati corretti e aggiunti i link di navigazione "Precedente" e "Successivo" in tutti i file della documentazione per garantire una navigazione sequenziale coerente.

## [9.0.4] - 2025-09-15 - Correzione Bug ORM

### 🔧 Correzioni

* **Correzione Bug `Query` per INSERT/UPDATE**: È stato corretto un bug critico nella classe `Query` che, in assenza di colonne specificate, inseriva erroneamente un asterisco (`*`) nelle query di tipo `INSERT` e `UPDATE`. Questo comportamento generava query SQL non valide e poteva causare fallimenti inaspettati nelle operazioni di scrittura. Ora la classe gestisce correttamente questi scenari, garantendo la generazione di query SQL corrette.

## [9.0.3] - 2025-09-14 - Correzioni e Miglioramenti alla Documentazione

Questa release di tipo patch si concentra esclusivamente sul miglioramento della documentazione per renderla più completa, accurata e facile da navigare. Non sono state apportate modifiche al codice sorgente del framework.

### 🚀 Miglioramenti

* **Aggiunta Guida "Best Practices"**: È stata aggiunta una nuova pagina (`best-practices.md`) con consigli su come utilizzare al meglio il framework.
* **Aggiunta Guida al "Deployment"**: È stata creata una nuova pagina (`deployment.md`) con una checklist per la messa in produzione delle applicazioni.
* **Aggiunta Guida "Troubleshooting"**: È stata aggiunta una nuova pagina (`troubleshooting.md`) per aiutare a risolvere i problemi più comuni.
* **Revisione Documentazione Fixtures**: La documentazione relativa alle `Data Fixtures` (`data-fixtures.md` e `getting-started.md`) è stata completamente riscritta per riflettere il corretto funzionamento basato su dipendenze (`setDependencies`) e sulla registrazione delle entità (`setEntity`).
* **Revisione Documentazione Form**: La documentazione sui `Form` (`forms.md`) è stata corretta per spiegare accuratamente il meccanismo di gestione degli errori, chiarendo che le proprietà `...Error` sono booleane e come utilizzare i messaggi personalizzati (`...CustomMessage`) e quelli standard dai file di lingua.

### 🔧 Correzioni

* **Correzione Documentazione Internazionalizzazione**: Chiarito in `internationalization.md` che la scelta del tipo di file di lingua (`.php` o `.json`) è una configurazione e non una ricerca sequenziale.
* **Correzione Percorsi Cache**: Aggiornati i percorsi di cache errati in `orm-additional-features.md` e `deployment.md` per essere coerenti con la configurazione.
* **Miglioramento Guida all'Installazione**: La guida `installation.md` è stata aggiornata per suggerire un metodo più robusto per la definizione di `ROOT_PATH`.
* **Correzione Link di Navigazione**: Sono stati corretti e aggiunti numerosi link di navigazione mancanti in vari file (`overview.md`, `static-assets.md`, `troubleshooting.md`, ecc.) per migliorare l'esperienza utente.

## [9.0.2] - 2025-08-24 - Miglioramenti a ORM e Documentazione

Questa versione si concentra sul miglioramento della qualità del codice, sulla correzione di bug minori e sull'arricchimento della documentazione per rendere il framework più robusto e facile da usare.

### 🚀 Miglioramenti

* **Refactoring di `SelfReferencedEntity`**: La classe è stata refattorizzata introducendo un metodo helper privato (`getShortClassName`) per eliminare la duplicazione del codice. Questo migliora la leggibilità, la manutenibilità e aderisce al principio DRY (Don't Repeat Yourself).
* **Chiarimenti nella Documentazione ORM (`orm-entities.md`)**: È stata migliorata significativamente la documentazione relativa al funzionamento dell'ORM. Ora viene spiegato in dettaglio il pattern "Lazy Loading con Gestione a Doppio Stato", evidenziandone i vantaggi in termini di performance e il comportamento specifico del metodo `toArray()`.

### 🔧 Correzioni

* **Correzione Esempi in Documentazione (`orm-entities.md`)**: Sono stati corretti un esempio di codice errato relativo alle collezioni di entità con relazioni multiple e un refuso in un nome di metodo (`count...`).
* **Correzione in `ContentType`**: È stata aggiunta una mappatura mancante nell'enumerazione `ContentType` per garantire una risoluzione dei MIME type più completa e affidabile.
* **Rigenerazione Documentazione PHPDoc**: La documentazione PHPDoc è stata rigenerata per essere allineata con le ultime modifiche al codice sorgente.

## [9.0.1] - 2025-08-15 - Ottimizzazione Streaming Risorse

Questa versione introduce un'importante ottimizzazione nel modo in cui le risorse (file statici come immagini, CSS, JS) vengono servite al client, migliorando performance e consumo di memoria.

### 🚀 Miglioramenti

* **Streaming Ottimizzato delle Risorse:** È stato rivisto il metodo `ResourceMaker::getResourceData`. Invece di utilizzare approcci diversi (`file_get_contents`, `readfile`) in base alla dimensione del file, ora viene impiegato un approccio di streaming unificato. I file vengono letti e inviati al client in blocchi (chunk) di 8KB. Questo riduce drasticamente il consumo di memoria per file di grandi dimensioni, previene errori di "memory exhaustion" e migliora la reattività del server.
* **Maggiore Robustezza:** Il nuovo metodo include un controllo esplicito sull'esito di `fopen`, lanciando un'eccezione `AccessDeniedException` se il file non può essere aperto, migliorando la gestione degli errori.

### 🔧 Correzioni

* Nessuna correzione specifica in questa versione.

## [9.0.0] - 2025-07-26 - Prima Versione Stabile

Siamo entusiasti di annunciare il rilascio di **SismaFramework 9.0.0**, la nostra prima versione stabile! Questo rilascio segna un'importante pietra miliare per il progetto, uscendo dalla fase beta e offrendo una base solida e affidabile per la creazione di applicazioni web moderne con PHP.

Con questa versione, ci impegniamo a mantenere la stabilità dell'API e a seguire il versioning semantico per i futuri aggiornamenti.

### ✨ Caratteristiche Principali

Questa versione consolida tutte le funzionalità sviluppate durante la fase beta, tra cui:

* **Architettura MVC Robusta:** Un'implementazione pulita del pattern Model-View-Controller che separa la logica di business dalla presentazione, promuovendo un codice organizzato e manutenibile.
* **ORM Potente (Data Mapper):** Un ORM integrato basato sul pattern Data Mapper a mappatura automatica. Gestisce Entità, Modelli, relazioni (incluse quelle auto-referenziate) e query complesse in modo intuitivo, con un sistema di lazy loading per ottimizzare le performance.
* **URL Rewriting Automatico:** Supporto nativo per URL "parlanti" (user-friendly) in notazione kebab-case, migliorando la SEO e l'esperienza utente.
* **Gestione Avanzata dei Form:** Un sistema di gestione dei form che automatizza la validazione dei dati, la gestione degli errori e il ripopolamento automatico, assicurando l'integrità dei dati.
* **Componente di Sicurezza Integrato:** Include Voters, Permissions e un sistema di Autenticazione per proteggere le applicazioni, con supporto per l'autenticazione a due fattori (MFA).
* **Sfruttamento di PHP Moderno:** Progettato per PHP 8.1+, utilizza funzionalità moderne come la tipizzazione forte e le `BackedEnum` per garantire la robustezza e la coerenza del codice.
* **Internazionalizzazione (i18n):** Supporto integrato per la creazione di applicazioni multilingua tramite file di localizzazione.
* **Crittografia a livello di Entità:** Possibilità di specificare quali proprietà di un'entità debbano essere crittografate in modo persistente nel database.

### ⚠️ Politiche di Supporto

* **Fine del Supporto per le Versioni Beta:** Come indicato nella nostra politica di sicurezza (`SECURITY.md`), tutte le versioni precedenti alla 9.0.0 sono considerate versioni di sviluppo (beta) e **non sono più supportate**. Si incoraggiano tutti gli utenti ad aggiornare a questa versione stabile per ricevere aggiornamenti e patch di sicurezza.

### 🙏 Ringraziamenti

Un ringraziamento speciale a tutti coloro che hanno contribuito a questo progetto, sia direttamente che indirettamente, attraverso ispirazione e feedback. Il vostro lavoro è stato fondamentale per arrivare a questo punto.

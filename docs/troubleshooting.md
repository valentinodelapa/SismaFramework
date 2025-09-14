# Troubleshooting: Risoluzione dei Problemi Comuni

Questa pagina elenca alcuni dei problemi più comuni che potresti incontrare durante lo sviluppo con SismaFramework e le loro possibili soluzioni.

## Errore 404 (Pagina non trovata)

Se visualizzi un errore 404 o la pagina di default del tuo web server invece della tua applicazione, prova a controllare quanto segue:

1.  **Configurazione del Web Server:**
    *   **Apache:** Assicurati che il file `.htaccess` sia presente nella root del tuo progetto e che il modulo `mod_rewrite` sia abilitato.
    *   **Nginx:** Verifica che la tua configurazione reindirizzi correttamente tutte le richieste a `Public/index.php`.

2.  **Convenzioni di Routing:**
    *   L'URL rispetta la convenzione `/nome-controller/nome-action`? Ricorda che i nomi sono in `kebab-case`.
    *   Il nome della classe del controller termina con `Controller` (es. `PostController`)?
    *   Il metodo dell'action è `public`?

3.  **Registrazione del Modulo:**
    *   Hai aggiunto il tuo modulo all'array `MODULE_FOLDERS` nel file `Config/config.php`?

## Errore "Class Not Found"

Questo errore indica che l'autoloader non riesce a trovare una classe che stai cercando di usare.

1.  **Namespace non corretto:** Controlla che il namespace dichiarato all'inizio del file corrisponda esattamente al percorso della cartella (standard PSR-4).
    *   **File:** `MyBlog/Application/Controllers/PostController.php`
    *   **Namespace:** `namespace MyBlog\Application\Controllers;`

2.  **Modulo non registrato:** Assicurati che il modulo che contiene la classe sia stato aggiunto all'array `MODULE_FOLDERS` in `config.php`.

3.  **Errore di battitura:** Controlla che non ci siano errori di battitura nel nome della classe o nell'istruzione `use`.

## Errore di Connessione al Database

Se l'applicazione non riesce a connettersi al database:

1.  **Credenziali errate:** Verifica che le costanti `DATABASE_HOST`, `DATABASE_NAME`, `DATABASE_USERNAME` e `DATABASE_PASSWORD` in `Config/config.php` siano corrette.
2.  **Server non raggiungibile:** Assicurati che il server del database sia in esecuzione e accessibile dalla macchina su cui gira l'applicazione.

## Problemi con l'ORM o le Relazioni

Se una relazione tra entità non funziona come previsto o se ricevi errori inaspettati dall'ORM:

1.  **Cache delle Relazioni non aggiornata:** Il file `referenceCache.json` potrebbe essere obsoleto. Eliminalo dalla sua cartella (`SismaFramework/Application/Cache/` di default). Il framework lo rigenererà automaticamente alla richiesta successiva.
2.  **Convenzioni non rispettate:** Controlla che i nomi delle tabelle e delle colonne nel database corrispondano alle convenzioni usate per i nomi delle classi `Entity` e delle loro proprietà.

## Gli Asset (CSS/JS) non vengono caricati

Se i tuoi file CSS, JavaScript o le immagini non vengono visualizzati:

1.  **Percorso errato:** Controlla il percorso nell'attributo `href` o `src` nel tuo HTML. Deve essere un percorso assoluto dalla root del sito (es. `/assets/css/style.css`).
2.  **File non trovato:** Assicurati che il file esista nel percorso corretto all'interno della cartella `Application/Assets/` di uno dei moduli registrati.

## Errore 403 (Access Denied / Forbidden)

Questo errore indica un problema di permessi.

1.  **Autorizzazione del Framework:** Una classe `Permission` sta bloccando l'accesso all'azione. Controlla la logica nel `Voter` associato per capire perché l'accesso viene negato.
2.  **Permessi del Filesystem:** Il web server potrebbe non avere i permessi di lettura/scrittura necessari. Controlla i permessi delle cartelle `Logs/` e `Cache/` come descritto nella guida al Deployment.

* * *

[Indice](index.md) | Precedente: [Deployment in Produzione](deployment.md)
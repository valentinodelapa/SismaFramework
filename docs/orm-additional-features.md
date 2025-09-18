# Funzionalità Avanzate dell'ORM

Oltre alle operazioni CRUD di base, l'ORM di SismaFramework offre diverse funzionalità avanzate che migliorano le performance, la sicurezza e l'integrità dei dati.

## Identity Map (Cache per Richiesta)

L'ORM implementa il pattern **Identity Map**. Questo significa che mantiene una cache interna di tutte le entità caricate durante il ciclo di vita di una singola richiesta HTTP.

I vantaggi sono due:
1.  **Performance:** Se richiedi la stessa entità più volte (es. `find(42)` in punti diversi del codice), la query al database verrà eseguita solo la prima volta. Le richieste successive restituiranno l'oggetto già presente in memoria, riducendo drasticamente le query duplicate.
2.  **Coerenza:** Garantisce che esista una sola istanza di un'entità specifica per richiesta. Se modifichi un'entità in una parte del codice, la modifica sarà visibile in qualsiasi altro punto che fa riferimento a quella stessa entità, prevenendo dati inconsistenti.

Questa cache è automatica e non richiede configurazione.

## Generazione Cache delle Relazioni

Le funzionalità "magiche" dell'ORM, come le collezioni inverse (es. `$user->postCollection`), sono possibili grazie a un file di cache, `referenceCache.json`.

Questo file contiene una mappa di tutte le relazioni tra le entità del tuo progetto. Viene generato automaticamente dal framework analizzando le proprietà tipizzate delle tue classi `Entity`.

Questa cache è fondamentale per:
-   **Risolvere le relazioni inverse:** Permette all'ORM di sapere che `postCollection` su un'entità `User` deve cercare tutte le entità `Post` la cui proprietà `author` è l'utente corrente.
-   **Proteggere l'integrità referenziale:** Viene usata dal componente di Sicurezza (in particolare dalla classe `ReferencedEntityDeletionPermission`) per impedire l'eliminazione di un'entità se è ancora referenziata da altre.

Il percorso di questo file è definito nel file `Config/config.php` tramite le costanti `REFERENCE_CACHE_DIRECTORY` e `REFERENCE_CACHE_PATH`. Per impostazione predefinita, si trova in:

```php
SismaFramework/Application/Cache/referenceCache.json
```

Se questo file diventa obsoleto (ad esempio, dopo aver aggiunto una nuova relazione), puoi semplicemente eliminarlo. Il framework lo rigenererà automaticamente alla richiesta successiva.

* * *

[Indice](index.md) | Precedente: [Introduzione all'ORM](orm.md) | Successivo: [Sicurezza: Autenticazione e Autorizzazione](security-component.md)

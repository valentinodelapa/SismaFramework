# Funzionalità aggiuntive

Sono presenti alcune funzionalità aggiuntive che supportano il funzionamento della componente ORM fornendo alcuni utili meccanismi di semplifazione e/o automazione 

## Caching delle entità

Ogni volta che un ringolo record viene richiamato da una tabella del database e trasformata in entità essa viene depositata in una cache virtuale che sfrutta il meccanismo per mezzo del quale nel linguaggio PHP ogni oggetto passato come parametro viene passato per riferimento.

Tale meccanismo permette di diminuire le chiamate al database ponendo tale cache come livello intermedio tra il database l'ORM: la query verrà eseguita solo se, in quel determinato ciclo del programma, non vi è in cache l'entità corrispondente al record richiesto.

## Auto-determinazione delle relazioni inverse delle entità

È presente un meccanismo che, scorrendo le entità presenti nei vari moduli del progetto (ed a tal scopo utilizza le costanti `ROOT_PATH`, `ENTITY_PATH `ed `ENTITY_NAMESPACE `presenti nel file di configurazione), è in grado di generare un file json che rappresenta la rete di relazioni inverse delle entità. Questa funzionalità permette la generazione automatica delle collezioni di oggetti (meccanismo descritto nel paragrafo Entità referenziate) e permette di implementare un meccanismo di blocco in fase di cancellazione utile a garantire l'integrità referenziale a livello di applicazione (è possibile utilizzare a tal scopo la classe `ReferencedEntityDeletionPermission`, il cui funzionamento sarà analizzato nel capitolo Permissions).

La costante che indica il percorso nel quale la libreria salverà il file json è indicato da due costanti presenti nel file di configurazione:

```php
...
const REFERENCE_CACHE_DIRECTORY = '';
const REFERENCE_CACHE_PATH = '';
...
```

* * *

[Indice](INDEX.md) | Precedente: [Modelli](ORM_MODELS.md) | Successivo: [Controllori](CONTROLLERS.md)



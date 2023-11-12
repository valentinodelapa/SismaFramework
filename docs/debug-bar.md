# Barra di Debug

La libreria fornisce nativamente uno strumento di analisi utile per un rapido debug in caso di problematiche ricontrate in fase di sviluppo. Per attivare lo strumento è sufficiente attivare la modalità di sviluppo tramite l'apposita costante presente nel file di configurazione.

```php
...
const DEVELOPEMENT_ENVIRONMENT = true;
...
```

La **Barra di Debug** si compone presenta alcune sezioni a destra ed altre a sinistra. Le sezioni di sinistra sono le seguenti:

* Database: questa sezione contiene l'elenco delle query eseguite durante l'esecuzione della richiesta

* Log: questa sezione riporta il contenuto del file di log.

* Form: questa sezione, nel caso in cui l'ultima richiesta rappresenti l'invio di un form, riporta l'esito della validazione dei campi dello stesso.

* Variables: questa sezione contiene l'elenco delle variabile che il controllore ha inviato alla vista.

A destra della **Barra di Debug** sono invece presenti le seguenti sezioni:

* Sezione Peso: questa sezione riporta il peso della pagina espresso in MB.

* Sezione Tempo: questa sezione riporta il tempo di esecuzione della pagina.

* * *

[Indice](index.md) | Precedente: [Funzionalita Aggiuntive](orm-additional-features.md)



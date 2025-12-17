# Best Practices

Questa pagina raccoglie una serie di consigli e buone pratiche per aiutarti a scrivere codice di alta qualità, sicuro e manutenibile con SismaFramework.

## 1. Sfrutta l'Architettura Modulare

Non creare un unico, grande modulo "Application". Suddividi la tua logica in moduli funzionali e riutilizzabili (es. `User`, `Blog`, `Shop`). Questo approccio:
*   Mantiene il codice organizzato e facile da navigare.
*   Promuove il riutilizzo del codice.
*   Semplifica la manutenzione e lo sviluppo in team.

## 2. Abbraccia la Tipizzazione Forte

SismaFramework è costruito su PHP 8.1+ e fa largo uso della tipizzazione forte. Usala sempre:
*   **Nelle proprietà delle Entità:** Garantisce l'integrità dei dati e abilita la "magia" dell'ORM.
*   **Negli argomenti delle Action:** Permette il binding automatico dei parametri dall'URL e l'iniezione di servizi.

Un codice fortemente tipizzato è più leggibile, meno soggetto a errori e più facile da analizzare per gli strumenti statici.

## 3. Usa l'ORM in Modo Intelligente

*   **Preferisci le query specifiche:** Invece di caricare un'intera collezione di entità per poi filtrarla in PHP, usa i metodi del `Model` (es. `getBy...`, `countBy...`) o crea metodi personalizzati che costruiscano query SQL mirate. Questo è molto più efficiente.
*   **Sii consapevole del Lazy Loading:** Il Lazy Loading è ottimo per le performance, ma può portare al "problema N+1" se usato in modo improprio all'interno di un ciclo. Se devi accedere a una relazione per ogni elemento di una collezione, valuta se una query personalizzata con una JOIN possa essere più performante.

## 4. Non Trascurare la Sicurezza

*   **Usa sempre le `Permission`:** Non inserire la logica di autorizzazione direttamente nei controller. Usa il sistema di `Voter` e `Permission` per proteggere le tue actions. È più pulito, riutilizzabile e sicuro.
*   **Non disabilitare la protezione CSRF:** Il componente `Form` gestisce automaticamente la protezione CSRF. Assicurati che sia sempre attiva per tutti i form che modificano dati.
*   **Sanifica sempre l'output:** Usa `htmlspecialchars()` in tutte le viste quando stampi dati che potrebbero provenire da un utente, per prevenire attacchi XSS.

## 5. Mantieni le Viste "Stupide"

La logica di business non deve risiedere nelle viste. Una vista dovrebbe solo occuparsi di presentare i dati che riceve dal controller.
*   **NO:** Eseguire query o calcoli complessi in un file `.php` di una vista.
*   **SÌ:** Preparare tutte le variabili e i dati necessari nel controller e passarli alla vista.

## 6. Non Modificare il Core del Framework

La cartella `SismaFramework/` dovrebbe essere considerata "read-only". Non modificare mai i file al suo interno, altrimenti renderai impossibili gli aggiornamenti futuri.

Per personalizzare il comportamento del framework:
*   Usa il file `Config/config.php`.
*   Crea i tuoi moduli e le tue classi.
*   Sfrutta l'ordine di registrazione dei moduli per sovrascrivere rotte o servizi.

## 7. Logga gli Eventi Importanti

Il logging non serve solo per gli errori. Usa il logger PSR-3 (`SismaLogger`) per registrare eventi significativi dell'applicazione (es. un utente amministratore che esegue un'azione critica, un pagamento andato a buon fine). Questo crea una traccia di audit (audit trail) che può essere preziosa.

```php
$logger = new \SismaFramework\Core\HelperClasses\SismaLogger();
$logger->info('Pagamento completato', [
    'code' => 'PAYMENT_SUCCESS',
    'file' => __FILE__,
    'line' => __LINE__
]);
```

Sfrutta i diversi livelli di log PSR-3 (`debug`, `info`, `warning`, `error`, `critical`) per categorizzare correttamente gli eventi.

---

[Indice](index.md) | Precedente: [Gestione Errori e Logging](error-handling-and-logging.md) | Successivo: [Deployment in Produzione](deployment.md)
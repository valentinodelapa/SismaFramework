# Introduzione

**SismaFramework** è un framework PHP moderno, basato sul pattern MVC, progettato per la creazione di applicazioni web robuste, sicure e manutenibili. Il suo obiettivo è fornire una solida struttura che automatizza i compiti ripetitivi, permettendo agli sviluppatori di concentrarsi sulla logica di business specifica della loro applicazione.

Sfrutta appieno le funzionalità di PHP 8.1+ (come la tipizzazione forte e le `BackedEnum`) per garantire un codice di alta qualità, più sicuro e meno soggetto a errori.

## La Filosofia del Framework

*   **Codice Pulito e Manutenibile:** SismaFramework promuove pratiche di programmazione solide e una struttura chiara per garantire che il codice sia leggibile e facile da mantenere nel tempo.
*   **Sicurezza Integrata:** La sicurezza non è un'opzione aggiuntiva, ma una parte fondamentale del framework, con componenti dedicati per l'autenticazione, l'autorizzazione e la protezione dalle vulnerabilità più comuni.
*   **Automazione Intelligente:** Molte attività comuni, come la gestione dei form, il routing e l'interazione con il database, sono automatizzate per ridurre il codice "boilerplate" e accelerare lo sviluppo.

## Concetti fondamentali

SismaFramework si fonda su pattern architetturali consolidati per garantire una struttura logica e scalabile.

### Model-View-Controller (MVC)

L'architettura separa nettamente la logica di business (**Model**), la presentazione dei dati (**View**) e la gestione delle richieste dell'utente (**Controller**). Questa separazione rende l'applicazione più organizzata, più facile da testare e più semplice da far evolvere.

### ORM

L'**Object-Relational Mapping (ORM)** permette di interagire con il database utilizzando oggetti PHP anziché scrivere query SQL manualmente. SismaFramework integra un ORM basato sul pattern **Data Mapper**, che mantiene separate la logica di dominio (le tue `Entity`) e la logica di persistenza, offrendo un'astrazione pulita e potente per le operazioni sul database.

Per maggiori dettagli, puoi consultare la sezione dedicata all'ORM.

---

[Indice](index.md) | Successivo: [Installazione](installation.md)

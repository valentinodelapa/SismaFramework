# Introduzione

L'ulizzo di un framework snellisce lo sviluppo di un’applicazione, automatizzando molti degli schemi impiegati per un dato scopo. Inoltre, aggiunge struttura al codice, spingendo lo sviluppatore a scrivere codice migliore, più leggibile e più mantenibile. Infine, semplifica la programmazione, perché impacchetta delle operazioni complesse in semplici istruzioni.

**SismaFramework** rispecchia il pattern *MVC* ed ottimizza lo sviluppo di applicazioni web tramite diverse caratteristiche chiave. Separa le regole logiche di un’applicazione web e automatizza dei compiti comuni in modo che lo sviluppatore possa concentrarsi interamente sulle specifiche dell’applicazione. Il risultato finale di questi vantaggi è che non serve più reinventare la ruota ogni volta che si costruisce una nuova applicazione.

Inoltre, è compatibile nativamente con i database *MySQL* e *MariaDB* ma predisposto per essere configurato con altri tipi di database.

## Caratteristiche generali

I requisiti principali della libreria sono i seguenti:

* Compatibile nativamente con MySQL e MariaDB ma configurabile per essere indipendente dal database.

* Sviluppato per automatizzare compiti complessi in semplici istruzioni.

* Sviluppato adottando diversi pattern per essere conforme alla maggior parte delle buone pratiche di programmazione.

* Di conseguenza il codice risulta essere auto-documentato adottando pratiche di codice agile e pulito.

* Semplice da estendere con moduli ad hoc o librerie di terze parti.

## Caratteristiche di automazione

La libreria automatizza la maggior parte delle caratteristiche comuni ai progetti permettendo allo sviluppatore di concentrarsi sulle specifiche dell’applicazione. Ecco alcuni esempi:

* La logica di presentazione è separata dalla logica di elaborazione.

* I form supportano la validazione e la ripopolazione automatica assicurando integrità ai dati e buon livello di esperienza utente.

* Le interfacce supportano l’internazionalizzazione mediante i file di linguaggio.

* L’escape dell’output garantisce protezione da attacchi mediante dati corrotti.

* La gestione automatica dell’autenticazione aiuta a creare in modo semplice aree protette.

* La gestione dell’url rewrite aiuta a creare indirizzi user-friendly evitando le query string.
  

## Concetti fondamentali

### OOP

La programmazione orientata agli oggetti (OOP) è uno stile di programmazione che si basa sul raggruppamento all'interno di un'unica entità (la classe) delle strutture dati e delle procedure che operano su di esse. In questo modo si creano degli oggetti software dotati di proprietà (dati) e metodi (procedure) che operano sui dati dell'oggetto stesso. Questo paradigma di programmazione permette di definire oggetti software in grado di interagire gli uni con gli altri attraverso lo scambio di messaggi. La programmazione orientata agli oggetti è particolarmente adatta nei contesti in cui si possono definire delle relazioni di interdipendenza tra i concetti da modellare (contenimento, uso, specializzazione).

### MVC

Il Model-View-Controller (MVC) è un pattern architetturale molto diffuso nello sviluppo di sistemi software, in particolare nell'ambito della programmazione orientata agli oggetti e in applicazioni web, in grado di separare la logica di presentazione dei dati dalla logica di business.

### ORM

L'Object-Relational Mapping (ORM) è una tecnica di programmazione che favorisce l'integrazione di sistemi software aderenti al paradigma della programmazione orientata agli oggetti con sistemi RDBMS. Un prodotto ORM fornisce, mediante un'interfaccia orientata agli oggetti, tutti i servizi inerenti alla persistenza dei dati, astraendo nel contempo le caratteristiche implementative dello specifico RDBMS utilizzato. In pratica, l'ORM permette di mappare le classi dell'applicazione con le tabelle del database. 

In altre parole, l'ORM è un sistema che consente di rappresentare le informazioni contenute in un database relazionale come oggetti di una classe.
Verranno analizzate in seguito le peculiarità del modulo ORM integrato nel progetto.

---

[Indice](INDEX.md) | Successivo: [Installazione](INSTALLATION.md)



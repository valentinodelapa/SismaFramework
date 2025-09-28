# SismaFramework

![PHPUnit tests status](https://img.shields.io/badge/tests-passing-green)
![Code coverage status](https://img.shields.io/badge/coverage-%3E85%25-green) 
![PHP Version Support](https://img.shields.io/badge/php-%3E%3D8.1-blue)
[![license](https://img.shields.io/badge/license-MIT-yellowgreen)](https://github.com/valentinodelapa/SismaFramework/blob/master/LICENSE)
[![Documentation](https://img.shields.io/badge/documentation-leggi-blue)](docs/index.md)
[![SLSA 3](https://img.shields.io/badge/SLSA-Level%203-blue)](https://slsa.dev/spec/v0.1/levels#level-3)

SismaFramework è un framework PHP moderno, basato sul pattern MVC, progettato per la creazione di applicazioni web robuste e manutenibili. Sfrutta le funzionalità di PHP 8.1+ come la tipizzazione forte e le `BackedEnum` per garantire la qualità e la coerenza del codice.

## Caratteristiche Principali

*   **Architettura MVC Robusta:** Separazione netta tra logica di business, dati e presentazione.
*   **ORM Potente (Data Mapper):** Un ORM a mappatura automatica con supporto per relazioni, lazy loading e crittografia a livello di proprietà.
*   **Componente di Sicurezza:** Gestione di autenticazione (con supporto MFA), permessi e ruoli tramite Voters.
*   **Gestione Avanzata dei Form:** Validazione, ripopolamento automatico e gestione degli errori integrati.
*   **URL Rewriting Automatico:** Supporto nativo per URL "parlanti" in notazione kebab-case per una migliore SEO.
*   **Internazionalizzazione (i18n):** Supporto integrato per applicazioni multilingua.

## Requisiti

*   PHP >= 8.1
*   Database supportati: MySQL, MariaDB (con possibilità di estendere il supporto ad altri RDBMS).

## Installazione e Documentazione

Per iniziare a usare SismaFramework, la guida migliore è la nostra documentazione completa.

*   **Leggi la documentazione completa**
*   **Guida all'installazione**

La documentazione ti guiderà attraverso la configurazione iniziale, i concetti fondamentali e l'utilizzo di tutti i componenti del framework.

## Contributi e Ringraziamenti

Questo progetto si ispira a diverse eccellenti librerie e guide open source. Un ringraziamento speciale va agli autori e alle community di:

*   **Symfony**
*   **Doctrine ORM**
*   **SimpleORM**
*   E altri progetti menzionati nel nostro file `NOTICE.md`.

Per i dettagli completi su licenze e copyright di terze parti, si prega di consultare il file `NOTICE.md`.

Ringrazio il mio amico Francesco Iezzi per aver esaminato la libreria dal punto di vista della sicurezza e aver fornito preziosi consigli in tale ambito.

Ringrazio inoltre i miei amici e colleghi per i supporto, il confronto ed i feedback forniti.
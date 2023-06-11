# ORM

Come già detto in precedenza un *ORM* fornisce un’interfaccia ad oggetti con la quale interagire con il database.

L’ORM integrato nella libreria **SismaFramework** è basato su pattern *Data Mapper* che consente di mantenere indipendenti la rappresentazione in memoria e lo store dei dati. In particolare, il *Data Mapper* è un livello di accesso ai dati che si occupa del trasferimento bidirezionale dei dati tra uno store persistente (spesso un database relazionale) e una rappresentazione in memoria dei dati (il livello di dominio). Il *Data Mapper* in questione è composto da un mapper gestisce, tramite classi ausiliarie, le interazioni principali al database, quali *INSERT*, *CREATE*, *UPDATE*, e *DELETE*.

---

[Indice](INDEX.md) | Precedente: [Struttura cartelle di progetto](PROJECT_FOLDER_STRUCTURE.md) | Successivo: [Entità](ORM_ENTITIES.md)

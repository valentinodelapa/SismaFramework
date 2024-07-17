# ORM

Come già detto in precedenza un *ORM* fornisce un’interfaccia ad oggetti con la quale interagire con il database.

L'ORM integrato nella libreria **SismaFramework** si basa sul pattern *Data Mapper a mappatura implicita*, che mantiene separate la rappresentazione in memoria e lo storage dei dati. Il **Data Mapper** funge da livello di astrazione intermedio, gestendo il trasferimento bidirezionale dei dati tra uno storage persistente (tipicamente un database relazionale) e la rappresentazione in memoria (il livello di dominio). Attraverso classi ausiliarie, gestisce le operazioni CRUD (**Create**, **Read**, **Update**, **Delete**) sul database. La mappatura è implicita, non utilizzando file esterni per la corrispondenza tra tabelle del database ed entità del modello.

---

[Indice](index.md) | Precedente: [Struttura cartelle di progetto](project-folder-structure.md) | Successivo: [Entità](orm-entities.md)



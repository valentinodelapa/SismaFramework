# Struttura cartelle di progetto

una volta aggiunta la libreria nella root del progetto è possibile creare la una o più cartelle ad hoc nelle quali suddivide il progetto.

La struttura predefinita completa delle cartelle di configurazione è la seguente:

```bash
NomeCartellaProgetto
└── NomeCartellaApplicazione
    ├── Assets
    │   ├── css
    │   ├── javascript
    │   ├── jpeg
    │   └── png
    ├── Controllers
    ├── Entites
    ├── Enumerations
    ├── Forms
    ├── Locales
    ├── Models
    ├── Permissions
    ├── Services
    ├── Templates
    ├── Views
    ├── Voters
    └── Wrappers
```

Mantenendo tale struttura le uniche modifiche da effettuare nel file di configurazione sono:

* la modifica della costante `APPLICATION` che dovrà riportare il nome della cartella dell'applicazione all'interno della cartella di progetto
  
  ```php
  const APPLICATION = 'NomeCartellaApplicazione';
  ```

* l'aggiunta del modulo (la cartella del progetto) all'interno dell'array `MODULE_FOLDERS`, come segue:
  
  ```php
  const MODULE_FOLDERS = [
      "ModuloProgetto",
      ...
  ];
  ```

La classe `Autoloader`, richiesta dal file `index.php` si occupa di risolvere il caricamento delle classi tramite Namespace, che deve rispecchiare il percorso del file a partire dalla cartella radice del progetto.

---

[Indice](index.md) | Precedente: [Installazione](installation.md) | Successivo: [Controllori](controllers.md)



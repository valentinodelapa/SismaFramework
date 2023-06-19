# Struttura cartelle di progetto

una volta aggiunta la libreria nella root del progetto è possibile creare la una o più cartelle ad hoc nelle quali suddivide il progetto.

La struttura predefinita completa delle cartelle di configurazione è la seguente:

```bash
NomeCartellaProgetto
|__ NomeCartellaApplicazione
    |__ Assets
    |   |__ css
    |   |__ javascript
    |   |__ jpeg
    |   |__ png
    |__ Controllers
    |__ Entites
    |__ Enumerations
    |__ Forms
    |__ Locales
    |__ Models
    |__ Permissions
    |__ Services
    |__ Templates
    |__ Views
    |__ Wrappers
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

[Indice](INDEX.md) | Precedente: [Installazione](INSTALLATION.md) | Successivo: [Controllori](CONTROLLERS.md)



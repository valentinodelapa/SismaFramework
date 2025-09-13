# Viste e Template

Le **Viste** sono responsabili della presentazione dei dati all'utente. In SismaFramework, una vista è un file `.php` che contiene principalmente codice HTML, ma può includere codice PHP per visualizzare le variabili passate dal Controller.

Le variabili vengono passate a una vista tramite il metodo `Render::generateView()`, come descritto nella sezione Controllori.

SismaFramework non impone un sistema di templating engine (come Twig o Blade), lasciando allo sviluppatore la libertà di strutturare le viste come preferisce, ad esempio creando layout riutilizzabili con `include` o `require` di PHP.

## Templates

I **Template** sono simili alle viste ma sono progettati per generare output sotto forma di stringa, anziché inviarlo direttamente al browser. Questo li rende ideali per scenari come la creazione del corpo di un'email o la generazione di file di testo.

I template utilizzano una sintassi semplice con segnaposto (es. `{{nome_variabile}}`) che vengono sostituiti con i valori forniti. La generazione avviene tramite il metodo `Templater::generateTemplate()`, come spiegato nella documentazione dei Controllori.

* * *

Indice | Precedente: Controllori | Successivo: Gestione dei Form

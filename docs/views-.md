# Viste

Le viste si occupano dell'esposizione dei dati verso l'utente. Esse sono file *.php* che generalmente includono una struttura html completa. Ereditano dai controllori tutte le informazioni che si è deciso di condividere tramite i metodi della classe `Render` citati nel precedente capitolo.

La libreria non implementa nativamente un sistema di "assemblaggio" delle viste ma lascia completa libertà allo sviluppatore di gestire tale aspetto: determinati progetti infatti potrebbero dover esporre esclusivamente dati grezzi e tale scelta progettuale supporta anche uno scenario di questo genere.

Di default la libreria 

## Templates

Nelle situazioni in cui è necessario che l'output di una chiamata non sia una pagina web (un caso esemplificativo potrebbe essere l'invio di un email) possono essere utilizzati i templates: essi tramite rivestono con un'interfaccia grafica le informazioni che vengono condivise dal controllore e restituiscono il tutto sotto forma di un  stringa.

Per la generazione di un template, come anticipato nel capitolo precedente, è possibile utilizzare il metodo statico `generateTemplate()` della classe `Templater`.

* * *

[Indice](index.md) | Precedente: [Controllori](controllers.md) | Successivo: [ORM](orm.md)

# NOTICE

Parte di questo software è ispirata alla repository [**MVC_todo**](https://github.com/ngrt/MVC_todo).

Ringraziamo gli autori del progetto **MVC_todo** per aver condiviso il loro lavoro con la comunità open source.

La repository non risulta essere soggetta ad alcuna licenza e non riporta alcuna notifica di copyright.

---

Parte di questo software è ispirata alle lezioni riguardanti il pattern MVC presenti nella guida [**Creare un e-commerce con PHP**](https://www.html.it/guide/creare-un-e-commerce-con-php/) del sito HTML.it, a cura di Gabriele Romanato.

Ringraziamo gli autori della guida.

---

Parte di questo software è ispirata alla repository [**Symfony**](https://github.com/symfony/symfony), rilasciato sotto licenza MIT.

Ringraziamo gli autori del progetto **Symfony** per aver condiviso il loro lavoro con la comunità open source.

Copia della [licenza della repository originale](https://github.com/symfony/symfony/blob/6.4/LICENSE) è inclusa nella cartella third-party-licenses/Symfony/ con indicata la notifica di copyright che riportiamo anche di seguito.

Copyright (c) 2004-present Fabien Potencier

---

La componente *ORM* è stata sviluppata basandosi su codice originale presente nella repository [**SimpleORM**](https://github.com/davideairaghi/php), rilasciata sotto licenza Apache 2.0. Abbiamo apportato modifiche significative al codice originale per adattarlo alle nostre esigenze: data l'entità delle stesse ci limiteremo ed elencare esclusivamente quelle che conivolgolo in codice originale, limitandoci a citare, ove necessario per motivi di contesto, le funzionalità aggiunte per lo scopo del corrente progetto.

- Tutto il codice è stato rifattorizzato per aderire agli standard di forte tipizzazione che caratterizzano il progetto nel quale è stato integrato. Inoltre, trattandosi di codice risalente al 2015, sono state apportate (e saranno apportate in futuro) le modifiche necessarie per adeguarlo alle best practices correnti e per renderlo compatibile alla versione più recente del linguaggio di programmazione.

- In determinate circostanze, le costanti di classe utili a gestire parole chiave sono state sostituire da enum, introdotti con la versione di php 8.1. Questa scelta strategica, affiancata alla forte tipizzazione che caratterizza l'intero framework, è stata adottata per garantire la coerenza delle informazioni che lo sviluppatore andrà ad inviare all'architettura che si occuperà di interagire con il database ed a migliorare la user exprerience.

- I namespace sono stati modificati per essere integrati in modo coerente nell'ambito di progetto.

- Le classi base `Adapter` e `ResultSet` sono state riorganizzate, spostandole in una posizione comune che ne identifichi la funzione e rendendole astratte, quindi non direttamente utilizzabili. I metodi che vengono implementati nelle classi figlie e che nella libreria originale erano abbozzati in modo da restituire `null`, sono stati invece resi astratti ed implementati direttamente nelle classi che le estendono.

- Le funzionalita che nella libreria originaria sono attribuite alle classi di tipo `Model` (ovvero alle estensioni della classe padre `Model`) sono state suddivise secondo l'SRP in due strutture separate, modificando il pattern architetturale originale *Active Record* in un pattern *Data Mapper a mappatura implicita*.
  
  - Le funzionalità di rappresentazione dei dati e l'implementazione della logica di dominio sono state demandate alle classi `Entity`, un nuovo tipo introdotto appositamente. Tale gerarchia di classi (che comprende `BaseEntity`, `ReferencedEntity `e `SelfReferencedEntity`) implementa numerosi comportamenti, non presenti nella concezione originaria della libreria. Come detto in precedenza, non reputiamo necessario dilungarci in tale sede nelle evolutive ed i meccanismi di meta-programmazione implementati (le cui caratteristiche è possile individuare all'interno della documentazione che correda la libreria corrente), ma ci limiteremo a specificare che la caratteristica che la classe `Entity` riprende dalla originaria classe `Model` è esclusivamente quella di *rappresentare* la tabella, modificando però l'approccio di accesso ai dati in stessa contenuti. Vengono inoltre gestite direttamente le *chiavi esterne* tramite la tipizzazione delle proprietà, utilizzando come tipo la classe alla quale la chiave esterna fa riferimento. Sono inoltre gestiti nativamente *vocabolari chiusi* per mezzo degli enum.
  
  - È stata creata la classe `DataMapper` alla quale sono state attribuite le funzionalità che si occupano della persistenza dei dati. La maggior parte dei metodi dell'originale classe *Model* (opportunamente rifattorizzati) sono quindi confluiti in essa; ad essi sono stati affiancati altri metodi specifici del contesto di progetto.

- Relativamente alle classi `Model` (`BaseModel `, `DependentModel`, `SelfReferencedModel`) implementate dalla corrente libreria, nonostante rappresentino una caratteristica peculiare della stessa e non abbiano legami diretti con quelle presenti nella libreria originaria, riteniamo sia necessario effettuare alcune precisazioni. Esse sono demandate ad incapsulare la costruzione delle query secondo il dialetto implementato dalla classe `Adapter`. Per quanto riguarda le funzionalità aggiuntive ed i meccanismi di meta-programmazione implementati al loro interno facciamo riferimento all'apposita sezione della documentazione.

- Le classi `ResulSet` sono state rifattorizzate in modo da restituire come risultato oggetti di tipo `Entity` (nel caso in cui la query restituisca una sola riga di risultato) o `SismaCollection` (qualora il risultato rappresenti molteplici record). Queste ultime sono un'estensione della classe nativa di PHP `ArrayObject` e sono state implementate in modo tale da mantenere la coerenza di tipo all'interno di ogni collezione. Come in precedenza, per ulteriori dettagli sulla funzionalità rappresentata dalla classe `SismaCollection`, facciamo riferimento all'apposita sezione della documentazione.

- La classe Query è stata oggetto di attenta revisione che ha comportato l'aggiunta di alcuni comportamenti e la rimozione di altri non reputati necessari nell'ambito del corrente progetto.

Ringraziamo gli autori del progetto **SimpleORM** per aver condiviso il loro lavoro con la comunità open source.

Copia della [licenza della repository originale](https://github.com/davideairaghi/php/blob/master/LICENSE) è inclusa nella cartella third-party-licenses/SimpleOrm/ con indicata notifica di copyright, incompleta nell'originale (Copyright {yyyy} {name of copyright owner}) e redatta seguendo il formato standard. 

Non essendo riusciti a contattare lo sviluppatore della repository originale, abbiamo agito autonomamente. Siamo partiti, infatti,  dal presupposto che, avendo rilasciato codice open source con licenza Apache 2.0 lo abbia fatto con le migliori intenzioni e comprenda la nostra buona fede (anche in caso di eventuali errori) nel compilare autonomamente quanto, per una svista, lui ha dimenticato di compilare. Il nome dello sviluppatore è presente all'interno della repository e quindi è stato semplice dedurlo. 

Riguardo l'anno abbiamo indicato come partenza quello di prima release (risalente al 6 ottobre 2015); nonostante l'ultima release risalga a pochi giorni dopo la prima (13 ottobre 2015) abbiamo ugualmente deciso di optare per il formato 2015-present, per tutelare al massimo i diritti dello sviluppatore originale. 

Anche nei files sorgente originali non era presente alcuna notifica di copyright e, non appena ci siamo accorti della cosa, abbiamo provveduto ad includerla in ogni singolo file derivato, in testa alla licenza del progetto corrente, indicando anche le modifiche effettuate.

Invitiamo lo sviluppatore della repository originale stesso a contattaci qualora decida di editare tale informazione nella repository originale in formato diverso rispetto a quello da noi utilizzato e voglia che venga fatto altrettando anche nei riferimenti presenti nel corrente progetto. Riportiamo la suddetta notifica di copyright anche di seguito.

Copyright (c) 2015-present Davide Airaghi

---

Parte della rifattorizzazione della componente ORM è ispirata alla repository [Doctrine Object Relational Mapper (ORM)](https://github.com/doctrine/orm), rilasciato sotto licenza MIT.

Ringraziamo gli autori del progetto **Doctrine Object Relational Mapper (ORM)** per aver condiviso il loro lavoro con la comunità open source.

Copia della [licenza della repository originale](https://github.com/symfony/symfony/blob/6.4/LICENSE) è inclusa nella cartella third-party-licenses/DoctrineORM/ con indicata la notifica di copyright che riportiamo anche di seguito.

Copyright (c) Doctrine Project

---

Il template predefinito di errore 500 utilizzato dal framework è individuabile nella libreria presente al [seguente indirizzo](https://codepen.io/gaiaian/details/QVVxaR), rilasciata sotto licenza MIT. Non è stato possibile individuare il nome di suddetta libreria in quanto, nella pagina indicata, è presente esclusivamente una descrizione, che, per complettezza, riportiamo di seguito: *Page that users will see when a web site throws a 500 error*.

Specifichiamo che, seppure il testo della licenza sia riconducibile alla licenza MIT, essa non è citata nello stesso e, pertanto, neppure nella copia inserita nella presente libreria.

Ringraziamo gli autori della suddetta libreria per aver condiviso il loro lavoro con la comunità open source.

Copia della [licenza della repository originale](https://codepen.io/gaiaian/details/QVVxaR) è inclusa nella cartella third-party-licenses/ErrorTemplate/ con indicata notifica di copyright che riportiamo anche di seguito.

Copyright (c) 2024 by Gayané (https://codepen.io/gaiaian/pen/QVVxaR)

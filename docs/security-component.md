# Componente Sicurezza

Tale modulo racchiude le funzionalità di sicurezza e di autenticazione, implementate a livello framework, di seguito descritte.

## Voters

I voters sono classi specializzate nell'implementare un algoritomo che esprima una risposta di tipo booleano dati determinati parametri in ingresso.

Una classe Voters deve estendere la classe astratta `SismaFramework\Security\BaseClasses\BaseVoter`, implementandone i metodi astratti:

* `isInstancePermitted()`: questo metodo serve filtrare la tipologia del parametro soggetto (i cui dettagli verranno analizzare nel momento in cui verra descritto il metodo pubblico esposto dalla classe) nel caso in cui esso debba rispettare determinati requisiti. Il suo valore di ritorno è di tipo booleano.
- `checkVote()`: questo metodo dovrà racchiudere la logica vera e propria dell'algoritmo del voter ed ha anch'esso un valore di ritorno di tipo  booleano.

I voters espongono un metodo statico pubblico denominato `isAllowed()` che accetta tre parametri:

- `$subject`: è il parametro il cui valore sarà testato nel metodo `checkVote()`.  DI default la tipologia di dato in ingresso è libera ma, nel caso fosse necessario che tale parametro sia di una determinata tipologia è possibile specificarne la logica di controllo all'interno del metodo `isInstancePermitted()`.

- `$accessControlEntry`: questo parametro viene utilizzato nell'ambito della logica dell'algoritmo (quindi all'interno del metodo `checkVote()`) per stabilire la tipologia di controllo da eseguire. Il parametro è di tipo `SismaFramework\Security\Enumerations\AccessControlEntry`.

- `$authenticable`: questo parametro, il cui valore può essere nullo o implementare l'interfaccia `SismaFramework\Security\Interfaces\Entities\AuthenticableInterface`, deve essere settato qualora la logica del dell'algoritmo del voter necessiti di tale informazione.

## Permissions

I permissions utilizzano i voters per scatenare un'eccezione in caso di risposta negativa di questi ultimi. Essi devono estendere la classe `SismaFramework\Security\BaseClasses\BasePermission` ed implementarne i due metodi astratti:

- `callParentPermissions()`: questo metodo serve a richiamare un'altra classe permission prima di eseguire la classe permission corrente. Non ha alcun valore di ritorno.

- `getVoter()`: questo metodo serve ad implementare la classe voter che la classe permission corrente dovrà utilizzare per fare il controllo ed, eventualmente, scatenare l'eccezione.

I permissions espongono il medesimo metodo statico `isAllowed()` descritto nel parametro riguardante i voters.

## Authentications

La classe `Authentication ` è una sorta di ibrido tra la funzionalità `Form` e la classe `Response` sviluppato appositamente per implementare i meccanismi di login in area riservata tramite username e password; supporta inoltre inplementazione dell'autenticazione a due fattori. Essa viene implementata direttamente dall'action che si occupa dell'autenticazione e richiama automaicamente al suo interno la classe `Request` dalla quale ottiene le informazioni necessarie per procedere.

Espone i seguenti metodi pubblici:

* `setUserModel()`: questo metodo inietta all'interno dellìoggetto il modello che si occuperà di reperire le informazioni dell'account per il quale il processo di autenticazione è implementato. Accetta come argomento un oggetto di tipo `BaseModel` che implementa l'interfaccia `UserModelInterface`.

* `setPasswordModel()`: questo metodo inietta all'interno dell'oggetto il modello che si occuperà di reperire la password dell'account per il quale il processo di autenticazione è implementato. Accetta come argomento un oggetto di tipo `BaseModel` che implementa l'interfaccia `PasswordModelInterface`.

* `setMultiFactorModel()`: questo metodo inietta all'interno dell'oggetto il modello che si occuperà di reperire, qualora sia implementato e settato l'accesso a più fattori, il token OPT collegato all'account per il quale il processo di autenticazione è implementato. Accetta come argomento un oggetto di tipo `BaseModel` che implementa l'interfaccia `MultiFactorModelInterface`.

* `setMultiFactorRecoveryModel()`: questo metodo inietta all'interno dell'oggetto il modello che si occuperà di reperire, qualora sia implementato e settato l'accesso a più fattori, i token di backup collegati all'account per il quale il processo di autenticazione è implementato, utili in caso di smarrimento e/o impossibilità di utilizzo del secondo fattore. Accetta come argomento un oggetto di tipo `BaseModel` che implementa l'interfaccia `MultiFactorRecoveryModelInterface`.

* `setMultiFactorWrapper()`: questo metodo inetta il wrapper che si occuperà, nel caso di implementazione di un servizio di accesso multi-fattore esterno, di interfacciarsi con il suddetto servizio e fornire le sue funzionalità al sistema. L'oggetto che il metodo accetta come argomento deve implementare l'interfaccia `MultiFactorWrapperInterface`.

* `checkUser()`: questo metodo controlla che ci sia una corrispondenza tra l'username ricevuto dal form *html* e un qualche untente registrato a sistema. Restituisce un valore di tipo `bool` che rappresenta l'esito della ricerca.

* `checkPassword()`: questo metodo, dato un oggetto di tipo `BaseEntity` che implementi l'interfaccia `UserInterface`, controlla la corrispondenza tra l'ultima password presente a sistema per l'utente il questione e quella inviata tramite form *html*. Restituisce un valore `bool` che rappresenta l'esito del confronto.

* `checkMultiFactor()`: questo metodo, dato un oggetto di tipo `BaseEntity` che implementi l'interfaccia `UserInterface`, in presenza dell'implementazione multi-fattore e dell'attivazione della stessa, controlla la che il token inserito framite form *html* corrisponda a quello fornito da sistema. Restituisce un valore `bool` che rappresenta l'esito del confronto.

* `checkMultiFactorRecovery()`: questo metodo, dato un oggetto di tipo `BaseEntity` che implementa l'interfaccia `MultiFactorInterface`, in presenza dell'implementazione multi-fattore e dell'attivazione della stessa, controlla la che il token inserito framite form *html* corrisponda ad uno dei codici di backup generati in fase si attivazione del secondo fattore di autenticazione, da utilizzare in caso di smarrimento e/o impossibilità di utilizzo dello stesso. Restituisce un valore `bool` che rappresenta l'esito del confronto.

* * *

[Indice](index.md) | Precedente: [Funzionalità aggiuntive](orm-additional-features.md) | Successivo: [Barra di Debug](debug-bar.md)



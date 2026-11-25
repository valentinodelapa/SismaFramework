# Riepilogo delle Convenzioni

SismaFramework si basa su una serie di convenzioni per automatizzare molte attività comuni, come il routing e la mappatura dell'ORM. Conoscere queste convenzioni è la chiave per usare il framework in modo efficace. Questa pagina funge da rapido riepilogo.

## Routing e URL

*   **Struttura URL:** `/nome-controller/nome-action/nome-param/valore-param`
*   **Mappatura Nomi:** I nomi nell'URL sono in `kebab-case` e vengono mappati in `CamelCase` nel codice.
    *   **URL:** `/user-profile/show-details`
    *   **Controller:** `UserProfileController`
    *   **Action:** `showDetails()`
*   **Controller di Default:** Se l'URL è vuoto, viene eseguita l'action indicata da `DEFAULT_ACTION` (default `index`) del controller indicato da `DEFAULT_PATH` (configurabili in `config.php`).

## Moduli e Configurazione

*   **Registrazione Moduli:** I moduli devono essere dichiarati nell'array `MODULE_FOLDERS` in `Config/config.php`, nell'ordine in cui vanno cercati controller e asset.
*   **Modulo Framework:** `'SismaFramework'` non va indicato: le sue classi sono caricate comunque, e l'applicazione di esempio che contiene (cartella `Sample/`) è il sito del framework, non un modulo da usare nei progetti. L'installazione genera la configurazione con `MODULE_FOLDERS` vuoto.

```php
const MODULE_FOLDERS = ['Blog', 'UserModule', 'ApiModule'];
```

## Controller

*   **Posizione:** `NomeModulo/Application/Controllers/`
*   **Nome Classe:** Deve terminare con `Controller` (es. `PostController`).
*   **Classe Base:** Deve estendere `SismaFramework\Core\BaseClasses\BaseController`.
*   **Actions:** Tutti i metodi `public` di un controller sono considerati "actions" e sono accessibili tramite URL.

## Viste

*   **Posizione:** `NomeModulo/Application/Views/`
*   **Percorso:** Il percorso passato a `Render::generateView('percorso/vista', ...)` è relativo alla cartella `Views`.
    *   `'post/index'` mappa a `NomeModulo/Application/Views/post/index.php`.

## ORM: Entità e Modelli

### Entità

*   **Posizione:** `NomeModulo/Application/Entities/`
*   **Nome Tabella:** Viene inferito dal nome della classe, convertito in `snake_case` **al singolare**.
    *   `Post` -> tabella `post`
    *   `User` -> tabella `user`
    *   `PostComment` -> tabella `post_comment`
    *   `Oauth2Client` -> tabella `oauth2_client`
    *
    **⚠️ Importante:** SismaFramework utilizza nomi di tabelle al **singolare**, non al plurale come altri framework. Eventuali numeri nei nomi di entità o proprietà fanno parte della parola che li precede senza underscore aggiuntivo prima della cifra (es. `$sha256Hash` <-> `sha256_hash`).
*   **Chiave Primaria:** Si assume che la colonna della chiave primaria si chiami `id`.
*   **Chiavi Esterne:** Vengono inferite dal nome della proprietà e dal suo tipo.
    *   `protected User $author;` -> colonna `author_id`.
    *   Una proprietà scalare il cui nome termina in `Id` non è una chiave esterna: si mappa sulla colonna con lo stesso nome in `snake_case`, sia in scrittura sia in lettura (es. `protected int $externalEventId;` <-> `external_event_id`, utile per gli identificativi provenienti da sistemi esterni). Una colonna che termina in `_id` viene trattata come chiave esterna solo se la proprietà senza suffisso esiste ed è di tipo entità.
*   **Collezioni Inverse (Lazy-Loaded):** Una "proprietà magica" viene aggiunta a un'entità per accedere alle entità che la referenziano.
    *   `$user->postCollection` -> restituisce una collezione di tutti i `Post` il cui `author` è `$user`.

### Modelli

*   **Posizione:** `NomeModulo/Application/Models/`
*   **Nome Classe:** Deve terminare con `Model` (es. `PostModel`).
*   **Metodi Magici:** Per query basate su relazioni.
    *   `$postModel->getByAuthor($user)` -> Trova tutti i post di un dato utente.
    *   `$postModel->countByAuthor($user)` -> Conta i post di un dato utente.
    *   `$postModel->deleteByAuthor($user)` -> Elimina i post di un dato utente.

## Form

*   **Posizione:** `NomeModulo/Application/Forms/`
*   **Nome Classe:** Solitamente termina con `Form` (es. `PostForm`).
*   **Campi HTML:** L'attributo `name` di un campo `<input>` o `<textarea>` deve corrispondere al nome della proprietà nell'entità associata.

## Internazionalizzazione (i18n)

*   **Posizione:** `NomeModulo/Application/Locales/`
*   **Nome File:** Deve corrispondere esattamente alla costante `LANGUAGE` in `config.php`, con estensione `.json`.
    *   `LANGUAGE = 'it_IT'` -> `it_IT.json`.
*   **Struttura:** Le chiavi della sezione `pages` rispecchiano la struttura della cartella `Views/`, con label `common` facoltative a ogni livello; nella sezione `templates` ogni template ha una chiave con il suo percorso completo relativo a `Templates/` (es. `"emails/welcome"`).
*   **Variabili:** Le label definite per una vista diventano variabili PHP disponibili nella vista; quelle definite per un template diventano segnaposto `{{...}}` del template.

## Asset Statici

*   **Posizione:** `NomeModulo/Application/Assets/`
*   **URL:** Il percorso nell'URL rispecchia la struttura all'interno della cartella `Assets`, senza prefisso.
    *   `/css/style.css` viene cercato in `NomeModulo/Application/Assets/css/style.css` nei moduli registrati, nell'ordine di `MODULE_FOLDERS`.

## Altre Convenzioni

| Componente | Posizione | Note |
|:---|:---|:---|
| **Data Fixtures** | `Application/Fixtures/` | Le classi devono estendere `BaseFixture`. |
| **Voters** | `Application/Voters/` | Le classi devono estendere `BaseVoter`. |
| **Permissions** | `Application/Permissions/` | Le classi devono estendere `BasePermission`. |
| **Templates** | `Application/Templates/` | Usati da `Templater` per generare stringhe. |
| **Enumerations** | `Application/Enumerations/` | Contiene le `Enum` specifiche del modulo. |

---

[Indice](index.md) | Precedente: [Architettura a Moduli](module-architecture.md) | Successivo: [Controllori](controllers.md)

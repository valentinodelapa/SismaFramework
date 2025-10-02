# Riepilogo delle Convenzioni

SismaFramework si basa su una serie di convenzioni per automatizzare molte attività comuni, come il routing e la mappatura dell'ORM. Conoscere queste convenzioni è la chiave per usare il framework in modo efficace. Questa pagina funge da rapido riepilogo.

## Routing e URL

*   **Struttura URL:** `/nome-controller/nome-action/nome-param/valore-param`
*   **Mappatura Nomi:** I nomi nell'URL sono in `kebab-case` e vengono mappati in `CamelCase` nel codice.
    *   **URL:** `/user-profile/show-details`
    *   **Controller:** `UserProfileController`
    *   **Action:** `showDetails()`
*   **Controller di Default:** Se l'URL è vuoto, viene eseguita l'action `index()` del `SampleController` (configurabile in `config.php`).

## Moduli e Configurazione

*   **Registrazione Moduli:** I moduli devono essere dichiarati nell'array `MODULE_FOLDERS` in `Config/config.php`.
*   **Modulo Framework:** `'SismaFramework'` contiene l'applicazione di esempio (cartella `Sample/`).
    *   **Opzionale:** Puoi mantenerlo se vuoi accedere agli esempi del framework
    *   **Setup pulito:** Rimuovilo e usa solo i tuoi moduli personalizzati

```php
// Opzione A: Con esempi del framework (opzionale)
const MODULE_FOLDERS = ['SismaFramework', 'Blog', 'UserModule'];

// Opzione B: Solo moduli personalizzati (setup pulito)
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
    *
    **⚠️ Importante:** SismaFramework utilizza nomi di tabelle al **singolare**, non al plurale come altri framework.
*   **Chiave Primaria:** Si assume che la colonna della chiave primaria si chiami `id`.
*   **Chiavi Esterne:** Vengono inferite dal nome della proprietà e dal suo tipo.
    *   `protected User $author;` -> colonna `author_id`.
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
*   **Nome File:** Deve corrispondere esattamente alla costante `LANGUAGE` in `config.php`, con estensione `.php` o `.json`.
    *   `LANGUAGE = 'it_IT'` -> `it_IT.php` o `it_IT.json`.
*   **Variabili:** Le chiavi definite nel file di lingua diventano variabili PHP disponibili nella vista.

## Asset Statici

*   **Posizione:** `NomeModulo/Application/Assets/`
*   **URL:** Il percorso nell'URL rispecchia la struttura all'interno della cartella `Assets`.
    *   `/assets/css/style.css` viene cercato in `NomeModulo/Application/Assets/css/style.css` in tutti i moduli registrati.

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

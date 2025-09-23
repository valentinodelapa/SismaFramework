# Viste e Template

Le **Viste** sono responsabili della presentazione dei dati all'utente. In SismaFramework, una vista è un file `.php` che contiene principalmente codice HTML, ma può includere codice PHP per visualizzare le variabili passate dal Controller.

Le variabili vengono passate a una vista tramite il metodo `Render::generateView()`. È buona norma utilizzare `htmlspecialchars()` quando si stampano dati forniti dall'utente per prevenire attacchi XSS.

### Esempio di una Vista

Se un controller passa `['pageTitle' => 'Dettaglio Articolo', 'post' => $postObject]` a una vista `blog/show.php`, il file della vista potrebbe essere così:

`MyModule/Application/Views/blog/show.php`

```php
<!DOCTYPE html>
<html lang="it">
<head>
    <title><?= htmlspecialchars($pageTitle) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($post->getTitle()) ?></h1>
    <article>
        <?= $post->getContent() // Supponendo che il contenuto sia già sanificato ?>
    </article>
</body>
</html>
```

### Creare Layout Riutilizzabili

Per evitare di ripetere lo stesso codice HTML (come `<head>`, `<body>`, header, footer) in ogni pagina, è una buona pratica creare un **layout di base**. SismaFramework non impone un sistema di templating engine (come Twig o Blade), quindi puoi usare semplici `require` di PHP per ottenere questo risultato in modo pulito ed efficiente.

Il pattern più comune consiste nel dividere il layout in due parti: un `header.php` e un `footer.php`.

#### 1. Creare i file di layout

Crea una cartella `layout` all'interno delle tue viste: `MyModule/Application/Views/layout/`.

`MyModule/Application/Views/layout/header.php`
```php
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Il Mio Sito') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <h1>Il Mio Fantastico Sito</h1>
        <nav>
            <a href="/">Home</a>
            <a href="/about">Chi Siamo</a>
        </nav>
    </header>
    <main>
```

`MyModule/Application/Views/layout/footer.php`
```php
    </main>
    <footer>
        <p>&copy; <?= date('Y') ?> Il Mio Sito. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>
```

#### 2. Usare il layout in una vista

Ora, in qualsiasi vista specifica (es. la pagina "Chi Siamo"), puoi includere questi due file per "avvolgere" il tuo contenuto.

`MyModule/Application/Views/page/about.php`
```php
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<h2>Chi Siamo</h2>
<p>Questa è la pagina "Chi Siamo" del nostro sito. Siamo un team di sviluppatori appassionati!</p>
    
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

Templates
---------

I **Template** sono simili alle viste ma sono progettati per generare output sotto forma di stringa, anziché inviarlo direttamente al browser. Questo li rende ideali per scenari come la creazione del corpo di un'email o la generazione di file di testo.

I template utilizzano una sintassi semplice con segnaposto (es. `{{nome_variabile}}`) che vengono sostituiti con i valori forniti.

### Esempio di un Template

1.  **Crea il file del template:**
    `MyModule/Application/Templates/emails/welcome.html`
   
   ```html
   <h1>Benvenuto, {{username}}!</h1>
   <p>Grazie per esserti registrato al nostro sito.</p>
   <p>Speriamo che la tua esperienza sia fantastica.</p>
   ```

2.  **Usa `Templater` per generare la stringa:**
   
   ```php
   use SismaFramework\Core\HelperClasses\Templater;
   
   // In un Controller o un Servizio...
   $datiEmail = [
       'username' => 'Mario Rossi'
   ];
   
   $corpoEmail = Templater::generateTemplate('emails/welcome', $datiEmail);
   
   // Ora la variabile $corpoEmail contiene l'HTML completo
   // e può essere usata per inviare un'email.
   // mail('utente@example.com', 'Benvenuto!', $corpoEmail, $headers);
   ```

---

[Indice](index.md) | Precedente: [Controllori](controllers.md) | Successivo: [Gestione dei Form](forms.md)

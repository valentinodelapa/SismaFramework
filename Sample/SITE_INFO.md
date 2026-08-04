# 🌐 Sito SismaFramework - Documentazione Completa

## 🎯 Cos'è il Sito del Framework

Il modulo **Sample** ora contiene un **sito completo e funzionante** che serve come:

1. **Vetrina del Framework** - Homepage professionale con presentazione
2. **Documentazione Integrata** - Viewer Markdown per tutti i file `.md` in `docs/`
3. **Esempi Live** - Demo interattive di tutte le funzionalità

## 📍 URLs Principali

### Homepage e Presentazione
- **`/home/index`** - Landing page principale
  - Hero section con CTA
  - Features cards
  - Quick start guide
  - Statistiche del framework

### Documentazione
- **`/docs/index`** - Indice della documentazione organizzato per sezioni
- **`/docs/view/file/{filename}`** - Viewer per singolo file MD

Esempi:
- `/docs/view/file/getting-started` → Visualizza `docs/getting-started.md`
- `/docs/view/file/orm` → Visualizza `docs/orm.md`
- `/docs/view/file/security` → Visualizza `docs/security.md`

### Esempi Tecnici
- **`/sample/index`** - Dashboard esempi
- `/sample/show-article/id/1` - Entity Injection
- `/sample/filter-by-status/status/P` - Enum Binding
- `/sample/search?q=test` - Request Autowiring
- E altri...

## 🎨 Caratteristiche del Sito

### Design e UI
✅ **Responsive** - Layout Bootstrap 5 che funziona su mobile/tablet/desktop
✅ **Moderno** - Gradient colors, cards, icons Bootstrap
✅ **Professionale** - Hero section, features grid, footer completo
✅ **Accessibile** - Breadcrumb, navigazione chiara, semantica HTML

### Documentazione Viewer
✅ **Parser Markdown** - Converte `.md` in HTML automaticamente
✅ **Syntax Highlighting** - Prism.js per code blocks (PHP, SQL, Bash)
✅ **Sidebar Navigation** - Menu fisso con tutte le sezioni
✅ **Prev/Next Buttons** - Navigazione sequenziale tra docs
✅ **Active State** - Evidenzia il documento corrente
✅ **GitHub Links** - "Edit on GitHub" per ogni pagina

### Features Tecniche
✅ **Zero Config** - Funziona out-of-the-box
✅ **Nessuna Dipendenza** - Usa solo CDN per CSS/JS
✅ **SEO Ready** - Titoli, meta tags, URL semantici
✅ **Performance** - Sticky sidebar, CSS ottimizzato

## 🛠️ Componenti Creati

### Controllers

#### **HomeController.php**
```php
/home/index      → Homepage con hero e features
/home/features   → Dettaglio features (opzionale)
/home/welcome    → Redirect a homepage
```

#### **DocsController.php**
```php
/docs/index               → Indice documentazione
/docs/view/file/{name}    → Viewer file Markdown

Funzionalità:
- Parser Markdown → HTML
- Estrazione titolo automatica
- Struttura docs organizzata
- Lettura file da docs/
```

#### **SampleController.php** (già esistente, migliorato)
Esempi tecnici con autowiring, entity injection, ecc.

### Views

#### **home/index.php**
- Hero section gradient
- Features cards (8 features principali)
- Quick start con codice
- Why Choose section
- Stats section (PHP 8.3+, Coverage >80%, MIT, MVC)
- CTA buttons

#### **docs/index.php**
- Cards organizzate per sezione (7 sezioni)
- Quick links (Getting Started, ORM, API)
- Help section con link utili

#### **docs/viewer.php**
- Sidebar sticky con navigazione completa
- Contenuto HTML dal Markdown
- Breadcrumb navigation
- Prev/Next buttons automatici
- GitHub edit/report links

#### **commonParts/siteLayoutHeader.php + siteLayoutFooter.php**
Layout master (diviso in header/footer, secondo il pattern documentato in `docs/views.md`) con:
- Navbar responsive con menu
- Footer completo con link
- CSS custom per docs, cards, code
- Script Prism.js per highlighting
- Variabili CSS per theming

## 📝 Parser Markdown

Il `DocsController` include un parser Markdown semplificato che supporta:

✅ Headers (H1, H2, H3)
✅ Code blocks con language (```php, ```sql)
✅ Inline code (`code`)
✅ Bold (**text**) e Italic (*text*)
✅ Links ([text](url))
✅ Lists (ordered e unordered)
✅ Blockquotes (> text)
✅ Horizontal rules (---)
✅ Paragrafi automatici

### Upgrade Opzionale
Per progetti reali, considera:
- **Parsedown** - `composer require erusev/parsedown`
- **League CommonMark** - `composer require league/commonmark`

## 🎯 Come Funziona

### Flow di Navigazione

1. **Homepage** → Utente entra in `/home/index`
   - Vede presentazione framework
   - Click su "Documentazione" → `/docs/index`

2. **Indice Docs** → Utente in `/docs/index`
   - Vede tutte le sezioni organizzate
   - Click su "Getting Started" → `/docs/view/file/getting-started`

3. **Viewer Doc** → Utente in `/docs/view/file/getting-started`
   - Legge contenuto Markdown renderizzato
   - Sidebar per navigare ad altri docs
   - Prev/Next per navigazione sequenziale
   - Edit on GitHub per contribuire

4. **Esempi** → Click su "Esempi" → `/sample/index`
   - Vede demo live del codice
   - Può testare le funzionalità

### Struttura Docs Organizzata

Il `getDocsStructure()` nel controller definisce 7 sezioni:

1. **Introduzione** - index, installation, getting-started, overview
2. **Core Concepts** - module-architecture, controllers, views, conventions
3. **ORM & Database** - orm, advanced-orm, orm-additional-features, custom-types
4. **Funzionalità** - forms, security, i18n, static-assets
5. **Avanzato** - enumerations, traits, helpers, fixtures, debug-bar
6. **Testing & Deploy** - testing, performance, deployment, troubleshooting
7. **Reference** - api-reference, config-reference, best-practices

## 🚀 Setup e Utilizzo

### 1. Configurazione (Opzionale)

Il sito funziona già senza configurazione! Ma puoi personalizzare:

**Cambia logo/brand nel layout:**
```php
// In Views/commonParts/siteLayoutHeader.php
<a class="navbar-brand" href="/home/index">
    <i class="bi bi-hexagon-fill"></i> TuoFramework
</a>
```

**Aggiungi/rimuovi features:**
```php
// In Controllers/HomeController.php -> index()
$this->vars['features'] = [
    // Aggiungi o modifica features qui
];
```

### 2. Aggiungi Nuovi Docs

Basta creare un file `.md` in `docs/` e aggiungerlo alla struttura:

```php
// In Controllers/DocsController.php -> getDocsStructure()
'Nuova Sezione' => [
    ['file' => 'nuovo-doc', 'title' => 'Nuovo Documento'],
],
```

### 3. Personalizza il Parser

Se vuoi supporto Markdown più avanzato:

```php
// In Controllers/DocsController.php
private function parseMarkdown(string $markdown): string
{
    // Opzione 1: Usa Parsedown
    $parsedown = new \Parsedown();
    return $parsedown->text($markdown);

    // Opzione 2: Usa CommonMark
    $converter = new \League\CommonMark\CommonMarkConverter();
    return $converter->convert($markdown);
}
```

## 💡 Vantaggi

✅ **Out-of-the-box** - Funziona appena clonato il framework
✅ **Zero dipendenze** - Solo CDN, nessun npm/webpack
✅ **SEO Friendly** - URL semantici, meta tags, sitemap-ready
✅ **Offline Docs** - Tutta la documentazione in locale
✅ **Customizable** - Facile da personalizzare e estendere
✅ **Educativo** - Mostra come costruire un sito MVC completo

## 🔗 Link Esterni

Il sito include link a:
- **GitHub** - Repository del framework
- **Issues** - Per segnalazioni bug/feature
- **Edit on GitHub** - Per contribuire alla documentazione

## 📊 Risultato Finale

Hai ora un **sito completo** per SismaFramework che:
- Presenta il framework in modo professionale
- Offre documentazione completa navigabile
- Mostra esempi pratici funzionanti
- Serve come punto di riferimento per gli sviluppatori
- Dimostra le potenzialità del framework stesso

---

**Il framework ora si presenta da solo! 🎉**

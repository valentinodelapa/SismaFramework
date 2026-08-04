# Sample - Sito e Esempi di SismaFramework

Questa cartella contiene il **sito ufficiale del framework** con documentazione integrata ed esempi completi funzionanti.

## 🌐 Sito del Framework

Il modulo Sample include:

### 1. **Homepage** (`/home/index`)
- Landing page professionale con hero section
- Panoramica features del framework
- Quick start guide
- Link a documentazione ed esempi

### 2. **Documentazione Integrata** (`/docs/*`)
- **`/docs/index`** - Indice completo della documentazione
- **`/docs/view/file/{nome-file}`** - Viewer Markdown con:
  - Parser Markdown → HTML
  - Syntax highlighting per code blocks
  - Sidebar con navigazione
  - Navigation prev/next
  - Link per edit su GitHub

### 3. **Esempi Live** (`/sample/*`)
- Demo interattive di tutte le funzionalità
- Codice commentato e spiegato
- Best practices in azione

## 📁 Struttura

```
Sample/
├── Controllers/
│   ├── HomeController.php      # Landing page del framework
│   ├── DocsController.php      # Viewer documentazione MD
│   └── SampleController.php    # Esempi tecnici
├── Entities/                   # Entity con tipi diversi, relazioni e crittografia
├── Enumerations/               # BackedEnum personalizzate
├── Models/                     # Model con query custom e gestione relazioni
└── Views/
    ├── home/                   # Homepage e presentazione
    ├── docs/                   # Viewer documentazione
    ├── sample/                 # Esempi tecnici
    └── commonParts/
        ├── siteLayoutHeader.php # Header + navbar del layout comune
        └── siteLayoutFooter.php # Footer del layout comune
```

## 🚀 Come Utilizzare gli Esempi

### 1. **Configurazione Database**

Prima di utilizzare gli esempi, crea le tabelle nel database:

```sql
-- Tabella autori (ReferencedEntity)
CREATE TABLE `sample_referenced_entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` text NOT NULL,  -- Crittografata dall'ORM
  `bio` text DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabella articoli base (BaseEntity)
CREATE TABLE `sample_base_entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `featured` tinyint(1) DEFAULT 0,
  `published_at` datetime NOT NULL,
  `status` char(1) NOT NULL,  -- Enum: D, P, A
  `internal_notes` text DEFAULT NULL,  -- Crittografata dall'ORM
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabella articoli con relazione (DependentEntity)
CREATE TABLE `sample_dependent_entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL,
  `status` char(1) NOT NULL,  -- Enum: D, P, A
  `views` int(11) DEFAULT 0,
  `sample_referenced_entity_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sample_referenced_entity_id` (`sample_referenced_entity_id`),
  CONSTRAINT `sample_dependent_entity_ibfk_1`
    FOREIGN KEY (`sample_referenced_entity_id`)
    REFERENCES `sample_referenced_entity` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. **Popola il Database (Opzionale)**

Puoi inserire dati di esempio manualmente o creare delle Fixtures seguendo la documentazione.

Esempio di inserimento manuale:

```sql
-- Inserisci un autore
INSERT INTO sample_referenced_entity (full_name, email, bio, verified)
VALUES ('Mario Rossi', 'mario@example.com', 'Sviluppatore PHP esperto', 1);

-- Inserisci articoli
INSERT INTO sample_base_entity (title, content, rating, featured, published_at, status)
VALUES
('Primo Articolo', 'Contenuto del primo articolo...', 4.5, 1, NOW(), 'P'),
('Secondo Articolo', 'Contenuto del secondo articolo...', 3.8, 0, NOW(), 'P');

-- Inserisci articoli con relazione
INSERT INTO sample_dependent_entity (title, content, created_at, status, views, sample_referenced_entity_id)
VALUES
('Articolo con Autore', 'Questo articolo ha un autore...', NOW(), 'P', 150, 1);
```

### 3. **Naviga gli Esempi**

Accedi all'applicazione e visita:

- **`/sample/index`** - Pagina principale con tutti gli esempi
- **`/sample/show-article/id/1`** - Entity Injection
- **`/sample/filter-by-status/status/P`** - Enum Parameter Binding
- **`/sample/articles-by-author/authorId/1`** - Relazioni e Lazy Loading
- **`/sample/search?q=test`** - Request Autowiring
- **`/sample/protected`** - Authentication

## 📚 Cosa Dimostrano gli Esempi

### **Entities**

#### **SampleBaseEntity** (BaseEntity)
Mostra l'uso di:
- ✅ Tipi nativi: `int`, `string`, `float`, `bool`
- ✅ Custom types: `SismaDateTime`
- ✅ BackedEnum: `ArticleStatus`
- ✅ Proprietà nullable
- ✅ Crittografia: `internalNotes` viene crittografata nel DB
- ✅ Valori default: `setPropertyDefaultValue()`

#### **SampleReferencedEntity** (ReferencedEntity)
Mostra:
- ✅ Entità referenziata da altre entità
- ✅ Cache automatica per performance
- ✅ Collezioni inverse: `$author->sampleDependentEntityCollection`
- ✅ Crittografia email

#### **SampleDependentEntity** (BaseEntity con relazione)
Mostra:
- ✅ Relazione Many-to-One
- ✅ Lazy Loading dell'autore
- ✅ Naming convention: `sampleReferencedEntity` → `sample_referenced_entity_id`

### **Models**

#### **SampleBaseEntityModel**
Dimostra:
- ✅ Ricerca testuale con `appendSearchCondition()`
- ✅ Metodi custom: `getPublishedArticles()`, `getFeaturedArticles()`
- ✅ Query Builder avanzato
- ✅ Conteggi con filtri enum

#### **SampleDependentEntityModel** (DependentModel)
Dimostra:
- ✅ Query basate su relazioni: `getArticlesByAuthor()`
- ✅ Metodi magici: `getBySampleReferencedEntity()`
- ✅ JOIN per evitare problema N+1
- ✅ Conteggi su entità correlate

### **Controller**

Il **SampleController** mostra:

1. **Entity Injection** - `showArticle(SampleBaseEntity $article)`
2. **Enum Binding** - `filterByStatus(ArticleStatus $status)`
3. **Autowiring Request** - `search(Request $request)`
4. **Autowiring Authentication** - `protected(Authentication $auth)`
5. **Custom Type Binding** - `articlesByDate(SismaDateTime $date)`
6. **Relazioni e Lazy Loading** - `articlesByAuthor(int $authorId)`

### **Enumerations**

**ArticleStatus** (BackedEnum) mostra:
- ✅ Valori tipizzati: `DRAFT = 'D'`, `PUBLISHED = 'P'`, `ARCHIVED = 'A'`
- ✅ Metodi custom: `getLabel()`, `isPublic()`
- ✅ Trait `SelectableEnumeration` per uso in form

### **Views**

Le viste dimostrano:
- ✅ Accesso sicuro ai dati: `htmlspecialchars()`
- ✅ Uso di proprietà entity: `$article->getTitle()`
- ✅ Metodi enum: `$status->getLabel()`
- ✅ Formattazione date: `$date->format('d/m/Y')`
- ✅ Controllo nullable: `$article->getContent() ?? ''`

## 🎯 Percorso di Apprendimento Consigliato

1. **Inizia con**: `/sample/index` per vedere tutti gli esempi
2. **Studia le Entity** in `Entities/` per capire la mappatura ORM
3. **Esplora i Model** in `Models/` per le query
4. **Analizza il Controller** in `Controllers/SampleController.php` per l'autowiring
5. **Guarda le Viste** in `Views/sample/` per il rendering

## 🔗 Link Utili

- [Documentazione Completa](../docs/index.md)
- [Guida Getting Started](../docs/getting-started.md)
- [ORM Guide](../docs/orm.md)
- [Controller Guide](../docs/controllers.md)

## 💡 Suggerimenti

- Gli esempi sono **funzionanti** e possono essere usati come template
- Copia e adatta il codice per i tuoi progetti
- Usa gli esempi per capire le best practices del framework
- Le Entity mostrano tutti i tipi supportati dall'ORM
- Il Controller mostra tutte le funzionalità di autowiring/binding

---

**Buon apprendimento con SismaFramework! 🚀**

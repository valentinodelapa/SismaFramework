<!DOCTYPE html>
<html lang="it" class="h-100">
    <head>
        <?php require_once __DIR__ . '/../commonParts/baseHead.php'; ?>
        <title>SismaFramework - Esempi</title>
    </head>
    <body class="h-100">
        <header class="h-25 d-flex align-items-center justify-content-center flex-row">
            <?php require_once __DIR__ . '/../commonParts/header.php'; ?>
        </header>
        <nav>
            <?php require_once __DIR__ . '/../commonParts/menu.php'; ?>
        </nav>
        <article class="container my-4">
            <h1>Esempi di SismaFramework</h1>
            <p class="lead">Questa pagina dimostra le principali funzionalità del framework</p>

            <!-- Esempio 1: Lista articoli con accesso alle proprietà -->
            <section class="mb-5">
                <h2>Tutti gli Articoli</h2>
                <?php if (empty($articles)): ?>
                    <p class="text-muted">Nessun articolo disponibile. Esegui le fixtures per popolare il database.</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($articles as $article): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($article->getTitle()) ?></h5>
                                        <p class="card-text">
                                            <?= htmlspecialchars(substr($article->getContent() ?? '', 0, 100)) ?>...
                                        </p>
                                        <p class="text-muted small">
                                            <strong>Stato:</strong> <?= $article->getStatus()->getLabel() ?><br>
                                            <strong>Rating:</strong> <?= $article->getRating() ?>/5.0<br>
                                            <strong>Pubblicato:</strong> <?= $article->getPublishedAt()->format('d/m/Y') ?>
                                        </p>
                                        <?php if ($article->getFeatured()): ?>
                                            <span class="badge bg-warning">In evidenza</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Esempio 2: Articoli pubblicati (filtrati dal model) -->
            <section class="mb-5">
                <h2>Ultimi 5 Articoli Pubblicati</h2>
                <?php if (empty($publishedArticles)): ?>
                    <p class="text-muted">Nessun articolo pubblicato.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($publishedArticles as $article): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($article->getTitle()) ?></strong>
                                <span class="text-muted">- <?= $article->getPublishedAt()->format('d/m/Y H:i') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <!-- Esempio 3: Articoli in evidenza -->
            <section class="mb-5">
                <h2>Articoli in Evidenza</h2>
                <?php if (empty($featuredArticles)): ?>
                    <p class="text-muted">Nessun articolo in evidenza.</p>
                <?php else: ?>
                    <div class="alert alert-info">
                        Ci sono <strong><?= count($featuredArticles) ?></strong> articoli in evidenza
                    </div>
                <?php endif; ?>
            </section>

            <!-- Esempio 4: Link alle altre action -->
            <section class="mb-5">
                <h2>Altre Funzionalità</h2>
                <div class="list-group">
                    <a href="/sample/show-article/id/1" class="list-group-item list-group-item-action">
                        <strong>Entity Injection</strong> - Mostra articolo con ID 1
                    </a>
                    <a href="/sample/filter-by-status/status/P" class="list-group-item list-group-item-action">
                        <strong>Enum Binding</strong> - Filtra articoli pubblicati
                    </a>
                    <a href="/sample/articles-by-author/authorId/1" class="list-group-item list-group-item-action">
                        <strong>Relazioni & Lazy Loading</strong> - Articoli dell'autore 1
                    </a>
                    <a href="/sample/search?q=test" class="list-group-item list-group-item-action">
                        <strong>Request Autowiring</strong> - Ricerca articoli
                    </a>
                    <a href="/sample/protected" class="list-group-item list-group-item-action">
                        <strong>Authentication</strong> - Area protetta
                    </a>
                </div>
            </section>

            <!-- Note per sviluppatori -->
            <section class="alert alert-success">
                <h5>📚 Per sviluppatori</h5>
                <p>Questi esempi mostrano:</p>
                <ul>
                    <li><strong>ORM</strong>: Entity, Model, relazioni, lazy loading</li>
                    <li><strong>Controller</strong>: Autowiring, entity injection, enum binding</li>
                    <li><strong>Views</strong>: Accesso sicuro ai dati con htmlspecialchars()</li>
                    <li><strong>Custom Types</strong>: SismaDateTime, BackedEnum</li>
                    <li><strong>Security</strong>: Crittografia proprietà, autenticazione</li>
                </ul>
                <p class="mb-0">Consulta la <a href="/docs" target="_blank">documentazione completa</a> per saperne di più.</p>
            </section>
        </article>
        <footer>
            <?php require_once __DIR__ . '/../commonParts/footer.php'; ?>
        </footer>
    </body>
</html>
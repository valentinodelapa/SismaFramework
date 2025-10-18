<?php ob_start(); ?>

<!-- Hero Section -->
<div class="bg-gradient bg-opacity-10" style="background: linear-gradient(135deg, #4a90e2 0%, #7b68ee 100%); padding: 3rem 0;">
    <div class="container">
        <h1 class="display-5 fw-bold text-white mb-3">
            <i class="bi bi-code-square"></i> Esempi di SismaFramework
        </h1>
        <p class="lead text-white opacity-75">
            Questa pagina dimostra le principali funzionalità del framework in azione
        </p>
    </div>
</div>

<div class="container my-5"

    <!-- Esempio 1: Lista articoli con accesso alle proprietà -->
    <section class="mb-5">
        <h2 class="mb-4">
            <i class="bi bi-file-text text-primary"></i> Tutti gli Articoli
        </h2>
        <?php if (empty($articles)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> Nessun articolo disponibile. Esegui le fixtures per popolare il database.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($articles as $article): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($article->title) ?></h5>
                                <p class="card-text text-muted">
                                    <?= htmlspecialchars(substr($article->content ?? '', 0, 100)) ?>...
                                </p>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-<?= $article->status->value === 'P' ? 'success' : 'secondary' ?>">
                                        <?= $article->status->getLabel() ?>
                                    </span>
                                    <?php if ($article->featured): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-star-fill"></i> In evidenza
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-muted">
                                    <div><i class="bi bi-star"></i> <strong>Rating:</strong> <?= $article->rating ?>/5.0</div>
                                    <div><i class="bi bi-calendar"></i> <strong>Pubblicato:</strong> <?= $article->publishedAt->format('d/m/Y') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Esempio 2: Articoli pubblicati (filtrati dal model) -->
    <section class="mb-5">
        <h2 class="mb-4">
            <i class="bi bi-check-circle text-success"></i> Ultimi 5 Articoli Pubblicati
        </h2>
        <?php if (empty($publishedArticles)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Nessun articolo pubblicato.
            </div>
        <?php else: ?>
            <div class="list-group shadow-sm">
                <?php foreach ($publishedArticles as $article): ?>
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($article->title) ?></h6>
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> <?= $article->publishedAt->format('d/m/Y H:i') ?>
                            </small>
                        </div>
                        <span class="badge bg-success">Pubblicato</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Esempio 3: Articoli in evidenza -->
    <section class="mb-5">
        <h2 class="mb-4">
            <i class="bi bi-star-fill text-warning"></i> Articoli in Evidenza
        </h2>
        <?php if (empty($featuredArticles)): ?>
            <div class="alert alert-secondary">
                <i class="bi bi-info-circle"></i> Nessun articolo in evidenza.
            </div>
        <?php else: ?>
            <div class="alert alert-primary shadow-sm">
                <i class="bi bi-star-fill"></i> Ci sono <strong><?= count($featuredArticles) ?></strong> articoli in evidenza
            </div>
        <?php endif; ?>
    </section>

    <!-- Esempio 4: Link alle altre action -->
    <section class="mb-5">
        <h2 class="mb-4">
            <i class="bi bi-boxes text-info"></i> Altre Funzionalità
        </h2>
        <div class="row g-3">
            <div class="col-md-6">
                <a href="/sample/show-article/article/1" class="card text-decoration-none shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Entity Injection
                        </h5>
                        <p class="card-text text-muted">Mostra articolo con ID 1</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="/sample/filter-by-status/status/P" class="card text-decoration-none shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <i class="bi bi-funnel"></i> Enum Binding
                        </h5>
                        <p class="card-text text-muted">Filtra articoli pubblicati</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="/sample/articles-by-author/authorId/1" class="card text-decoration-none shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <i class="bi bi-link-45deg"></i> Relazioni & Lazy Loading
                        </h5>
                        <p class="card-text text-muted">Articoli dell'autore 1</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="/sample/search?q=test" class="card text-decoration-none shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <i class="bi bi-search"></i> Request Autowiring
                        </h5>
                        <p class="card-text text-muted">Ricerca articoli</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Note per sviluppatori -->
    <section class="card border-success shadow-sm">
        <div class="card-body">
            <h5 class="card-title text-success">
                <i class="bi bi-mortarboard"></i> Per Sviluppatori
            </h5>
            <p class="card-text">Questi esempi mostrano:</p>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle-fill text-success"></i> <strong>ORM</strong>: Entity, Model, relazioni, lazy loading</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i> <strong>Controller</strong>: Autowiring, entity injection, enum binding</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i> <strong>Views</strong>: Accesso sicuro ai dati con htmlspecialchars()</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle-fill text-success"></i> <strong>Custom Types</strong>: SismaDateTime, BackedEnum</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i> <strong>Security</strong>: Crittografia proprietà, autenticazione</li>
                    </ul>
                </div>
            </div>
            <hr>
            <p class="mb-0">
                <a href="/docs/index" class="btn btn-outline-success">
                    <i class="bi bi-book"></i> Consulta la documentazione completa
                </a>
            </p>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';

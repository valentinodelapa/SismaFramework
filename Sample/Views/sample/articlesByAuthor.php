<?php ob_start(); ?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sample/index">Esempi</a></li>
            <li class="breadcrumb-item active">Articoli per Autore</li>
        </ol>
    </nav>

    <h1 class="display-5 mb-4">
        <i class="bi bi-person-circle"></i> Articoli di <?= htmlspecialchars($author->fullName) ?>
    </h1>

    <!-- Informazioni autore -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="card-title mb-3">
                        <i class="bi bi-person-badge"></i> <?= htmlspecialchars($author->fullName) ?>
                        <?php if ($author->verified): ?>
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill"></i> Verificato
                            </span>
                        <?php endif; ?>
                    </h4>
                    <p class="card-text">
                        <i class="bi bi-envelope"></i> <strong>Email (crittografata nel DB):</strong>
                        <code><?= htmlspecialchars($author->email) ?></code>
                    </p>
                    <?php if ($author->bio): ?>
                        <p class="card-text text-muted"><?= nl2br(htmlspecialchars($author->bio)) ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-center">
                    <div class="badge bg-primary fs-4 p-3">
                        <?= count($articles) ?>
                        <div class="small">articoli</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista articoli -->
    <h2 class="mb-4">
        <i class="bi bi-journal-text"></i> Articoli (<?= count($articles) ?>)
    </h2>

    <?php if (empty($articles)): ?>
        <div class="alert alert-info shadow-sm">
            <i class="bi bi-info-circle"></i> Questo autore non ha ancora pubblicato articoli.
        </div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($articles as $article): ?>
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                        <h5 class="mb-1"><?= htmlspecialchars($article->title) ?></h5>
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> <?= $article->createdAt->format('d/m/Y') ?>
                        </small>
                    </div>
                    <p class="mb-2 text-muted"><?= htmlspecialchars(substr($article->content, 0, 150)) ?>...</p>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-<?= $article->status->isPublic() ? 'success' : 'secondary' ?>">
                            <?= $article->status->getLabel() ?>
                        </span>
                        <small class="text-muted">
                            <i class="bi bi-eye"></i> <?= $article->views ?> visualizzazioni
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Spiegazione tecnica -->
    <div class="card border-info shadow-sm mt-5">
        <div class="card-body">
            <h5 class="card-title text-info">
                <i class="bi bi-link-45deg"></i> Esempio di Relazioni e Lazy Loading
            </h5>
            <p class="card-text">Questa pagina mostra:</p>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Relazione Many-to-One:</strong><br>
                            <small>Ogni articolo appartiene a un autore</small><br>
                            <code class="small">$article->sampleReferencedEntity</code>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Lazy Loading:</strong><br>
                            <small>L'autore viene caricato solo quando accedi alla proprietà</small>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Collezione Inversa:</strong><br>
                            <small>Dall'autore puoi accedere a tutti i suoi articoli</small><br>
                            <code class="small">$author->sampleDependentEntityCollection</code>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Query Ottimizzate:</strong><br>
                            <small>Il model usa <code>getArticlesByAuthor()</code> per evitare N+1</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="/sample/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Torna alla lista
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';

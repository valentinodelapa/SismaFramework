<?php ob_start(); ?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sample/index">Esempi</a></li>
            <li class="breadcrumb-item active">Articolo</li>
        </ol>
    </nav>

    <article>
        <h1 class="display-5 mb-4"><?= htmlspecialchars($article->title) ?></h1>

        <div class="mb-4 d-flex gap-2">
            <span class="badge bg-<?= $article->status->isPublic() ? 'success' : 'secondary' ?> fs-6">
                <?= $article->status->getLabel() ?>
            </span>
            <?php if ($article->featured): ?>
                <span class="badge bg-warning text-dark fs-6">
                    <i class="bi bi-star-fill"></i> In evidenza
                </span>
            <?php endif; ?>
        </div>

        <div class="mb-4 text-muted">
            <i class="bi bi-calendar"></i> Pubblicato il <?= $article->publishedAt->format('d/m/Y H:i') ?> |
            <i class="bi bi-star"></i> Rating: <?= $article->rating ?>/5.0
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title text-primary">
                    <i class="bi bi-file-text"></i> Contenuto
                </h5>
                <hr>
                <div class="fs-5">
                    <?= nl2br(htmlspecialchars($article->content ?? 'Nessun contenuto')) ?>
                </div>
            </div>
        </div>

        <?php if ($article->internalNotes): ?>
            <div class="alert alert-warning shadow-sm">
                <h5 class="alert-heading">
                    <i class="bi bi-lock-fill"></i> Note Interne (Crittografate nel DB)
                </h5>
                <p class="mb-0"><?= htmlspecialchars($article->internalNotes) ?></p>
            </div>
        <?php endif; ?>

        <div class="card border-info shadow-sm mt-4">
            <div class="card-body">
                <h5 class="card-title text-info">
                    <i class="bi bi-box-arrow-in-right"></i> Esempio di Entity Injection
                </h5>
                <p class="card-text">Questa pagina dimostra come il framework carica automaticamente l'entity dall'URL:</p>
                <pre class="bg-dark text-light p-3 rounded"><code class="language-php">public function showArticle(SampleBaseEntity $article): Response</code></pre>
                <p class="mb-0">
                    L'URL <code class="text-primary">/sample/show-article/article/<?= $article->id ?></code> viene convertito automaticamente
                    nell'oggetto <code class="text-success">SampleBaseEntity</code> con ID <strong><?= $article->id ?></strong>.
                    Il nome del parametro URL ('article') corrisponde al nome del parametro del metodo.
                </p>
            </div>
        </div>
    </article>

    <div class="mt-5">
        <a href="/sample/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Torna alla lista
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';

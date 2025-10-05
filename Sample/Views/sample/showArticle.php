<!DOCTYPE html>
<html lang="it">
    <head>
        <?php require_once __DIR__ . '/../commonParts/baseHead.php'; ?>
        <title><?= htmlspecialchars($pageTitle ?? 'Articolo') ?> - SismaFramework</title>
    </head>
    <body>
        <div class="container my-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/sample/index">Home</a></li>
                    <li class="breadcrumb-item active">Articolo</li>
                </ol>
            </nav>

            <article>
                <h1><?= htmlspecialchars($article->getTitle()) ?></h1>

                <div class="mb-3">
                    <span class="badge bg-<?= $article->getStatus()->isPublic() ? 'success' : 'secondary' ?>">
                        <?= $article->getStatus()->getLabel() ?>
                    </span>
                    <?php if ($article->getFeatured()): ?>
                        <span class="badge bg-warning">In evidenza</span>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <small class="text-muted">
                        Pubblicato il <?= $article->getPublishedAt()->format('d/m/Y H:i') ?> |
                        Rating: <?= $article->getRating() ?>/5.0
                    </small>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?= nl2br(htmlspecialchars($article->getContent() ?? 'Nessun contenuto')) ?>
                    </div>
                </div>

                <?php if ($article->getInternalNotes()): ?>
                    <div class="alert alert-warning mt-4">
                        <strong>Note interne (crittografate nel DB):</strong><br>
                        <?= htmlspecialchars($article->getInternalNotes()) ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <h4>📋 Esempio di Entity Injection</h4>
                    <p>Questa pagina dimostra come il framework carica automaticamente l'entity dall'URL:</p>
                    <code>public function showArticle(SampleBaseEntity $article): Response</code>
                    <p class="mt-2">
                        L'URL <code>/sample/show-article/id/<?= $article->getId() ?></code> viene convertito automaticamente
                        nell'oggetto <code>SampleBaseEntity</code> con ID <?= $article->getId() ?>.
                    </p>
                </div>
            </article>

            <a href="/sample/index" class="btn btn-secondary mt-4">← Torna alla lista</a>
        </div>
    </body>
</html>

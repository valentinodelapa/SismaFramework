<!DOCTYPE html>
<html lang="it">
    <head>
        <?php require_once __DIR__ . '/../commonParts/baseHead.php'; ?>
        <title>Articoli di <?= htmlspecialchars($author->getFullName()) ?> - SismaFramework</title>
    </head>
    <body>
        <div class="container my-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/sample/index">Home</a></li>
                    <li class="breadcrumb-item active">Articoli per Autore</li>
                </ol>
            </nav>

            <h1>Articoli di <?= htmlspecialchars($author->getFullName()) ?></h1>

            <!-- Informazioni autore -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <?= htmlspecialchars($author->getFullName()) ?>
                        <?php if ($author->getVerified()): ?>
                            <span class="badge bg-success">Verificato</span>
                        <?php endif; ?>
                    </h5>
                    <p class="card-text">
                        <strong>Email (crittografata nel DB):</strong> <?= htmlspecialchars($author->getEmail()) ?>
                    </p>
                    <?php if ($author->getBio()): ?>
                        <p class="card-text"><?= nl2br(htmlspecialchars($author->getBio())) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lista articoli -->
            <h2>Articoli (<?= count($articles) ?>)</h2>

            <?php if (empty($articles)): ?>
                <div class="alert alert-info">
                    Questo autore non ha ancora pubblicato articoli.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($articles as $article): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= htmlspecialchars($article->getTitle()) ?></h5>
                                <small><?= $article->getCreatedAt()->format('d/m/Y') ?></small>
                            </div>
                            <p class="mb-1"><?= htmlspecialchars(substr($article->getContent(), 0, 150)) ?>...</p>
                            <small>
                                <span class="badge bg-<?= $article->getStatus()->isPublic() ? 'success' : 'secondary' ?>">
                                    <?= $article->getStatus()->getLabel() ?>
                                </span>
                                Visualizzazioni: <?= $article->getViews() ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Spiegazione tecnica -->
            <div class="alert alert-info mt-4">
                <h5>🔗 Esempio di Relazioni e Lazy Loading</h5>
                <p>Questa pagina mostra:</p>
                <ul class="mb-0">
                    <li>
                        <strong>Relazione Many-to-One:</strong> Ogni articolo appartiene a un autore
                        (<code>SampleDependentEntity->getSampleReferencedEntity()</code>)
                    </li>
                    <li>
                        <strong>Lazy Loading:</strong> L'autore viene caricato solo quando accedi alla proprietà
                    </li>
                    <li>
                        <strong>Collezione Inversa:</strong> Dall'autore puoi accedere a tutti i suoi articoli:
                        <br><code>$author->sampleDependentEntityCollection</code>
                    </li>
                    <li>
                        <strong>Query Ottimizzate:</strong> Il model usa <code>getArticlesByAuthor()</code> per evitare N+1
                    </li>
                </ul>
            </div>

            <a href="/sample/index" class="btn btn-secondary mt-3">← Torna alla lista</a>
        </div>
    </body>
</html>

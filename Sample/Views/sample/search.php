<?php ob_start(); ?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sample/index">Esempi</a></li>
            <li class="breadcrumb-item active">Ricerca</li>
        </ol>
    </nav>

    <h1 class="display-5 mb-4">
        <i class="bi bi-search"></i> Ricerca Articoli
    </h1>

    <!-- Form di ricerca -->
    <form method="GET" action="/sample/search" class="mb-5">
        <div class="input-group input-group-lg shadow-sm">
            <span class="input-group-text bg-primary text-white">
                <i class="bi bi-search"></i>
            </span>
            <input type="text"
                   name="q"
                   class="form-control"
                   placeholder="Cerca negli articoli..."
                   value="<?= htmlspecialchars($searchKey) ?>">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-arrow-right-circle"></i> Cerca
            </button>
        </div>
    </form>

    <!-- Risultati -->
    <?php if ($searchKey !== ''): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>
                <i class="bi bi-list-ul"></i> Risultati per "<?= htmlspecialchars($searchKey) ?>"
            </h3>
            <span class="badge bg-primary fs-5"><?= count($results) ?> trovati</span>
        </div>

        <?php if (empty($results)): ?>
            <div class="alert alert-warning shadow-sm">
                <i class="bi bi-exclamation-triangle"></i> Nessun articolo trovato per la tua ricerca.
                <hr>
                <p class="mb-0">Prova con parole chiave diverse o più generiche.</p>
            </div>
        <?php else: ?>
            <div class="list-group shadow-sm">
                <?php foreach ($results as $article): ?>
                    <a href="/sample/show-article/article/<?= $article->id ?>"
                       class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                            <h5 class="mb-1">
                                <i class="bi bi-file-text text-primary"></i> <?= htmlspecialchars($article->title) ?>
                            </h5>
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> <?= $article->publishedAt->format('d/m/Y') ?>
                            </small>
                        </div>
                        <p class="mb-2 text-muted"><?= htmlspecialchars(substr($article->content ?? '', 0, 200)) ?>...</p>
                        <div class="d-flex gap-2">
                            <span class="badge bg-<?= $article->status->isPublic() ? 'success' : 'secondary' ?>">
                                <?= $article->status->getLabel() ?>
                            </span>
                            <small class="text-muted">
                                <i class="bi bi-star"></i> <?= $article->rating ?>/5.0
                            </small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info shadow-sm">
            <i class="bi bi-info-circle"></i> <strong>Inizia la tua ricerca!</strong>
            <p class="mb-0">Inserisci una parola chiave nel campo sopra per cercare negli articoli.</p>
        </div>
    <?php endif; ?>

    <!-- Spiegazione tecnica -->
    <div class="card border-success shadow-sm mt-5">
        <div class="card-body">
            <h5 class="card-title text-success">
                <i class="bi bi-lightbulb"></i> Esempio di Request Autowiring e Ricerca Testuale
            </h5>
            <p class="card-text">Questa pagina dimostra:</p>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Autowiring del Request:</strong><br>
                            <code class="small">public function search(Request $request): Response</code><br>
                            <small>Il framework inietta automaticamente l'oggetto Request</small>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Query String:</strong><br>
                            <small>Accesso ai parametri GET con <code>$request->query['q']</code></small>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Ricerca Testuale:</strong><br>
                            <small>La logica di ricerca è implementata in <code>appendSearchCondition()</code> del Model</small><br>
                            <small>Cerca in: titolo e contenuto degli articoli</small>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Metodo Model:</strong><br>
                            <code class="small">$model->getEntityCollection($searchKey)</code>
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

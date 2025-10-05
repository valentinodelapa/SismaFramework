<!DOCTYPE html>
<html lang="it">
    <head>
        <?php require_once __DIR__ . '/../commonParts/baseHead.php'; ?>
        <title>Ricerca - SismaFramework</title>
    </head>
    <body>
        <div class="container my-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/sample/index">Home</a></li>
                    <li class="breadcrumb-item active">Ricerca</li>
                </ol>
            </nav>

            <h1>Ricerca Articoli</h1>

            <!-- Form di ricerca -->
            <form method="GET" action="/sample/search" class="mb-4">
                <div class="input-group">
                    <input type="text"
                           name="q"
                           class="form-control"
                           placeholder="Cerca negli articoli..."
                           value="<?= htmlspecialchars($searchKey) ?>">
                    <button class="btn btn-primary" type="submit">Cerca</button>
                </div>
            </form>

            <!-- Risultati -->
            <?php if ($searchKey !== ''): ?>
                <h3>Risultati per "<?= htmlspecialchars($searchKey) ?>" (<?= count($results) ?>)</h3>

                <?php if (empty($results)): ?>
                    <div class="alert alert-warning">
                        Nessun articolo trovato per la tua ricerca.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($results as $article): ?>
                            <a href="/sample/show-article/id/<?= $article->getId() ?>"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($article->getTitle()) ?></h5>
                                    <small class="text-muted"><?= $article->getPublishedAt()->format('d/m/Y') ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars(substr($article->getContent() ?? '', 0, 200)) ?>...</p>
                                <small>
                                    <span class="badge bg-<?= $article->getStatus()->isPublic() ? 'success' : 'secondary' ?>">
                                        <?= $article->getStatus()->getLabel() ?>
                                    </span>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    Inserisci una parola chiave per cercare negli articoli.
                </div>
            <?php endif; ?>

            <!-- Spiegazione tecnica -->
            <div class="alert alert-success mt-4">
                <h5>🔍 Esempio di Request Autowiring e Ricerca Testuale</h5>
                <p>Questa pagina dimostra:</p>
                <ul class="mb-0">
                    <li>
                        <strong>Autowiring del Request:</strong>
                        <br><code>public function search(Request $request): Response</code>
                        <br>Il framework inietta automaticamente l'oggetto Request
                    </li>
                    <li>
                        <strong>Query String:</strong> Accesso ai parametri GET con <code>$request->getQuery('q')</code>
                    </li>
                    <li>
                        <strong>Ricerca Testuale:</strong> La logica di ricerca è implementata in <code>appendSearchCondition()</code> del Model
                        <br>Cerca in: titolo e contenuto degli articoli
                    </li>
                    <li>
                        <strong>Metodo Model:</strong> <code>$model->getEntityCollection($searchKey)</code>
                    </li>
                </ul>
            </div>

            <a href="/sample/index" class="btn btn-secondary mt-3">← Torna alla lista</a>
        </div>
    </body>
</html>
